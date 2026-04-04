<?php

namespace Tests\Feature\Coupon;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateCouponTest extends TestCase
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

  private function validPayload(array $overrides = []): array
  {
    return array_merge([
      'code'        => 'NEWYEAR2025',
      'type'        => 'PERCENTAGE',
      'value'       => 15,
      'description' => 'New Year sale',
      'is_active'   => true,
    ], $overrides);
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_admin_can_create_coupon(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload();

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(201)
      ->assertJson([
        'success' => true,
        'message' => 'Coupon created',
        'data'    => [
          'code' => 'NEWYEAR2025',
          'type' => 'PERCENTAGE',
          'value' => 15,
        ],
      ]);

    $this->assertDatabaseHas('coupons', ['code' => 'NEWYEAR2025']);
  }

  public function test_code_is_uppercased(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload(['code' => 'lowercase']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(201);
    $this->assertEquals('LOWERCASE', $response->json('data.code'));
  }

  public function test_create_fixed_coupon(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload([
      'code'  => 'FIXED20',
      'type'  => 'FIXED',
      'value' => 20.00,
    ]);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(201)
      ->assertJson([
        'data' => [
          'type'  => 'FIXED',
          'value' => 20.00,
        ],
      ]);
  }

  public function test_create_with_all_optional_fields(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload([
      'min_order_amount'    => 50.00,
      'max_discount_amount' => 30.00,
      'usage_limit'         => 100,
      'per_user_limit'      => 2,
      'is_public'           => true,
      'starts_at'           => '2025-01-01 00:00:00',
      'expires_at'          => '2025-12-31 23:59:59',
    ]);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(201)
      ->assertJson([
        'data' => [
          'min_order_amount'    => 50.00,
          'max_discount_amount' => 30.00,
          'usage_limit'         => 100,
          'per_user_limit'      => 2,
          'is_public'           => true,
        ],
      ]);
  }

  public function test_super_admin_can_create_coupon(): void
  {
    $admin = User::factory()->superAdmin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token   = auth('api')->login($admin);
    $headers = ['Authorization' => "Bearer $token"];

    $response = $this->postJson($this->endpoint, $this->validPayload(['code' => 'SA_COUPON']), $headers);

    $response->assertStatus(201);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_create_fails_without_code(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload();
    unset($payload['code']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  public function test_create_fails_without_type(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload();
    unset($payload['type']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  public function test_create_fails_without_value(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload();
    unset($payload['value']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  public function test_create_fails_with_invalid_type(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload(['type' => 'BOGUS']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  public function test_create_fails_with_duplicate_code(): void
  {
    Coupon::factory()->create(['code' => 'DUPLICATE']);
    $headers = $this->authAdmin();
    $payload = $this->validPayload(['code' => 'DUPLICATE']);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  public function test_create_fails_with_zero_value(): void
  {
    $headers = $this->authAdmin();
    $payload = $this->validPayload(['value' => 0]);

    $response = $this->postJson($this->endpoint, $payload, $headers);

    $response->assertStatus(422);
  }

  /* ── Auth & Permission ─────────────────────────────────────────── */

  public function test_customer_cannot_create_coupon(): void
  {
    $user = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->postJson($this->endpoint, $this->validPayload(), ['Authorization' => "Bearer $token"]);

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_create_coupon(): void
  {
    $response = $this->postJson($this->endpoint, $this->validPayload());

    $response->assertStatus(401);
  }
}
