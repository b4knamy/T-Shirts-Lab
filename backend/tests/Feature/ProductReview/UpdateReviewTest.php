<?php

namespace Tests\Feature\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateReviewTest extends TestCase
{
  use RefreshDatabase;

  private function authUser(?User $user = null): array
  {
    $user  = $user ?? User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_user_can_update_own_review_rating(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create([
      'user_id' => $user->id,
      'rating'  => 3,
    ]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating' => 5,
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Review updated',
        'data'    => ['rating' => 5],
      ]);

    $this->assertDatabaseHas('product_reviews', [
      'id'     => $review->id,
      'rating' => 5,
    ]);
  }

  public function test_user_can_update_own_review_comment(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create([
      'user_id' => $user->id,
      'comment' => 'Old comment',
    ]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'comment' => 'Updated comment',
    ], $headers);

    $response->assertOk();
    $this->assertEquals('Updated comment', $response->json('data.comment'));
  }

  public function test_user_can_update_rating_and_comment(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create(['user_id' => $user->id]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating'  => 1,
      'comment' => 'Terrible quality',
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'data' => [
          'rating'  => 1,
          'comment' => 'Terrible quality',
        ],
      ]);
  }

  /* ── Cannot update other user's review ─────────────────────── */

  public function test_user_cannot_update_other_users_review(): void
  {
    $owner   = User::factory()->create();
    $review  = ProductReview::factory()->create(['user_id' => $owner->id]);
    $other   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $headers = $this->authUser($other);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating' => 1,
    ], $headers);

    $response->assertStatus(404);
  }

  /* ── Not found ───────────────────────────────────────────────── */

  public function test_update_nonexistent_review(): void
  {
    $headers = $this->authUser();

    $response = $this->patchJson('/api/v1/reviews/00000000-0000-0000-0000-000000000000', [
      'rating' => 5,
    ], $headers);

    $response->assertStatus(404);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_unauthenticated_cannot_update_review(): void
  {
    $review = ProductReview::factory()->create();

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating' => 5,
    ]);

    $response->assertStatus(401);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_update_fails_with_rating_above_5(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create(['user_id' => $user->id]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating' => 6,
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_update_fails_with_rating_below_1(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create(['user_id' => $user->id]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'rating' => 0,
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['rating']);
  }

  public function test_update_fails_with_comment_too_long(): void
  {
    $user   = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $review = ProductReview::factory()->create(['user_id' => $user->id]);
    $headers = $this->authUser($user);

    $response = $this->patchJson("/api/v1/reviews/{$review->id}", [
      'comment' => str_repeat('A', 2001),
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['comment']);
  }
}
