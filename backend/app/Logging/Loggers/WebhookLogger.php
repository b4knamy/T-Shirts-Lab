<?php

namespace App\Logging\Loggers;

class WebhookLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'webhook';
    }

    public function paymentSucceeded(string $paymentIntentId, float $amount, ?string $currency): void
    {
        $this->info('webhook.payment_intent.succeeded', [
            'payment_intent_id' => $paymentIntentId,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    public function paymentNotFoundForSucceeded(string $paymentIntentId): void
    {
        $this->warning('webhook.payment_not_found_for_succeeded', [
            'payment_intent_id' => $paymentIntentId,
        ]);
    }

    public function paymentFailed(string $paymentIntentId, string $reason): void
    {
        $this->warning('webhook.payment_intent.payment_failed', [
            'payment_intent_id' => $paymentIntentId,
            'failure_reason' => $reason,
        ]);
    }

    public function paymentNotFoundForFailed(string $paymentIntentId): void
    {
        $this->warning('webhook.payment_not_found_for_failed', [
            'payment_intent_id' => $paymentIntentId,
        ]);
    }

    public function received(string $eventType, ?string $eventId, string $ip): void
    {
        $this->info('webhook.received', [
            'event_type' => $eventType,
            'event_id' => $eventId,
            'ip' => $ip,
        ]);
    }

    public function unhandledEventType(string $eventType, ?string $eventId): void
    {
        $this->info('webhook.unhandled_event_type', [
            'event_type' => $eventType,
            'event_id' => $eventId,
        ]);
    }

    public function invalidSignature(string $ip, string $error): void
    {
        $this->error('webhook.invalid_signature', [
            'ip' => $ip,
            'error' => $error,
        ]);
    }

    public function processingError(string $ip, string $error): void
    {
        $this->error('webhook.processing_error', [
            'ip' => $ip,
            'error' => $error,
        ]);
    }
}
