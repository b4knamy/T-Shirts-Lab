<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteCouponTest extends TestCase
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

  public function test_admin_can_delete_coupon(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint($coupon->id), [], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Coupon deleted',
      ]);

    $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
  }

  public function test_delete_nonexistent_coupon(): void
  {
    $headers = $this->authAdmin();

    $response = $this->deleteJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [], $headers);

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Coupon not found',
      ]);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_delete_coupon(): void
  {
    $coupon = Coupon::factory()->create();
    $user   = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->deleteJson($this->endpoint($coupon->id), [], ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_delete_coupon(): void
  {
    $coupon = Coupon::factory()->create();

    $response = $this->deleteJson($this->endpoint($coupon->id));

    $response->assertStatus(401);
  }
}
