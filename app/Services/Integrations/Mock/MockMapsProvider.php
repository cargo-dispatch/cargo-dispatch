<?php

namespace App\Services\Integrations\Mock;

use App\Services\Integrations\Contracts\MapsProviderInterface;

class MockMapsProvider implements MapsProviderInterface
{
    public function estimateRoute(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): array {
        $distanceMiles = $this->haversineMiles($fromLat, $fromLng, $toLat, $toLng);

        // Assume average speed 55 mph
        $durationMinutes = $distanceMiles > 0
            ? (int) round(($distanceMiles / 55) * 60)
            : 0;

        return [
            'distance_miles'   => round($distanceMiles, 1),
            'duration_minutes' => $durationMinutes,
        ];
    }

    private function haversineMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 3958.8; // Miles
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}

