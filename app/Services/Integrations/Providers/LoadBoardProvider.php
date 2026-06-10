<?php

namespace App\Services\Integrations\Providers;

use App\Models\Customers\Customer;
use App\Models\Shipments\Shipment;
use App\Models\VehicleType\VehicleType;
use App\Services\Integrations\Contracts\LoadBoardProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Load Board Provider — auto-switches between DAT Freight (real) and built-in mock.
 *
 * Mock  → uses your existing Shipments as the load board, works out of the box.
 * Real  → set DAT_USERNAME + DAT_PASSWORD in .env → DAT One API is used automatically.
 *          Register at: https://www.dat.com/
 */
class LoadBoardProvider implements LoadBoardProviderInterface
{
    private string $datUser;
    private string $datPass;
    private string $baseUrl = 'https://api.dat.com/freight';

    private array $lanes = [
        ['pickup' => 'New York, NY',    'drop' => 'Philadelphia, PA',   'region' => 'Northeast', 'miles' => 95],
        ['pickup' => 'Boston, MA',       'drop' => 'New York, NY',       'region' => 'Northeast', 'miles' => 215],
        ['pickup' => 'Chicago, IL',      'drop' => 'Detroit, MI',        'region' => 'Midwest',   'miles' => 280],
        ['pickup' => 'Dallas, TX',       'drop' => 'Houston, TX',        'region' => 'South',     'miles' => 240],
        ['pickup' => 'Los Angeles, CA',  'drop' => 'San Francisco, CA',  'region' => 'West',      'miles' => 380],
        ['pickup' => 'Atlanta, GA',      'drop' => 'Miami, FL',          'region' => 'Southeast', 'miles' => 660],
        ['pickup' => 'Seattle, WA',      'drop' => 'Portland, OR',       'region' => 'Northwest', 'miles' => 175],
        ['pickup' => 'Denver, CO',       'drop' => 'Salt Lake City, UT', 'region' => 'Mountain',  'miles' => 525],
        ['pickup' => 'Phoenix, AZ',      'drop' => 'Las Vegas, NV',      'region' => 'Southwest', 'miles' => 290],
        ['pickup' => 'Minneapolis, MN',  'drop' => 'Chicago, IL',        'region' => 'Midwest',   'miles' => 410],
        ['pickup' => 'Nashville, TN',    'drop' => 'Atlanta, GA',        'region' => 'Southeast', 'miles' => 250],
        ['pickup' => 'Kansas City, MO',  'drop' => 'St. Louis, MO',      'region' => 'Midwest',   'miles' => 250],
        ['pickup' => 'Charlotte, NC',    'drop' => 'Washington, DC',     'region' => 'Southeast', 'miles' => 390],
        ['pickup' => 'San Antonio, TX',  'drop' => 'Dallas, TX',         'region' => 'South',     'miles' => 275],
        ['pickup' => 'Columbus, OH',     'drop' => 'Pittsburgh, PA',     'region' => 'Midwest',   'miles' => 185],
        ['pickup' => 'Indianapolis, IN', 'drop' => 'Cincinnati, OH',     'region' => 'Midwest',   'miles' => 110],
        ['pickup' => 'Memphis, TN',      'drop' => 'New Orleans, LA',    'region' => 'South',     'miles' => 395],
        ['pickup' => 'Sacramento, CA',   'drop' => 'Los Angeles, CA',    'region' => 'West',      'miles' => 385],
        ['pickup' => 'El Paso, TX',      'drop' => 'Albuquerque, NM',    'region' => 'Southwest', 'miles' => 265],
        ['pickup' => 'Jacksonville, FL', 'drop' => 'Tampa, FL',          'region' => 'Southeast', 'miles' => 200],
    ];

    private array $equipmentTypes = ['Flatbed', 'Refrigerated', 'Dry Van', 'Box Truck', 'Step Deck', 'Lowboy'];

    public function __construct()
    {
        $this->datUser = config('services.dat.username', '');
        $this->datPass = config('services.dat.password', '');
    }

    protected function isReal(): bool
    {
        return !empty($this->datUser) && !empty($this->datPass);
    }

    // -------------------------------------------------------------------------
    // Get open loads
    // -------------------------------------------------------------------------

    public function getOpenLoads(array $filters = []): Collection
    {
        return $this->isReal()
            ? $this->realOpenLoads($filters)
            : $this->mockOpenLoads($filters);
    }

    private function realOpenLoads(array $filters): Collection
    {
        // DAT uses OAuth2 — get a token first
        $token = Http::asForm()
            ->post('https://identity.dat.com/access/oauth/token', [
                'grant_type' => 'password',
                'username'   => $this->datUser,
                'password'   => $this->datPass,
                'client_id'  => 'dat-freight-api',
            ])->throw()->json('access_token');

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/loads", [
                'limit'          => 100,
                'equipmentTypes' => $filters['equipment'] ?? null,
                'originRegion'   => $filters['region'] ?? null,
            ])->throw()->json();

        return collect($response['loads'] ?? [])->map(fn ($load) => collect([
            'external_load_id'  => $load['loadId'],
            'source'            => 'dat',
            'pickup_address'    => "{$load['origin']['city']}, {$load['origin']['state']}",
            'drop_address'      => "{$load['destination']['city']}, {$load['destination']['state']}",
            'pickup_time'       => $load['pickupDate'] ?? null,
            'equipment'         => $load['equipmentType'] ?? null,
            'weight'            => $load['weightPounds'] ?? null,
            'distance_miles'    => (float) ($load['distanceMiles'] ?? 0),
            'rate_total_usd'    => (float) ($load['rate']['rateUsd'] ?? 0),
            'rate_per_mile_usd' => (float) ($load['rate']['ratePerMileUsd'] ?? 0),
            'status'            => 'pending',
            '_source'           => 'dat',
        ]))->values();
    }

    private function mockOpenLoads(array $filters): Collection
    {
        $this->ensureSeededShipments();

        $shipments = Shipment::query()
            ->with('vehicleType')
            ->whereIn('status', ['pending', 'active'])
            ->latest()
            ->limit(300)
            ->get();

        $loads = $shipments->map(function (Shipment $shipment) {
            $lane  = $this->lanes[$shipment->id % count($this->lanes)];
            $equip = $this->equipmentTypes[$shipment->id % count($this->equipmentTypes)];

            $daysOut      = ($shipment->id % 14) + 1;
            $pickupTime   = Carbon::now()->addDays($daysOut)->setHour(6 + ($shipment->id % 12))->setMinute(0);
            $deliveryTime = $pickupTime->copy()->addHours((int) ceil($lane['miles'] / 55) + 2);

            if (blank($shipment->external_load_id)) {
                $rpm = mt_rand(140, 300) / 100.0;
                $shipment->external_load_id  = 'LB-' . str_pad((string) $shipment->id, 6, '0', STR_PAD_LEFT);
                $shipment->external_source   = 'mock-board';
                $shipment->rate_per_mile_usd = $rpm;
                $shipment->rate_total_usd    = round($lane['miles'] * $rpm, 2);
                $shipment->saveQuietly();
            }

            return collect([
                'external_load_id'    => $shipment->external_load_id,
                'source'              => 'mock-board',
                'shipment_id'         => $shipment->id,
                'customer_id'         => $shipment->customer_id,
                'pickup_address'      => $lane['pickup'],
                'drop_address'        => $lane['drop'],
                'region'              => $lane['region'],
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
                '_source'             => 'mock',
            ]);
        });

        return $this->applyFilters($loads, $filters);
    }

    // -------------------------------------------------------------------------
    // Post a load
    // -------------------------------------------------------------------------

    public function postLoad(array $payload): array
    {
        return $this->isReal()
            ? $this->realPostLoad($payload)
            : $this->mockPostLoad($payload);
    }

    private function realPostLoad(array $payload): array
    {
        $token = Http::asForm()
            ->post('https://identity.dat.com/access/oauth/token', [
                'grant_type' => 'password',
                'username'   => $this->datUser,
                'password'   => $this->datPass,
                'client_id'  => 'dat-freight-api',
            ])->throw()->json('access_token');

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/loads", [
                'origin'        => ['city' => $payload['pickup_city'], 'state' => $payload['pickup_state']],
                'destination'   => ['city' => $payload['drop_city'], 'state' => $payload['drop_state']],
                'equipmentType' => $payload['equipment_type'] ?? 'Dry Van',
                'weightPounds'  => $payload['weight'] ?? null,
                'pickupDate'    => $payload['pickup_time'] ?? null,
            ])->throw()->json();

        return [
            'external_load_id' => $response['loadId'],
            'status'           => 'POSTED',
            '_source'          => 'dat',
        ];
    }

    private function mockPostLoad(array $payload): array
    {
        $shipment = new Shipment($payload);
        $shipment->status          = 'pending';
        $shipment->external_source = 'mock-board';
        $shipment->save();

        $shipment->external_load_id = 'LB-' . strtoupper(Str::random(8));
        $shipment->saveQuietly();

        return [
            'external_load_id' => $shipment->external_load_id,
            'status'           => 'POSTED',
            'shipment_id'      => $shipment->id,
            '_source'          => 'mock',
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function applyFilters(Collection $loads, array $filters): Collection
    {
        if (!empty($filters['equipment'])) {
            $loads = $loads->filter(fn ($l) => stripos($l['equipment'], $filters['equipment']) !== false);
        }
        if (!empty($filters['region'])) {
            $loads = $loads->filter(fn ($l) => stripos($l['region'] ?? '', $filters['region']) !== false);
        }
        if (!empty($filters['min_rate'])) {
            $loads = $loads->filter(fn ($l) => $l['rate_total_usd'] >= (float) $filters['min_rate']);
        }
        if (!empty($filters['max_miles'])) {
            $loads = $loads->filter(fn ($l) => $l['distance_miles'] <= (float) $filters['max_miles']);
        }
        return $loads->values();
    }

    private function ensureSeededShipments(): void
    {
        if (!app()->environment('local') && !env('MOCK_SEED_ON_DEMAND', false)) return;
        if (Shipment::whereIn('status', ['pending', 'active'])->exists()) return;

        $customers    = Customer::select('id')->get();
        $vehicleTypes = VehicleType::select('id')->get();
        if ($customers->isEmpty() || $vehicleTypes->isEmpty()) return;

        $count = (int) env('MOCK_SHIPMENTS_COUNT', 120);
        DB::transaction(function () use ($customers, $vehicleTypes, $count) {
            for ($i = 1; $i <= $count; $i++) {
                Shipment::create([
                    'customer_id'     => $customers->random()->id,
                    'vehicle_type_id' => $vehicleTypes->random()->id,
                    'weight'          => round(mt_rand(10, 44) * 1000, 2),
                    'pallets'         => mt_rand(4, 26),
                    'status'          => ($i % 5 === 0) ? 'active' : 'pending',
                ]);
            }
        });
    }
}
