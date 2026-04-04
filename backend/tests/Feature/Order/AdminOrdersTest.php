<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
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

  /* ── Admin list all orders ──────────────────────────────────── */

  public function test_admin_can_list_all_orders(): void
  {
    Order::factory()->count(5)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/orders', $headers);

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

    $this->assertEquals(5, $response->json('data.total'));
  }

  public function test_admin_orders_pagination(): void
  {
    Order::factory()->count(10)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson('/api/v1/orders?limit=3', $headers);

    $response->assertOk();
    $this->assertCount(3, $response->json('data.data'));
    $this->assertEquals(10, $response->json('data.total'));
  }

  /* ── Admin update status ────────────────────────────────────── */

  public function test_admin_can_update_order_status(): void
  {
    $order   = Order::factory()->pending()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status' => 'CONFIRMED',
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Order status updated',
        'data'    => [
          'id'     => $order->id,
          'status' => 'CONFIRMED',
        ],
      ]);
  }

  public function test_admin_can_add_notes_with_status_update(): void
  {
    $order   = Order::factory()->pending()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status'      => 'PROCESSING',
      'admin_notes' => 'Rush order',
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'data' => [
          'status'      => 'PROCESSING',
          'admin_notes' => 'Rush order',
        ],
      ]);
  }

  public function test_cancel_order_releases_stock(): void
  {
    $product = Product::factory()->create([
      'price'             => 50.00,
      'stock_quantity'    => 7,
      'reserved_quantity' => 3,
      'status'            => 'ACTIVE',
    ]);
    $order = Order::factory()->pending()->create();
    OrderItem::factory()->create([
      'order_id'   => $order->id,
      'product_id' => $product->id,
      'quantity'   => 3,
    ]);
    $headers = $this->authAdmin();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status' => 'CANCELLED',
    ], $headers);

    $response->assertOk();

    $product->refresh();
    $this->assertEquals(10, $product->stock_quantity);
    $this->assertEquals(0, $product->reserved_quantity);
  }

  public function test_update_status_nonexistent_order(): void
  {
    $headers = $this->authAdmin();

    $response = $this->patchJson('/api/v1/orders/00000000-0000-0000-0000-000000000000/status', [
      'status' => 'CONFIRMED',
    ], $headers);

    $response->assertStatus(404);
  }

  public function test_update_status_all_valid_statuses(): void
  {
    $validStatuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED'];
    $headers       = $this->authAdmin();

    foreach ($validStatuses as $status) {
      $order = Order::factory()->create();

      $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => $status,
      ], $headers);

      $response->assertOk()
        ->assertJson(['data' => ['status' => $status]]);
    }
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_update_status_fails_without_status(): void
  {
    $order   = Order::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [], $headers);

    $response->assertStatus(422)
      ->assertJsonStructure(['errors' => ['status']]);
  }

  public function test_update_status_fails_with_invalid_status(): void
  {
    $order   = Order::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status' => 'BOGUS',
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonStructure(['errors' => ['status']]);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_list_all_orders(): void
  {
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/orders', ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_customer_cannot_update_order_status(): void
  {
    $order = Order::factory()->create();
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $token = auth('api')->login($user);

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status' => 'CONFIRMED',
    ], ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_list_all_orders(): void
  {
    $response = $this->getJson('/api/v1/orders');

    $response->assertStatus(401);
  }

  public function test_unauthenticated_cannot_update_order_status(): void
  {
    $order = Order::factory()->create();

    $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
      'status' => 'CONFIRMED',
    ]);

    $response->assertStatus(401);
  }
}
