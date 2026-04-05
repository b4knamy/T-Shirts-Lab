<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidateCouponTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/coupons/validate';

    private function authUser(): array
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    private function authUserWithModel(): array
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($user);

        return ['headers' => ['Authorization' => "Bearer $token"], 'user' => $user];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_validate_valid_percentage_coupon(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create();
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 100.00,
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'discount' => 10.00,
                ],
            ]);

        $this->assertEquals($coupon->code, $response->json('data.coupon.code'));
    }

    public function test_validate_valid_fixed_coupon(): void
    {
        $coupon = Coupon::factory()->fixed(20)->create([
            'min_order_amount' => null,
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 100.00,
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'discount' => 20.00,
                ],
            ]);
    }

    public function test_validate_is_case_insensitive(): void
    {
        $coupon = Coupon::factory()->fixed(15)->create([
            'code' => 'SUMMER2024',
            'min_order_amount' => null,
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => 'summer2024',
            'subtotal' => 100.00,
        ], $headers);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_percentage_coupon_respects_max_discount(): void
    {
        $coupon = Coupon::factory()->percentage(50)->create([
            'max_discount_amount' => 30.00,
            'min_order_amount' => null,
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 200.00,
        ], $headers);

        $response->assertOk()
            ->assertJson([
                'data' => ['discount' => 30.00],
            ]);
    }

    /* ── Failures ────────────────────────────────────────────────── */

    public function test_coupon_not_found(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => 'DOESNOTEXIST',
            'subtotal' => 100.00,
        ], $headers);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Coupon not found',
            ]);
    }

    public function test_expired_coupon(): void
    {
        $coupon = Coupon::factory()->expired()->create();
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 100.00,
        ], $headers);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This coupon is no longer valid',
            ]);
    }

    public function test_inactive_coupon(): void
    {
        $coupon = Coupon::factory()->inactive()->create();
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 100.00,
        ], $headers);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This coupon is no longer valid',
            ]);
    }

    public function test_user_reached_usage_limit(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $coupon = Coupon::factory()->create(['per_user_limit' => 1]);
        $token = auth('api')->login($user);

        // Simulate previous usage
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => Order::factory()->create(['user_id' => $user->id])->id,
        ]);

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 100.00,
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You have already used this coupon the maximum number of times',
            ]);
    }

    public function test_subtotal_below_minimum(): void
    {
        $coupon = Coupon::factory()->fixed(20)->create([
            'min_order_amount' => 100.00,
        ]);
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => $coupon->code,
            'subtotal' => 50.00,
        ], $headers);

        $response->assertStatus(422);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_validate_fails_without_code(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'subtotal' => 100.00,
        ], $headers);

        $response->assertStatus(422);
    }

    public function test_validate_fails_without_subtotal(): void
    {
        $headers = $this->authUser();

        $response = $this->postJson($this->endpoint, [
            'code' => 'TESTCODE',
        ], $headers);

        $response->assertStatus(422);
    }

    /* ── Auth ────────────────────────────────────────────────────── */

    public function test_unauthenticated_cannot_validate(): void
    {
        $response = $this->postJson($this->endpoint, [
            'code' => 'TESTCODE',
            'subtotal' => 100.00,
        ]);

        $response->assertStatus(401);
    }
}
