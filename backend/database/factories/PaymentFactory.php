<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    private static array $paymentStatuses = ['PENDING', 'PROCESSING', 'PAID', 'FAILED', 'REFUNDED', 'PARTIALLY_REFUNDED'];

    private static array $paymentMethods = ['card', 'boleto', 'pix'];

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'stripe_payment_intent_id' => 'pi_'.Str::random(24),
            'amount' => fake()->randomFloat(2, 50.00, 600.00),
            'currency' => 'brl',
            'status' => fake()->randomElement(self::$paymentStatuses),
            'payment_method' => fake()->randomElement(self::$paymentMethods),
            'refund_amount' => null,
            'refunded_at' => null,
            'metadata' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'COMPLETED',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'FAILED']);
    }

    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'REFUNDED',
                'refund_amount' => $attributes['amount'],
                'refunded_at' => now(),
            ];
        });
    }
}
