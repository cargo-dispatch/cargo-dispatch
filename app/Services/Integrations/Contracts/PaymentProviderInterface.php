<?php

namespace App\Services\Integrations\Contracts;

interface PaymentProviderInterface
{
    /**
     * Create a freight invoice / payment request.
     * Returns ['invoice_id' => string, 'payment_url' => string, 'status' => string]
     */
    public function createInvoice(array $data): array;

    /**
     * Get the current status of a payment.
     * Returns ['status' => string, 'amount_paid' => float, 'paid_at' => string|null]
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Issue a full or partial refund on a payment.
     */
    public function refund(string $paymentId, float $amount): array;
}
