<?php

namespace Tests\Feature\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListReviewsTest extends TestCase
{
    use RefreshDatabase;

    /* ── Public access ───────────────────────────────────────────── */

    public function test_anyone_can_list_product_reviews(): void
    {
        $product = Product::factory()->create();
        ProductReview::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'reviews',
                    'average_rating',
                    'total_reviews',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['total_reviews' => 3],
            ]);
    }

    public function test_reviews_include_user_info(): void
    {
        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $product = Product::factory()->create();
        ProductReview::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk();
        $review = $response->json('data.reviews.0');
        $this->assertEquals('John', $review['user']['first_name']);
        $this->assertEquals('Doe', $review['user']['last_name']);
    }

    public function test_average_rating_calculation(): void
    {
        $product = Product::factory()->create();
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 5]);
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 3]);
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 4]);

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk();
        $this->assertEquals(4.0, $response->json('data.average_rating'));
    }

    public function test_empty_reviews(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                ],
            ]);
    }

    public function test_reviews_are_paginated(): void
    {
        $product = Product::factory()->create();
        ProductReview::factory()->count(15)->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/v1/products/{$product->id}/reviews");

        $response->assertOk();
        $this->assertEquals(10, $response->json('data.pagination.per_page'));
        $this->assertEquals(15, $response->json('data.pagination.total'));
        $this->assertEquals(2, $response->json('data.pagination.last_page'));
    }

    public function test_reviews_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000/reviews');

        $response->assertStatus(404);
    }
}
