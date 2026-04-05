<?php

namespace Tests\Feature\Order;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/orders';

    private function authUser(): array
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    private function authUserWithModel(): array
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);

        return [
            'headers' => ['Authorization' => "Bearer $token"],
            'user' => $user,
        ];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_authenticated_user_can_create_order(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ], $headers);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order created',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'subtotal',
                    'total',
                    'status',
                    'payment_status',
                    'items',
                ],
            ]);
    }

    public function test_order_has_correct_calculations(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'discount_price' => null,
            'stock_quantity' => 20,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ], $headers);

        $response->assertStatus(201);

        $data = $response->json('data');
        // subtotal = 50 * 4 = 200
        $this->assertEquals(200.00, $data['subtotal']);
        // shipping free for 200+
        $this->assertEquals(0.00, $data['shipping_cost']);
        // status
        $this->assertEquals('PENDING', $data['status']);
        $this->assertEquals('PENDING', $data['payment_status']);
    }

    public function test_order_uses_discount_price(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
            'discount_price' => 75.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $headers);

        $response->assertStatus(201);
        $this->assertEquals(75.00, $response->json('data.subtotal'));
    }

    public function test_order_decrements_stock(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ], $headers);

        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity);
        $this->assertEquals(3, $product->reserved_quantity);
    }

    public function test_order_with_multiple_items(): void
    {
        $product1 = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $product2 = Product::factory()->create([
            'price' => 80.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
        ], $headers);

        $response->assertStatus(201);
        // subtotal = (50*2) + (80*1) = 180
        $this->assertEquals(180.00, $response->json('data.subtotal'));
    }

    public function test_order_with_coupon(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $coupon = Coupon::factory()->fixed(20)->create([
            'min_order_amount' => null,
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'coupon_code' => $coupon->code,
        ], $headers);

        $response->assertStatus(201);
        $this->assertEquals(20.00, $response->json('data.discount_amount'));
    }

    public function test_order_with_customer_notes(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_notes' => 'Please wrap as gift',
        ], $headers);

        $response->assertStatus(201);
        $this->assertEquals('Please wrap as gift', $response->json('data.customer_notes'));
    }

    /* ── Failures ────────────────────────────────────────────────── */

    public function test_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 2,
            'status' => 'ACTIVE',
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ], $headers);

        $response->assertStatus(400);
    }

    public function test_invalid_coupon(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 10,
            'status' => 'ACTIVE',
        ]);
        $coupon = Coupon::factory()->expired()->create();
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'coupon_code' => $coupon->code,
        ], $headers);

        $response->assertStatus(400);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_create_fails_without_items(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [], $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_create_fails_with_empty_items(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [],
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_create_fails_without_product_id(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['quantity' => 1]],
        ], $headers);

        $response->assertStatus(422);
    }

    public function test_create_fails_without_quantity(): void
    {
        $product = Product::factory()->create(['status' => 'ACTIVE']);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => $product->id]],
        ], $headers);

        $response->assertStatus(422);
    }

    public function test_create_fails_with_zero_quantity(): void
    {
        $product = Product::factory()->create(['status' => 'ACTIVE']);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
        ], $headers);

        $response->assertStatus(422);
    }

    public function test_create_fails_with_nonexistent_product(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => '00000000-0000-0000-0000-000000000000', 'quantity' => 1]],
        ], $headers);

        $response->assertStatus(422);
    }

    /* ── Auth ────────────────────────────────────────────────────── */

    public function test_unauthenticated_cannot_create_order(): void
    {
        $product = Product::factory()->create(['status' => 'ACTIVE']);

        $response = $this->postJson($this->endpoint, [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(401);
    }
}
