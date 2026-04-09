<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CouponFiltersTest extends TestCase
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

    /* ── Filter by type ──────────────────────────────────────────── */

    public function test_filter_coupons_by_type_percentage(): void
    {
        Coupon::factory()->percentage()->count(3)->create();
        Coupon::factory()->fixed()->count(2)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?type=PERCENTAGE', $headers);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.total'));

        foreach ($response->json('data.data') as $coupon) {
            $this->assertEquals('PERCENTAGE', $coupon['type']);
        }
    }

    public function test_filter_coupons_by_type_fixed(): void
    {
        Coupon::factory()->percentage()->count(2)->create();
        Coupon::factory()->fixed()->count(4)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?type=FIXED', $headers);

        $response->assertOk();
        $this->assertEquals(4, $response->json('data.total'));

        foreach ($response->json('data.data') as $coupon) {
            $this->assertEquals('FIXED', $coupon['type']);
        }
    }

    public function test_no_type_filter_returns_all(): void
    {
        Coupon::factory()->percentage()->count(2)->create();
        Coupon::factory()->fixed()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.total'));
    }

    /* ── Filter by status ────────────────────────────────────────── */

    public function test_filter_coupons_by_status_active(): void
    {
        Coupon::factory()->count(3)->create([
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        Coupon::factory()->inactive()->count(2)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=active', $headers);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_filter_coupons_by_status_inactive(): void
    {
        Coupon::factory()->count(2)->create(['is_active' => true]);
        Coupon::factory()->inactive()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=inactive', $headers);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_filter_coupons_by_status_expired(): void
    {
        Coupon::factory()->expired()->count(2)->create();
        Coupon::factory()->count(3)->create([
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=expired', $headers);

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total'));
    }

    public function test_active_filter_excludes_expired_coupons(): void
    {
        Coupon::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        Coupon::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=active', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_active_filter_includes_null_expires_at(): void
    {
        Coupon::factory()->create([
            'is_active' => true,
            'expires_at' => null,
        ]);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=active', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    /* ── Search by code ──────────────────────────────────────────── */

    public function test_search_coupons_by_partial_code(): void
    {
        Coupon::factory()->create(['code' => 'BLACKFRIDAY']);
        Coupon::factory()->create(['code' => 'SUMMER50']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=BLACK', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('BLACKFRIDAY', $response->json('data.data.0.code'));
    }

    public function test_search_coupons_is_case_insensitive(): void
    {
        Coupon::factory()->create(['code' => 'SUMMER50']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=summer', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    /* ── Combined filters ────────────────────────────────────────── */

    public function test_combine_type_and_status_filters(): void
    {
        Coupon::factory()->percentage()->create([
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        Coupon::factory()->percentage()->inactive()->create();
        Coupon::factory()->fixed()->create([
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?type=PERCENTAGE&status=active', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('PERCENTAGE', $response->json('data.data.0.type'));
    }

    public function test_combine_search_and_type_filters(): void
    {
        Coupon::factory()->percentage()->create(['code' => 'SAVE10PCT']);
        Coupon::factory()->fixed()->create(['code' => 'SAVE20FIX']);
        Coupon::factory()->percentage()->create(['code' => 'WINTER25']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=SAVE&type=PERCENTAGE', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('SAVE10PCT', $response->json('data.data.0.code'));
    }

    public function test_combine_all_three_filters(): void
    {
        Coupon::factory()->percentage()->create([
            'code' => 'XMAS2024',
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        Coupon::factory()->percentage()->inactive()->create(['code' => 'XMASDEAD']);
        Coupon::factory()->fixed()->create([
            'code' => 'XMASFIX',
            'is_active' => true,
            'expires_at' => now()->addDays(10),
        ]);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=XMAS&type=PERCENTAGE&status=active', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('XMAS2024', $response->json('data.data.0.code'));
    }

    /* ── Pagination with filters ─────────────────────────────────── */

    public function test_pagination_with_type_filter(): void
    {
        Coupon::factory()->percentage()->count(5)->create();
        Coupon::factory()->fixed()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?type=PERCENTAGE&limit=2', $headers);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
        $this->assertEquals(5, $response->json('data.total'));
    }
}
