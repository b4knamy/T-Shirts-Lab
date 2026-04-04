<?php

namespace Tests\Feature\ProductImage;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteImageTest extends TestCase
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

  public function test_admin_can_delete_image(): void
  {
    $product = Product::factory()->create();
    $image   = ProductImage::factory()->create(['product_id' => $product->id]);
    $headers = $this->authAdmin();

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/{$image->id}",
      [],
      $headers
    );

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Image deleted',
      ]);

    $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
  }

  public function test_deleting_primary_promotes_next(): void
  {
    $product   = Product::factory()->create();
    $primary   = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
    $secondary = ProductImage::factory()->create([
      'product_id' => $product->id,
      'is_primary' => false,
      'sort_order' => 2,
    ]);
    $headers = $this->authAdmin();

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/{$primary->id}",
      [],
      $headers
    );

    $response->assertOk();

    $secondary->refresh();
    $this->assertTrue($secondary->is_primary);
  }

  public function test_deleting_non_primary_does_not_change_primary(): void
  {
    $product   = Product::factory()->create();
    $primary   = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
    $secondary = ProductImage::factory()->create([
      'product_id' => $product->id,
      'is_primary' => false,
      'sort_order' => 2,
    ]);
    $headers = $this->authAdmin();

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/{$secondary->id}",
      [],
      $headers
    );

    $response->assertOk();

    $primary->refresh();
    $this->assertTrue($primary->is_primary);
  }

  /* ── Not found ───────────────────────────────────────────────── */

  public function test_delete_nonexistent_image(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/00000000-0000-0000-0000-000000000000",
      [],
      $headers
    );

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Image not found',
      ]);
  }

  public function test_delete_image_from_wrong_product(): void
  {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $image    = ProductImage::factory()->create(['product_id' => $product2->id]);
    $headers  = $this->authAdmin();

    $response = $this->deleteJson(
      "/api/v1/products/{$product1->id}/images/{$image->id}",
      [],
      $headers
    );

    $response->assertStatus(404);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_delete_image(): void
  {
    $product = Product::factory()->create();
    $image   = ProductImage::factory()->create(['product_id' => $product->id]);
    $user    = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/{$image->id}",
      [],
      $headers
    );

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_delete_image(): void
  {
    $product = Product::factory()->create();
    $image   = ProductImage::factory()->create(['product_id' => $product->id]);

    $response = $this->deleteJson(
      "/api/v1/products/{$product->id}/images/{$image->id}"
    );

    $response->assertStatus(401);
  }
}
