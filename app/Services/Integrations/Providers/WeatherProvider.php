<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\WeatherProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * Weather Provider — auto-switches between Tomorrow.io (real) and mock.
 *
 * Mock  → returns realistic fake weather data, works out of the box.
 * Real  → set TOMORROW_IO_KEY in .env → Tomorrow.io API is used automatically.
 */
class WeatherProvider implements WeatherProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.tomorrow.io/v4';

    public function __construct()
    {
        $this->apiKey = config('services.tomorrow_io.key', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->apiKey);
    }

    // -------------------------------------------------------------------------
    // Route weather
    // -------------------------------------------------------------------------

    public function getRouteWeather(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        return $this->isReal()
            ? $this->realRouteWeather($lat1, $lng1, $lat2, $lng2)
            : $this->mockRouteWeather($lat1, $lng1, $lat2, $lng2);
    }

    private function realRouteWeather(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        // Sample 3 points: origin, midpoint, destination
        $midLat = ($lat1 + $lat2) / 2;
        $midLng = ($lng1 + $lng2) / 2;
        $points = [[$lat1, $lng1], [$midLat, $midLng], [$lat2, $lng2]];

        $waypoints = [];
        foreach ($points as $idx => [$lat, $lng]) {
            $response = Http::get("{$this->baseUrl}/weather/realtime", [
                'location' => "{$lat},{$lng}",
                'units'    => 'imperial',
                'apikey'   => $this->apiKey,
            ])->throw()->json();

            $values = $response['data']['values'] ?? [];
            $waypoints[] = [
                'point'              => $idx === 0 ? 'origin' : ($idx === 1 ? 'midpoint' : 'destination'),
                'lat'                => $lat,
                'lng'                => $lng,
                'temp_f'             => $values['temperature'] ?? null,
                'wind_mph'           => $values['windSpeed'] ?? null,
                'precipitation_in'   => $values['precipitationIntensity'] ?? null,
                'visibility_miles'   => $values['visibility'] ?? null,
                'weather_code'       => $values['weatherCode'] ?? null,
                'conditions'         => $this->codeToLabel($values['weatherCode'] ?? 0),
            ];
        }

        return ['waypoints' => $waypoints, '_source' => 'tomorrow_io'];
    }

    private function mockRouteWeather(float $lat1, float $lng1, float $lat2, float $lng2): array
    {
        $conditions = ['Clear', 'Partly Cloudy', 'Overcast', 'Rain', 'Thunderstorm', 'Fog', 'Windy'];
        $midLat = ($lat1 + $lat2) / 2;
        $midLng = ($lng1 + $lng2) / 2;

        $points = [
            ['point' => 'origin',      'lat' => $lat1,   'lng' => $lng1],
            ['point' => 'midpoint',    'lat' => $midLat, 'lng' => $midLng],
            ['point' => 'destination', 'lat' => $lat2,   'lng' => $lng2],
        ];

        $waypoints = array_map(function ($p) use ($conditions) {
            $seed = crc32("{$p['lat']},{$p['lng']}" . date('Y-m-d-H'));
            return array_merge($p, [
                'temp_f'           => 55 + abs($seed % 40),
                'wind_mph'         => 5 + abs($seed % 25),
                'precipitation_in' => round(abs($seed % 100) / 200, 2),
                'visibility_miles' => 10 - abs($seed % 5),
                'conditions'       => $conditions[abs($seed) % count($conditions)],
            ]);
        }, $points);

        return ['waypoints' => $waypoints, '_source' => 'mock'];
    }

    // -------------------------------------------------------------------------
    // Weather alerts
    // -------------------------------------------------------------------------

    public function getWeatherAlerts(string $stateCode): array
    {
        return $this->isReal()
            ? $this->realAlerts($stateCode)
            : $this->mockAlerts($stateCode);
    }

    private function realAlerts(string $stateCode): array
    {
        // Tomorrow.io doesn't have a state-level alert endpoint directly;
        // use NWS (National Weather Service) free API instead.
        $response = Http::withHeaders(['User-Agent' => 'TruckDispatch/1.0'])
            ->get("https://api.weather.gov/alerts/active", [
                'area'   => strtoupper($stateCode),
                'status' => 'actual',
            ])->throw()->json();

        $alerts = collect($response['features'] ?? [])->map(function ($f) {
            $props = $f['properties'];
            return [
                'id'          => $props['id'],
                'event'       => $props['event'],
                'headline'    => $props['headline'],
                'severity'    => $props['severity'],
                'urgency'     => $props['urgency'],
                'description' => $props['description'],
                'expires'     => $props['expires'],
            ];
        })->values()->all();

        return ['state' => $stateCode, 'alerts' => $alerts, '_source' => 'nws'];
    }

    private function mockAlerts(string $stateCode): array
    {
        $seed   = crc32($stateCode . date('Y-m-d'));
        $events = ['Winter Storm Warning', 'High Wind Advisory', 'Dense Fog Advisory', 'Flash Flood Watch'];
        $count  = abs($seed) % 3; // 0–2 alerts

        $alerts = [];
        for ($i = 0; $i < $count; $i++) {
            $alerts[] = [
                'id'          => "MOCK-{$stateCode}-{$i}",
                'event'       => $events[($seed + $i) % count($events)],
                'headline'    => "{$events[($seed + $i) % count($events)]} in effect until tomorrow.",
                'severity'    => 'Moderate',
                'urgency'     => 'Expected',
                'description' => 'Drivers should exercise caution. Reduced visibility and slippery roads possible.',
                'expires'     => now()->addDay()->toISOString(),
            ];
        }

        return ['state' => $stateCode, 'alerts' => $alerts, '_source' => 'mock'];
    }

    private function codeToLabel(int $code): string
    {
        return match (true) {
            $code === 1000              => 'Clear',
            in_array($code, [1100, 1101, 1102]) => 'Partly Cloudy',
            $code === 1001              => 'Cloudy',
            in_array($code, [4000, 4001, 4200, 4201]) => 'Rain',
            in_array($code, [5000, 5001, 5100, 5101]) => 'Snow',
            in_array($code, [8000])     => 'Thunderstorm',
            in_array($code, [2000, 2100]) => 'Fog',
            default                     => 'Unknown',
        };
    }
}
