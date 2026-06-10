<?php

namespace App\Mail\Transport;

use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class BrevoTransportFactory
{
    public function __invoke(array $config)
    {
        return new BrevoTransport($config['key']);
    }
}