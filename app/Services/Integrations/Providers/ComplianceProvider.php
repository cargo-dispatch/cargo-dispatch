<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\ComplianceProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Compliance Provider — auto-switches between FMCSA API (real) and mock.
 *
 * Mock  → returns realistic fake carrier safety data, works out of the box.
 * Real  → set FMCSA_API_KEY in .env → FMCSA public API is used (FREE).
 *          Register free at: https://mobile.fmcsa.dot.gov/developer/home.page
 */
class ComplianceProvider implements ComplianceProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://mobile.fmcsa.dot.gov/qc/services';

    public function __construct()
    {
        $this->apiKey = config('services.fmcsa.key', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->apiKey);
    }

    // -------------------------------------------------------------------------
    // Carrier info
    // -------------------------------------------------------------------------

    public function getCarrierInfo(string $dotNumber): array
    {
        return $this->isReal()
            ? $this->realCarrierInfo($dotNumber)
            : $this->mockCarrierInfo($dotNumber);
    }

    private function realCarrierInfo(string $dotNumber): array
    {
        $response = Http::get("{$this->baseUrl}/carriers/{$dotNumber}", [
            'webKey' => $this->apiKey,
        ])->throw()->json();

        $c = $response['content']['carrier'] ?? [];

        return [
            'dot_number'          => $dotNumber,
            'legal_name'          => $c['legalName'] ?? 'Unknown',
            'dba_name'            => $c['dbaName'] ?? null,
            'safety_rating'       => $c['safetyRating'] ?? 'Unrated',
            'safety_rating_date'  => $c['safetyRatingDate'] ?? null,
            'out_of_service_pct'  => (float) ($c['oosRate'] ?? 0),
            'total_inspections'   => (int) ($c['totalInspections'] ?? 0),
            'total_crashes'       => (int) ($c['totalCrashes'] ?? 0),
            'total_drivers'       => (int) ($c['totalDrivers'] ?? 0),
            'total_power_units'   => (int) ($c['totalPowerUnits'] ?? 0),
            'state'               => $c['phyState'] ?? null,
            '_source'             => 'fmcsa',
        ];
    }

    private function mockCarrierInfo(string $dotNumber): array
    {
        $seed    = crc32($dotNumber);
        $ratings = ['Satisfactory', 'Conditional', 'Unrated', 'Satisfactory', 'Satisfactory'];

        return [
            'dot_number'         => $dotNumber,
            'legal_name'         => 'Mock Carrier LLC ' . strtoupper(substr(md5($dotNumber), 0, 4)),
            'dba_name'           => null,
            'safety_rating'      => $ratings[abs($seed) % count($ratings)],
            'safety_rating_date' => now()->subMonths(abs($seed) % 24)->format('Y-m-d'),
            'out_of_service_pct' => round(abs($seed % 800) / 100, 1),
            'total_inspections'  => 100 + abs($seed % 900),
            'total_crashes'      => abs($seed % 12),
            'total_drivers'      => 10 + abs($seed % 90),
            'total_power_units'  => 8 + abs($seed % 80),
            'state'              => ['TX', 'FL', 'CA', 'IL', 'OH'][abs($seed) % 5],
            '_source'            => 'mock',
        ];
    }

    // -------------------------------------------------------------------------
    // Authority check
    // -------------------------------------------------------------------------

    public function checkAuthority(string $dotNumber): array
    {
        return $this->isReal()
            ? $this->realAuthority($dotNumber)
            : $this->mockAuthority($dotNumber);
    }

    private function realAuthority(string $dotNumber): array
    {
        $response = Http::get("{$this->baseUrl}/carriers/{$dotNumber}/authority", [
            'webKey' => $this->apiKey,
        ])->throw()->json();

        $a = $response['content']['Items'][0] ?? [];

        return [
            'dot_number'         => $dotNumber,
            'active'             => ($a['commonAuthorityStatus'] ?? '') === 'A',
            'authority_type'     => $a['authorityType'] ?? 'Unknown',
            'insurance_on_file'  => !empty($a['bipdInsuranceRequired']),
            'revocation_reason'  => $a['revocationReason'] ?? null,
            '_source'            => 'fmcsa',
        ];
    }

    private function mockAuthority(string $dotNumber): array
    {
        $seed = crc32($dotNumber . 'auth');

        return [
            'dot_number'        => $dotNumber,
            'active'            => (abs($seed) % 10) > 1, // 80% active
            'authority_type'    => (abs($seed) % 3 === 0) ? 'Contract' : 'Common',
            'insurance_on_file' => (abs($seed) % 5) > 0,  // 80% has insurance
            'revocation_reason' => null,
            '_source'           => 'mock',
        ];
    }
}
