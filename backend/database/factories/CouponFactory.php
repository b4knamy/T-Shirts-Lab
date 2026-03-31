<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
  protected $model = Coupon::class;

  public function definition(): array
  {
    $type  = fake()->randomElement(['PERCENTAGE', 'FIXED']);
    $value = $type === 'PERCENTAGE'
      ? fake()->randomElement([5, 10, 15, 20, 25, 30])
      : fake()->randomFloat(2, 5, 50);

    return [
      'code'               => strtoupper(Str::random(3) . fake()->numerify('###')),
      'description'        => fake()->sentence(),
      'type'               => $type,
      'value'              => $value,
      'min_order_amount'   => fake()->boolean(60) ? fake()->randomElement([50, 100, 150, 200]) : null,
      'max_discount_amount' => $type === 'PERCENTAGE'
        ? fake()->randomElement([null, 30, 50, 100])
        : null,
      'usage_limit'        => fake()->boolean(50) ? fake()->numberBetween(50, 500) : null,
      'usage_count'        => 0,
      'per_user_limit'     => fake()->randomElement([1, 1, 1, 2, 3]),
      'is_active'          => true,
      'is_public'          => false,
      'starts_at'          => now(),
      'expires_at'         => now()->addDays(fake()->numberBetween(7, 60)),
    ];
  }

  public function public(): static
  {
    return $this->state(fn() => [
      'is_public'  => true,
      'starts_at'  => now(),
      'expires_at' => now()->addDays(fake()->numberBetween(1, 14)),
    ]);
  }

  public function percentage(float $value = 10): static
  {
    return $this->state(fn() => [
      'type'  => 'PERCENTAGE',
      'value' => $value,
    ]);
  }

  public function fixed(float $value = 20): static
  {
    return $this->state(fn() => [
      'type'  => 'FIXED',
      'value' => $value,
    ]);
  }

  public function expired(): static
  {
    return $this->state(fn() => [
      'starts_at'  => now()->subDays(30),
      'expires_at' => now()->subDays(1),
    ]);
  }

  public function inactive(): static
  {
    return $this->state(fn() => ['is_active' => false]);
  }
}
