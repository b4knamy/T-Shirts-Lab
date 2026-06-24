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

    public function handleCheckoutSessionCompleted(object $checkoutSession): void
    {
        $orderId = $this->readMetadataValue($checkoutSession->metadata ?? null, 'order_id')
            ?? (is_string($checkoutSession->client_reference_id ?? null) ? $checkoutSession->client_reference_id : null);

        if (! $orderId) {
            return;
        }

        $sessionId = is_string($checkoutSession->id ?? null) ? $checkoutSession->id : null;
        $paymentIntentId = is_string($checkoutSession->payment_intent ?? null) ? $checkoutSession->payment_intent : null;
        $paymentStatus = is_string($checkoutSession->payment_status ?? null) ? $checkoutSession->payment_status : null;
        $checkoutStatus = is_string($checkoutSession->status ?? null) ? $checkoutSession->status : null;
        $currency = $this->toCurrencyString($checkoutSession->currency ?? null, 'brl');
        $amountTotal = round(((int) ($checkoutSession->amount_total ?? 0)) / 100, 2);

        $result = DB::transaction(function () use ($orderId, $sessionId, $paymentIntentId, $paymentStatus, $checkoutStatus, $currency, $amountTotal) {
            $order = Order::whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return [
                    'order' => null,
                    'payment' => null,
                    'previousStatus' => null,
                    'shouldNotify' => false,
                ];
            }

            $payment = Payment::where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'amount' => $amountTotal > 0 ? $amountTotal : (float) $order->total,
                    'currency' => $currency,
                    'status' => $paymentStatus === 'paid' ? 'COMPLETED' : 'PROCESSING',
                    'metadata' => [],
                ]);
            }

            $paymentMetadata = array_merge((array) ($payment->metadata ?? []), [
                'checkout_session_id' => $sessionId,
                'checkout_payment_status' => $paymentStatus,
                'checkout_status' => $checkoutStatus,
            ]);

            $paymentUpdate = [
                'currency' => $currency,
                'metadata' => $paymentMetadata,
            ];

            if ($paymentIntentId && $payment->stripe_payment_intent_id !== $paymentIntentId) {
                $paymentUpdate['stripe_payment_intent_id'] = $paymentIntentId;
            }

            if ($amountTotal > 0) {
                $paymentUpdate['amount'] = $amountTotal;
            }

            if ($paymentStatus === 'paid') {
                $paymentUpdate['status'] = 'COMPLETED';
            } elseif ($payment->status === 'PENDING') {
                $paymentUpdate['status'] = 'PROCESSING';
            }

            $payment->fill($paymentUpdate);
            if ($payment->isDirty()) {
                $payment->save();
            }

            $previousStatus = $order->status;
            $orderUpdate = [];

            if ($amountTotal > 0) {
                $orderUpdate['total'] = $amountTotal;
            }

            if ($paymentStatus === 'paid') {
                $orderUpdate['payment_status'] = 'COMPLETED';
                if ($order->status !== 'CONFIRMED') {
                    $orderUpdate['status'] = 'CONFIRMED';
                }
            } elseif ($order->payment_status === 'PENDING') {
                $orderUpdate['payment_status'] = 'PROCESSING';
            }

            $order->fill($orderUpdate);
            $shouldNotify = false;
            if ($order->isDirty()) {
                $shouldNotify = ($orderUpdate['status'] ?? null) === 'CONFIRMED' && $previousStatus !== 'CONFIRMED';
                $order->save();
            }

            return [
                'order' => $order->fresh(['user']),
                'payment' => $payment,
                'previousStatus' => $previousStatus,
                'shouldNotify' => $shouldNotify,
            ];
        });

        if (! $result['order'] || ! $result['payment']) {
            return;
        }

        if ($result['shouldNotify']) {
            $this->orderNotificationService->sendStatusUpdate(
                $result['order'],
                newStatus: 'CONFIRMED',
                previousStatus: $result['previousStatus'],
            );
        }

        if ($paymentIntentId) {
            $this->paymentLogger->completedViaWebhook($paymentIntentId, $result['payment']->order_id, $result['order']->order_number);
        }
    }

    public function handlePaymentSucceeded(object $paymentIntent): void
    {
        $paymentIntentId = is_string($paymentIntent->id ?? null) ? $paymentIntent->id : '';
        if ($paymentIntentId === '') {
            return;
        }

        $this->webhookLogger->paymentSucceeded($paymentIntentId, ((int) ($paymentIntent->amount ?? 0)) / 100, $paymentIntent->currency ?? null);

        $result = DB::transaction(function () use ($paymentIntent, $paymentIntentId) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            $order = null;

            if (! $payment) {
                $orderId = $this->readMetadataValue($paymentIntent->metadata ?? null, 'order_id');
                if ($orderId) {
                    $order = Order::whereKey($orderId)
                        ->lockForUpdate()
                        ->first();

                    if ($order) {
                        $payment = Payment::where('order_id', $order->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $payment) {
                            $payment = Payment::create([
                                'order_id' => $order->id,
                                'stripe_payment_intent_id' => $paymentIntentId,
                                'amount' => round(((int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0)) / 100, 2),
                                'currency' => $this->toCurrencyString($paymentIntent->currency ?? null, 'brl'),
                                'status' => 'PROCESSING',
                                'metadata' => [],
                            ]);
                        }
                    }
                }
            }

            if (! $payment) {
                return [
                    'payment' => null,
                    'order' => null,
                    'previousStatus' => null,
                    'shouldNotify' => false,
                ];
            }

            if (! $order) {
                $order = Order::whereKey($payment->order_id)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $order) {
                return [
                    'payment' => $payment,
                    'order' => null,
                    'previousStatus' => null,
                    'shouldNotify' => false,
                ];
            }

            $paymentMetadata = array_merge((array) ($payment->metadata ?? []), [
                'stripe_status' => $paymentIntent->status ?? null,
            ]);

            $paymentUpdate = [
                'status' => 'COMPLETED',
                'payment_method' => $paymentIntent->payment_method ?? $payment->payment_method,
                'metadata' => $paymentMetadata,
            ];

            if ($payment->stripe_payment_intent_id !== $paymentIntentId) {
                $paymentUpdate['stripe_payment_intent_id'] = $paymentIntentId;
            }

            $amountReceived = round(((int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0)) / 100, 2);
            if ($amountReceived > 0) {
                $paymentUpdate['amount'] = $amountReceived;
            }

            $payment->fill($paymentUpdate);
            if ($payment->isDirty()) {
                $payment->save();
            }

            $previousStatus = $order->status;
            $shouldNotify = false;
            if ($order->payment_status !== 'COMPLETED' || $order->status !== 'CONFIRMED') {
                $order->update([
                    'payment_status' => 'COMPLETED',
                    'status' => 'CONFIRMED',
                ]);
                $shouldNotify = $previousStatus !== 'CONFIRMED';
            }

            return [
                'payment' => $payment,
                'order' => $order->fresh(['user']),
                'previousStatus' => $previousStatus,
                'shouldNotify' => $shouldNotify,
            ];
        });

        if (! $result['payment']) {
            $this->webhookLogger->paymentNotFoundForSucceeded($paymentIntentId);

            return;
        }

        if (! $result['order']) {
            $this->webhookLogger->paymentNotFoundForSucceeded($paymentIntentId);

            return;
        }

        if ($result['shouldNotify']) {
            $this->orderNotificationService->sendStatusUpdate(
                $result['order'],
                newStatus: 'CONFIRMED',
                previousStatus: $result['previousStatus'],
            );
        }

        $this->paymentLogger->completedViaWebhook($paymentIntentId, $result['payment']->order_id, $result['order']->order_number);
    }

    public function handlePaymentFailed(object $paymentIntent): void
    {
        $paymentIntentId = is_string($paymentIntent->id ?? null) ? $paymentIntent->id : '';
        if ($paymentIntentId === '') {
            return;
        }

        $failureMessage = $paymentIntent->last_payment_error?->message ?? 'Payment failed';

        $this->webhookLogger->paymentFailed($paymentIntentId, $failureMessage);

        $result = DB::transaction(function () use ($paymentIntent, $paymentIntentId, $failureMessage) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            $order = null;

            if (! $payment) {
                $orderId = $this->readMetadataValue($paymentIntent->metadata ?? null, 'order_id');
                if ($orderId) {
                    $order = Order::whereKey($orderId)
                        ->lockForUpdate()
                        ->first();

                    if ($order) {
                        $payment = Payment::where('order_id', $order->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $payment) {
                            $payment = Payment::create([
                                'order_id' => $order->id,
                                'stripe_payment_intent_id' => $paymentIntentId,
                                'amount' => (float) $order->total,
                                'currency' => $this->toCurrencyString($paymentIntent->currency ?? null, 'brl'),
                                'status' => 'PROCESSING',
                                'metadata' => [],
                            ]);
                        }
                    }
                }
            }

            if (! $payment) {
                return [
                    'payment' => null,
                    'order' => null,
                    'itemCount' => 0,
                    'shouldSendFailureMail' => false,
                ];
            }

            if (! $order) {
                $order = Order::whereKey($payment->order_id)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $order) {
                return [
                    'payment' => $payment,
                    'order' => null,
                    'itemCount' => 0,
                    'shouldSendFailureMail' => false,
                ];
            }

            $alreadyFailed = $payment->status === 'FAILED';

            $paymentMetadata = array_merge((array) ($payment->metadata ?? []), [
                'stripe_status' => $paymentIntent->status ?? null,
            ]);

            $paymentUpdate = [
                'status' => 'FAILED',
                'failure_reason' => $failureMessage,
                'metadata' => $paymentMetadata,
            ];

            if ($payment->stripe_payment_intent_id !== $paymentIntentId) {
                $paymentUpdate['stripe_payment_intent_id'] = $paymentIntentId;
            }

            $payment->fill($paymentUpdate);
            if ($payment->isDirty()) {
                $payment->save();
            }

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
            $this->webhookLogger->paymentNotFoundForFailed($paymentIntentId);

            return;
        }

        if (! $result['order']) {
            $this->webhookLogger->paymentNotFoundForFailed($paymentIntentId);

            return;
        }

        if ($result['shouldSendFailureMail']) {
            $this->orderNotificationService->sendPaymentFailed($result['order'], $failureMessage);
        }

        $this->paymentLogger->failedViaWebhook($paymentIntentId, $result['payment']->order_id, $failureMessage, $result['itemCount']);
    }

    private function readMetadataValue(mixed $metadata, string $key): ?string
    {
        if (is_array($metadata) && isset($metadata[$key])) {
            return (string) $metadata[$key];
        }

        if (is_object($metadata) && isset($metadata->{$key})) {
            return (string) $metadata->{$key};
        }

        return null;
    }

    private function toCurrencyString(mixed $value, string $default): string
    {
        if (is_string($value) && $value !== '') {
            return strtolower($value);
        }

        return $default;
    }
}
