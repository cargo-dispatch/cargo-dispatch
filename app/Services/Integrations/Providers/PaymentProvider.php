<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\PaymentProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Payment Provider — auto-switches between Stripe (real) and mock.
 *
 * Mock  → returns fake invoice IDs + payment URLs, works out of the box.
 * Real  → set STRIPE_SECRET_KEY in .env → Stripe API is used automatically.
 */
class PaymentProvider implements PaymentProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->apiKey);
    }

    // -------------------------------------------------------------------------
    // Create invoice
    // -------------------------------------------------------------------------

    public function createInvoice(array $data): array
    {
        return $this->isReal()
            ? $this->realCreateInvoice($data)
            : $this->mockCreateInvoice($data);
    }

    private function realCreateInvoice(array $data): array
    {
        // 1. Create a customer if no stripe_customer_id
        $customerId = $data['stripe_customer_id'] ?? null;
        if (!$customerId) {
            $customer = Http::withToken($this->apiKey)
                ->asForm()
                ->post("{$this->baseUrl}/customers", [
                    'name'  => $data['customer_name'] ?? 'Freight Customer',
                    'email' => $data['customer_email'] ?? null,
                ])->throw()->json();
            $customerId = $customer['id'];
        }

        // 2. Create invoice item
        Http::withToken($this->apiKey)
            ->asForm()
            ->post("{$this->baseUrl}/invoiceitems", [
                'customer'    => $customerId,
                'amount'      => (int) round(($data['amount_usd'] ?? 0) * 100), // cents
                'currency'    => 'usd',
                'description' => $data['description'] ?? 'Freight Invoice',
            ])->throw();

        // 3. Create & finalize invoice
        $invoice = Http::withToken($this->apiKey)
            ->asForm()
            ->post("{$this->baseUrl}/invoices", [
                'customer'         => $customerId,
                'auto_advance'     => true,
                'collection_method'=> 'send_invoice',
                'days_until_due'   => $data['days_until_due'] ?? 30,
            ])->throw()->json();

        // Finalize so a payment_intent is created
        $finalized = Http::withToken($this->apiKey)
            ->asForm()
            ->post("{$this->baseUrl}/invoices/{$invoice['id']}/finalize")
            ->throw()
            ->json();

        return [
            'invoice_id'  => $finalized['id'],
            'payment_url' => $finalized['hosted_invoice_url'] ?? null,
            'status'      => $finalized['status'],
            'amount_usd'  => ($finalized['amount_due'] ?? 0) / 100,
            '_source'     => 'stripe',
        ];
    }

    private function mockCreateInvoice(array $data): array
    {
        $invoiceId = 'INV-' . strtoupper(Str::random(10));

        return [
            'invoice_id'  => $invoiceId,
            'payment_url' => url("/mock/invoices/{$invoiceId}"),
            'status'      => 'open',
            'amount_usd'  => (float) ($data['amount_usd'] ?? 0),
            '_source'     => 'mock',
        ];
    }

    // -------------------------------------------------------------------------
    // Payment status
    // -------------------------------------------------------------------------

    public function getPaymentStatus(string $paymentId): array
    {
        return $this->isReal()
            ? $this->realPaymentStatus($paymentId)
            : $this->mockPaymentStatus($paymentId);
    }

    private function realPaymentStatus(string $paymentId): array
    {
        $invoice = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/invoices/{$paymentId}")
            ->throw()
            ->json();

        return [
            'status'      => $invoice['status'],
            'amount_paid' => ($invoice['amount_paid'] ?? 0) / 100,
            'paid_at'     => $invoice['status_transitions']['paid_at'] ?? null,
            '_source'     => 'stripe',
        ];
    }

    private function mockPaymentStatus(string $paymentId): array
    {
        $seed     = crc32($paymentId);
        $statuses = ['open', 'paid', 'open', 'open', 'void'];
        $status   = $statuses[abs($seed) % count($statuses)];

        return [
            'status'      => $status,
            'amount_paid' => $status === 'paid' ? 1250.00 : 0,
            'paid_at'     => $status === 'paid' ? now()->subDays(abs($seed) % 10)->toISOString() : null,
            '_source'     => 'mock',
        ];
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    public function refund(string $paymentId, float $amount): array
    {
        return $this->isReal()
            ? $this->realRefund($paymentId, $amount)
            : $this->mockRefund($paymentId, $amount);
    }

    private function realRefund(string $paymentId, float $amount): array
    {
        // Get the payment_intent from the invoice first
        $invoice = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/invoices/{$paymentId}")
            ->throw()
            ->json();

        $intentId = $invoice['payment_intent'] ?? null;
        if (!$intentId) {
            return ['success' => false, 'error' => 'No payment intent on invoice'];
        }

        $refund = Http::withToken($this->apiKey)
            ->asForm()
            ->post("{$this->baseUrl}/refunds", [
                'payment_intent' => $intentId,
                'amount'         => (int) round($amount * 100),
            ])->throw()->json();

        return [
            'success'    => $refund['status'] === 'succeeded',
            'refund_id'  => $refund['id'],
            'amount_usd' => ($refund['amount'] ?? 0) / 100,
            '_source'    => 'stripe',
        ];
    }

    private function mockRefund(string $paymentId, float $amount): array
    {
        return [
            'success'    => true,
            'refund_id'  => 'REF-' . strtoupper(Str::random(8)),
            'amount_usd' => $amount,
            '_source'    => 'mock',
        ];
    }
}
