<?php

namespace Tests\Feature\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
  use RefreshDatabase;

  private function authAdmin(): array
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($admin);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Admin list reviews ──────────────────────────────────────── */

  public function test_admin_can_list_all_reviews(): void
  {
    ProductReview::factory()->count(5)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/reviews', $headers);

    $response->assertOk()
      ->assertJsonStructure([
        'success',
        'data' => [
          'data',
          'meta' => ['total', 'page', 'limit', 'total_pages'],
        ],
      ]);

    $this->assertEquals(5, $response->json('data.meta.total'));
  }

  public function test_admin_can_filter_unreplied_reviews(): void
  {
    ProductReview::factory()->count(3)->create();
    ProductReview::factory()->withAdminReply()->count(2)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/reviews?unreplied=1', $headers);

    $response->assertOk();
    $this->assertEquals(3, $response->json('data.meta.total'));
  }

  public function test_admin_reviews_include_product_info(): void
  {
    $product = Product::factory()->create(['name' => 'Special Tee']);
    ProductReview::factory()->create(['product_id' => $product->id]);
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/reviews', $headers);

    $response->assertOk();
    $item = $response->json('data.data.0');
    $this->assertArrayHasKey('product', $item);
    $this->assertEquals('Special Tee', $item['product']['name']);
  }

  public function test_admin_reviews_pagination(): void
  {
    $product = Product::factory()->create();
    ProductReview::factory()->count(20)->create(['product_id' => $product->id]);
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/reviews?limit=5', $headers);

    $response->assertOk();
    $this->assertEquals(5, $response->json('data.meta.limit'));
    $this->assertEquals(20, $response->json('data.meta.total'));
    $this->assertEquals(4, $response->json('data.meta.total_pages'));
  }

  /* ── Admin reply ─────────────────────────────────────────────── */

  public function test_admin_can_reply_to_review(): void
  {
    $review  = ProductReview::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/reviews/{$review->id}/reply", [
      'admin_reply' => 'Thank you for your feedback!',
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Reply added',
        'data'    => [
          'admin_reply' => 'Thank you for your feedback!',
        ],
      ]);

    $review->refresh();
    $this->assertEquals('Thank you for your feedback!', $review->admin_reply);
    $this->assertNotNull($review->admin_replied_at);
  }

  public function test_admin_reply_fails_without_text(): void
  {
    $review  = ProductReview::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/reviews/{$review->id}/reply", [], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['admin_reply']);
  }

  public function test_admin_reply_fails_with_text_too_long(): void
  {
    $review  = ProductReview::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/reviews/{$review->id}/reply", [
      'admin_reply' => str_repeat('A', 2001),
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['admin_reply']);
  }

  public function test_admin_reply_to_nonexistent_review(): void
  {
    $headers = $this->authAdmin();

    $response = $this->postJson('/api/v1/reviews/00000000-0000-0000-0000-000000000000/reply', [
      'admin_reply' => 'Reply',
    ], $headers);

    $response->assertStatus(404);
  }

  /* ── Admin delete ────────────────────────────────────────────── */

  public function test_admin_can_delete_review(): void
  {
    $review  = ProductReview::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->deleteJson("/api/v1/reviews/{$review->id}", [], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Review deleted',
      ]);

    $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
  }

  public function test_admin_delete_nonexistent_review(): void
  {
    $headers = $this->authAdmin();

    $response = $this->deleteJson('/api/v1/reviews/00000000-0000-0000-0000-000000000000', [], $headers);

    $response->assertStatus(404);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_access_admin_review_endpoints(): void
  {
    $user = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer $token"];
    $review  = ProductReview::factory()->create();

    $this->getJson('/api/v1/reviews', $headers)->assertStatus(403);
    $this->postJson("/api/v1/reviews/{$review->id}/reply", [
      'admin_reply' => 'Reply',
    ], $headers)->assertStatus(403);
    $this->deleteJson("/api/v1/reviews/{$review->id}", [], $headers)->assertStatus(403);
  }

  public function test_unauthenticated_cannot_access_admin_review_endpoints(): void
  {
    $review = ProductReview::factory()->create();

    $this->getJson('/api/v1/reviews')->assertStatus(401);
    $this->postJson("/api/v1/reviews/{$review->id}/reply", [
      'admin_reply' => 'Reply',
    ])->assertStatus(401);
    $this->deleteJson("/api/v1/reviews/{$review->id}")->assertStatus(401);
  }

  public function test_moderator_can_reply_to_review(): void
  {
    $review = ProductReview::factory()->create();
    $mod    = User::factory()->moderator()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($mod);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->postJson("/api/v1/reviews/{$review->id}/reply", [
      'admin_reply' => 'Moderator reply',
    ], $headers);

    $response->assertOk();
  }
}
