<?php

namespace Tests\Feature\ProductImage;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreImageTest extends TestCase
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

  /* ── URL-based store ─────────────────────────────────────────── */

  public function test_admin_can_add_image_by_url(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/tshirt.jpg',
      'alt_text'  => 'Blue t-shirt front',
    ], $headers);

    $response->assertStatus(201)
      ->assertJsonStructure([
        'success',
        'message',
        'data' => ['id', 'image_url', 'alt_text', 'sort_order', 'is_primary'],
      ])
      ->assertJson([
        'success' => true,
        'message' => 'Image added',
        'data'    => [
          'image_url' => 'https://example.com/tshirt.jpg',
          'alt_text'  => 'Blue t-shirt front',
        ],
      ]);

    $this->assertDatabaseHas('product_images', [
      'product_id' => $product->id,
      'image_url'  => 'https://example.com/tshirt.jpg',
    ]);
  }

  public function test_first_image_becomes_primary(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/first.jpg',
    ], $headers);

    $response->assertStatus(201);
    $this->assertTrue($response->json('data.is_primary'));
  }

  public function test_second_image_not_primary_by_default(): void
  {
    $product = Product::factory()->create();
    ProductImage::factory()->primary()->create(['product_id' => $product->id]);
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/second.jpg',
    ], $headers);

    $response->assertStatus(201);
    $this->assertNotTrue($response->json('data.is_primary'));
  }

  public function test_setting_new_image_as_primary_unsets_previous(): void
  {
    $product  = Product::factory()->create();
    $existing = ProductImage::factory()->primary()->create(['product_id' => $product->id]);
    $headers  = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url'  => 'https://example.com/new-primary.jpg',
      'is_primary' => true,
    ], $headers);

    $response->assertStatus(201);
    $this->assertTrue($response->json('data.is_primary'));

    $existing->refresh();
    $this->assertFalse($existing->is_primary);
  }

  public function test_auto_sort_order(): void
  {
    $product = Product::factory()->create();
    ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 3]);
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/next.jpg',
    ], $headers);

    $response->assertStatus(201);
    $this->assertEquals(4, $response->json('data.sort_order'));
  }

  public function test_custom_sort_order(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url'  => 'https://example.com/tshirt.jpg',
      'sort_order' => 10,
    ], $headers);

    $response->assertStatus(201);
    $this->assertEquals(10, $response->json('data.sort_order'));
  }

  /* ── Upload file ─────────────────────────────────────────────── */

  public function test_admin_can_upload_image_file(): void
  {
    Storage::fake('public');
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image'    => UploadedFile::fake()->image('tshirt.jpg', 800, 800),
      'alt_text' => 'Uploaded image',
    ], $headers);

    $response->assertStatus(201)
      ->assertJson([
        'success' => true,
        'message' => 'Image uploaded',
      ]);

    $this->assertNotEmpty($response->json('data.image_url'));
  }

  public function test_upload_png(): void
  {
    Storage::fake('public');
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image' => UploadedFile::fake()->image('tshirt.png', 800, 800),
    ], $headers);

    $response->assertStatus(201);
  }

  public function test_upload_webp(): void
  {
    Storage::fake('public');
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image' => UploadedFile::fake()->image('tshirt.webp', 800, 800),
    ], $headers);

    $response->assertStatus(201);
  }

  public function test_upload_fails_without_file(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['image']);
  }

  public function test_upload_fails_with_non_image(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['image']);
  }

  public function test_upload_fails_with_oversized_file(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image' => UploadedFile::fake()->image('huge.jpg')->size(6000),
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['image']);
  }

  public function test_upload_first_becomes_primary(): void
  {
    Storage::fake('public');
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images/upload", [
      'image' => UploadedFile::fake()->image('first.jpg', 800, 800),
    ], $headers);

    $response->assertStatus(201);
    $this->assertTrue($response->json('data.is_primary'));
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_store_fails_without_image_url(): void
  {
    $product = Product::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'alt_text' => 'Some text',
    ], $headers);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['image_url']);
  }

  public function test_store_for_nonexistent_product(): void
  {
    $headers = $this->authAdmin();

    $response = $this->postJson('/api/v1/products/00000000-0000-0000-0000-000000000000/images', [
      'image_url' => 'https://example.com/img.jpg',
    ], $headers);

    $response->assertStatus(404);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_add_image(): void
  {
    $product = Product::factory()->create();
    $user    = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/img.jpg',
    ], $headers);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_add_image(): void
  {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/v1/products/{$product->id}/images", [
      'image_url' => 'https://example.com/img.jpg',
    ]);

    $response->assertStatus(401);
  }
}
