<?php

namespace App\Services\Integrations\Contracts;

interface FuelProviderInterface
{
    /**
     * Find nearby diesel stations within a radius.
     * Returns name, address, lat, lng, price_per_gallon.
     */
    public function getNearbyStations(float $lat, float $lng, int $radiusMiles = 25): array;

    /**
     * Get average diesel price for a US state code (e.g. 'TX').
     */
    public function getStateDieselPrice(string $stateCode): float;
}
