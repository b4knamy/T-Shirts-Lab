<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {}

    public function handleStripe(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::channel('security')->error('Stripe webhook: invalid signature', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::channel('webhook')->error('Stripe webhook: processing error', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook error'], 400);
        }

        Log::channel('webhook')->info('Stripe webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id ?? null,
            'ip' => $request->ip(),
        ]);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->webhookService->handlePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->webhookService->handlePaymentFailed($event->data->object);
                break;
            default:
                Log::channel('webhook')->info('Stripe webhook: unhandled event type', [
                    'event_type' => $event->type,
                    'event_id' => $event->id ?? null,
                ]);
                break;
        }

        return response()->json(['received' => true]);
    }
}
