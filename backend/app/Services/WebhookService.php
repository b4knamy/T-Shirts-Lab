<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function handlePaymentSucceeded(object $paymentIntent): void
    {
        Log::channel('webhook')->info('Webhook: payment_intent.succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => ($paymentIntent->amount ?? 0) / 100,
            'currency' => $paymentIntent->currency ?? null,
        ]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            Log::channel('webhook')->warning('Webhook: payment not found for succeeded intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $payment->update([
            'status' => 'COMPLETED',
            'payment_method' => $paymentIntent->payment_method,
        ]);

        $payment->order->update([
            'payment_status' => 'COMPLETED',
            'status' => 'CONFIRMED',
        ]);

        Log::channel('payment')->info('Payment completed via webhook', [
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $payment->order_id,
            'order_number' => $payment->order->order_number ?? null,
        ]);
    }

    public function handlePaymentFailed(object $paymentIntent): void
    {
        $failureMessage = $paymentIntent->last_payment_error?->message ?? 'Payment failed';

        Log::channel('webhook')->warning('Webhook: payment_intent.payment_failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_reason' => $failureMessage,
        ]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            Log::channel('webhook')->warning('Webhook: payment not found for failed intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $payment->update([
            'status' => 'FAILED',
            'failure_reason' => $failureMessage,
        ]);

        $payment->order->update([
            'payment_status' => 'FAILED',
        ]);

        // Release reserved stock
        foreach ($payment->order->items as $item) {
            $item->product->increment('stock_quantity', $item->quantity);
            $item->product->decrement('reserved_quantity', $item->quantity);
        }

        Log::channel('payment')->warning('Payment failed via webhook, stock released', [
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $payment->order_id,
            'failure_reason' => $failureMessage,
            'items_released' => $payment->order->items->count(),
        ]);
    }
}
