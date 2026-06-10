<?php

namespace App\Http\Controllers;

use App\Events\ShipmentRealtimeUpdated;
use App\Http\Controllers\Controller;
use App\Models\AssociatedDriver\AssociatedDriver;
use App\Models\Drivers\Driver;
use App\Models\Shipments\Shipment;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Models\Vehicles\Vehicle;
use App\Services\Ai\LoadMatchAiService;
use App\Services\Integrations\Mock\MockEldProvider;
use App\Services\Integrations\Mock\MockLoadBoardProvider;
use App\Services\Notifications\ExpoNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiBoardController extends Controller
{
    public function __construct(
        protected MockLoadBoardProvider $loadBoard,
        protected MockEldProvider       $eld,
        protected LoadMatchAiService    $ai
    ) {}

    public function index(): \Illuminate\View\View
    {
        return view('dispatch.ai-board', ['name' => 'AI Load Board']);
    }

    // GET /api/ai-board/open-loads
    public function openLoads(Request $request): JsonResponse
    {
        $filters = array_filter($request->only([
            'equipment', 'region', 'date_from', 'date_to', 'min_rate', 'max_miles'
        ]));

        $loads   = $this->loadBoard->getOpenLoads($filters);
        $allLoads = $this->loadBoard->getOpenLoads([]);

        return response()->json([
            'loads' => $loads,
            'meta'  => [
                'total'     => $loads->count(),
                'equipment' => $allLoads->pluck('equipment')->filter()->unique()->sort()->values(),
                'regions'   => $allLoads->pluck('region')->filter()->unique()->sort()->values(),
            ],
        ]);
    }

    // GET /api/ai-board/eld-snapshot
    public function eldSnapshot(): JsonResponse
    {
        $truckLocations = $this->eld->getTruckLocations();
        $driverStatuses = $this->eld->getDriverStatuses()->keyBy('driver_id');

        $trucks = $truckLocations->map(function ($truck) use ($driverStatuses) {
            $vehicle  = Vehicle::with(['vehicleAssignment.driver', 'vehicleType'])->find($truck['vehicle_id']);
            $driver   = $vehicle?->vehicleAssignment?->driver;
            $driverId = $driver?->id;
            $hosData  = $driverId ? $driverStatuses->get($driverId) : null;

            $busy = Shipment::where('vehicle_id', $vehicle?->id)
                ->whereIn('status', ['active', 'pending'])->exists();

            return [
                'vehicle_id'                   => $truck['vehicle_id'],
                'unit_number'                  => $truck['unit_number'],
                'lat'                          => $truck['lat'],
                'lng'                          => $truck['lng'],
                'speed_mph'                    => $truck['speed_mph'],
                'vehicle_type'                 => $vehicle?->vehicleType?->vehicle_type ?? 'Unknown',
                'vehicle_status'               => $busy ? 'busy' : ($vehicle?->status ?? 'available'),
                'vehicle_db_id'                => $vehicle?->id,
                'license_plate'                => $vehicle?->license_plate_number ?? null,
                'driver_id'                    => $driverId,
                'driver_name'                  => $driver
                    ? trim(($driver->firstname ?? '') . ' ' . ($driver->lastname ?? ''))
                    : 'Unassigned',
                'driver_db_id'                 => $driverId,
                'hos_drive_remaining_minutes'  => $hosData['hos']['drive_remaining_minutes']    ?? 0,
                'hos_on_duty_remaining_minutes'=> $hosData['hos']['on_duty_remaining_minutes']  ?? 0,
                'current_duty_status'          => $hosData['current_status'] ?? 'unknown',
                'equipment'                    => strtolower($vehicle?->vehicleType?->vehicle_type ?? ''),
            ];
        });

        return response()->json(['trucks' => $trucks]);
    }

    // POST /api/ai-board/rank
    public function rank(Request $request): JsonResponse
    {
        $payload = $request->input('payload');
        if (empty($payload['load']) || empty($payload['candidates'])) {
            return response()->json(['error' => 'Missing load or candidates'], 422);
        }
        try {
            return response()->json(['ranking' => $this->ai->rankCandidates($payload)]);
        } catch (\Throwable $e) {
            Log::error('AI ranking failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /api/ai-board/assign-external
    // Creates a shipment from a FreightFinder load then assigns vehicle + driver
    public function assignExternal(Request $request): JsonResponse
    {
        $v = $request->validate([
            'vehicle_id'           => 'required|integer|exists:vehicles,id',
            'driver_id'            => 'nullable|integer|exists:drivers,id',
            'load.origin'          => 'required|string',
            'load.destination'     => 'required|string',
            'load.equipment'       => 'nullable|string',
            'load.date'            => 'nullable|string',
            'load.company'         => 'nullable|string',
            'load.external_load_id'=> 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $load = $request->input('load');

            $shipment = Shipment::create([
                'pickup_address'   => $load['origin'],
                'drop_address'     => $load['destination'],
                'status'           => 'active',
                'vehicle_id'       => $v['vehicle_id'],
                'driver_id'        => $v['driver_id'] ?? null,
                'external_source'  => 'freightfinder',
                'external_load_id' => $load['external_load_id'] ?? null,
                'pickup_time'      => !empty($load['date']) ? date('Y-m-d H:i:s', strtotime($load['date'])) : now(),
                'delivery_time'    => now()->addDay(),
                'createdBy'        => 'Admin',
            ]);

            if (!empty($v['driver_id'])) {
                VehicleAssignment::updateOrCreate(
                    ['vehicle_id' => $v['vehicle_id']],
                    ['driver_id'  => $v['driver_id']]
                );
                AssociatedDriver::firstOrCreate([
                    'shipment_id' => $shipment->id,
                    'driver_id'   => $v['driver_id'],
                ]);
            }

            DB::commit();

            $shipment->refresh();
            event(new ShipmentRealtimeUpdated('assigned_driver', $shipment));
            if (!empty($v['driver_id'])) {
                $driver = Driver::find($v['driver_id']);
                if ($driver) {
                    ExpoNotificationService::notifyShipmentAssigned($driver, $shipment);
                }
            }

            return response()->json([
                'success'     => true,
                'message'     => 'External load booked and assigned successfully.',
                'shipment_id' => $shipment->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AssignExternal failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/ai-board/assign
    public function assign(Request $request): JsonResponse
    {
        $v = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'vehicle_id'  => 'required|integer|exists:vehicles,id',
            'driver_id'   => 'nullable|integer|exists:drivers,id',
        ]);

        try {
            DB::beginTransaction();

            $shipment = Shipment::findOrFail($v['shipment_id']);

            // 1. shipments — assign vehicle + driver, activate
            $shipment->vehicle_id = $v['vehicle_id'];
            $shipment->status     = 'active';
            if (!empty($v['driver_id'])) {
                $shipment->driver_id = $v['driver_id'];
            }
            $shipment->save();

            // 2. vehicle_assignments — upsert driver ↔ vehicle
            if (!empty($v['driver_id'])) {
                VehicleAssignment::updateOrCreate(
                    ['vehicle_id' => $v['vehicle_id']],
                    ['driver_id'  => $v['driver_id']]
                );
            }

            // 3. shipment_associated_drivers — log driver on this shipment
            if (!empty($v['driver_id'])) {
                AssociatedDriver::firstOrCreate([
                    'shipment_id' => $shipment->id,
                    'driver_id'   => $v['driver_id'],
                ]);
            }

            DB::commit();

            // 4. Fire realtime event + push notification so mobile updates immediately
            $shipment->refresh();
            event(new ShipmentRealtimeUpdated('assigned_driver', $shipment));
            if (!empty($v['driver_id'])) {
                $driver = Driver::find($v['driver_id']);
                if ($driver) {
                    ExpoNotificationService::notifyShipmentAssigned($driver, $shipment);
                }
            }

            $shipment->load(['vehicle.vehicleType', 'vehicle.vehicleAssignment.driver']);
            $d = $shipment->vehicle?->vehicleAssignment?->driver;

            return response()->json([
                'success'     => true,
                'message'     => 'Load assigned successfully.',
                'shipment_id' => $shipment->id,
                'vehicle'     => [
                    'id'           => $shipment->vehicle?->id,
                    'unit_number'  => $shipment->vehicle?->vehicle_id,
                    'vehicle_type' => $shipment->vehicle?->vehicleType?->vehicle_type,
                ],
                'driver' => $d ? [
                    'id'   => $d->id,
                    'name' => trim(($d->firstname ?? '') . ' ' . ($d->lastname ?? '')),
                ] : null,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assign failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}