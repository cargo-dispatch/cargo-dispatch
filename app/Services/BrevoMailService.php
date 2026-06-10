<?php

namespace App\Services;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class BrevoMailService
{
    protected $api;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            config('services.brevo.key')
        );
        
        $this->api = new TransactionalEmailsApi(
            new Client(),
            $config
        );
    }

    public function send($to, $subject, $htmlContent, $textContent = null, $fromName = null, $fromEmail = null)
    {
        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => [
                'name' => $fromName ?? config('mail.from.name'),
                'email' => $fromEmail ?? config('mail.from.address')
            ],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent ?? strip_tags($htmlContent)
        ]);

        try {
            $result = $this->api->sendTransacEmail($sendSmtpEmail);
            logger()->info('Email sent successfully via Brevo API', ['to' => $to]);
            return $result;
        } catch (\Exception $e) {
            logger()->error('Brevo API error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendWithAttachment($to, $subject, $htmlContent, $attachments = [])
    {
        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address')
            ],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'attachment' => $attachments
        ]);

        try {
            return $this->api->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            logger()->error('Brevo API error: ' . $e->getMessage());
            throw $e;
        }
    }
}