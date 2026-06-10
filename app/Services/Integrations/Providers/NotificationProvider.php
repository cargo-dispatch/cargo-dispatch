<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\NotificationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notification Provider — auto-switches between Twilio (real) and Laravel log (mock).
 *
 * Mock  → logs all messages to Laravel log (laravel.log), no SMS sent.
 * Real  → set TWILIO_SID + TWILIO_TOKEN + TWILIO_FROM in .env → real SMS is sent.
 */
class NotificationProvider implements NotificationProviderInterface
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct()
    {
        $this->sid   = config('services.twilio.sid', '');
        $this->token = config('services.twilio.token', '');
        $this->from  = config('services.twilio.from', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->sid) && !empty($this->token) && !empty($this->from);
    }

    // -------------------------------------------------------------------------
    // Single SMS
    // -------------------------------------------------------------------------

    public function sendSms(string $toPhone, string $message): array
    {
        return $this->isReal()
            ? $this->realSms($toPhone, $message)
            : $this->mockSms($toPhone, $message);
    }

    private function realSms(string $toPhone, string $message): array
    {
        $response = Http::withBasicAuth($this->sid, $this->token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                'To'   => $toPhone,
                'From' => $this->from,
                'Body' => $message,
            ])
            ->throw()
            ->json();

        return [
            'success'    => ($response['status'] ?? '') !== 'failed',
            'message_id' => $response['sid'] ?? null,
            'status'     => $response['status'] ?? 'sent',
            'error'      => $response['error_message'] ?? null,
            '_source'    => 'twilio',
        ];
    }

    private function mockSms(string $toPhone, string $message): array
    {
        $msgId = 'MOCK-' . strtoupper(substr(md5($toPhone . $message . microtime()), 0, 12));
        Log::channel('daily')->info("[SMS MOCK] To: {$toPhone} | Msg: {$message} | ID: {$msgId}");

        return [
            'success'    => true,
            'message_id' => $msgId,
            'status'     => 'queued',
            'error'      => null,
            '_source'    => 'mock',
        ];
    }

    // -------------------------------------------------------------------------
    // Bulk SMS
    // -------------------------------------------------------------------------

    public function sendBulk(array $phoneNumbers, string $message): array
    {
        return array_map(
            fn (string $phone) => array_merge(['phone' => $phone], $this->sendSms($phone, $message)),
            $phoneNumbers
        );
    }
}
