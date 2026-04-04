<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShowCouponTest extends TestCase
{
  use RefreshDatabase;

  private function endpoint(string $id): string
  {
    return "/api/v1/coupons/{$id}";
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

  public function test_admin_can_show_coupon(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint($coupon->id), $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'data'    => [
          'id'   => $coupon->id,
          'code' => $coupon->code,
          'type' => $coupon->type,
        ],
      ]);
  }

  public function test_show_coupon_full_structure(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint($coupon->id), $headers);

    $response->assertOk()
      ->assertJsonStructure([
        'data' => [
          'id',
          'code',
          'description',
          'type',
          'value',
          'min_order_amount',
          'max_discount_amount',
          'usage_limit',
          'usage_count',
          'per_user_limit',
          'is_active',
          'is_public',
          'starts_at',
          'expires_at',
          'created_at',
          'updated_at',
        ],
      ]);
  }

  public function test_show_nonexistent_coupon(): void
  {
    $headers = $this->authAdmin();

    $response = $this->getJson($this->endpoint('00000000-0000-0000-0000-000000000000'), $headers);

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Coupon not found',
      ]);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_show_coupon(): void
  {
    $coupon = Coupon::factory()->create();
    $user   = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->getJson($this->endpoint($coupon->id), ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_show_coupon(): void
  {
    $coupon = Coupon::factory()->create();

    $response = $this->getJson($this->endpoint($coupon->id));

    $response->assertStatus(401);
  }
}
