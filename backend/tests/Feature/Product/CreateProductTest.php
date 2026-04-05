<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/products';

    private function authAdmin(): array
    {
        $admin = User::factory()->admin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);

        return ['Authorization' => "Bearer $token"];
    }

    private function validPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_merge([
            'name' => 'Dragon Ball Tee',
            'description' => 'Awesome Dragon Ball t-shirt',
            'category_id' => $category->id,
            'price' => 79.90,
            'stock_quantity' => 100,
        ], $overrides);
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_create_product(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'slug', 'sku', 'description', 'price', 'category_id', 'status'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Product created',
                'data' => [
                    'name' => 'Dragon Ball Tee',
                    'price' => 79.90,
                    'stock_quantity' => 100,
                ],
            ]);

        $this->assertDatabaseHas('products', ['name' => 'Dragon Ball Tee']);
    }

    public function test_slug_and_sku_auto_generated(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.slug'));
        $this->assertNotEmpty($response->json('data.sku'));
        $this->assertStringStartsWith('TSL-', $response->json('data.sku'));
    }

    public function test_custom_sku_is_used(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload(['sku' => 'CUSTOM-SKU-001']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201);
        $this->assertEquals('CUSTOM-SKU-001', $response->json('data.sku'));
    }

    public function test_create_with_all_optional_fields(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload([
            'long_description' => 'A very long description here.',
            'cost_price' => 25.00,
            'discount_price' => 59.90,
            'discount_percent' => 25.0,
            'is_featured' => true,
            'status' => 'DRAFT',
            'color' => 'Preto',
            'size' => 'G',
        ]);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'long_description' => 'A very long description here.',
                    'cost_price' => 25.00,
                    'discount_price' => 59.90,
                    'discount_percent' => 25.0,
                    'is_featured' => true,
                    'status' => 'DRAFT',
                    'color' => 'Preto',
                    'size' => 'G',
                ],
            ]);
    }

    public function test_default_status_is_active(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201);
        $this->assertEquals('ACTIVE', $response->json('data.status'));
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_super_admin_can_create_product(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(201);
    }

    public function test_moderator_can_create_product(): void
    {
        $mod = User::factory()->moderator()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($mod);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(201);
    }

    public function test_customer_cannot_create_product(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_product(): void
    {
        $response = $this->postJson($this->endpoint, $this->validPayload());

        $response->assertStatus(401);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_create_fails_without_name(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_create_fails_without_description(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['description']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['description']]);
    }

    public function test_create_fails_without_category_id(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['category_id']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_id']]);
    }

    public function test_create_fails_with_invalid_category_id(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload(['category_id' => '00000000-0000-0000-0000-000000000000']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_id']]);
    }

    public function test_create_fails_without_price(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['price']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['price']]);
    }

    public function test_create_fails_with_negative_price(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload(['price' => -10]);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['price']]);
    }

    public function test_create_fails_without_stock_quantity(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['stock_quantity']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['stock_quantity']]);
    }

    public function test_create_fails_with_invalid_status(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload(['status' => 'INVALID_STATUS']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_create_fails_with_duplicate_sku(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'sku' => 'DUPE-SKU']);
        $headers = $this->authAdmin();

        $payload = $this->validPayload(['sku' => 'DUPE-SKU']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['sku']]);
    }

    public function test_create_fails_with_empty_body(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, [], $headers);

        $response->assertStatus(422);
    }
}
