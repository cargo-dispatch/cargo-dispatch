<?php

namespace App\Http\Controllers\Vehicleassignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Drivers\Driver;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Models\Vehicles\Vehicle;
use App\Models\Shipments\Shipment;
use App\Events\ShipmentRealtimeUpdated;
use App\Services\Notifications\ExpoNotificationService;
use Illuminate\Support\Facades\Log;

class VehicleAssignmentController extends Controller
{
    public function index()
    {
        $drivers = Driver::all();
     $vehicles = Vehicle::with('vehicleType')->get();
     $name = 'Vehicle Assignment';

        return view('vehicleassigment.index', compact('drivers', 'vehicles','name'));
    }
public function store(Request $request)
{
    $request->validate([
        'driver_id'    => 'required|exists:drivers,id',
        'vehicle_id'   => 'required|exists:vehicles,id',
        'force_assign' => 'sometimes|boolean',
    ]);

    try {
        // Block if driver is off_duty or sleeper (unless admin forces)
        if (!$request->boolean('force_assign')) {
            $driver = Driver::findOrFail($request->driver_id);
            $unavailableStatuses = ['off_duty', 'sleeper'];
            if (in_array($driver->current_duty_status, $unavailableStatuses)) {
                return response()->json([
                    'success'            => false,
                    'driver_unavailable' => true,
                    'message'            => "Driver {$driver->firstname} {$driver->lastname} is currently {$driver->current_duty_status}. Use force_assign=true to override.",
                    'driver_status'      => $driver->current_duty_status,
                    'driver_name'        => $driver->firstname . ' ' . $driver->lastname,
                ], 422);
            }
        }

        // Check if driver already has an assignment
        $existingAssignment = VehicleAssignment::where('driver_id', $request->driver_id)->first();
        $isNewAssignment = !$existingAssignment;
        $oldVehicleId = $existingAssignment?->vehicle_id;

        if ($existingAssignment) {
            $existingAssignment->update(['vehicle_id' => $request->vehicle_id]);
            $message = 'Vehicle assignment updated successfully';
        } else {
            VehicleAssignment::create([
                'driver_id' => $request->driver_id,
                'vehicle_id' => $request->vehicle_id
            ]);
            $message = 'Vehicle assigned successfully';
        }

        $driverId = (int) $request->driver_id;
        $driver = Driver::find($driverId);

        // If vehicle changed, clear driver_id from old vehicle's active shipments
        if ($oldVehicleId && (int) $oldVehicleId !== (int) $request->vehicle_id) {
            $oldShipments = Shipment::where('vehicle_id', $oldVehicleId)
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->get();
            foreach ($oldShipments as $shipment) {
                $shipment->update(['driver_id' => null]);
                event(new ShipmentRealtimeUpdated('deleted', $shipment, $driverId));
            }
            Log::info('VehicleAssignment: cleared driver_id on old vehicle shipments', [
                'driver_id' => $driverId, 'old_vehicle_id' => $oldVehicleId, 'count' => $oldShipments->count(),
            ]);
        }

        // Write driver_id onto all active shipments for the newly assigned vehicle
        // so that the mobile my-shipments API (which filters by driver_id) returns them.
        $shipments = Shipment::where('vehicle_id', $request->vehicle_id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        foreach ($shipments as $shipment) {
            $shipment->update(['driver_id' => $driverId]);
            $shipment->refresh();
            event(new ShipmentRealtimeUpdated('assigned_driver', $shipment));
        }

        Log::info('VehicleAssignment: set driver_id + fired events', [
            'driver_id' => $driverId, 'vehicle_id' => $request->vehicle_id, 'shipment_count' => $shipments->count(),
        ]);

        // Push only when VA is newly created (not an update) AND shipments already exist on the vehicle.
        // For the reverse order (shipment added after VA exists), DispatchController::assignVehicle sends the push.
        $isNewAssignment = !$existingAssignment;
        if ($isNewAssignment && $driver && $shipments->isNotEmpty()) {
            ExpoNotificationService::sendToDriver(
                $driver,
                '🚛 Vehicle assigned — shipments waiting',
                $shipments->count() . ' shipment(s) are now assigned to your vehicle.',
                ['event_type' => 'assigned_vehicle', 'vehicle_id' => (int) $request->vehicle_id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);

    } catch (\Exception $e) {
        Log::error('VehicleAssignment store error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error assigning vehicle: ' . $e->getMessage()
        ], 500);
    }
}

public function getAllAssignments()
{
    try {
        // Get all drivers with their vehicle assignments and related vehicle data
        $assignments = Driver::with(['vehicleAssignments.vehicle.vehicleType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($driver) {
                // Get the latest assignment for each driver
                $latestAssignment = $driver->vehicleAssignments->sortByDesc('created_at')->first();
                
                return [
                    'id' => $latestAssignment ? $latestAssignment->id : null,
                    'driver_id' => $driver->id,
                   'username' => $driver->firstname . ' ' . $driver->lastname,
                    'driver' => [
                        'id' => $driver->id,
                      'username' => $driver->firstname . ' ' . $driver->lastname,
                    ],
                    'vehicle' => $latestAssignment && $latestAssignment->vehicle ? [
                        'id' => $latestAssignment->vehicle->id,
                        'vehicle_id' => $latestAssignment->vehicle->vehicle_id,
                        'vehicle_type' => $latestAssignment->vehicle->vehicleType
                    ] : null,
                    'vehicle_ids' => $latestAssignment ? [$latestAssignment->vehicle_id] : [],
                    'created_at' => $latestAssignment ? $latestAssignment->created_at : $driver->created_at
                ];
            });

        return response()->json($assignments);
                     
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching all assignments: ' . $e->getMessage()
        ], 500);
    }
}
    public function updateAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id'
        ]);

        try {
            $assignment = VehicleAssignment::findOrFail($assignmentId);
            $driverId = (int) $assignment->driver_id;
            $oldVehicleId = (int) $assignment->vehicle_id;
            $newVehicleId = (int) $request->vehicle_id;

            $assignment->update(['vehicle_id' => $newVehicleId]);

            if ($oldVehicleId !== $newVehicleId) {
                // Clear driver_id from old vehicle's active shipments
                $oldShipments = Shipment::where('vehicle_id', $oldVehicleId)
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->get();
                foreach ($oldShipments as $shipment) {
                    $shipment->update(['driver_id' => null]);
                    event(new ShipmentRealtimeUpdated('deleted', $shipment, $driverId));
                }

                // Write driver_id onto new vehicle's active shipments
                $newShipments = Shipment::where('vehicle_id', $newVehicleId)
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->get();
                foreach ($newShipments as $shipment) {
                    $shipment->update(['driver_id' => $driverId]);
                    $shipment->refresh();
                    event(new ShipmentRealtimeUpdated('assigned_driver', $shipment));
                }

                Log::info('VehicleAssignment: updated vehicle, notified driver', [
                    'driver_id' => $driverId, 'old_vehicle_id' => $oldVehicleId,
                    'new_vehicle_id' => $newVehicleId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('VehicleAssignment updateAssignment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment: ' . $e->getMessage()
            ], 500);
        }
    }

public function removeAssignment($assignmentId)
{
    try {
        $assignment = VehicleAssignment::with('driver')->findOrFail($assignmentId);
        $driverId = (int) $assignment->driver_id;
        $vehicleId = (int) $assignment->vehicle_id;

        // Collect shipments before deleting so we can notify the driver
        $shipments = Shipment::where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        $assignment->delete();

        // Clear driver_id and tell the driver's app to remove these shipments
        foreach ($shipments as $shipment) {
            $shipment->update(['driver_id' => null]);
            event(new ShipmentRealtimeUpdated('deleted', $shipment, $driverId));
        }

        Log::info('VehicleAssignment: removed, notified driver', [
            'driver_id' => $driverId, 'vehicle_id' => $vehicleId, 'shipment_count' => $shipments->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment removed successfully'
        ]);

    } catch (\Exception $e) {
        Log::error('VehicleAssignment removeAssignment error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error removing assignment: ' . $e->getMessage()
        ], 500);
    }
}
}