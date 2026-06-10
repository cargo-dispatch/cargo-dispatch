<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $email,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Cargo Dispatch — Your Account Details');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.customer-welcome');
    }
}
