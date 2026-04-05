<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function paginateAll(int $page, int $limit): array
    {
        return $this->orderRepository->paginateAll($page, $limit);
    }

    public function paginateForUser(string $userId, int $page, int $limit): array
    {
        return $this->orderRepository->paginateForUser($userId, $page, $limit);
    }

    public function findById(string $id): ?Order
    {
        return $this->orderRepository->findById($id);
    }

    public function createOrder(array $data, string $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

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

                $product->decrement('stock_quantity', $item['quantity']);
                $product->increment('reserved_quantity', $item['quantity']);
            }

            // Apply coupon if provided
            $discountAmount = 0;
            $couponId = null;
            $couponCode = $data['coupon_code'] ?? null;

            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper($couponCode))->first();

                if (! $coupon || ! $coupon->isValid()) {
                    throw new \RuntimeException('Invalid or expired coupon');
                }

                if ($coupon->hasUserReachedLimit($userId)) {
                    throw new \RuntimeException('Coupon usage limit reached');
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
            }

            $taxAmount = round(($subtotal - $discountAmount) * 0.08, 2);
            $shippingCost = $subtotal >= 200 ? 0 : 15.00;
            $total = round($subtotal - $discountAmount + $taxAmount + $shippingCost, 2);

            $order = $this->orderRepository->create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
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

            // Record coupon usage
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
    }

    public function updateStatus(string $id, string $status, ?string $adminNotes = null): Order
    {
        $order = $this->orderRepository->findById($id);

        if (! $order) {
            throw new \RuntimeException('Order not found', 404);
        }

        // Release reserved stock on cancellation
        if ($status === 'CANCELLED') {
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
                $item->product->decrement('reserved_quantity', $item->quantity);
            }
        }

        return $this->orderRepository->updateStatus($id, $status, $adminNotes);
    }
}
