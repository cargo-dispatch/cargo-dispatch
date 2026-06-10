<?php

namespace App\Services\Integrations\Contracts;

interface NotificationProviderInterface
{
    /**
     * Send an SMS to a single phone number.
     * Returns ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function sendSms(string $toPhone, string $message): array;

    /**
     * Send the same SMS to multiple phone numbers.
     * Returns array of per-recipient results.
     */
    public function sendBulk(array $phoneNumbers, string $message): array;
}
