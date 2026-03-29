<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class PaymentService
{
  public function __construct(
    private readonly PaymentRepositoryInterface $paymentRepository
  ) {
    Stripe::setApiKey(config('services.stripe.secret'));
  }

  public function createIntent(Order $order, string $currency = 'brl'): array
  {
    $amount = (int) round($order->total * 100);

    $paymentIntent = PaymentIntent::create([
      'amount'   => $amount,
      'currency' => $currency,
      'metadata' => [
        'order_id'     => $order->id,
        'order_number' => $order->order_number,
        'user_id'      => $order->user_id,
      ],
      'automatic_payment_methods' => ['enabled' => true],
    ]);

    $this->paymentRepository->createOrUpdate($order->id, [
      'stripe_payment_intent_id' => $paymentIntent->id,
      'amount'                   => $order->total,
      'currency'                 => $currency,
      'status'                   => 'PROCESSING',
      'metadata'                 => ['stripe_status' => $paymentIntent->status],
    ]);

    $order->update(['payment_status' => 'PROCESSING']);

    return [
      'clientSecret'    => $paymentIntent->client_secret,
      'paymentIntentId' => $paymentIntent->id,
    ];
  }

  public function confirm(string $paymentIntentId, string $paymentMethodId): array
  {
    $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
    $paymentIntent->confirm(['payment_method' => $paymentMethodId]);

    $payment = $this->paymentRepository->findByPaymentIntentId($paymentIntentId);

    if ($payment) {
      $status = $paymentIntent->status === 'succeeded' ? 'COMPLETED' : 'PROCESSING';
      $this->paymentRepository->update($paymentIntentId, [
        'status'         => $status,
        'payment_method' => $paymentMethodId,
      ]);

      if ($status === 'COMPLETED') {
        $payment->order->update([
          'payment_status' => 'COMPLETED',
          'status'         => 'CONFIRMED',
        ]);
      }
    }

    return [
      'status'          => $paymentIntent->status,
      'paymentIntentId' => $paymentIntent->id,
    ];
  }

  public function getStatus(string $paymentIntentId): array
  {
    $payment = $this->paymentRepository->findByPaymentIntentId($paymentIntentId);

    if (!$payment) {
      throw new \RuntimeException('Payment not found', 404);
    }

    $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

    return [
      'payment'       => $payment,
      'stripeStatus'  => $paymentIntent->status,
    ];
  }

  public function refund(string $paymentIntentId, ?float $amount = null, ?string $reason = null): array
  {
    $payment = $this->paymentRepository->findByPaymentIntentId($paymentIntentId);

    if (!$payment) {
      throw new \RuntimeException('Payment not found', 404);
    }

    if ($payment->status !== 'COMPLETED') {
      throw new \InvalidArgumentException('Payment cannot be refunded');
    }

    $refundData = ['payment_intent' => $paymentIntentId];

    if ($amount !== null) {
      $refundData['amount'] = (int) round($amount * 100);
    }

    if ($reason !== null) {
      $refundData['reason'] = $reason;
    }

    $refund       = Refund::create($refundData);
    $refundAmount = $amount ?? (float) $payment->amount;

    $this->paymentRepository->update($paymentIntentId, [
      'status'       => 'REFUNDED',
      'refund_amount' => $refundAmount,
      'refunded_at'  => now(),
    ]);

    $payment->order->update([
      'payment_status' => 'REFUNDED',
      'status'         => 'REFUNDED',
    ]);

    return [
      'refundId' => $refund->id,
      'amount'   => $refundAmount,
      'status'   => 'REFUNDED',
    ];
  }
}
