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

class PaymentStatusTest extends TestCase
{
  use RefreshDatabase;

  private function authAs(User $user): array
  {
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_user_can_get_payment_status(): void
  {
    $user    = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order   = Order::factory()->paid()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->paid()->create([
      'order_id'                  => $order->id,
      'stripe_payment_intent_id'  => 'pi_test_123',
    ]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('getStatus')
      ->once()
      ->with('pi_test_123')
      ->andReturn([
        'payment'       => $payment,
        'stripe_status' => 'succeeded',
      ]);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->getJson('/api/v1/payments/pi_test_123', $this->authAs($user));

    $response->assertOk()
      ->assertJson(['success' => true]);
  }

  public function test_payment_status_not_found(): void
  {
    $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('getStatus')
      ->once()
      ->andThrow(new \RuntimeException('Payment not found', 404));
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->getJson('/api/v1/payments/pi_nonexistent', $this->authAs($user));

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Payment not found',
      ]);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_unauthenticated_cannot_get_payment_status(): void
  {
    $response = $this->getJson('/api/v1/payments/pi_test_123');

    $response->assertStatus(401);
  }
}
