<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function handleStripe(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook error'], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function handlePaymentSucceeded($paymentIntent): void
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

    private function handlePaymentFailed($paymentIntent): void
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
