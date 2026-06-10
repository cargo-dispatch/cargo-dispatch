<?php

namespace App\Services\Integrations\Mock;

use App\Models\Customers\Customer;
use App\Models\Shipments\Shipment;
use App\Models\VehicleType\VehicleType;
use App\Services\Integrations\Contracts\LoadBoardProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockLoadBoardProvider implements LoadBoardProviderInterface
{
    private array $lanes = [
        ['pickup' => 'New York, NY',      'drop' => 'Philadelphia, PA',   'region' => 'Northeast', 'miles' => 95],
        ['pickup' => 'Boston, MA',         'drop' => 'New York, NY',       'region' => 'Northeast', 'miles' => 215],
        ['pickup' => 'Chicago, IL',        'drop' => 'Detroit, MI',        'region' => 'Midwest',   'miles' => 280],
        ['pickup' => 'Dallas, TX',         'drop' => 'Houston, TX',        'region' => 'South',     'miles' => 240],
        ['pickup' => 'Los Angeles, CA',    'drop' => 'San Francisco, CA',  'region' => 'West',      'miles' => 380],
        ['pickup' => 'Atlanta, GA',        'drop' => 'Miami, FL',          'region' => 'Southeast', 'miles' => 660],
        ['pickup' => 'Seattle, WA',        'drop' => 'Portland, OR',       'region' => 'Northwest', 'miles' => 175],
        ['pickup' => 'Denver, CO',         'drop' => 'Salt Lake City, UT', 'region' => 'Mountain',  'miles' => 525],
        ['pickup' => 'Phoenix, AZ',        'drop' => 'Las Vegas, NV',      'region' => 'Southwest', 'miles' => 290],
        ['pickup' => 'Minneapolis, MN',    'drop' => 'Chicago, IL',        'region' => 'Midwest',   'miles' => 410],
        ['pickup' => 'Nashville, TN',      'drop' => 'Atlanta, GA',        'region' => 'Southeast', 'miles' => 250],
        ['pickup' => 'Kansas City, MO',    'drop' => 'St. Louis, MO',      'region' => 'Midwest',   'miles' => 250],
        ['pickup' => 'Charlotte, NC',      'drop' => 'Washington, DC',     'region' => 'Southeast', 'miles' => 390],
        ['pickup' => 'San Antonio, TX',    'drop' => 'Dallas, TX',         'region' => 'South',     'miles' => 275],
        ['pickup' => 'Columbus, OH',       'drop' => 'Pittsburgh, PA',     'region' => 'Midwest',   'miles' => 185],
        ['pickup' => 'Indianapolis, IN',   'drop' => 'Cincinnati, OH',     'region' => 'Midwest',   'miles' => 110],
        ['pickup' => 'Memphis, TN',        'drop' => 'New Orleans, LA',    'region' => 'South',     'miles' => 395],
        ['pickup' => 'Sacramento, CA',     'drop' => 'Los Angeles, CA',    'region' => 'West',      'miles' => 385],
        ['pickup' => 'El Paso, TX',        'drop' => 'Albuquerque, NM',    'region' => 'Southwest', 'miles' => 265],
        ['pickup' => 'Jacksonville, FL',   'drop' => 'Tampa, FL',          'region' => 'Southeast', 'miles' => 200],
    ];

    private array $equipmentTypes = [
        'Flatbed', 'Refrigerated', 'Dry Van', 'Box Truck', 'Step Deck', 'Lowboy',
    ];

    public function getOpenLoads(array $filters = []): Collection
    {
        $this->ensureSeededShipmentsIfEmpty();

        $shipments = Shipment::query()
            ->with('vehicleType')
            ->whereIn('status', ['pending', 'active'])
            ->latest()
            ->limit(300)
            ->get();

        $loads = $shipments->map(function (Shipment $shipment) {
            $lane  = $this->lanes[$shipment->id % count($this->lanes)];
            $equip = $this->equipmentTypes[$shipment->id % count($this->equipmentTypes)];

            // Always generate dates — never rely on null DB values
            $daysOut      = ($shipment->id % 14) + 1;
            $pickupTime   = Carbon::now()->addDays($daysOut)->setHour(6 + ($shipment->id % 12))->setMinute(0);
            $deliveryTime = $pickupTime->copy()->addHours((int) ceil($lane['miles'] / 55) + 2);

            if (blank($shipment->external_load_id)) {
                $shipment->external_load_id = 'LB-' . str_pad((string) $shipment->id, 6, '0', STR_PAD_LEFT);
                $shipment->external_source  = 'mock-board';

                if (blank($shipment->rate_total_usd)) {
                    $rpm = $this->randomRatePerMile();
                    $shipment->rate_per_mile_usd = $rpm;
                    $shipment->rate_total_usd    = round($lane['miles'] * $rpm, 2);
                }

                $shipment->saveQuietly();
            }

            return collect([
                'external_load_id'    => $shipment->external_load_id,
                'source'              => $shipment->external_source ?? 'mock-board',
                'shipment_id'         => $shipment->id,
                'customer_id'         => $shipment->customer_id,

                // Always use lane addresses — fixes column shifting
                'pickup_address'      => $lane['pickup'],
                'drop_address'        => $lane['drop'],
                'region'              => $lane['region'],

                // Always populated — fixes column shifting
                'pickup_time'         => $pickupTime->toISOString(),
                'delivery_time'       => $deliveryTime->toISOString(),
                'pickup_date_label'   => $pickupTime->format('M d, Y'),
                'delivery_date_label' => $deliveryTime->format('M d, Y'),
                'pickup_time_label'   => $pickupTime->format('h:i A'),
                'delivery_time_label' => $deliveryTime->format('h:i A'),

                'equipment'           => $shipment->vehicleType?->vehicle_type ?? $equip,
                'weight'              => $shipment->weight  ?? (mt_rand(10, 44) * 1000),
                'pallets'             => $shipment->pallets ?? mt_rand(4, 26),
                'distance_miles'      => (float) ($shipment->distance_miles ?? $lane['miles']),
                'rate_total_usd'      => (float) ($shipment->rate_total_usd ?? 0),
                'rate_per_mile_usd'   => (float) ($shipment->rate_per_mile_usd ?? 0),
                'status'              => $shipment->status,
            ]);
        });

        return $this->applyFilters($loads, $filters);
    }

    private function applyFilters(Collection $loads, array $filters): Collection
    {
        if (!empty($filters['equipment'])) {
            $loads = $loads->filter(fn($l) => stripos($l['equipment'], $filters['equipment']) !== false);
        }
        if (!empty($filters['region'])) {
            $loads = $loads->filter(fn($l) => stripos($l['region'], $filters['region']) !== false);
        }
        if (!empty($filters['date_from'])) {
            $from  = Carbon::parse($filters['date_from'])->startOfDay();
            $loads = $loads->filter(fn($l) => Carbon::parse($l['pickup_time'])->gte($from));
        }
        if (!empty($filters['date_to'])) {
            $to    = Carbon::parse($filters['date_to'])->endOfDay();
            $loads = $loads->filter(fn($l) => Carbon::parse($l['pickup_time'])->lte($to));
        }
        if (!empty($filters['min_rate'])) {
            $loads = $loads->filter(fn($l) => $l['rate_total_usd'] >= (float) $filters['min_rate']);
        }
        if (!empty($filters['max_miles'])) {
            $loads = $loads->filter(fn($l) => $l['distance_miles'] <= (float) $filters['max_miles']);
        }

        return $loads->values();
    }

    public function postLoad(array $payload): array
    {
        $shipment = new Shipment();
        $shipment->customer_id       = $payload['customer_id'] ?? null;
        $shipment->vehicle_type_id   = $payload['vehicle_type_id'] ?? null;
        $shipment->pickup_address    = $payload['pickup_address'] ?? null;
        $shipment->drop_address      = $payload['drop_address'] ?? null;
        $shipment->pickup_time       = $payload['pickup_time'] ?? null;
        $shipment->delivery_time     = $payload['delivery_time'] ?? null;
        $shipment->weight            = $payload['weight'] ?? null;
        $shipment->pallets           = $payload['pallets'] ?? null;
        $shipment->status            = 'pending';
        $shipment->rate_total_usd    = $payload['rate_total_usd'] ?? null;
        $shipment->rate_per_mile_usd = $payload['rate_per_mile_usd'] ?? null;
        $shipment->currency          = $payload['currency'] ?? 'USD';
        $shipment->save();

        $shipment->external_load_id = 'LB-' . strtoupper(Str::random(8));
        $shipment->external_source  = 'mock-board';
        $shipment->saveQuietly();

        return [
            'external_load_id' => $shipment->external_load_id,
            'status'           => 'POSTED',
            'shipment_id'      => $shipment->id,
        ];
    }

    private function randomRatePerMile(): float
    {
        return mt_rand(140, 300) / 100.0;
    }

    private function ensureSeededShipmentsIfEmpty(): void
    {
        // Only seed in local/dev environments, and only when requested.
        if (!app()->environment('local') && !env('MOCK_SEED_ON_DEMAND', false)) {
            return;
        }

        $hasAnyOpen = Shipment::query()
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($hasAnyOpen) {
            return;
        }

        $customers = Customer::query()->select('id')->get();
        $vehicleTypes = VehicleType::query()->select('id')->get();

        // If base reference data is missing, just leave it empty.
        if ($customers->isEmpty() || $vehicleTypes->isEmpty()) {
            return;
        }

        $targetCount = (int) env('MOCK_SHIPMENTS_COUNT', 120);
        if ($targetCount <= 0) {
            return;
        }

        DB::transaction(function () use ($customers, $vehicleTypes, $targetCount) {
            for ($i = 1; $i <= $targetCount; $i++) {
                // Status mix so both pending and active appear.
                $status = ($i % 5 === 0) ? 'active' : 'pending';

                $shipment = Shipment::create([
                    'customer_id' => $customers->random()->id,
                    'vehicle_type_id' => $vehicleTypes->random()->id,
                    'weight' => round(mt_rand(10, 44) * 1000, 2),
                    'pallets' => mt_rand(4, 26),
                    'status' => $status,
                ]);

                // Ensure modulo-based lane selection variety by touching only once.
                // (No need to persist pickup/drop here; MockLoadBoardProvider computes labels.)
                $shipment->saveQuietly();
            }
        });
    }
}