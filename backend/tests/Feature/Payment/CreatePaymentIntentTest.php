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

class CreatePaymentIntentTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/payments/create-intent';

  private function authAs(User $user): array
  {
    $token = auth('api')->login($user);

    return ['Authorization' => "Bearer $token"];
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_user_can_create_payment_intent(): void
  {
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->pending()->create(['user_id' => $user->id, 'total' => 100.00]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('createIntent')
      ->once()
      ->andReturn([
        'clientSecret'    => 'pi_test_secret',
        'paymentIntentId' => 'pi_test_123',
      ]);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
    ], $this->authAs($user));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'message' => 'Payment intent created',
        'data'    => [
          'clientSecret'    => 'pi_test_secret',
          'paymentIntentId' => 'pi_test_123',
        ],
      ]);
  }

  public function test_create_intent_with_currency(): void
  {
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('createIntent')
      ->once()
      ->withArgs(function ($o, $currency) {
        return $currency === 'usd';
      })
      ->andReturn([
        'clientSecret'    => 'pi_test_secret',
        'paymentIntentId' => 'pi_test_123',
      ]);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
      'currency' => 'usd',
    ], $this->authAs($user));

    $response->assertOk();
  }

  /* ── Failures ────────────────────────────────────────────────── */

  public function test_cannot_create_intent_for_another_users_order(): void
  {
    $user1 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $user2 = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->pending()->create(['user_id' => $user1->id]);

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
    ], $this->authAs($user2));

    $response->assertStatus(404)
      ->assertJson([
        'success' => false,
        'message' => 'Order not found',
      ]);
  }

  public function test_cannot_create_intent_for_already_paid_order(): void
  {
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->paid()->create(['user_id' => $user->id]);

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
    ], $this->authAs($user));

    $response->assertStatus(400)
      ->assertJson([
        'success' => false,
        'message' => 'Order already paid',
      ]);
  }

  public function test_create_intent_for_nonexistent_order(): void
  {
    $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

    $response = $this->postJson($this->endpoint, [
      'order_id' => '00000000-0000-0000-0000-000000000000',
    ], $this->authAs($user));

    $response->assertStatus(422);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_create_intent_fails_without_order_id(): void
  {
    $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

    $response = $this->postJson($this->endpoint, [], $this->authAs($user));

    $response->assertStatus(422)
      ->assertJsonStructure(['errors' => ['order_id']]);
  }

  public function test_create_intent_fails_with_invalid_currency(): void
  {
    $user  = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
      'currency' => 'us',      // must be exactly 3 chars
    ], $this->authAs($user));

    $response->assertStatus(422);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_unauthenticated_cannot_create_intent(): void
  {
    $order = Order::factory()->pending()->create();

    $response = $this->postJson($this->endpoint, [
      'order_id' => $order->id,
    ]);

    $response->assertStatus(401);
  }
}
