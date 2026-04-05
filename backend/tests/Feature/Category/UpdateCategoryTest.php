<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateCategoryTest extends TestCase
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

    public function test_admin_can_update_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'name' => 'New Name',
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Category updated',
                'data' => ['name' => 'New Name'],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_slug_regenerates_on_name_change(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'name' => 'Brand New Name',
        ], $headers);

        $response->assertOk();
        $this->assertStringContainsString('brand-new-name', $response->json('data.slug'));
    }

    public function test_update_description(): void
    {
        $category = Category::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'description' => 'Updated description',
        ], $headers);

        $response->assertOk();
        $this->assertEquals('Updated description', $response->json('data.description'));
    }

    public function test_update_is_active(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'is_active' => false,
        ], $headers);

        $response->assertOk();
        $this->assertFalse($response->json('data.is_active'));
    }

    public function test_update_image_url(): void
    {
        $category = Category::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'image_url' => 'https://example.com/new.jpg',
        ], $headers);

        $response->assertOk();
        $this->assertEquals('https://example.com/new.jpg', $response->json('data.image_url'));
    }

    /* ── Not found ───────────────────────────────────────────────── */

    public function test_update_nonexistent_category(): void
    {
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [
            'name' => 'Anything',
        ], $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Category not found',
            ]);
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_update_category(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->patchJson($this->endpoint($category->id), [
            'name' => 'Hacked',
        ], $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->patchJson($this->endpoint($category->id), [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(401);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_update_fails_with_name_too_long(): void
    {
        $category = Category::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'name' => str_repeat('A', 101),
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_fails_with_invalid_image_url(): void
    {
        $category = Category::factory()->create();
        $headers = $this->authAdmin();

        $response = $this->patchJson($this->endpoint($category->id), [
            'image_url' => 'not-a-url',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_url']);
    }
}
