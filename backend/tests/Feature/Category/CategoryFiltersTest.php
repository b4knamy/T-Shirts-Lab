<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CategoryFiltersTest extends TestCase
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

  /* ── Filter by status ────────────────────────────────────────── */

  public function test_filter_categories_active(): void
  {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->count(2)->create(['is_active' => false]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?status=active', $headers);

    $response->assertOk();
    $this->assertCount(3, $response->json('data.data'));
    $this->assertEquals(3, $response->json('data.total'));
  }

  public function test_filter_categories_inactive(): void
  {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->count(2)->create(['is_active' => false]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?status=inactive', $headers);

    $response->assertOk();
    $this->assertCount(2, $response->json('data.data'));
    $this->assertEquals(2, $response->json('data.total'));
  }

  public function test_no_status_filter_returns_all(): void
  {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->count(2)->create(['is_active' => false]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint, $headers);

    $response->assertOk();
    $this->assertEquals(5, $response->json('data.total'));
  }

  public function test_filter_active_returns_empty_when_none(): void
  {
    Category::factory()->count(3)->create(['is_active' => false]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?status=active', $headers);

    $response->assertOk();
    $this->assertCount(0, $response->json('data.data'));
  }

  /* ── Search + status combined ────────────────────────────────── */

  public function test_combine_search_and_status_filters(): void
  {
    Category::factory()->create(['name' => 'Anime Tees', 'is_active' => true]);
    Category::factory()->create(['name' => 'Anime Vintage', 'is_active' => false]);
    Category::factory()->create(['name' => 'Sports Gear', 'is_active' => true]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=anime&status=active', $headers);

    $response->assertOk();
    $this->assertCount(1, $response->json('data.data'));
    $this->assertEquals('Anime Tees', $response->json('data.data.0.name'));
  }

  public function test_search_is_case_insensitive(): void
  {
    Category::factory()->create(['name' => 'Premium Collection']);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=premium', $headers);

    $response->assertOk();
    $this->assertCount(1, $response->json('data.data'));
  }

  public function test_search_returns_empty_when_no_match(): void
  {
    Category::factory()->count(3)->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?search=nonexistent', $headers);

    $response->assertOk();
    $this->assertCount(0, $response->json('data.data'));
  }

  /* ── Pagination with filters ─────────────────────────────────── */

  public function test_pagination_with_status_filter(): void
  {
    Category::factory()->count(5)->create(['is_active' => true]);
    Category::factory()->count(3)->create(['is_active' => false]);
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint . '?status=active&limit=2&page=1', $headers);

    $response->assertOk();
    $this->assertCount(2, $response->json('data.data'));
    $this->assertEquals(5, $response->json('data.total'));
  }
}
