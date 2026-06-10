<?php

namespace App\Mail;

use App\Models\Drivers\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $driverName;
    public string $companyName;

    public function __construct(
        public Driver $driver,
        public string $reason,
    ) {
        $this->driverName  = trim($driver->firstname . ' ' . $driver->lastname);
        $this->companyName = config('app.name', 'TruckDispatch');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update on Your {$this->companyName} Driver Application",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.driver-rejected');
    }
}
