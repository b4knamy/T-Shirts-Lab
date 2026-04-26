<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly ?string $checkoutUrl;

    public function __construct(
        public readonly string $firstName,
        public readonly string $orderNumber,
        public readonly float $total,
        public readonly ?string $failureReason = null,
        public readonly ?string $orderId = null,
    ) {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->checkoutUrl = $frontendUrl !== '' && $orderId
            ? "{$frontendUrl}/checkout?order_id={$orderId}"
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Payment failed: {$this->orderNumber}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.payment-failed');
    }
}
