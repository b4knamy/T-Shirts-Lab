<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
  public function run(): void
  {
    $customers = User::where('role', 'CUSTOMER')->get();
    $products = Product::where('status', 'ACTIVE')->get();

    if ($customers->isEmpty() || $products->isEmpty()) {
      $this->command->warn('⚠️  OrderSeeder: No customers or products found. Run UserSeeder and ProductSeeder first.');

      return;
    }

    // Create orders with realistic data
    foreach ($customers as $customer) {
      $orderCount = rand(1, 4);

      for ($i = 0; $i < $orderCount; $i++) {
        $order = Order::factory()
          ->for($customer)
          ->create();

        // Add 1-3 items per order
        $itemCount = rand(1, 3);
        $selectedProducts = $products->random(min($itemCount, $products->count()));

        $subtotal = 0;
        foreach ($selectedProducts as $product) {
          $quantity = rand(1, 3);
          $unitPrice = (float) ($product->discount_price ?? $product->price);
          $total = round($unitPrice * $quantity, 2);
          $subtotal += $total;

          OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $total,
          ]);
        }

        // Recalculate order totals based on actual items
        $shippingCost = $subtotal >= 50 ? 0 : 9.99;
        $taxAmount = 0;
        $total = round($subtotal + $shippingCost, 2);

        $order->update([
          'subtotal' => $subtotal,
          'shipping_cost' => $shippingCost,
          'tax_amount' => $taxAmount,
          'total' => $total,
        ]);

        // Create payment for non-pending orders
        if ($order->payment_status === 'COMPLETED') {
          Payment::factory()->paid()->create([
            'order_id' => $order->id,
            'amount' => $total,
          ]);
        } elseif ($order->payment_status !== 'PENDING') {
          Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $total,
            'status' => $order->payment_status,
          ]);
        }
      }
    }

    $totalOrders = Order::count();
    $this->command->info("✅ OrderSeeder: {$totalOrders} orders with items and payments created.");
  }
}
