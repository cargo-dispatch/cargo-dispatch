<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;

class BrevoTransport implements TransportInterface
{
    protected $apiKey;
    protected $apiUrl = 'https://api.brevo.com/v3/smtp/email';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $email = MessageConverter::toEmail($message);
        
        $from = $email->getFrom();
        $to = $email->getTo();
        
        $data = [
            'sender' => [
                'name' => $from[0]->getName() ?? '',
                'email' => $from[0]->getAddress()
            ],
            'to' => array_map(function($address) {
                return ['email' => $address->getAddress()];
            }, $to),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody() ?? '',
            'textContent' => $email->getTextBody() ?? ''
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json'
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Brevo cURL error: ' . $error);
        }

        if ($httpCode !== 201) {
            $errorMsg = json_decode($response, true)['message'] ?? $response;
            throw new \Exception('Brevo API error: ' . $errorMsg);
        }

        return new SentMessage($message, $envelope ?? Envelope::create($message));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}