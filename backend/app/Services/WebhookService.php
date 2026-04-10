<?php

namespace App\Services;

use App\Models\Payment;

class WebhookService
{
  public function handlePaymentSucceeded(object $paymentIntent): void
  {
    $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

    if (! $payment) {
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
  }

  public function handlePaymentFailed(object $paymentIntent): void
  {
    $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

    if (! $payment) {
      return;
    }

    $payment->update([
      'status' => 'FAILED',
      'failure_reason' => $paymentIntent->last_payment_error?->message ?? 'Payment failed',
    ]);

    $payment->order->update([
      'payment_status' => 'FAILED',
    ]);

    // Release reserved stock
    foreach ($payment->order->items as $item) {
      $item->product->increment('stock_quantity', $item->quantity);
      $item->product->decrement('reserved_quantity', $item->quantity);
    }
  }
}
