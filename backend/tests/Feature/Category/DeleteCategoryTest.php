<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteCategoryTest extends TestCase
{
  use RefreshDatabase;

  private function endpoint(string $id): string
  {
    return "/api/v1/categories/$id";
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

  public function test_admin_can_delete_category(): void
  {
    $category = Category::factory()->create();
    $headers  = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint($category->id), [], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Category deleted',
      ]);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
  }

  /* ── Cannot delete with products ─────────────────────────────── */

  public function test_cannot_delete_category_with_products(): void
  {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint($category->id), [], $headers);

    $response->assertStatus(422)
      ->assertJson([
        'success' => false,
        'message' => 'Cannot delete category with existing products. Re-assign products first.',
      ]);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
  }

  /* ── Not found ───────────────────────────────────────────────── */

  public function test_delete_nonexistent_category(): void
  {
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [], $headers);

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Category not found',
      ]);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_delete_category(): void
  {
    $category = Category::factory()->create();
    $user     = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->deleteJson($this->endpoint($category->id), [], $headers);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_delete_category(): void
  {
    $category = Category::factory()->create();

    $response = $this->deleteJson($this->endpoint($category->id));

    $response->assertStatus(401);
  }
}
