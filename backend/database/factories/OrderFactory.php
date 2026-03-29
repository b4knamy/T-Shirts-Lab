<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
  protected $model = Order::class;

  private static array $orderStatuses   = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED'];
  private static array $paymentStatuses = ['PENDING', 'PAID', 'FAILED', 'REFUNDED'];

  public function definition(): array
  {
    $subtotal     = fake()->randomFloat(2, 50.00, 500.00);
    $shippingCost = $subtotal >= 200 ? 0.00 : fake()->randomFloat(2, 10.00, 35.00);
    $discount     = fake()->boolean(20) ? fake()->randomFloat(2, 5.00, 50.00) : 0.00;
    $tax          = round($subtotal * 0.08, 2);
    $total        = round($subtotal + $shippingCost - $discount + $tax, 2);

    return [
      'user_id'            => User::factory(),
      'order_number'       => 'ORD-' . strtoupper(Str::random(4)) . '-' . fake()->numerify('####'),
      'status'             => fake()->randomElement(self::$orderStatuses),
      'payment_status'     => fake()->randomElement(self::$paymentStatuses),
      'subtotal'           => $subtotal,
      'shipping_cost'      => $shippingCost,
      'discount_amount'    => $discount,
      'tax_amount'         => $tax,
      'total_amount'       => $total,
      'shipping_address'   => json_encode([
        'street'       => 'Rua ' . ucwords(fake()->words(3, true)),
        'number'       => (string) fake()->numberBetween(1, 9999),
        'neighborhood' => ucwords(fake()->words(2, true)),
        'city'         => fake()->city(),
        'state'        => 'SP',
        'zip_code'     => fake()->numerify('#####-###'),
        'country'      => 'BR',
      ]),
      'notes'              => fake()->boolean(20) ? fake()->sentence() : null,
      'admin_notes'        => null,
      'estimated_delivery' => now()->addDays(fake()->numberBetween(5, 20)),
    ];
  }

  public function pending(): static
  {
    return $this->state(fn() => [
      'status'         => 'PENDING',
      'payment_status' => 'PENDING',
    ]);
  }

  public function paid(): static
  {
    return $this->state(fn() => [
      'status'         => 'CONFIRMED',
      'payment_status' => 'PAID',
    ]);
  }

  public function delivered(): static
  {
    return $this->state(fn() => [
      'status'         => 'DELIVERED',
      'payment_status' => 'PAID',
    ]);
  }

  public function cancelled(): static
  {
    return $this->state(fn() => [
      'status'         => 'CANCELLED',
      'payment_status' => fake()->randomElement(['PENDING', 'REFUNDED']),
    ]);
  }
}
