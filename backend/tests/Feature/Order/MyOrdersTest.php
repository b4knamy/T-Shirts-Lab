<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MyOrdersTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/orders/my-orders';

    private function authAs(User $user): array
    {
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_user_can_list_own_orders(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

        $product = Product::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $headers = $this->authAs($user);

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'total',
                    'page',
                    'limit',
                ],
                'meta',
            ]);

        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_user_only_sees_own_orders(): void
    {
        $user1 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $user2 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

        Order::factory()->count(3)->create(['user_id' => $user1->id]);
        Order::factory()->count(2)->create(['user_id' => $user2->id]);

        $response = $this->getJson($this->endpoint, $this->authAs($user1));

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_my_orders_pagination(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        Order::factory()->count(10)->create(['user_id' => $user->id]);

        $response = $this->getJson($this->endpoint.'?limit=3', $this->authAs($user));

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
        $this->assertEquals(10, $response->json('data.total'));
    }

    public function test_empty_orders(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

        $response = $this->getJson($this->endpoint, $this->authAs($user));

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total'));
    }

    /* ── Auth ────────────────────────────────────────────────────── */

    public function test_unauthenticated_cannot_list_orders(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401);
    }
}
