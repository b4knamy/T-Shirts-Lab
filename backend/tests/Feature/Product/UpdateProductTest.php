<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    use RefreshDatabase;

    private function endpoint(string $id): string
    {
        return "/api/v1/products/$id";
    }

    private function authAdmin(): array
    {
        $admin = User::factory()->admin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);

        return ['Authorization' => "Bearer $token"];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_update_product_name(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'name' => 'Updated Product Name',
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Product updated',
                'data' => ['name' => 'Updated Product Name'],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    public function test_slug_regenerates_on_name_change(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'name' => 'Totally New Name',
        ], $headers);

        $response->assertOk();
        $this->assertStringContainsString('totally-new-name', $response->json('data.slug'));
    }

    public function test_update_price(): void
    {
        $product = Product::factory()->create(['price' => 50.00]);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'price' => 99.90,
        ], $headers);

        $response->assertOk();
        $this->assertEquals(99.90, $response->json('data.price'));
    }

    public function test_update_status(): void
    {
        $product = Product::factory()->create(['status' => 'ACTIVE']);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'status' => 'INACTIVE',
        ], $headers);

        $response->assertOk();
        $this->assertEquals('INACTIVE', $response->json('data.status'));
    }

    public function test_update_stock_quantity(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'stock_quantity' => 200,
        ], $headers);

        $response->assertOk();
        $this->assertEquals(200, $response->json('data.stock_quantity'));
    }

    public function test_update_is_featured(): void
    {
        $product = Product::factory()->create(['is_featured' => false]);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'is_featured' => true,
        ], $headers);

        $response->assertOk();
        $this->assertTrue($response->json('data.is_featured'));
    }

    public function test_update_category(): void
    {
        $newCategory = Category::factory()->create();
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'category_id' => $newCategory->id,
        ], $headers);

        $response->assertOk();
        $this->assertEquals($newCategory->id, $response->json('data.category_id'));
    }

    public function test_update_multiple_fields(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'name' => 'Multi Update',
            'price' => 123.45,
            'stock_quantity' => 999,
            'color' => 'Vermelho',
            'size' => 'GG',
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Multi Update',
                    'price' => 123.45,
                    'stock_quantity' => 999,
                    'color' => 'Vermelho',
                    'size' => 'GG',
                ],
            ]);
    }

    /* ── Not found ───────────────────────────────────────────────── */

    public function test_update_nonexistent_product(): void
    {
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [
            'name' => 'Anything',
        ], $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found',
            ]);
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_update_product(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->patchJson($this->endpoint($product->id), [
            'name' => 'Hacked',
        ], $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->patchJson($this->endpoint($product->id), [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(401);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_update_fails_with_negative_price(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'price' => -5,
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['price']]);
    }

    public function test_update_fails_with_invalid_status(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'status' => 'INVALID_STATUS',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_update_fails_with_invalid_category(): void
    {
        $product = Product::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($product->id), [
            'category_id' => '00000000-0000-0000-0000-000000000000',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_id']]);
    }
}
