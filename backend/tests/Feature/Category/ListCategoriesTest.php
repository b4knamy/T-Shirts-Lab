<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ListCategoriesTest extends TestCase
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

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_list_categories(): void
    {
        Category::factory()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'total',
                    'page',
                    'limit',
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_super_admin_can_list_categories(): void
    {
        Category::factory()->count(2)->create();
        $admin = User::factory()->superAdmin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_moderator_can_list_categories(): void
    {
        Category::factory()->count(2)->create();
        $mod = User::factory()->moderator()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($mod);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk();
    }

    public function test_customer_cannot_list_categories(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer $token"];

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_categories(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401);
    }

    /* ── Search ──────────────────────────────────────────────────── */

    public function test_search_categories_by_name(): void
    {
        Category::factory()->create(['name' => 'Anime Collection']);
        Category::factory()->create(['name' => 'Sports Gear']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=anime', $headers);

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Anime Collection', $response->json('data.data.0.name'));
    }

    /* ── Pagination ──────────────────────────────────────────────── */

    public function test_pagination_with_custom_limit(): void
    {
        Category::factory()->count(5)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?limit=2&page=1', $headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
        $this->assertEquals(5, $response->json('data.total'));
    }

    public function test_pagination_second_page(): void
    {
        Category::factory()->count(5)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?limit=3&page=2', $headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }
}
