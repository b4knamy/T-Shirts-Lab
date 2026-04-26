<?php

namespace App\Services;

use App\Logging\Loggers\PaymentLogger;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class PaymentService
{
    public function __construct(
        private PaymentLogger $paymentLogger,
        private OrderNotificationService $orderNotificationService,
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createIntent(Order $order, string $currency = 'brl'): array
    {
        $currency = strtolower($currency);
        $order->loadMissing(['user', 'items.product.images', 'items.design']);

        if ((float) $order->total <= 0) {
            throw new \InvalidArgumentException('Order total must be greater than zero');
        }

        $this->paymentLogger->creatingIntent($order, $currency);

        ['successUrl' => $successUrl, 'cancelUrl' => $cancelUrl] = $this->buildCheckoutUrls($order);

        $session = Session::create([
            'mode' => Session::MODE_PAYMENT,
            'line_items' => $this->buildCheckoutLineItems($order, $currency),
            'billing_address_collection' => Session::BILLING_ADDRESS_COLLECTION_REQUIRED,
            'customer_creation' => Session::CUSTOMER_CREATION_IF_REQUIRED,
            'client_reference_id' => $order->id,
            'customer_email' => $order->user?->email,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
            ],
            'payment_intent_data' => [
                'description' => $this->buildPaymentIntentDescription($order),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
            ],
            'custom_text' => [
                'submit' => [
                    'message' => $this->buildCheckoutSubmitMessage($order),
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'stripe_payment_intent_id' => null,
                'amount' => $order->total,
                'currency' => $currency,
                'status' => 'PROCESSING',
                'metadata' => [
                    'stripe_status' => $session->status,
                    'checkout_session_id' => $session->id,
                ],
            ]
        );

        $order->update(['payment_status' => 'PROCESSING']);

        $this->paymentLogger->intentCreated($order, $session->id, $session->status ?? 'open');

        return [
            'checkoutUrl' => $session->url,
            'checkoutSessionId' => $session->id,
        ];
    }

    private function buildCheckoutUrls(Order $order): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        if ($frontendUrl === '') {
            $frontendUrl = rtrim((string) config('app.url'), '/');
        }

        return [
            'successUrl' => "{$frontendUrl}/orders/{$order->id}?checkout=success&session_id={CHECKOUT_SESSION_ID}",
            'cancelUrl' => "{$frontendUrl}/checkout?checkout=cancelled&order_id={$order->id}",
        ];
    }

    private function buildCheckoutLineItems(Order $order, string $currency): array
    {
        $lineItems = $this->buildCheckoutOrderItemLines($order, $currency);

        if ((float) $order->shipping_cost > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round((float) $order->shipping_cost * 100),
                    'product_data' => [
                        'name' => 'Shipping',
                        'description' => "Order {$order->order_number}",
                    ],
                ],
            ];
        }

        if (empty($lineItems)) {
            throw new \InvalidArgumentException('Order total must be greater than zero');
        }

        return $lineItems;
    }

    private function buildCheckoutOrderItemLines(Order $order, string $currency): array
    {
        $rows = [];

        foreach ($order->items as $index => $item) {
            $baseTotalMinor = max(0, (int) round((float) $item->total_price * 100));
            if ($baseTotalMinor <= 0) {
                continue;
            }

            $rows[] = [
                'index' => $index + 1,
                'item' => $item,
                'base_minor' => $baseTotalMinor,
                'adjusted_minor' => $baseTotalMinor,
            ];
        }

        if (empty($rows)) {
            return [];
        }

        $baseItemsMinor = array_sum(array_column($rows, 'base_minor'));
        $targetItemsMinor = max(0, (int) round(((float) $order->subtotal - (float) $order->discount_amount) * 100));

        if ($baseItemsMinor > 0 && $targetItemsMinor !== $baseItemsMinor) {
            $allocated = 0;

            foreach ($rows as $key => $row) {
                $adjustedMinor = (int) floor(($row['base_minor'] * $targetItemsMinor) / $baseItemsMinor);
                $rows[$key]['adjusted_minor'] = max(0, $adjustedMinor);
                $allocated += $rows[$key]['adjusted_minor'];
            }

            $remainder = $targetItemsMinor - $allocated;
            $rowCount = count($rows);
            $cursor = 0;

            while ($remainder > 0 && $rowCount > 0) {
                $rows[$cursor]['adjusted_minor']++;
                $remainder--;
                $cursor = ($cursor + 1) % $rowCount;
            }
        }

        $lineItems = [];

        foreach ($rows as $row) {
            if ($row['adjusted_minor'] <= 0) {
                continue;
            }

            $productData = [
                'name' => $this->buildCheckoutItemName($row['item'], $row['index']),
                'description' => $this->buildCheckoutItemDescription($row['item']),
            ];

            $imageUrls = $this->buildCheckoutItemImageUrls($row['item']);
            if (! empty($imageUrls)) {
                $productData['images'] = $imageUrls;
            }

            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $row['adjusted_minor'],
                    'product_data' => $productData,
                ],
            ];
        }

        return $lineItems;
    }

    private function buildCheckoutItemName(object $orderItem, int $index): string
    {
        $name = trim((string) ($orderItem->product?->name ?? 'T-Shirt'));
        if ($name === '') {
            $name = "Item {$index}";
        }

        return Str::limit($name, 120, '');
    }

    private function buildCheckoutItemDescription(object $orderItem): string
    {
        $parts = ['Qty: '.((int) ($orderItem->quantity ?? 1))];
        $designName = trim((string) ($orderItem->design?->name ?? ''));

        if ($designName !== '') {
            $parts[] = 'Design: '.$designName;
        }

        return Str::limit(implode(' | ', $parts), 500, '');
    }

    private function buildCheckoutItemImageUrls(object $orderItem): array
    {
        $primaryImageUrl = $this->resolveCheckoutPrimaryImageUrl($orderItem);

        return $primaryImageUrl ? [$primaryImageUrl] : [];
    }

    private function resolveCheckoutPrimaryImageUrl(object $orderItem): ?string
    {
        $productImages = $orderItem->product?->images;
        if (! $productImages || $productImages->isEmpty()) {
            return null;
        }

        $primaryImage = $productImages->firstWhere('is_primary', true) ?? $productImages->sortBy('sort_order')->first();
        $rawImageUrl = trim((string) ($primaryImage?->image_url ?? ''));

        if ($rawImageUrl === '') {
            return null;
        }

        if (Str::startsWith($rawImageUrl, ['http://', 'https://'])) {
            return $rawImageUrl;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/'.ltrim($rawImageUrl, '/');
    }

    private function buildPaymentIntentDescription(Order $order): string
    {
        $itemCount = (int) $order->items->sum('quantity');

        return Str::limit("Order {$order->order_number} ({$itemCount} items)", 300, '');
    }

    private function buildCheckoutSubmitMessage(Order $order): string
    {
        $itemCount = (int) $order->items->sum('quantity');
        $message = "Order {$order->order_number} | {$itemCount} items";

        if ((float) $order->discount_amount > 0) {
            $message .= ' | Discount applied';
        }

        return Str::limit($message, 120, '');
    }

    public function confirm(string $paymentIntentId, string $paymentMethodId): array
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $paymentIntent->confirm(['payment_method' => $paymentMethodId]);

        $status = $paymentIntent->status === 'succeeded' ? 'COMPLETED' : 'PROCESSING';

        $notificationPayload = DB::transaction(function () use ($paymentIntentId, $paymentMethodId, $status, $paymentIntent) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return null;
            }

            $order = Order::whereKey($payment->order_id)
                ->lockForUpdate()
                ->first();

            $previousStatus = $order?->status;

            $payment->update([
                'status' => $status,
                'payment_method' => $paymentMethodId,
            ]);

            if ($status === 'COMPLETED' && $order) {
                $order->update([
                    'payment_status' => 'COMPLETED',
                    'status' => 'CONFIRMED',
                ]);
            }

            $this->paymentLogger->confirmed($paymentIntentId, $payment->order_id, $status, $paymentIntent->status);

            if ($status !== 'COMPLETED' || ! $order) {
                return null;
            }

            return [
                'order' => $order->fresh(['user']),
                'previousStatus' => $previousStatus,
            ];
        });

        if ($notificationPayload) {
            $this->orderNotificationService->sendStatusUpdate(
                $notificationPayload['order'],
                newStatus: 'CONFIRMED',
                previousStatus: $notificationPayload['previousStatus'],
            );
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
        $result = DB::transaction(function () use ($paymentIntentId, $amount, $reason) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new \RuntimeException('Payment not found', 404);
            }

            if ($payment->status !== 'COMPLETED') {
                throw new \InvalidArgumentException('Payment cannot be refunded');
            }

            $order = Order::whereKey($payment->order_id)
                ->lockForUpdate()
                ->first();

            $refundAmount = $amount ?? (float) $payment->amount;

            $this->paymentLogger->processingRefund($paymentIntentId, $payment->order_id, $refundAmount, $reason);

            $refundData = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $refundData['amount'] = (int) round($amount * 100);
            }

            if ($reason !== null) {
                $refundData['reason'] = $reason;
            }

            $refund = Refund::create($refundData);
            $previousStatus = $order?->status;

            $payment->update([
                'status' => 'REFUNDED',
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
            ]);

            if ($order) {
                $order->update([
                    'payment_status' => 'REFUNDED',
                    'status' => 'REFUNDED',
                ]);
            }

            $this->paymentLogger->refundCompleted($paymentIntentId, $refund->id, $payment->order_id, $refundAmount);

            return [
                'refundId' => $refund->id,
                'amount' => $refundAmount,
                'status' => 'REFUNDED',
                'order' => $order?->fresh(['user']),
                'previousStatus' => $previousStatus,
            ];
        });

        if ($result['order']) {
            $this->orderNotificationService->sendStatusUpdate(
                $result['order'],
                newStatus: 'REFUNDED',
                previousStatus: $result['previousStatus'],
            );
        }

        return [
            'refundId' => $result['refundId'],
            'amount' => $result['amount'],
            'status' => $result['status'],
        ];
    }
}
