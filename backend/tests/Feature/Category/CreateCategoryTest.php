<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateCategoryTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/categories';

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
        return array_merge([
            'name' => 'Anime',
            'description' => 'Japanese animation merch',
            'image_url' => 'https://example.com/anime.jpg',
            'is_active' => true,
        ], $overrides);
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_create_category(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'slug', 'description', 'image_url', 'is_active'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Category created',
                'data' => [
                    'name' => 'Anime',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('categories', ['name' => 'Anime']);
    }

    public function test_super_admin_can_create_category(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(201);
    }

    public function test_slug_is_auto_generated(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, $this->validPayload([
            'name' => 'Games & Tech',
        ]), $headers);

        $response->assertStatus(201);
        $this->assertStringContainsString('games-tech', $response->json('data.slug'));
    }

    public function test_slug_collision_appends_random(): void
    {
        Category::factory()->create(['name' => 'Anime', 'slug' => 'anime']);
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, $this->validPayload([
            'name' => 'Anime',
        ]), $headers);

        $response->assertStatus(201);
        $slug = $response->json('data.slug');
        $this->assertStringStartsWith('anime-', $slug);
        $this->assertNotEquals('anime', $slug);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $headers = $this->authAdmin();
        $payload = $this->validPayload();
        unset($payload['is_active']);

        $response = $this->postJson($this->endpoint, $payload, $headers);

        $response->assertStatus(201);
        $this->assertTrue($response->json('data.is_active'));
    }

    public function test_create_without_optional_fields(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, [
            'name' => 'Minimalist',
        ], $headers);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Minimalist',
                    'description' => null,
                    'image_url' => null,
                    'is_active' => true,
                ],
            ]);
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_create_category(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->postJson($this->endpoint, $this->validPayload(), $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_category(): void
    {
        $response = $this->postJson($this->endpoint, $this->validPayload());

        $response->assertStatus(401);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_create_fails_without_name(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, [
            'description' => 'Some description',
        ], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_fails_with_name_too_long(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, $this->validPayload([
            'name' => str_repeat('A', 101),
        ]), $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_fails_with_invalid_image_url(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, $this->validPayload([
            'image_url' => 'not-a-valid-url',
        ]), $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_url']);
    }

    public function test_create_fails_with_empty_body(): void
    {
        $headers = $this->authAdmin();

        $response = $this->postJson($this->endpoint, [], $headers);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
