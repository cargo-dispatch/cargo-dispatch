<?php

namespace App\Services\Integrations\Providers;

use App\Services\Geographic\GeoCalculator;
use App\Services\Integrations\BaseProvider;
use App\Services\Integrations\Contracts\MapsProviderInterface;
use Illuminate\Support\Facades\Http;

class MapsProvider extends BaseProvider implements MapsProviderInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key', '');
    }

    public function estimateRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        return $this->isReal()
            ? $this->realRoute($fromLat, $fromLng, $toLat, $toLng)
            : $this->mockRoute($fromLat, $fromLng, $toLat, $toLng);
    }

    private function realRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin'      => "{$fromLat},{$fromLng}",
            'destination' => "{$toLat},{$toLng}",
            'mode'        => 'driving',
            'avoid'       => 'tolls',
            'key'         => $this->apiKey,
        ])->throw()->json();

        $leg = $response['routes'][0]['legs'][0] ?? [];

        return [
            'distance_miles'   => round(($leg['distance']['value'] ?? 0) * 0.000621371, 1),
            'duration_minutes' => (int) round(($leg['duration']['value'] ?? 0) / 60),
            'polyline'         => $response['routes'][0]['overview_polyline']['points'] ?? null,
            '_source'          => 'google_maps',
        ];
    }

    private function mockRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $miles   = GeoCalculator::haversineMiles($fromLat, $fromLng, $toLat, $toLng);
        $minutes = $miles > 0
            ? (int) round(($miles / GeoCalculator::AVG_TRUCK_SPEED_MPH) * 60)
            : 0;

        return [
            'distance_miles'   => round($miles, 1),
            'duration_minutes' => $minutes,
            'polyline'         => null,
            '_source'          => 'mock',
        ];
    }
}
