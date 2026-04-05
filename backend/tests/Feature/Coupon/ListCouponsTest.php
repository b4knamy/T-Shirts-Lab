<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ListCouponsTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/coupons';

    private function authAdmin(): array
    {
        $admin = User::factory()->admin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);

        return ['Authorization' => "Bearer $token"];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_admin_can_list_coupons(): void
    {
        Coupon::factory()->count(5)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint, $headers);

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

        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_search_coupons_by_code(): void
    {
        Coupon::factory()->create(['code' => 'SUMMER2024']);
        Coupon::factory()->create(['code' => 'WINTER2024']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=SUMMER', $headers);

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('SUMMER2024', $response->json('data.data.0.code'));
    }

    public function test_pagination_with_custom_limit(): void
    {
        Coupon::factory()->count(10)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?limit=3', $headers);

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
        $this->assertEquals(10, $response->json('data.total'));
    }

    public function test_pagination_second_page(): void
    {
        Coupon::factory()->count(5)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?page=2&limit=3', $headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    /* ── Auth & Permission ─────────────────────────────────────────── */

    public function test_customer_cannot_list_coupons(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);

        $response = $this->getJson($this->endpoint, ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_coupons(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401);
    }
}
