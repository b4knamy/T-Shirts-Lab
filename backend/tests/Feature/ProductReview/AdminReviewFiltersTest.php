<?php

namespace Tests\Feature\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReviewFiltersTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/reviews';

  private function authAdmin(): array
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($admin);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Filter by rating ────────────────────────────────────────── */

  public function test_filter_reviews_by_rating_5(): void
  {
    ProductReview::factory()->count(3)->create(['rating' => 5]);
    ProductReview::factory()->count(2)->create(['rating' => 3]);
    ProductReview::factory()->count(1)->create(['rating' => 1]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?rating=5', $headers);

    $response->assertOk();
    $this->assertEquals(3, $response->json('data.meta.total'));
  }

  public function test_filter_reviews_by_rating_1(): void
  {
    ProductReview::factory()->count(2)->create(['rating' => 1]);
    ProductReview::factory()->count(4)->create(['rating' => 4]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?rating=1', $headers);

    $response->assertOk();
    $this->assertEquals(2, $response->json('data.meta.total'));
  }

  public function test_filter_reviews_by_rating_returns_empty_when_none_match(): void
  {
    ProductReview::factory()->count(3)->create(['rating' => 5]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?rating=2', $headers);

    $response->assertOk();
    $this->assertEquals(0, $response->json('data.meta.total'));
  }

  public function test_no_rating_filter_returns_all(): void
  {
    ProductReview::factory()->count(2)->create(['rating' => 5]);
    ProductReview::factory()->count(3)->create(['rating' => 3]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint, $headers);

    $response->assertOk();
    $this->assertEquals(5, $response->json('data.meta.total'));
  }

  /* ── Search by product name ──────────────────────────────────── */

  public function test_search_reviews_by_product_name(): void
  {
    $product1 = Product::factory()->create(['name' => 'Dragon Ball Z Tee']);
    $product2 = Product::factory()->create(['name' => 'Naruto Shippuden Tee']);

    ProductReview::factory()->count(2)->create(['product_id' => $product1->id]);
    ProductReview::factory()->count(3)->create(['product_id' => $product2->id]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=Dragon', $headers);

    $response->assertOk();
    $this->assertEquals(2, $response->json('data.meta.total'));
  }

  public function test_search_reviews_by_product_name_case_insensitive(): void
  {
    $product = Product::factory()->create(['name' => 'One Piece Adventure']);
    ProductReview::factory()->create(['product_id' => $product->id]);
    ProductReview::factory()->count(2)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=one piece', $headers);

    $response->assertOk();
    $this->assertEquals(1, $response->json('data.meta.total'));
  }

  public function test_search_reviews_by_partial_product_name(): void
  {
    $product = Product::factory()->create(['name' => 'Premium Cotton Shirt']);
    ProductReview::factory()->count(3)->create(['product_id' => $product->id]);
    ProductReview::factory()->count(2)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=Premium', $headers);

    $response->assertOk();
    $this->assertEquals(3, $response->json('data.meta.total'));
  }

  public function test_search_reviews_returns_empty_when_no_product_matches(): void
  {
    ProductReview::factory()->count(3)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=NonexistentProduct', $headers);

    $response->assertOk();
    $this->assertEquals(0, $response->json('data.meta.total'));
  }

  /* ── Combined filters ────────────────────────────────────────── */

  public function test_combine_rating_and_search_filters(): void
  {
    $product = Product::factory()->create(['name' => 'Attack on Titan Tee']);
    ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 5]);
    ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 3]);
    ProductReview::factory()->create(['rating' => 5]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?rating=5&search=Attack', $headers);

    $response->assertOk();
    $this->assertEquals(1, $response->json('data.meta.total'));
  }

  public function test_combine_unreplied_and_rating_filters(): void
  {
    ProductReview::factory()->count(2)->create(['rating' => 5]);
    ProductReview::factory()->withAdminReply()->count(3)->create(['rating' => 5]);
    ProductReview::factory()->count(4)->create(['rating' => 3]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?unreplied=1&rating=5', $headers);

    $response->assertOk();
    $this->assertEquals(2, $response->json('data.meta.total'));
  }

  public function test_combine_unreplied_and_search_filters(): void
  {
    $product = Product::factory()->create(['name' => 'Jujutsu Kaisen Hoodie']);
    ProductReview::factory()->create(['product_id' => $product->id]);
    ProductReview::factory()->withAdminReply()->create(['product_id' => $product->id]);
    ProductReview::factory()->count(3)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?unreplied=1&search=Jujutsu', $headers);

    $response->assertOk();
    $this->assertEquals(1, $response->json('data.meta.total'));
  }

  public function test_combine_all_three_filters(): void
  {
    $product = Product::factory()->create(['name' => 'Demon Slayer Tee']);
    ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 4]);
    ProductReview::factory()->withAdminReply()->create(['product_id' => $product->id, 'rating' => 4]);
    ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 2]);
    ProductReview::factory()->create(['rating' => 4]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?unreplied=1&rating=4&search=Demon', $headers);

    $response->assertOk();
    $this->assertEquals(1, $response->json('data.meta.total'));
  }

  /* ── Pagination with filters ─────────────────────────────────── */

  public function test_pagination_with_rating_filter(): void
  {
    $product = Product::factory()->create();
    ProductReview::factory()->count(8)->create(['product_id' => $product->id, 'rating' => 5]);
    ProductReview::factory()->count(4)->create(['product_id' => $product->id, 'rating' => 2]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?rating=5&limit=3', $headers);

    $response->assertOk();
    $this->assertCount(3, $response->json('data.data'));
    $this->assertEquals(8, $response->json('data.meta.total'));
    $this->assertEquals(3, $response->json('data.meta.total_pages'));
  }

  public function test_pagination_with_search_filter(): void
  {
    $product = Product::factory()->create(['name' => 'Bleach Zanpakuto Tee']);
    ProductReview::factory()->count(6)->create(['product_id' => $product->id]);
    ProductReview::factory()->count(4)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=Bleach&limit=2', $headers);

    $response->assertOk();
    $this->assertCount(2, $response->json('data.data'));
    $this->assertEquals(6, $response->json('data.meta.total'));
  }
}
