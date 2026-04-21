<?php

namespace App\Services;

use App\Logging\Loggers\PaymentLogger;
use App\Logging\Loggers\WebhookLogger;
use App\Models\Payment;

class WebhookService
{
    public function __construct(
        private WebhookLogger $webhookLogger,
        private PaymentLogger $paymentLogger,
    ) {}

    public function handlePaymentSucceeded(object $paymentIntent): void
    {
        $this->webhookLogger->paymentSucceeded($paymentIntent->id, ($paymentIntent->amount ?? 0) / 100, $paymentIntent->currency ?? null);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            $this->webhookLogger->paymentNotFoundForSucceeded($paymentIntent->id);

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

        $this->paymentLogger->completedViaWebhook($paymentIntent->id, $payment->order_id, $payment->order->order_number ?? null);
    }

    public function handlePaymentFailed(object $paymentIntent): void
    {
        $failureMessage = $paymentIntent->last_payment_error?->message ?? 'Payment failed';

        $this->webhookLogger->paymentFailed($paymentIntent->id, $failureMessage);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            $this->webhookLogger->paymentNotFoundForFailed($paymentIntent->id);

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

        $this->paymentLogger->failedViaWebhook($paymentIntent->id, $payment->order_id, $failureMessage, $payment->order->items->count());
    }
}
