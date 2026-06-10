<?php

namespace App\Mail;

use App\Models\Drivers\DriverInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $registrationUrl;
    public string $driverName;
    public string $companyName;
    public string $expiresIn;

    public function __construct(public DriverInvitation $invitation)
    {
        $this->registrationUrl = route('driver.register', $invitation->token);
        $this->driverName      = trim($invitation->firstname . ' ' . $invitation->lastname) ?: 'Driver';
        $this->companyName     = config('app.name', 'TruckDispatch');
        $this->expiresIn       = $invitation->expires_at->diffForHumans();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->companyName} — Complete Your Driver Profile",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.driver-invitation');
    }
}
