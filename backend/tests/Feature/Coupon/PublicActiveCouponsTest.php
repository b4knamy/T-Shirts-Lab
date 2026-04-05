<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicActiveCouponsTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/coupons/active';

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_list_public_active_coupons(): void
    {
        Coupon::factory()->public()->count(3)->create();
        // Private coupon should be excluded
        Coupon::factory()->create(['is_public' => false, 'is_active' => true]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_excludes_expired_coupons(): void
    {
        Coupon::factory()->public()->create();
        Coupon::factory()->public()->expired()->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_excludes_inactive_coupons(): void
    {
        Coupon::factory()->public()->create();
        Coupon::factory()->public()->inactive()->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_excludes_coupons_at_usage_limit(): void
    {
        Coupon::factory()->public()->create();
        Coupon::factory()->public()->create([
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);

        $response = $this->getJson($this->endpoint);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_returns_empty_when_none_available(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(0, $response->json('data'));
    }

    public function test_is_publicly_accessible(): void
    {
        // No auth needed
        $response = $this->getJson($this->endpoint);

        $response->assertOk();
    }

    public function test_coupon_structure(): void
    {
        Coupon::factory()->public()->create();

        $response = $this->getJson($this->endpoint);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'description',
                        'type',
                        'value',
                        'is_active',
                        'is_public',
                        'starts_at',
                        'expires_at',
                    ],
                ],
            ]);
    }
}
