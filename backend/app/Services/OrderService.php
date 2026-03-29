<?php

namespace App\Services;

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
      $subtotal   = 0;
      $orderItems = [];

      foreach ($data['items'] as $item) {
        $product = Product::findOrFail($item['productId']);

        if ($product->stock_quantity < $item['quantity']) {
          throw new \RuntimeException(
            "Estoque insuficiente para o produto: {$product->name}"
          );
        }

        $unitPrice  = (float) ($product->discount_price ?? $product->price);
        $totalPrice = $unitPrice * $item['quantity'];
        $subtotal  += $totalPrice;

        $orderItems[] = [
          'product_id'         => $item['productId'],
          'design_id'          => $item['designId'] ?? null,
          'quantity'           => $item['quantity'],
          'unit_price'         => $unitPrice,
          'total_price'        => $totalPrice,
          'customization_data' => $item['customizationData'] ?? null,
        ];

        $product->decrement('stock_quantity', $item['quantity']);
        $product->increment('reserved_quantity', $item['quantity']);
      }

      $taxAmount    = round($subtotal * 0.08, 2);
      $shippingCost = $subtotal >= 200 ? 0 : 15.00;
      $total        = $subtotal + $taxAmount + $shippingCost;

      $order = $this->orderRepository->create([
        'order_number'       => Order::generateOrderNumber(),
        'user_id'            => $userId,
        'subtotal'           => $subtotal,
        'discount_amount'    => 0,
        'tax_amount'         => $taxAmount,
        'shipping_cost'      => $shippingCost,
        'total'              => $total,
        'status'             => 'PENDING',
        'payment_status'     => 'PENDING',
        'shipping_address_id' => $data['shippingAddressId'] ?? null,
        'billing_address_id' => $data['billingAddressId'] ?? null,
        'customer_notes'     => $data['customerNotes'] ?? null,
      ]);

      foreach ($orderItems as $itemData) {
        $order->items()->create($itemData);
      }

      return $order->load(['items.product.images', 'items.design', 'payment', 'user']);
    });
  }

  public function updateStatus(string $id, string $status, ?string $adminNotes = null): Order
  {
    $order = $this->orderRepository->findById($id);

    if (!$order) {
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
