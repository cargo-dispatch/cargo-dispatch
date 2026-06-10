<?php

namespace App\Mail;

use App\Models\Drivers\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $driverName;
    public string $loginUrl;
    public string $companyName;

    public function __construct(
        public Driver $driver,
        public string $plainPassword,
    ) {
        $this->driverName  = trim($driver->firstname . ' ' . $driver->lastname);
        $this->loginUrl    = config('app.url') . '/driver-app'; // React portal URL
        $this->companyName = config('app.name', 'TruckDispatch');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to {$this->companyName} — Your Account is Approved!",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.driver-approved');
    }
}
