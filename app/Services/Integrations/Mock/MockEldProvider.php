<?php

namespace App\Services\Integrations\Mock;

use App\Models\Vehicles\Vehicle;
use App\Models\Drivers\Driver;
use App\Services\Integrations\Contracts\EldProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockEldProvider implements EldProviderInterface
{
    /**
     * Simulated driver HOS and duty status based on existing drivers.
     */
    public function getDriverStatuses(): Collection
    {
        /** @var Collection<int, Driver> $drivers */
        $drivers = Driver::query()->get();

        $now = now();

        return $drivers->map(function (Driver $driver) use ($now) {
            // Generate deterministic pseudo‑random numbers so refreshes look natural
            $seed = crc32((string) $driver->id . $now->format('Y-m-d-H'));
            $rand = static function (int $min, int $max) use ($seed) {
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                return $min + ($seed % max(1, $max - $min));
            };

            $dutyStatuses = ['off_duty', 'sleeper', 'driving', 'on_duty_not_driving'];
            $statusIndex  = $rand(0, count($dutyStatuses));
            $status       = $dutyStatuses[$statusIndex % count($dutyStatuses)];

            $hosDriveRemaining   = $rand(60, 660);  // 1–11 hours
            $hosOnDutyRemaining  = $rand(120, 840); // 2–14 hours
            $hosCycleRemaining   = $rand(600, 4200); // 10–70 hours

            // Optionally update DB snapshot (non‑critical if it fails)
            $driver->fill([
                'current_duty_status'           => $status,
                'hos_drive_remaining_minutes'   => $hosDriveRemaining,
                'hos_on_duty_remaining_minutes' => $hosOnDutyRemaining,
                'hos_cycle_remaining_minutes'   => $hosCycleRemaining,
            ]);

            // Avoid mass‑updating timestamps every poll
            $driver->timestamps = false;
            $driver->saveQuietly();

            return collect([
                'driver_id'      => $driver->id,
                'name'           => trim(($driver->firstname ?? '') . ' ' . ($driver->lastname ?? '')),
                'current_status' => $status,
                'hos'            => [
                    'drive_remaining_minutes'   => $hosDriveRemaining,
                    'on_duty_remaining_minutes' => $hosOnDutyRemaining,
                    'cycle_remaining_minutes'   => $hosCycleRemaining,
                ],
                'location'       => [
                    'lat' => (float) ($driver->current_latitude ?? 0),
                    'lng' => (float) ($driver->current_longitude ?? 0),
                ],
                'last_update_utc' => optional(
                    $driver->last_location_update instanceof Carbon
                        ? $driver->last_location_update
                        : ($driver->last_location_update ? Carbon::parse($driver->last_location_update) : null)
                )->toISOString(),
            ]);
        });
    }

    /**
     * Simulated truck locations that move smoothly between targets.
     */
    public function getTruckLocations(): Collection
    {
        /** @var Collection<int, Vehicle> $vehicles */
        $vehicles = Vehicle::query()->get();

        $now = now();

        return DB::transaction(function () use ($vehicles, $now) {
            return $vehicles->map(function (Vehicle $vehicle) use ($now) {
                // Initialise starting point if missing
                if (blank($vehicle->current_latitude) || blank($vehicle->current_longitude)) {
                    [$lat, $lng] = $this->initialCoordinatesFromGeo($vehicle->geo_coordinates ?? '0,0');
                    $vehicle->current_latitude  = $lat;
                    $vehicle->current_longitude = $lng;
                    $vehicle->target_latitude   = $lat + $this->randomSmallOffset();
                    $vehicle->target_longitude  = $lng + $this->randomSmallOffset();
                    $vehicle->current_speed_mph = 55;
                    $vehicle->current_heading_deg = 90;
                    $vehicle->last_location_update = $now;
                }

                $this->advanceVehicleTowardsTarget($vehicle, $now);

                $vehicle->timestamps = false;
                $vehicle->saveQuietly();

                return collect([
                    'vehicle_id' => $vehicle->id,
                    'unit_number' => $vehicle->vehicle_id ?? $vehicle->license_plate_number,
                    'lat' => (float) $vehicle->current_latitude,
                    'lng' => (float) $vehicle->current_longitude,
                    'speed_mph' => (float) ($vehicle->current_speed_mph ?? 0),
                    'heading_deg' => (float) ($vehicle->current_heading_deg ?? 0),
                    'last_update_utc' => optional(
                        $vehicle->last_location_update instanceof Carbon
                            ? $vehicle->last_location_update
                            : ($vehicle->last_location_update ? Carbon::parse($vehicle->last_location_update) : null)
                    )->toISOString(),
                ]);
            });
        });
    }

    private function initialCoordinatesFromGeo(string $geo): array
    {
        if (Str::contains($geo, ',')) {
            [$lat, $lng] = array_map('floatval', explode(',', $geo));
            return [$lat, $lng];
        }

        // Default roughly to continental US center
        return [39.8283, -98.5795];
    }

    private function randomSmallOffset(): float
    {
        return mt_rand(-1000, 1000) / 100000.0;
    }

    private function advanceVehicleTowardsTarget(Vehicle $vehicle, $now): void
    {
        $lastUpdate = $vehicle->last_location_update
            ? ( $vehicle->last_location_update instanceof Carbon
                ? $vehicle->last_location_update
                : Carbon::parse($vehicle->last_location_update)
            )
            : $now->copy()->subMinutes(1);
        $seconds    = max(5, $lastUpdate->diffInSeconds($now));

        $speedMph = $vehicle->current_speed_mph ?? 55;
        $distanceMiles = ($speedMph * $seconds) / 3600.0;

        $lat1 = deg2rad((float) $vehicle->current_latitude);
        $lng1 = deg2rad((float) $vehicle->current_longitude);

        $targetLat = (float) ($vehicle->target_latitude ?? $vehicle->current_latitude);
        $targetLng = (float) ($vehicle->target_longitude ?? $vehicle->current_longitude);

        $bearing = rad2deg(atan2(
            sin(deg2rad($targetLng) - $lng1) * cos(deg2rad($targetLat)),
            cos($lat1) * sin(deg2rad($targetLat)) - sin($lat1) * cos(deg2rad($targetLat)) * cos(deg2rad($targetLng) - $lng1)
        ));

        $heading = fmod(($bearing + 360), 360.0);
        $vehicle->current_heading_deg = $heading;

        // Earth radius in miles
        $R = 3958.8;
        $angularDistance = $distanceMiles / $R;

        $lat2 = asin(
            sin($lat1) * cos($angularDistance) +
            cos($lat1) * sin($angularDistance) * cos(deg2rad($heading))
        );

        $lng2 = $lng1 + atan2(
            sin(deg2rad($heading)) * sin($angularDistance) * cos($lat1),
            cos($angularDistance) - sin($lat1) * sin($lat2)
        );

        $vehicle->current_latitude  = rad2deg($lat2);
        $vehicle->current_longitude = rad2deg($lng2);
        $vehicle->last_location_update = $now;

        // If very close to target, choose a new destination
        $remainingMiles = $this->haversineMiles(
            $vehicle->current_latitude,
            $vehicle->current_longitude,
            $targetLat,
            $targetLng
        );

        if ($remainingMiles < 2.0) {
            $vehicle->target_latitude  = $vehicle->current_latitude + $this->randomSmallOffset() * 20;
            $vehicle->target_longitude = $vehicle->current_longitude + $this->randomSmallOffset() * 20;
        }
    }

    private function haversineMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 3958.8;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}

