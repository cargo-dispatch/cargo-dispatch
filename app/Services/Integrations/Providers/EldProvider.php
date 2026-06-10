<?php

namespace App\Services\Integrations\Providers;

use App\Models\Drivers\Driver;
use App\Models\Vehicles\Vehicle;
use App\Services\Geographic\GeoCalculator;
use App\Services\Integrations\BaseProvider;
use App\Services\Integrations\Contracts\EldProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * ELD Provider — auto-switches between Samsara (real) and built-in mock.
 *
 * Mock  → no env key needed, works out of the box.
 * Real  → set SAMSARA_API_KEY in .env → real Samsara API is used automatically.
 */
class EldProvider extends BaseProvider implements EldProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.samsara.com';

    public function __construct()
    {
        $this->apiKey = config('services.samsara.key', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->apiKey);
    }

    // -------------------------------------------------------------------------
    // Driver statuses
    // -------------------------------------------------------------------------

    public function getDriverStatuses(): Collection
    {
        return $this->isReal()
            ? $this->realDriverStatuses()
            : $this->mockDriverStatuses();
    }

    private function realDriverStatuses(): Collection
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/fleet/drivers", ['limit' => 500])
            ->throw()
            ->json();

        return collect($response['data'] ?? [])->map(function (array $d) {
            return collect([
                'driver_id'      => $d['id'],
                'name'           => $d['name'],
                'current_status' => $d['eldSettings']['rulesetName'] ?? 'unknown',
                'hos'            => [
                    'drive_remaining_minutes'   => ($d['hosDriveRemainingMs'] ?? 0) / 60000,
                    'on_duty_remaining_minutes' => ($d['hosOnDutyRemainingMs'] ?? 0) / 60000,
                    'cycle_remaining_minutes'   => ($d['hosCycleRemainingMs'] ?? 0) / 60000,
                ],
                'location'       => [
                    'lat' => $d['currentVehicle']['location']['latitude'] ?? 0,
                    'lng' => $d['currentVehicle']['location']['longitude'] ?? 0,
                ],
                'last_update_utc' => $d['currentVehicle']['location']['time'] ?? null,
            ]);
        });
    }

    private function mockDriverStatuses(): Collection
    {
        $drivers = Driver::query()->get();
        $now     = now();

        return $drivers->map(function (Driver $driver) use ($now) {
            $seed = crc32((string) $driver->id . $now->format('Y-m-d-H'));
            $rand = static function (int $min, int $max) use (&$seed) {
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                return $min + ($seed % max(1, $max - $min));
            };

            $statuses    = ['off_duty', 'sleeper', 'driving', 'on_duty_not_driving'];
            $status      = $statuses[$rand(0, count($statuses)) % count($statuses)];
            $driveRemain = $rand(60, 660);
            $onRemain    = $rand(120, 840);
            $cycleRemain = $rand(600, 4200);

            $driver->fill([
                'current_duty_status'           => $status,
                'hos_drive_remaining_minutes'   => $driveRemain,
                'hos_on_duty_remaining_minutes' => $onRemain,
                'hos_cycle_remaining_minutes'   => $cycleRemain,
            ]);
            $driver->timestamps = false;
            $driver->saveQuietly();

            return collect([
                'driver_id'      => $driver->id,
                'name'           => trim(($driver->firstname ?? '') . ' ' . ($driver->lastname ?? '')),
                'current_status' => $status,
                'hos'            => [
                    'drive_remaining_minutes'   => $driveRemain,
                    'on_duty_remaining_minutes' => $onRemain,
                    'cycle_remaining_minutes'   => $cycleRemain,
                ],
                'location' => [
                    'lat' => (float) ($driver->current_latitude ?? 0),
                    'lng' => (float) ($driver->current_longitude ?? 0),
                ],
                'last_update_utc' => optional(
                    $driver->last_location_update instanceof Carbon
                        ? $driver->last_location_update
                        : ($driver->last_location_update ? Carbon::parse($driver->last_location_update) : null)
                )->toISOString(),
                '_source' => 'mock',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Truck locations
    // -------------------------------------------------------------------------

    public function getTruckLocations(): Collection
    {
        return $this->isReal()
            ? $this->realTruckLocations()
            : $this->mockTruckLocations();
    }

    private function realTruckLocations(): Collection
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/fleet/vehicles/stats", [
                'types' => 'gps,engineStates',
                'limit' => 500,
            ])
            ->throw()
            ->json();

        return collect($response['data'] ?? [])->map(function (array $v) {
            $gps = $v['gps'] ?? [];
            return collect([
                'vehicle_id'     => $v['id'],
                'unit_number'    => $v['name'],
                'lat'            => $gps['latitude'] ?? 0,
                'lng'            => $gps['longitude'] ?? 0,
                'speed_mph'      => $gps['speedMilesPerHour'] ?? 0,
                'heading_deg'    => $gps['headingDegrees'] ?? 0,
                'last_update_utc'=> $gps['time'] ?? null,
                '_source'        => 'samsara',
            ]);
        });
    }

    private function mockTruckLocations(): Collection
    {
        $vehicles = Vehicle::query()->get();
        $now      = now();

        return $vehicles->map(function (Vehicle $vehicle) use ($now) {
            if (blank($vehicle->current_latitude) || blank($vehicle->current_longitude)) {
                [$lat, $lng] = $this->coordsFromGeo($vehicle->geo_coordinates ?? '0,0');
                $vehicle->current_latitude     = $lat;
                $vehicle->current_longitude    = $lng;
                $vehicle->target_latitude      = $lat + $this->smallOffset();
                $vehicle->target_longitude     = $lng + $this->smallOffset();
                $vehicle->current_speed_mph    = GeoCalculator::AVG_TRUCK_SPEED_MPH;
                $vehicle->current_heading_deg  = 90;
                $vehicle->last_location_update = $now;
            }

            $this->advanceTowardsTarget($vehicle, $now);
            $vehicle->timestamps = false;
            $vehicle->saveQuietly();

            return collect([
                'vehicle_id'      => $vehicle->id,
                'unit_number'     => $vehicle->vehicle_id ?? $vehicle->license_plate_number,
                'lat'             => (float) $vehicle->current_latitude,
                'lng'             => (float) $vehicle->current_longitude,
                'speed_mph'       => (float) ($vehicle->current_speed_mph ?? 0),
                'heading_deg'     => (float) ($vehicle->current_heading_deg ?? 0),
                'last_update_utc' => optional(
                    $vehicle->last_location_update instanceof Carbon
                        ? $vehicle->last_location_update
                        : ($vehicle->last_location_update ? Carbon::parse($vehicle->last_location_update) : null)
                )->toISOString(),
                '_source' => 'mock',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers (mock movement)
    // -------------------------------------------------------------------------

    private function coordsFromGeo(string $geo): array
    {
        if (str_contains($geo, ',')) {
            [$lat, $lng] = array_map('floatval', explode(',', $geo));
            return [$lat, $lng];
        }
        return [GeoCalculator::US_CENTER_LAT, GeoCalculator::US_CENTER_LNG];
    }

    private function smallOffset(): float
    {
        return mt_rand(-1000, 1000) / 100000.0;
    }

    private function advanceTowardsTarget(Vehicle $vehicle, Carbon $now): void
    {
        $last = $vehicle->last_location_update
            ? ($vehicle->last_location_update instanceof Carbon
                ? $vehicle->last_location_update
                : Carbon::parse($vehicle->last_location_update))
            : $now->copy()->subMinutes(1);

        $seconds      = max(5, $last->diffInSeconds($now));
        $speedMph     = $vehicle->current_speed_mph ?? 55;
        $distMiles    = ($speedMph * $seconds) / 3600.0;
        $lat1         = deg2rad((float) $vehicle->current_latitude);
        $lng1         = deg2rad((float) $vehicle->current_longitude);
        $targetLat    = (float) ($vehicle->target_latitude ?? $vehicle->current_latitude);
        $targetLng    = (float) ($vehicle->target_longitude ?? $vehicle->current_longitude);

        $bearing = rad2deg(atan2(
            sin(deg2rad($targetLng) - $lng1) * cos(deg2rad($targetLat)),
            cos($lat1) * sin(deg2rad($targetLat)) - sin($lat1) * cos(deg2rad($targetLat)) * cos(deg2rad($targetLng) - $lng1)
        ));
        $heading = fmod(($bearing + 360), 360.0);
        $vehicle->current_heading_deg = $heading;

        $R   = 3958.8; // Earth radius in miles — needed for spherical dead-reckoning, not a haversine call
        $ang = $distMiles / $R;
        $lat2 = asin(sin($lat1) * cos($ang) + cos($lat1) * sin($ang) * cos(deg2rad($heading)));
        $lng2 = $lng1 + atan2(
            sin(deg2rad($heading)) * sin($ang) * cos($lat1),
            cos($ang) - sin($lat1) * sin($lat2)
        );

        $vehicle->current_latitude   = rad2deg($lat2);
        $vehicle->current_longitude  = rad2deg($lng2);
        $vehicle->last_location_update = $now;

        $rem = GeoCalculator::haversineMiles(
            $vehicle->current_latitude, $vehicle->current_longitude, $targetLat, $targetLng
        );
        if ($rem < 2.0) {
            $vehicle->target_latitude  = $vehicle->current_latitude + $this->smallOffset() * 20;
            $vehicle->target_longitude = $vehicle->current_longitude + $this->smallOffset() * 20;
        }
    }

}
