<?php

namespace App\Services;

use App\Logging\Loggers\PaymentLogger;
use App\Logging\Loggers\WebhookLogger;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WebhookService
{
    public function __construct(
        private WebhookLogger $webhookLogger,
        private PaymentLogger $paymentLogger,
        private OrderNotificationService $orderNotificationService,
    ) {}

    public function handlePaymentSucceeded(object $paymentIntent): void
    {
        $this->webhookLogger->paymentSucceeded($paymentIntent->id, ($paymentIntent->amount ?? 0) / 100, $paymentIntent->currency ?? null);

        $result = DB::transaction(function () use ($paymentIntent) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return [
                    'payment' => null,
                    'order' => null,
                    'previousStatus' => null,
                    'orderId' => null,
                    'orderNumber' => null,
                ];
            }

            $order = Order::whereKey($payment->order_id)
                ->lockForUpdate()
                ->first();

            $previousStatus = $order?->status;

            $payment->update([
                'status' => 'COMPLETED',
                'payment_method' => $paymentIntent->payment_method,
            ]);

            if ($order) {
                $order->update([
                    'payment_status' => 'COMPLETED',
                    'status' => 'CONFIRMED',
                ]);
            }

            return [
                'payment' => $payment,
                'order' => $order?->fresh(['user']),
                'previousStatus' => $previousStatus,
                'orderId' => $payment->order_id,
                'orderNumber' => $order?->order_number,
            ];
        });

        if (! $result['payment']) {
            $this->webhookLogger->paymentNotFoundForSucceeded($paymentIntent->id);

            return;
        }

        if ($result['order']) {
            $this->orderNotificationService->sendStatusUpdate(
                $result['order'],
                newStatus: 'CONFIRMED',
                previousStatus: $result['previousStatus'],
            );
        }

        $this->paymentLogger->completedViaWebhook($paymentIntent->id, $result['orderId'], $result['orderNumber']);
    }

    public function handlePaymentFailed(object $paymentIntent): void
    {
        $failureMessage = $paymentIntent->last_payment_error?->message ?? 'Payment failed';

        $this->webhookLogger->paymentFailed($paymentIntent->id, $failureMessage);

        $result = DB::transaction(function () use ($paymentIntent, $failureMessage) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return [
                    'payment' => null,
                    'order' => null,
                    'itemCount' => 0,
                    'shouldSendFailureMail' => false,
                ];
            }

            $order = Order::whereKey($payment->order_id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return [
                    'payment' => $payment,
                    'order' => null,
                    'itemCount' => 0,
                    'shouldSendFailureMail' => false,
                ];
            }

            $alreadyFailed = $payment->status === 'FAILED';

            $payment->update([
                'status' => 'FAILED',
                'failure_reason' => $failureMessage,
            ]);

            if ($order->payment_status !== 'FAILED') {
                $order->update([
                    'payment_status' => 'FAILED',
                ]);
            }

            $shouldReleaseStock = ! $alreadyFailed && $order->status !== 'CANCELLED';

            if ($shouldReleaseStock) {
                $items = $order->items()->get();

                foreach ($items as $item) {
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        continue;
                    }

                    $product->update([
                        'stock_quantity' => $product->stock_quantity + $item->quantity,
                        'reserved_quantity' => max(0, $product->reserved_quantity - $item->quantity),
                    ]);
                }
            }

            $freshOrder = $order->fresh(['user', 'items']);

            return [
                'payment' => $payment,
                'order' => $freshOrder,
                'itemCount' => $freshOrder->items->count(),
                'shouldSendFailureMail' => ! $alreadyFailed && $order->status !== 'CANCELLED',
            ];
        });

        if (! $result['payment']) {
            $this->webhookLogger->paymentNotFoundForFailed($paymentIntent->id);

            return;
        }

        if (! $result['order']) {
            $this->webhookLogger->paymentNotFoundForFailed($paymentIntent->id);

            return;
        }

        if ($result['shouldSendFailureMail']) {
            $this->orderNotificationService->sendPaymentFailed($result['order'], $failureMessage);
        }

        $this->paymentLogger->failedViaWebhook($paymentIntent->id, $result['payment']->order_id, $failureMessage, $result['itemCount']);
    }
}
