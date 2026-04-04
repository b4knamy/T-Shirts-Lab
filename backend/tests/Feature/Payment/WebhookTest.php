<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class WebhookTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/webhooks/stripe';

  /* ── payment_intent.succeeded ────────────────────────────────── */

  public function test_payment_succeeded_updates_payment_and_order(): void
  {
    $user    = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $order   = Order::factory()->pending()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create([
      'order_id'                  => $order->id,
      'stripe_payment_intent_id'  => 'pi_test_succeeded',
      'status'                    => 'PROCESSING',
    ]);

    $event = $this->makeFakeEvent('payment_intent.succeeded', [
      'id'             => 'pi_test_succeeded',
      'payment_method' => 'pm_card_visa',
    ]);

    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andReturn($event);

    $response = $this->postJson($this->endpoint, [], [
      'Stripe-Signature' => 'valid_sig',
    ]);

    $response->assertOk()
      ->assertJson(['received' => true]);

    $payment->refresh();
    $this->assertEquals('COMPLETED', $payment->status);
    $this->assertEquals('pm_card_visa', $payment->payment_method);

    $order->refresh();
    $this->assertEquals('COMPLETED', $order->payment_status);
    $this->assertEquals('CONFIRMED', $order->status);
  }

  /* ── payment_intent.payment_failed ──────────────────────────── */

  public function test_payment_failed_updates_payment_and_releases_stock(): void
  {
    $user    = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
    $product = Product::factory()->create([
      'stock_quantity'    => 8,
      'reserved_quantity' => 2,
    ]);
    $order = Order::factory()->pending()->create(['user_id' => $user->id]);

    OrderItem::factory()->create([
      'order_id'   => $order->id,
      'product_id' => $product->id,
      'quantity'   => 2,
    ]);

    $payment = Payment::factory()->create([
      'order_id'                  => $order->id,
      'stripe_payment_intent_id'  => 'pi_test_failed',
      'status'                    => 'PROCESSING',
    ]);

    $event = $this->makeFakeEvent('payment_intent.payment_failed', [
      'id'                 => 'pi_test_failed',
      'last_payment_error' => (object) ['message' => 'Card declined'],
    ]);

    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andReturn($event);

    $response = $this->postJson($this->endpoint, [], [
      'Stripe-Signature' => 'valid_sig',
    ]);

    $response->assertOk()
      ->assertJson(['received' => true]);

    $payment->refresh();
    $this->assertEquals('FAILED', $payment->status);
    $this->assertEquals('Card declined', $payment->failure_reason);

    $order->refresh();
    $this->assertEquals('FAILED', $order->payment_status);

    $product->refresh();
    $this->assertEquals(10, $product->stock_quantity);
    $this->assertEquals(0, $product->reserved_quantity);
  }

  /* ── Graceful handling when no payment found ────────────────── */

  public function test_succeeded_event_with_unknown_payment_returns_ok(): void
  {
    $event = $this->makeFakeEvent('payment_intent.succeeded', [
      'id'             => 'pi_unknown',
      'payment_method' => 'pm_card_visa',
    ]);

    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andReturn($event);

    $response = $this->postJson($this->endpoint, [], [
      'Stripe-Signature' => 'valid_sig',
    ]);

    $response->assertOk()
      ->assertJson(['received' => true]);
  }

  public function test_unhandled_event_type_returns_ok(): void
  {
    $event = $this->makeFakeEvent('charge.refunded', [
      'id' => 'ch_test_123',
    ]);

    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andReturn($event);

    $response = $this->postJson($this->endpoint, [], [
      'Stripe-Signature' => 'valid_sig',
    ]);

    $response->assertOk()
      ->assertJson(['received' => true]);
  }

  /* ── Invalid signature ──────────────────────────────────────── */

  public function test_invalid_signature_returns_400(): void
  {
    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andThrow(new \Stripe\Exception\SignatureVerificationException('Invalid signature'));

    $response = $this->postJson($this->endpoint, ['anything' => true], [
      'Stripe-Signature' => 'invalid_signature',
    ]);

    $response->assertStatus(400)
      ->assertJson(['error' => 'Invalid signature']);
  }

  public function test_missing_signature_returns_400(): void
  {
    Mockery::mock('alias:\Stripe\Webhook')
      ->shouldReceive('constructEvent')
      ->once()
      ->andThrow(new \Stripe\Exception\SignatureVerificationException('No signature'));

    $response = $this->postJson($this->endpoint, ['anything' => true]);

    $response->assertStatus(400);
  }

  /* ── Helpers ─────────────────────────────────────────────────── */

  private function makeFakeEvent(string $type, array $objectData): object
  {
    $event       = new \stdClass();
    $event->type = $type;
    $event->data = (object) [
      'object' => (object) $objectData,
    ];

    return $event;
  }
}
