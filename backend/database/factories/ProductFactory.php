<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
  protected $model = Product::class;

  private static array $colors = ['Preto', 'Branco', 'Cinza', 'Azul', 'Vermelho', 'Verde', 'Amarelo'];
  private static array $sizes  = ['P', 'M', 'G', 'GG', 'XGG'];

  public function definition(): array
  {
    $name = fake()->words(3, true);

    return [
      'sku'              => strtoupper('TSL-' . Str::random(8)),
      'name'             => ucwords($name),
      'slug'             => Str::slug($name) . '-' . Str::random(6),
      'description'      => fake()->paragraph(),
      'long_description' => fake()->paragraphs(2, true),
      'category_id'      => Category::factory(),
      'price'            => fake()->randomFloat(2, 39.90, 199.90),
      'cost_price'       => fake()->randomFloat(2, 15.00, 50.00),
      'discount_price'   => null,
      'discount_percent' => null,
      'stock_quantity'   => fake()->numberBetween(0, 200),
      'reserved_quantity' => 0,
      'status'           => 'ACTIVE',
      'is_featured'      => fake()->boolean(25),
      'color'            => fake()->randomElement(self::$colors),
      'size'             => fake()->randomElement(self::$sizes),
    ];
  }

  public function featured(): static
  {
    return $this->state(fn() => [
      'is_featured' => true,
      'status'      => 'ACTIVE',
    ]);
  }

  public function outOfStock(): static
  {
    return $this->state(fn() => [
      'stock_quantity' => 0,
      'status'         => 'OUT_OF_STOCK',
    ]);
  }

  public function draft(): static
  {
    return $this->state(fn() => ['status' => 'DRAFT']);
  }

  public function withDiscount(float $percent = 20): static
  {
    return $this->state(function (array $attributes) use ($percent) {
      return [
        'discount_percent' => $percent,
        'discount_price'   => round($attributes['price'] * (1 - $percent / 100), 2),
      ];
    });
  }
}
