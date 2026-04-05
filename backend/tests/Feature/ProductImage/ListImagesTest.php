<?php

namespace Tests\Feature\ProductImage;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ListImagesTest extends TestCase
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

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_list_product_images(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->count(3)->create(['product_id' => $product->id]);
        $headers = $this->authAdmin();

        $response = $this->getJson("/api/v1/products/{$product->id}/images", $headers);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_images_ordered_by_sort_order(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 3]);
        ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 1]);
        ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 2]);
        $headers = $this->authAdmin();

        $response = $this->getJson("/api/v1/products/{$product->id}/images", $headers);

        $response->assertOk();
        $sortOrders = array_column($response->json('data'), 'sort_order');
        $this->assertEquals([1, 2, 3], $sortOrders);
    }

    public function test_empty_when_no_images(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->getJson("/api/v1/products/{$product->id}/images", $headers);

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_product_not_found(): void
    {
        $headers = $this->authAdmin();

        $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000/images', $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found',
            ]);
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_list_images(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->getJson("/api/v1/products/{$product->id}/images", $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_images(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}/images");

        $response->assertStatus(401);
    }
}
