<?php

namespace Tests\Feature\Payment;

use App\Mail\OrderStatusMail;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/webhooks/stripe';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /* ── payment_intent.succeeded ────────────────────────────────── */

    public function test_payment_succeeded_updates_payment_and_order(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $order = Order::factory()->pending()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_test_succeeded',
            'status' => 'PROCESSING',
        ]);

        $event = $this->makeFakeEvent('payment_intent.succeeded', [
            'id' => 'pi_test_succeeded',
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

        Mail::assertSent(OrderStatusMail::class, function ($mail) use ($order, $user) {
            return $mail->hasTo($user->email)
                && $mail->orderNumber === $order->order_number
                && $mail->currentStatus === 'CONFIRMED'
                && $mail->previousStatus === 'PENDING';
        });
    }

    public function test_checkout_session_completed_updates_order_and_links_payment_intent(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $order = Order::factory()->pending()->create([
            'user_id' => $user->id,
            'subtotal' => 100.00,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'tax_amount' => 0,
            'total' => 100.00,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => null,
            'amount' => 100.00,
            'currency' => 'brl',
            'status' => 'PROCESSING',
            'metadata' => [],
        ]);

        $event = $this->makeFakeEvent('checkout.session.completed', [
            'id' => 'cs_test_123',
            'payment_intent' => 'pi_test_checkout_123',
            'payment_status' => 'paid',
            'status' => 'complete',
            'currency' => 'brl',
            'amount_total' => 10000,
            'metadata' => (object) ['order_id' => $order->id],
            'client_reference_id' => $order->id,
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
        $this->assertEquals('pi_test_checkout_123', $payment->stripe_payment_intent_id);
        $this->assertEquals('COMPLETED', $payment->status);
        $this->assertEquals(100.00, (float) $payment->amount);

        $order->refresh();
        $this->assertEquals('COMPLETED', $order->payment_status);
        $this->assertEquals('CONFIRMED', $order->status);
        $this->assertEquals(0.00, (float) $order->tax_amount);
        $this->assertEquals(100.00, (float) $order->total);

        Mail::assertSent(OrderStatusMail::class, function ($mail) use ($order, $user) {
            return $mail->hasTo($user->email)
                && $mail->orderNumber === $order->order_number
                && $mail->currentStatus === 'CONFIRMED';
        });
    }

    /* ── payment_intent.payment_failed ──────────────────────────── */

    public function test_payment_failed_updates_payment_and_releases_stock(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $product = Product::factory()->create([
            'stock_quantity' => 8,
            'reserved_quantity' => 2,
        ]);
        $order = Order::factory()->pending()->create(['user_id' => $user->id]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_test_failed',
            'status' => 'PROCESSING',
        ]);

        $event = $this->makeFakeEvent('payment_intent.payment_failed', [
            'id' => 'pi_test_failed',
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

        Mail::assertNotSent(OrderStatusMail::class);

        Mail::assertSent(PaymentFailedMail::class, function ($mail) use ($order, $user) {
            return $mail->hasTo($user->email)
                && $mail->orderNumber === $order->order_number
                && $mail->failureReason === 'Card declined';
        });
    }

    public function test_payment_failed_event_is_idempotent(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $product = Product::factory()->create([
            'stock_quantity' => 8,
            'reserved_quantity' => 2,
        ]);
        $order = Order::factory()->pending()->create(['user_id' => $user->id]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_test_failed_idempotent',
            'status' => 'PROCESSING',
        ]);

        $event = $this->makeFakeEvent('payment_intent.payment_failed', [
            'id' => 'pi_test_failed_idempotent',
            'last_payment_error' => (object) ['message' => 'Card declined'],
        ]);

        Mockery::mock('alias:\Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->twice()
            ->andReturn($event);

        $this->postJson($this->endpoint, [], [
            'Stripe-Signature' => 'valid_sig',
        ])->assertOk();

        $this->postJson($this->endpoint, [], [
            'Stripe-Signature' => 'valid_sig',
        ])->assertOk();

        $product->refresh();
        $this->assertEquals(10, $product->stock_quantity);
        $this->assertEquals(0, $product->reserved_quantity);

        Mail::assertSent(PaymentFailedMail::class, function ($mail) use ($order, $user) {
            return $mail->hasTo($user->email)
                && $mail->orderNumber === $order->order_number
                && $mail->failureReason === 'Card declined';
        });
        Mail::assertSentCount(1);
    }

    /* ── Graceful handling when no payment found ────────────────── */

    public function test_succeeded_event_with_unknown_payment_returns_ok(): void
    {
        $event = $this->makeFakeEvent('payment_intent.succeeded', [
            'id' => 'pi_unknown',
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
            ->andThrow(new SignatureVerificationException('Invalid signature'));

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
            ->andThrow(new SignatureVerificationException('No signature'));

        $response = $this->postJson($this->endpoint, ['anything' => true]);

        $response->assertStatus(400);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function makeFakeEvent(string $type, array $objectData): object
    {
        $event = new \stdClass;
        $event->type = $type;
        $event->data = (object) [
            'object' => (object) $objectData,
        ];

        return $event;
    }
}
