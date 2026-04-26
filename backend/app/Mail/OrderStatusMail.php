<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $statusLabel;

    public readonly string $headline;

    public readonly string $summary;

    public readonly ?string $orderUrl;

    public function __construct(
        public readonly string $firstName,
        public readonly string $orderId,
        public readonly string $orderNumber,
        public readonly float $total,
        public readonly string $currentStatus,
        public readonly ?string $previousStatus = null,
        public readonly ?string $adminNotes = null,
    ) {
        $this->statusLabel = $this->humanizeStatus($currentStatus);
        $this->headline = $this->headlineForStatus($currentStatus);
        $this->summary = $this->summaryForStatus($currentStatus);

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->orderUrl = $frontendUrl !== '' ? "{$frontendUrl}/orders/{$orderId}" : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectForStatus($this->currentStatus));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.status-update');
    }

    private function subjectForStatus(string $status): string
    {
        return match ($status) {
            'PENDING' => "Order received: {$this->orderNumber}",
            'CONFIRMED' => "Order confirmed: {$this->orderNumber}",
            'PROCESSING' => "Order in preparation: {$this->orderNumber}",
            'SHIPPED' => "Order shipped: {$this->orderNumber}",
            'DELIVERED' => "Order delivered: {$this->orderNumber}",
            'CANCELLED' => "Order cancelled: {$this->orderNumber}",
            'REFUNDED' => "Order refunded: {$this->orderNumber}",
            default => "Order update: {$this->orderNumber}",
        };
    }

    private function headlineForStatus(string $status): string
    {
        return match ($status) {
            'PENDING' => 'Your order has been placed',
            'CONFIRMED' => 'Your payment was approved',
            'PROCESSING' => 'Your order is being prepared',
            'SHIPPED' => 'Your order is on the way',
            'DELIVERED' => 'Your order was delivered',
            'CANCELLED' => 'Your order was cancelled',
            'REFUNDED' => 'Your order was refunded',
            default => 'Your order has a new update',
        };
    }

    private function summaryForStatus(string $status): string
    {
        return match ($status) {
            'PENDING' => 'We received your order and we are waiting for payment confirmation.',
            'CONFIRMED' => 'Payment confirmed. We will start preparing your order now.',
            'PROCESSING' => 'Our team is preparing your items for shipment.',
            'SHIPPED' => 'Good news, your package has been shipped and is on the way.',
            'DELIVERED' => 'Your order was delivered. We hope you enjoy your new pieces.',
            'CANCELLED' => 'This order has been cancelled. If this was unexpected, contact support.',
            'REFUNDED' => 'The refund process has been completed for this order.',
            default => 'There was an update on your order status.',
        };
    }

    private function humanizeStatus(string $status): string
    {
        return ucfirst(strtolower($status));
    }
}
