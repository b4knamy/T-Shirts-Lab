<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class RefundPaymentTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/payments/refund';

  private function authAs(User $user): array
  {
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_admin_can_refund_payment(): void
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('refund')
      ->once()
      ->andReturn([
        'refundId' => 're_test_123',
        'amount'   => 50.00,
        'status'   => 'REFUNDED',
      ]);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
      'amount'            => 50.00,
      'reason'            => 'requested_by_customer',
    ], $this->authAs($admin));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Refund processed',
        'data'    => [
          'refundId' => 're_test_123',
          'status'   => 'REFUNDED',
        ],
      ]);
  }

  public function test_super_admin_can_refund(): void
  {
    $superAdmin = User::factory()->superAdmin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('refund')
      ->once()
      ->andReturn([
        'refundId' => 're_test_456',
        'amount'   => 30.00,
        'status'   => 'REFUNDED',
      ]);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
      'amount'            => 30.00,
    ], $this->authAs($superAdmin));

    $response->assertOk();
  }

  public function test_refund_stripe_failure(): void
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('refund')
      ->once()
      ->andThrow(new \RuntimeException('Payment not found', 404));
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
    ], $this->authAs($admin));

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
      ]);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_refund_fails_without_payment_intent_id(): void
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $response = $this->postJson($this->endpoint, [], $this->authAs($admin));

    $response->assertStatus(422)
      ->assertJsonStructure(['errors' => ['payment_intent_id']]);
  }

  public function test_refund_fails_with_negative_amount(): void
  {
    $admin = User::factory()->admin()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
      'amount'            => -10.00,
    ], $this->authAs($admin));

    $response->assertStatus(422);
  }

  /* ── Auth / Permissions ──────────────────────────────────────── */

  public function test_customer_cannot_refund(): void
  {
    $customer = User::factory()->create([
      'password_hash' => Hash::make('Secret@123'),
    ]);

    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
    ], $this->authAs($customer));

    $response->assertStatus(403);
  }

  public function test_unauthenticated_cannot_refund(): void
  {
    $response = $this->postJson($this->endpoint, [
      'payment_intent_id' => 'pi_test_123',
    ]);

    $response->assertStatus(401);
  }
}
