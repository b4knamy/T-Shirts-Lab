<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowProductTest extends TestCase
{
  use RefreshDatabase;

  /* ── Show by ID ──────────────────────────────────────────────── */

  public function test_get_product_by_id(): void
  {
    $category = Category::factory()->create();
    $product  = Product::factory()->create(['category_id' => $category->id]);
    ProductImage::factory()->primary()->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
      ->assertJsonStructure([
        'success',
        'data' => [
          'id',
          'sku',
          'name',
          'slug',
          'description',
          'category_id',
          'category',
          'price',
          'stock_quantity',
          'status',
          'images',
        ],
      ])
      ->assertJson([
        'success' => true,
        'data'    => ['id' => $product->id],
      ]);
  }

  public function test_product_includes_reviews_stats(): void
  {
    $category = Category::factory()->create();
    $product  = Product::factory()->create(['category_id' => $category->id]);
    ProductReview::factory()->count(3)->create([
      'product_id' => $product->id,
      'rating'     => 4,
    ]);

    $response = $this->getJson("/api/v1/products/{$product->id}");

    $response->assertOk();
    $this->assertEquals(4.0, $response->json('data.average_rating'));
    $this->assertEquals(3, $response->json('data.reviews_count'));
  }

  public function test_product_not_found(): void
  {
    $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Product not found',
      ]);
  }

  /* ── Show by slug ────────────────────────────────────────────── */

  public function test_get_product_by_slug(): void
  {
    $category = Category::factory()->create();
    $product  = Product::factory()->create([
      'category_id' => $category->id,
      'slug'        => 'dragon-ball-tee-abc123',
    ]);

    $response = $this->getJson('/api/v1/products/slug/dragon-ball-tee-abc123');

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'data'    => ['id' => $product->id, 'slug' => 'dragon-ball-tee-abc123'],
      ]);
  }

  public function test_slug_not_found(): void
  {
    $response = $this->getJson('/api/v1/products/slug/nonexistent-slug');

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Product not found',
      ]);
  }

  /* ── Featured ────────────────────────────────────────────────── */

  public function test_get_featured_products(): void
  {
    $category = Category::factory()->create();
    Product::factory()->count(3)->featured()->create(['category_id' => $category->id]);
    Product::factory()->count(2)->create([
      'category_id' => $category->id,
      'is_featured' => false,
    ]);

    $response = $this->getJson('/api/v1/products/featured');

    $response->assertOk()
      ->assertJson(['success' => true]);

    // Should only have featured products
    $data = $response->json('data');
    foreach ($data as $item) {
      $this->assertTrue($item['is_featured']);
    }
  }

  public function test_featured_respects_limit(): void
  {
    $category = Category::factory()->create();
    Product::factory()->count(10)->featured()->create(['category_id' => $category->id]);

    $response = $this->getJson('/api/v1/products/featured?limit=4');

    $response->assertOk();
    $this->assertCount(4, $response->json('data'));
  }

  /* ── Public categories ───────────────────────────────────────── */

  public function test_get_public_categories(): void
  {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/v1/products/categories');

    $response->assertOk()
      ->assertJson(['success' => true]);

    $this->assertCount(3, $response->json('data'));
  }
}
