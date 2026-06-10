<?php

namespace App\Services\Integrations\Contracts;

interface MapsProviderInterface
{
    /**
     * Basic distance / duration estimate between two coordinates.
     *
     * @param  float  $fromLat
     * @param  float  $fromLng
     * @param  float  $toLat
     * @param  float  $toLng
     * @return array<string, mixed>  e.g. ['distance_miles' => 123.4, 'duration_minutes' => 210]
     */
    public function estimateRoute(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): array;
}

