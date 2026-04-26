<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductReviewAdminReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly ?string $productUrl;

    public function __construct(
        public readonly string $firstName,
        public readonly string $productName,
        public readonly int $rating,
        public readonly ?string $userComment,
        public readonly string $adminReply,
        public readonly ?string $productSlug = null,
    ) {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->productUrl = $frontendUrl !== '' && $productSlug
            ? "{$frontendUrl}/products/{$productSlug}"
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Reply to your review: {$this->productName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reviews.admin-reply');
    }
}
