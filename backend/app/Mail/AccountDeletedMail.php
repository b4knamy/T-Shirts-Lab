<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firstName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your T-Shirts Lab account has been deleted');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.account-deleted');
    }
}
