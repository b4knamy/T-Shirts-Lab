<?php

namespace Tests\Feature\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateReviewTest extends TestCase
{
  use RefreshDatabase;

  private function authUser(?User $user = null): array
  {
    $user  = $user ?? User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_authenticated_user_can_create_review(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating'  => 5,
      'comment' => 'Amazing t-shirt!',
    ], $headers);

    $response->assertStatus(201)
      ->assertJsonStructure([
        'success',
        'message',
        'data' => ['id', 'user_id', 'product_id', 'rating', 'comment', 'user', 'created_at'],
      ])
      ->assertJson([
        'success' => true,
        'message' => 'Review submitted',
        'data'    => [
          'rating'  => 5,
          'comment' => 'Amazing t-shirt!',
        ],
      ]);

    $this->assertDatabaseHas('product_reviews', [
      'product_id' => $product->id,
      'rating'     => 5,
    ]);
  }

  public function test_create_review_without_comment(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 4,
    ], $headers);

    $response->assertStatus(201);
    $this->assertNull($response->json('data.comment'));
  }

  public function test_review_includes_user_data(): void
  {
    $user    = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
      'first_name'    => 'Maria',
      'last_name'     => 'Silva',
    ]);
    $product = Product::factory()->create();
    $headers = $this->authUser($user);

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 5,
    ], $headers);

    $response->assertStatus(201);
    $this->assertEquals('Maria', $response->json('data.user.first_name'));
    $this->assertEquals('Silva', $response->json('data.user.last_name'));
  }

  /* ── Duplicate review ────────────────────────────────────────── */

  public function test_cannot_review_same_product_twice(): void
  {
    $user    = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $product = Product::factory()->create();
    $headers = $this->authUser($user);

    ProductReview::factory()->create([
      'user_id'    => $user->id,
      'product_id' => $product->id,
    ]);

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 3,
    ], $headers);

    $response->assertStatus(422)
      ->assertJson([
        'success' => false,
        'message' => 'You have already reviewed this product',
      ]);
  }

  /* ── Nonexistent product ─────────────────────────────────────── */

  public function test_cannot_review_nonexistent_product(): void
  {
    $headers = $this->authUser();

    $response = $this->postJson('/api/v1/products/00000000-0000-0000-0000-000000000000/reviews', [
      'rating' => 5,
    ], $headers);

    $response->assertStatus(404);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_unauthenticated_cannot_create_review(): void
  {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 5,
    ]);

    $response->assertStatus(401);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_create_fails_without_rating(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'comment' => 'Nice!',
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_create_fails_with_rating_below_1(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 0,
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_create_fails_with_rating_above_5(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 6,
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_create_fails_with_non_integer_rating(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating' => 3.5,
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_create_fails_with_comment_too_long(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authUser();

    $response = $this->postJson("/api/v1/products/{$product->id}/reviews", [
      'rating'  => 5,
      'comment' => str_repeat('A', 2001),
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['comment']);
  }
}
