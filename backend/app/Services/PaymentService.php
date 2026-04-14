<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createIntent(Order $order, string $currency = 'brl'): array
    {
        $amount = (int) round($order->total * 100);

        Log::channel('payment')->info('Creating payment intent', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $order->total,
            'currency' => $currency,
            'user_id' => $order->user_id,
        ]);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => $currency,
                'status' => 'PROCESSING',
                'metadata' => ['stripe_status' => $paymentIntent->status],
            ]
        );

        $order->update(['payment_status' => 'PROCESSING']);

        Log::channel('payment')->info('Payment intent created', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent->id,
            'stripe_status' => $paymentIntent->status,
        ]);

        return [
            'clientSecret' => $paymentIntent->client_secret,
            'paymentIntentId' => $paymentIntent->id,
        ];
    }

    public function confirm(string $paymentIntentId, string $paymentMethodId): array
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $paymentIntent->confirm(['payment_method' => $paymentMethodId]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($payment) {
            $status = $paymentIntent->status === 'succeeded' ? 'COMPLETED' : 'PROCESSING';
            $payment->update([
                'status' => $status,
                'payment_method' => $paymentMethodId,
            ]);

            if ($status === 'COMPLETED') {
                $payment->order->update([
                    'payment_status' => 'COMPLETED',
                    'status' => 'CONFIRMED',
                ]);
            }

            Log::channel('payment')->info('Payment confirmed', [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $payment->order_id,
                'status' => $status,
                'stripe_status' => $paymentIntent->status,
            ]);
        }

        return [
            'status' => $paymentIntent->status,
            'paymentIntentId' => $paymentIntent->id,
        ];
    }

    public function getStatus(string $paymentIntentId): array
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $payment) {
            throw new \RuntimeException('Payment not found', 404);
        }

        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        return [
            'payment' => $payment,
            'stripe_status' => $paymentIntent->status,
        ];
    }

    public function refund(string $paymentIntentId, ?float $amount = null, ?string $reason = null): array
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $payment) {
            throw new \RuntimeException('Payment not found', 404);
        }

        if ($payment->status !== 'COMPLETED') {
            throw new \InvalidArgumentException('Payment cannot be refunded');
        }

        Log::channel('payment')->info('Processing refund', [
            'payment_intent_id' => $paymentIntentId,
            'order_id' => $payment->order_id,
            'refund_amount' => $amount ?? (float) $payment->amount,
            'reason' => $reason,
        ]);

        $refundData = ['payment_intent' => $paymentIntentId];

        if ($amount !== null) {
            $refundData['amount'] = (int) round($amount * 100);
        }

        if ($reason !== null) {
            $refundData['reason'] = $reason;
        }

        $refund = Refund::create($refundData);
        $refundAmount = $amount ?? (float) $payment->amount;

        $payment->update([
            'status' => 'REFUNDED',
            'refund_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);

        $payment->order->update([
            'payment_status' => 'REFUNDED',
            'status' => 'REFUNDED',
        ]);

        Log::channel('payment')->info('Refund completed', [
            'payment_intent_id' => $paymentIntentId,
            'refund_id' => $refund->id,
            'order_id' => $payment->order_id,
            'refund_amount' => $refundAmount,
        ]);

        return [
            'refundId' => $refund->id,
            'amount' => $refundAmount,
            'status' => 'REFUNDED',
        ];
    }
}
