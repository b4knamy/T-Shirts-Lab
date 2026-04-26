<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firstName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to T-Shirts Lab');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.welcome-account');
    }
}
