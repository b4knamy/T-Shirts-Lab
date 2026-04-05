<?php

namespace Tests\Feature\ProductImage;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateImageTest extends TestCase
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

    public function test_admin_can_update_alt_text(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'alt_text' => 'Old alt',
        ]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['alt_text' => 'New alt text'],
            $headers
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Image updated',
                'data' => ['alt_text' => 'New alt text'],
            ]);
    }

    public function test_admin_can_update_sort_order(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'sort_order' => 1,
        ]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['sort_order' => 5],
            $headers
        );

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.sort_order'));
    }

    public function test_set_image_as_primary(): void
    {
        $product = Product::factory()->create();
        $primary = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
        $secondary = ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => false,
            'sort_order' => 2,
        ]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$secondary->id}",
            ['is_primary' => true],
            $headers
        );

        $response->assertOk();
        $this->assertTrue($response->json('data.is_primary'));

        $primary->refresh();
        $this->assertFalse($primary->is_primary);
    }

    /* ── Not found ───────────────────────────────────────────────── */

    public function test_update_nonexistent_image(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/00000000-0000-0000-0000-000000000000",
            ['alt_text' => 'Test'],
            $headers
        );

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Image not found',
            ]);
    }

    public function test_update_image_from_wrong_product(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product2->id]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product1->id}/images/{$image->id}",
            ['alt_text' => 'Test'],
            $headers
        );

        $response->assertStatus(404);
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_update_image(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product->id]);
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['alt_text' => 'Hacked'],
            $headers
        );

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_update_image(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product->id]);

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['alt_text' => 'Hacked']
        );

        $response->assertStatus(401);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_update_fails_with_alt_text_too_long(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product->id]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['alt_text' => str_repeat('A', 256)],
            $headers
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alt_text']);
    }

    public function test_update_fails_with_negative_sort_order(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product->id]);
        $headers = $this->authAdmin();

        $response = $this->patchJson(
            "/api/v1/products/{$product->id}/images/{$image->id}",
            ['sort_order' => -1],
            $headers
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }
}
