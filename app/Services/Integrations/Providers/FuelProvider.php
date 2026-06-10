<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\FuelProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Fuel Provider — auto-switches between NREL AFDC API (real) and mock.
 *
 * Mock  → returns realistic fake fuel stations + prices, works out of the box.
 * Real  → set NREL_API_KEY in .env → US Dept of Energy AFDC API is used (FREE).
 *          Register free at: https://developer.nrel.gov/signup/
 */
class FuelProvider implements FuelProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://developer.nrel.gov/api/alt-fuel-stations/v1';

    // Average diesel prices per state (mock baseline, $/gallon)
    private array $statePrices = [
        'CA' => 4.89, 'NY' => 4.45, 'WA' => 4.62, 'OR' => 4.55,
        'TX' => 3.78, 'FL' => 3.95, 'GA' => 3.82, 'IL' => 4.12,
        'OH' => 3.99, 'PA' => 4.18, 'MI' => 4.05, 'NC' => 3.88,
        'TN' => 3.75, 'MO' => 3.71, 'CO' => 4.21, 'AZ' => 4.05,
        'NV' => 4.38, 'MN' => 4.02, 'IN' => 3.95, 'KY' => 3.72,
        'default' => 4.00,
    ];

    public function __construct()
    {
        $this->apiKey = config('services.nrel.key', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->apiKey);
    }

    // -------------------------------------------------------------------------
    // Nearby stations
    // -------------------------------------------------------------------------

    public function getNearbyStations(float $lat, float $lng, int $radiusMiles = 25): array
    {
        return $this->isReal()
            ? $this->realStations($lat, $lng, $radiusMiles)
            : $this->mockStations($lat, $lng, $radiusMiles);
    }

    private function realStations(float $lat, float $lng, int $radiusMiles): array
    {
        $response = Http::get("{$this->baseUrl}.json", [
            'api_key'         => $this->apiKey,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'radius'          => $radiusMiles,
            'fuel_type'       => 'DIESEL',
            'status'          => 'E',    // open
            'limit'           => 20,
        ])->throw()->json();

        return collect($response['fuel_stations'] ?? [])->map(fn ($s) => [
            'name'              => $s['station_name'],
            'address'           => "{$s['street_address']}, {$s['city']}, {$s['state']}",
            'lat'               => (float) $s['latitude'],
            'lng'               => (float) $s['longitude'],
            'price_per_gallon'  => null, // AFDC doesn't carry live prices
            'phone'             => $s['ev_level1_evse_num'] ?? null,
            '_source'           => 'nrel_afdc',
        ])->values()->all();
    }

    private function mockStations(float $lat, float $lng, int $radiusMiles): array
    {
        $chains  = ['Pilot Flying J', 'Love\'s Travel Stop', 'TA / Petro', 'Kwik Trip', 'Casey\'s'];
        $basePrice = $this->statePrices['default'];

        return array_map(function ($i) use ($lat, $lng, $chains, $basePrice) {
            $seed    = crc32("{$lat},{$lng},{$i}" . date('Y-m-d'));
            $offsetLat = ($seed % 500) / 10000.0;
            $offsetLng = (($seed * 3) % 500) / 10000.0;

            return [
                'name'             => $chains[abs($seed) % count($chains)],
                'address'          => abs($seed % 9999) . ' Highway ' . abs($seed % 80) . ', USA',
                'lat'              => round($lat + $offsetLat, 6),
                'lng'              => round($lng + $offsetLng, 6),
                'price_per_gallon' => round($basePrice + ($seed % 40) / 100, 2),
                'distance_miles'   => round(abs($seed % ($radiusMiles * 10)) / 10, 1),
                '_source'          => 'mock',
            ];
        }, range(1, 8));
    }

    // -------------------------------------------------------------------------
    // State diesel price
    // -------------------------------------------------------------------------

    public function getStateDieselPrice(string $stateCode): float
    {
        // Both mock and real return the same local price table
        // (live diesel price APIs are expensive; EIA has a free one below)
        // Real → set EIA_API_KEY if you want live prices from EIA.gov
        $eiaKey = config('services.eia.key', '');
        if (!empty($eiaKey)) {
            return $this->realDieselPrice($stateCode, $eiaKey);
        }

        return $this->statePrices[strtoupper($stateCode)] ?? $this->statePrices['default'];
    }

    private function realDieselPrice(string $stateCode, string $key): float
    {
        // EIA Open Data API v2 — weekly retail diesel by state
        // Free key: https://www.eia.gov/opendata/register.php
        try {
            $response = Http::get('https://api.eia.gov/v2/petroleum/pri/wfr/data/', [
                'api_key'   => $key,
                'frequency' => 'weekly',
                'data[0]'   => 'value',
                'facets[series][]' => "EMD_EPD2D_PTE_{$stateCode}_DPG",
                'sort[0][column]' => 'period',
                'sort[0][direction]' => 'desc',
                'length'    => 1,
            ])->json();

            return (float) ($response['response']['data'][0]['value'] ?? $this->statePrices['default']);
        } catch (\Throwable) {
            return $this->statePrices[strtoupper($stateCode)] ?? $this->statePrices['default'];
        }
    }
}
