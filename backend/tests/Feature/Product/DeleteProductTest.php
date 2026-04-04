<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteProductTest extends TestCase
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

  public function test_admin_can_delete_product(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint($product->id), [], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Product deleted',
      ]);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
  }

  public function test_super_admin_can_delete_product(): void
  {
    $product = Product::factory()->create();
    $admin   = User::factory()->superAdmin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($admin);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->deleteJson($this->endpoint($product->id), [], $headers);

    $response->assertOk();
  }

  /* ── Not found ───────────────────────────────────────────────── */

  public function test_delete_nonexistent_product(): void
  {
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [], $headers);

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Product not found',
      ]);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_delete_product(): void
  {
    $product = Product::factory()->create();
    $user    = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->deleteJson($this->endpoint($product->id), [], $headers);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_delete_product(): void
  {
    $product = Product::factory()->create();

    $response = $this->deleteJson($this->endpoint($product->id));

    $response->assertStatus(401);
  }
}
