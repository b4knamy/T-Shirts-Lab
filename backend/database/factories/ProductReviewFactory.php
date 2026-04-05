<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
        ];
    }

    public function withAdminReply(): static
    {
        return $this->state(fn () => [
            'admin_reply' => fake()->sentence(),
            'admin_replied_at' => now(),
        ]);
    }

    public function withoutComment(): static
    {
        return $this->state(fn () => [
            'comment' => null,
        ]);
    }
}
