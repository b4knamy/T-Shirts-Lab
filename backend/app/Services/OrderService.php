<?php

namespace App\Services;

use App\Logging\Loggers\OrderLogger;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderLogger $orderLogger,
        private OrderNotificationService $orderNotificationService,
    ) {}

    public function paginateAll(array $filters, int $page, int $limit): array
    {
        $query = Order::with(['items.product', 'payment', 'user'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'ilike', "%{$search}%")
                            ->orWhere('last_name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('status', $filters['payment_status']);
            });
        }

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('orders', 'total');
    }

    public function paginateForUser(string $userId, int $page, int $limit): array
    {
        $query = Order::with(['items.product', 'payment'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('orders', 'total');
    }

    public function findById(string $id): ?Order
    {
        return Order::with(['items.product.images', 'items.design', 'payment', 'user'])
            ->find($id);
    }

    public function createOrder(array $data, string $userId): Order
    {
        $result = DB::transaction(function () use ($data, $userId) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($data['items'] as $item) {
                $product = Product::whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \RuntimeException(
                        "Estoque insuficiente para o produto: {$product->name}"
                    );
                }

                $unitPrice = (float) ($product->discount_price ?? $product->price);
                $totalPrice = $unitPrice * $item['quantity'];
                $subtotal += $totalPrice;

                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'design_id' => $item['design_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'customization_data' => $item['customization_data'] ?? null,
                ];

                $product->update([
                    'stock_quantity' => $product->stock_quantity - $item['quantity'],
                    'reserved_quantity' => $product->reserved_quantity + $item['quantity'],
                ]);
            }

            $discountAmount = 0;
            $couponId = null;
            $couponCode = $data['coupon_code'] ?? null;

            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper($couponCode))
                    ->lockForUpdate()
                    ->first();

                if (! $coupon || ! $coupon->isValid()) {
                    throw new \RuntimeException('Invalid or expired coupon');
                }

                if ($coupon->hasUserReachedLimit($userId)) {
                    throw new \RuntimeException('Coupon usage limit reached');
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
            }

            $shippingCost = $subtotal >= 50 ? 0 : 9.99;
            $total = round($subtotal - $discountAmount + $shippingCost, 2);

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'status' => 'PENDING',
                'payment_status' => 'PENDING',
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'coupon_id' => $couponId,
            ]);

            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }

            if ($couponId) {
                CouponUsage::create([
                    'coupon_id' => $couponId,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                ]);
                Coupon::where('id', $couponId)->increment('usage_count');
            }

            return $order->load(['items.product.images', 'items.design', 'payment', 'user']);
        });

        $this->orderLogger->created($result, $userId, $data['coupon_code'] ?? null);
        $this->orderNotificationService->sendCreated($result);

        return $result;
    }

    public function updateStatus(string $id, string $status, ?string $adminNotes = null): Order
    {
        ['order' => $updatedOrder, 'previousStatus' => $previousStatus] = DB::transaction(function () use ($id, $status, $adminNotes) {
            $order = Order::whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new \RuntimeException('Order not found', 404);
            }

            $order->load(['items.product', 'payment', 'user']);

            $previousStatus = $order->status;

            if ($status === 'CANCELLED' && $previousStatus !== 'CANCELLED') {
                $this->releaseReservedStock($order);
            }

            $updateData = ['status' => $status];
            if ($adminNotes !== null) {
                $updateData['admin_notes'] = $adminNotes;
            }

            $order->fill($updateData);
            if ($order->isDirty()) {
                $order->save();
            }

            return [
                'order' => $order->fresh(['items.product', 'payment', 'user']),
                'previousStatus' => $previousStatus,
            ];
        });

        $this->orderLogger->statusUpdated($id, $updatedOrder->order_number, $previousStatus, $status, $adminNotes);

        $this->orderNotificationService->sendStatusUpdate(
            $updatedOrder,
            newStatus: $status,
            previousStatus: $previousStatus,
            adminNotes: $adminNotes,
        );

        return $updatedOrder;
    }

    private function releaseReservedStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $product) {
                continue;
            }

            $product->update([
                'stock_quantity' => $product->stock_quantity + $item->quantity,
                'reserved_quantity' => max(0, $product->reserved_quantity - $item->quantity),
            ]);
        }
    }
}
