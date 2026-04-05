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

class ConfirmPaymentTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/payments/confirm';

    private function authAs(User $user): array
    {
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    /* ── Success ─────────────────────────────────────────────────── */

    public function test_user_can_confirm_payment(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);
        $order = Order::factory()->pending()->create(['user_id' => $user->id]);

        // Create a payment record for this order
        Payment::factory()->create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => 'pi_test_123',
            'status' => 'PROCESSING',
        ]);

        $mock = Mockery::mock(PaymentService::class);
        $mock->shouldReceive('confirm')
            ->once()
            ->with('pi_test_123', 'pm_test_card')
            ->andReturn([
                'status' => 'succeeded',
                'id' => 'pi_test_123',
                'amount' => 10000,
            ]);
        $this->app->instance(PaymentService::class, $mock);

        $response = $this->postJson($this->endpoint, [
            'payment_intent_id' => 'pi_test_123',
            'payment_method_id' => 'pm_test_card',
        ], $this->authAs($user));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Payment confirmed',
            ]);
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_confirm_fails_without_payment_intent_id(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

        $response = $this->postJson($this->endpoint, [
            'payment_method_id' => 'pm_test_card',
        ], $this->authAs($user));

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['payment_intent_id']]);
    }

    public function test_confirm_fails_without_payment_method_id(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('Secret@123')]);

        $response = $this->postJson($this->endpoint, [
            'payment_intent_id' => 'pi_test_123',
        ], $this->authAs($user));

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['payment_method_id']]);
    }

    /* ── Auth ────────────────────────────────────────────────────── */

    public function test_unauthenticated_cannot_confirm_payment(): void
    {
        $response = $this->postJson($this->endpoint, [
            'payment_intent_id' => 'pi_test_123',
            'payment_method_id' => 'pm_test_card',
        ]);

        $response->assertStatus(401);
    }
}
