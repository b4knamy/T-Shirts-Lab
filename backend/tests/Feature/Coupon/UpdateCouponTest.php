<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateCouponTest extends TestCase
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

  public function test_admin_can_update_coupon_code(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'code' => 'UPDATED2025',
    ], $headers);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Coupon updated',
        'data'    => ['code' => 'UPDATED2025'],
      ]);
  }

  public function test_updated_code_is_uppercased(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'code' => 'lower',
    ], $headers);

    $response->assertOk();
    $this->assertEquals('LOWER', $response->json('data.code'));
  }

  public function test_update_value(): void
  {
    $coupon  = Coupon::factory()->percentage(10)->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'value' => 25,
    ], $headers);

    $response->assertOk()
      ->assertJson(['data' => ['value' => 25]]);
  }

  public function test_update_is_active(): void
  {
    $coupon  = Coupon::factory()->create(['is_active' => true]);
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'is_active' => false,
    ], $headers);

    $response->assertOk()
      ->assertJson(['data' => ['is_active' => false]]);
  }

  public function test_update_nonexistent_coupon(): void
  {
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint('00000000-0000-0000-0000-000000000000'), [
      'code' => 'WHATEVER',
    ], $headers);

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Coupon not found',
      ]);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_update_fails_with_duplicate_code(): void
  {
    Coupon::factory()->create(['code' => 'EXISTING']);
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'code' => 'EXISTING',
    ], $headers);

    $response->assertStatus(422);
  }

  public function test_update_fails_with_invalid_type(): void
  {
    $coupon  = Coupon::factory()->create();
    $headers = $this->authAdmin();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'type' => 'INVALID',
    ], $headers);

    $response->assertStatus(422);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_update_coupon(): void
  {
    $coupon = Coupon::factory()->create();
    $user   = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'code' => 'NEW',
    ], ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_update_coupon(): void
  {
    $coupon = Coupon::factory()->create();

    $response = $this->patchJson($this->endpoint($coupon->id), [
      'code' => 'NEW',
    ]);

    $response->assertStatus(401);
  }
}
