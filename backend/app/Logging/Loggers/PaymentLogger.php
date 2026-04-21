<?php

namespace App\Logging\Loggers;

use App\Models\Order;

class PaymentLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'payment';
    }

    public function creatingIntent(Order $order, string $currency): void
    {
        $this->info('payment.creating_intent', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $order->total,
            'currency' => $currency,
            'user_id' => $order->user_id,
        ]);
    }

    public function intentCreated(Order $order, string $paymentIntentId, string $stripeStatus): void
    {
        $this->info('payment.intent_created', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntentId,
            'stripe_status' => $stripeStatus,
        ]);
    }

    public function confirmed(string $paymentIntentId, string $orderId, string $status, string $stripeStatus): void
    {
        $this->info('payment.confirmed', [
            'payment_intent_id' => $paymentIntentId,
            'order_id' => $orderId,
            'status' => $status,
            'stripe_status' => $stripeStatus,
        ]);
    }

    public function processingRefund(string $paymentIntentId, string $orderId, float $amount, ?string $reason): void
    {
        $this->info('payment.processing_refund', [
            'payment_intent_id' => $paymentIntentId,
            'order_id' => $orderId,
            'refund_amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function refundCompleted(string $paymentIntentId, string $refundId, string $orderId, float $amount): void
    {
        $this->info('payment.refund_completed', [
            'payment_intent_id' => $paymentIntentId,
            'refund_id' => $refundId,
            'order_id' => $orderId,
            'refund_amount' => $amount,
        ]);
    }

    public function completedViaWebhook(string $paymentIntentId, string $orderId, ?string $orderNumber): void
    {
        $this->info('payment.completed_via_webhook', [
            'payment_intent_id' => $paymentIntentId,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
        ]);
    }

    public function failedViaWebhook(string $paymentIntentId, string $orderId, string $reason, int $itemsReleased): void
    {
        $this->warning('payment.failed_via_webhook', [
            'payment_intent_id' => $paymentIntentId,
            'order_id' => $orderId,
            'failure_reason' => $reason,
            'items_released' => $itemsReleased,
        ]);
    }
}
