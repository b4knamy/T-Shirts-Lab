<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShowOrderTest extends TestCase
{
  use RefreshDatabase;

  private function endpoint(string $id): string
  {
    return "/api/v1/orders/{$id}";
  }

  private function authAs(User $user): array
  {
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_user_can_show_own_order(): void
  {
    $user    = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $product = Product::factory()->create();
    $order   = Order::factory()->pending()->create(['user_id' => $user->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    $response = $this->getJson($this->endpoint($order->id), $this->authAs($user));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'data'    => [
          'id'     => $order->id,
          'status' => 'PENDING',
        ],
      ])
      ->assertJsonStructure([
        'data' => [
          'id',
          'order_number',
          'subtotal',
          'discount_amount',
          'tax_amount',
          'shipping_cost',
          'total',
          'status',
          'payment_status',
          'items',
        ],
      ]);
  }

  public function test_admin_can_show_any_order(): void
  {
    $customer = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $admin    = User::factory()->admin()->create(['password_hash' => Hash::make('Secret@123')]);
    $order    = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->getJson($this->endpoint($order->id), $this->authAs($admin));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'data'    => ['id' => $order->id],
      ]);
  }

  public function test_customer_cannot_show_other_users_order(): void
  {
    $user1 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $user2 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->create(['user_id' => $user1->id]);

    $response = $this->getJson($this->endpoint($order->id), $this->authAs($user2));

    $response->assertStatus(403);
  }

  public function test_order_not_found(): void
  {
    $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

    $response = $this->getJson(
      $this->endpoint('00000000-0000-0000-0000-000000000000'),
      $this->authAs($user)
    );

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Order not found',
      ]);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_unauthenticated_cannot_show_order(): void
  {
    $order = Order::factory()->create();

    $response = $this->getJson($this->endpoint($order->id));

    $response->assertStatus(401);
  }
}
