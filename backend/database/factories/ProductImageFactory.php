<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
  protected $model = ProductImage::class;

  public function definition(): array
  {
    $width  = fake()->randomElement([800, 1000, 1200]);
    $height = $width;

    return [
      'product_id' => Product::factory(),
      'image_url'  => "https://placehold.co/{$width}x{$height}/png",
      'alt_text'   => fake()->sentence(4),
      'sort_order' => fake()->numberBetween(1, 10),
      'is_primary' => false,
    ];
  }

  public function primary(): static
  {
    return $this->state(fn() => [
      'sort_order' => 1,
      'is_primary' => true,
    ]);
  }
}
