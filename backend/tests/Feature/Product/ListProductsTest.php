<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListProductsTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/products';

    /* ── Public access ───────────────────────────────────────────── */

    public function test_anyone_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'total',
                    'page',
                    'limit',
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_products_include_category_and_images(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductImage::factory()->primary()->create(['product_id' => $product->id]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $item = $response->json('data.data.0');
        $this->assertArrayHasKey('category', $item);
        $this->assertArrayHasKey('images', $item);
        $this->assertEquals($category->id, $item['category']['id']);
    }

    public function test_only_active_products_by_default(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id, 'status' => 'ACTIVE']);
        Product::factory()->create(['category_id' => $category->id, 'status' => 'DRAFT']);
        Product::factory()->create(['category_id' => $category->id, 'status' => 'INACTIVE']);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_filter_by_status_all(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id, 'status' => 'ACTIVE']);
        Product::factory()->create(['category_id' => $category->id, 'status' => 'DRAFT']);

        $response = $this->getJson($this->endpoint.'?status=ALL');

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
    }

    /* ── Search ──────────────────────────────────────────────────── */

    public function test_search_by_product_name(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Dragon Ball Tee']);
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Plain White']);

        $response = $this->getJson($this->endpoint.'?search=dragon');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Dragon Ball Tee', $response->json('data.data.0.name'));
    }

    public function test_search_by_sku(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'sku' => 'TSL-UNIQUE123']);
        Product::factory()->create(['category_id' => $category->id, 'sku' => 'TSL-OTHER456']);

        $response = $this->getJson($this->endpoint.'?search=UNIQUE123');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    /* ── Filter ──────────────────────────────────────────────────── */

    public function test_filter_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $cat1->id]);
        Product::factory()->create(['category_id' => $cat2->id]);

        $response = $this->getJson($this->endpoint.'?categoryId='.$cat1->id);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_filter_by_min_price(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 150.00]);

        $response = $this->getJson($this->endpoint.'?minPrice=100');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_filter_by_max_price(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 150.00]);

        $response = $this->getJson($this->endpoint.'?maxPrice=100');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }

    /* ── Sorting ─────────────────────────────────────────────────── */

    public function test_sort_by_price_asc(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'price' => 150.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);

        $response = $this->getJson($this->endpoint.'?sortBy=price_asc');

        $response->assertOk();
        $prices = array_column($response->json('data.data'), 'price');
        $this->assertEquals([50.0, 150.0], $prices);
    }

    public function test_sort_by_price_desc(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'price' => 50.00]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 150.00]);

        $response = $this->getJson($this->endpoint.'?sortBy=price_desc');

        $response->assertOk();
        $prices = array_column($response->json('data.data'), 'price');
        $this->assertEquals([150.0, 50.0], $prices);
    }

    /* ── Pagination ──────────────────────────────────────────────── */

    public function test_pagination_with_custom_limit(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson($this->endpoint.'?limit=2&page=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
        $this->assertEquals(5, $response->json('data.total'));
    }

    public function test_pagination_default_limit(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(25)->create(['category_id' => $category->id]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $this->assertCount(20, $response->json('data.data'));
        $this->assertEquals(25, $response->json('data.total'));
    }
}
