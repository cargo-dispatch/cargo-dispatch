<?php

namespace App\Http\Controllers\Dispatch;

use App\Events\ShipmentChanged;
use App\Events\ShipmentRealtimeUpdated;
use App\Http\Controllers\Controller;
use App\Models\Shipments\Shipment;
use App\Models\Vehicles\Vehicle;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Services\Notifications\ExpoNotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchController extends Controller
{
    private function getStatusCounts()
{
    $today = Carbon::today();

    // Pending/assigned: count all not-yet-delivered (pickup may be upcoming)
    $pendingStatuses = ['pending', 'assigned'];
    $pendingCounts = DB::table('shipments')
        ->select('status', DB::raw('count(*) as total'))
        ->whereNull('deleted_at')
        ->whereIn('status', $pendingStatuses)
        ->whereDate('delivery_time', '>=', $today)
        ->groupBy('status')
        ->pluck('total', 'status')
        ->toArray();

    // Active/complete/cancel: within today's date range
    $otherCounts = DB::table('shipments')
        ->select('status', DB::raw('count(*) as total'))
        ->whereNull('deleted_at')
        ->whereNotIn('status', $pendingStatuses)
        ->whereDate('pickup_time', '<=', $today)
        ->whereDate('delivery_time', '>=', $today)
        ->groupBy('status')
        ->pluck('total', 'status')
        ->toArray();

    $counts = array_merge($pendingCounts, $otherCounts);
     
    return [
        'pending'    => $counts['pending'] ?? 0,
        'active'     => ($counts['active'] ?? 0) + ($counts['assigned'] ?? 0) + ($counts['picked_up'] ?? 0) + ($counts['in_transit'] ?? 0),
        'complete'   => ($counts['complete'] ?? 0) + ($counts['delivered'] ?? 0),
        'cancel'     => ($counts['cancel'] ?? 0) + ($counts['cancelled'] ?? 0),
    ];
}
     public function index()
    {
     
        $name ='Todays shipments';
       
        $statusCounts = $this->getStatusCounts();
        return view('dispatch.index', compact('statusCounts','name'));
    }

    public function aiBoard()
    {
        $name = 'AI Load Board';
        $statusCounts = $this->getStatusCounts();
        return view('dispatch.ai-board', compact('name', 'statusCounts'));
    }

    public function tomorrowDispatch(){
                $name ='Next Day Schedule';

       
        return view('dispatch.nextDayDispatch.index',compact('name'));
    }

    public function getCounts(Request $request)
    {
        $counts = $this->getStatusCounts();
        
        return response()->json([
            'success' => true,
            'data' => $counts
        ]);
    }
public function getMapData($id)
{
    try {
        // Get shipment with vehicle assignment and driver details
        $shipment = Shipment::with([
            'vehicle.vehicleAssignment.driver' // Navigate through relationships
        ])->findOrFail($id);
        
        $mapData = [
            'pickup' => $shipment->pickup_address,
            'drop' => $shipment->drop_address,
            'has_vehicle' => false,
            'vehicle_location' => null
        ];
        
        // Check if vehicle is assigned and has a driver
        if ($shipment->vehicle && 
            $shipment->vehicle->vehicleAssignment && 
            $shipment->vehicle->vehicleAssignment->driver) {
            
            $driver = $shipment->vehicle->vehicleAssignment->driver;
            
            // Only include location if driver has valid coordinates
            if ($driver->current_latitude && $driver->current_longitude) {
                $mapData['has_vehicle'] = true;
                $mapData['vehicle_location'] = [
                    'lat' => $driver->current_latitude,
                    'lng' => $driver->current_longitude,
                    'driver_name' => trim($driver->firstname . ' ' . $driver->lastname),
                    'vehicle_number' => $shipment->vehicle->vehicle_id ?? 'N/A',
                    'last_updated' => $driver->last_location_update ? 
                        \Carbon\Carbon::parse($driver->last_location_update)->diffForHumans() : 'Unknown'
                ];
            }
        }
        
        return response()->json($mapData);
        
    } catch (\Exception $e) {
        Log::error('Map data fetch error', [
            'shipment_id' => $id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'error' => 'Failed to fetch map data',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function show($id)
{
    $customer = Shipment::with(['audits.user', 'customer', 'vehicleType'])->findOrFail($id);

    $audits = $customer->audits->map(function ($audit) {
        $audit->user_name = $audit->user ? $audit->user->name : null;
        return $audit;
    });

    return response()->json([
        'estimated_cost' => $customer->estimated_cost,
        'customer_name' => $customer->customer->first_name . ' ' . $customer->customer->last_name,
        'vehicle_type' => $customer->vehicleType->vehicle_type,
        'pickup_address' => $customer->pickup_address,
        'pallets' => $customer->pallets,
        'volume' => $customer->volume,
        'weight' => $customer->weight,
        'drop_address' => $customer->drop_address,

        // Format using Carbon
        'pickup_time' => Carbon::parse($customer->pickup_time)->format('M d, Y / h:i A'),
        'delivery_time' => Carbon::parse($customer->delivery_time)->format('M d, Y / h:i A'),

        'equipment_required' => $customer->equipment_required,
        'special_instructions' => $customer->special_instructions,
    ]);
}


public function getShipments(Request $request)
{
    try {
        $status = $request->get('status', 'pending');
        $currentDate = Carbon::today()->toDateString();

      $vehicles = Vehicle::with('vehicleType')->get()->map(function ($vehicle) {
    return [
        'id' => $vehicle->id,
        'vehicle_id' => $vehicle->vehicle_id,
       'vehicle_type' => [
    'image' => $vehicle->vehicleType->image 
        ? asset('storage/' . $vehicle->vehicleType->image) 
        : asset('storage/default.png')
]
    ];
});


        // Map UI status tabs to all equivalent DB statuses (legacy + mobile workflow)
        $statusMap = [
            'pending'  => ['pending'],
            'active'   => ['active', 'assigned', 'picked_up', 'in_transit'],
            'complete' => ['complete', 'delivered'],
            'cancel'   => ['cancel', 'cancelled'],
        ];
        $statuses = $statusMap[$status] ?? [$status];

        $query = Shipment::with(['customer', 'vehicleType'])
            ->whereIn('status', $statuses)
            ->where(function ($q) use ($currentDate, $statuses) {
                // Pending/unassigned: show if not yet delivered (pickup may be today or future)
                if (array_intersect($statuses, ['pending', 'assigned'])) {
                    $q->whereDate('delivery_time', '>=', $currentDate);
                } else {
                    // Active/completed: keep the strict today-range filter
                    $q->whereDate('pickup_time', '<=', $currentDate)
                      ->whereDate('delivery_time', '>=', $currentDate);
                }
            })
            ->orderBy('pickup_time', 'asc');

        $shipments = $query->get();

        return response()->json([
            'status' => 'success',
            'vehicle' => $vehicles,
            'data' => $shipments,
            'count' => $shipments->count()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching shipments: ' . $e->getMessage()
        ], 500);
    }
}
public function getTomorrowShipmentCounts(Request $request) 
{
    try {
        // Get selected date or default to tomorrow
        $selectedDate = $request->get('date', \Carbon\Carbon::tomorrow()->toDateString());

        // Get counts for each status on the selected date
        $counts = [
            'pending' => Shipment::where('status', 'pending')
                ->whereDate('pickup_time', $selectedDate)
          
                ->count(),


            'cancel' => Shipment::where('status', 'cancel')
                ->whereDate('pickup_time', $selectedDate)
              
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $counts
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching shipment counts: ' . $e->getMessage()
        ], 500);
    }
}

public function getTomorrowShipments(Request $request) 
{
    try {
        $status = $request->get('status', 'pending');
        $selectedDate = $request->get('date', \Carbon\Carbon::tomorrow()->toDateString());
        

        // Vehicles
        $vehicles = Vehicle::with('vehicleType')->get()->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'vehicle_id' => $vehicle->vehicle_id,
                'vehicle_type' => [
                    'image' => $vehicle->vehicleType->image
                        ? asset('storage/' . $vehicle->vehicleType->image)
                        : asset('storage/default.png')
                ]
            ];
        });

        // Filtered Shipments
        $shipments = \App\Models\Shipments\Shipment::with(['customer', 'vehicleType'])
            ->where('status', $status)
            ->whereDate('pickup_time',$selectedDate)
            
            ->orderBy('created_at', 'desc')
            ->get();

        // Status Counts (for this selected date)
        $pendingCount = \App\Models\Shipments\Shipment::where('status', 'pending')
            ->whereDate('pickup_time',$selectedDate)
    
            ->count();

        $cancelCount = \App\Models\Shipments\Shipment::where('status', 'cancel')
            ->whereDate('pickup_time', $selectedDate)
        
            ->count();
           

        return response()->json([
            'status' => 'success',
            'vehicle' => $vehicles,
            'data' => $shipments,
            'count' => $shipments->count(),
            'selected_date' => $selectedDate,
            'tab_counts' => [
                'pending' => $pendingCount,
                'cancel' => $cancelCount
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching tomorrow\'s shipments: ' . $e->getMessage()
        ], 500);
    }
}


public function updateStatus(Request $request, $id)
{
    try {
        // Validate input
        $request->validate([
            'status' => 'required|in:pending,active,complete,cancel'
        ]);
        
        // Find the shipment
        $shipment = Shipment::findOrFail($id);
        $newStatus = $request->status;
        
        // Special validation for "complete" status
        if ($newStatus === 'complete') {
            
            // Check 1: Vehicle assigned?
            if (empty($shipment->vehicle_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => '⚠️ Cannot mark as complete: Please assign a vehicle first!'
                ], 422);
            }
            
            // Check 2: Driver assigned to vehicle?
            $vehicleAssignment = DB::table('vehicle_assignments')
                ->where('vehicle_id', $shipment->vehicle_id)
                ->first();
            
            if (!$vehicleAssignment || empty($vehicleAssignment->driver_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => '⚠️ Cannot mark as complete: Please assign a driver to this vehicle first!'
                ], 422);
            }
            
            // Check 3: Driver association exists in shipment_associated_drivers?
            $driverAssociation = DB::table('shipment_associated_drivers')
                ->where('shipment_id', $shipment->id)
                ->where('driver_id', $vehicleAssignment->driver_id)
                ->first();
            
            // Begin transaction for complete status
            DB::beginTransaction();
            
            try {
                // Update shipment status
                $shipment->status = 'complete';
                $shipment->save();
                event(new ShipmentRealtimeUpdated('status_updated', $shipment));
                if ($shipment->driver_id) {
                    $driver = \App\Models\Drivers\Driver::find($shipment->driver_id);
                    if ($driver) {
                        ExpoNotificationService::notifyShipmentUpdated($driver, $shipment, $shipment->status);
                    }
                }
                
                // Insert/Update driver association if not exists
                if (!$driverAssociation) {
                    DB::table('shipment_associated_drivers')->insert([
                        'shipment_id' => $shipment->id,
                        'driver_id' => $vehicleAssignment->driver_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                DB::commit();
                
                // Get updated counts
                $counts = $this->getStatusCounts();
                
                return response()->json([
                    'status' => 'success',
                    'message' => '✅ Shipment marked as complete successfully!',
                    'counts' => $counts
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            
        } else {
            // For other statuses (pending, active, cancel) - just update
            $shipment->status = $newStatus;
            $shipment->save();
            event(new ShipmentRealtimeUpdated('status_updated', $shipment));
            if ($shipment->driver_id) {
                $driver = \App\Models\Drivers\Driver::find($shipment->driver_id);
                if ($driver) {
                    ExpoNotificationService::notifyShipmentUpdated($driver, $shipment, $shipment->status);
                }
            }
            
            $counts = $this->getStatusCounts();
            
            return response()->json([
                'status' => 'success',
                'message' => '✅ Status updated successfully!',
                'counts' => $counts
            ]);
        }
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Shipment not found'
        ], 404);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid status provided'
        ], 422);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}
    public function cancelShipment(Request $request, $id)
{
    try {
        $shipment = Shipment::findOrFail($id);
        
        // Additional validation or business logic
        $shipment->update([
            'status' => 'cancel',
            'cancel_reason' => $request->input('reason', null),
            'canceled_at' => now()
        ]);
        event(new ShipmentRealtimeUpdated('status_updated', $shipment));
        if ($shipment->driver_id) {
            $driver = \App\Models\Drivers\Driver::find($shipment->driver_id);
            if ($driver) {
                ExpoNotificationService::notifyShipmentUpdated($driver, $shipment, $shipment->status);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Shipment canceled successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error canceling shipment: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Bulk update shipment status
     */

     public function assignVehicle(Shipment $shipment, Request $request)
{
    $validated = $request->validate([
        'vehicle_id'     => 'required|exists:vehicles,id',
        'force_assign'   => 'sometimes|boolean',
    ]);

    try {
        // Find the most recent driver assignment for this vehicle
        $assignment = VehicleAssignment::where('vehicle_id', $validated['vehicle_id'])
            ->with('driver')
            ->latest()
            ->first();

        // Block assignment if driver is unavailable (unless admin force-assigns)
        if ($assignment && $assignment->driver && !($validated['force_assign'] ?? false)) {
            $driver = $assignment->driver;
            $unavailableStatuses = ['off_duty', 'sleeper'];
            if (in_array($driver->current_duty_status, $unavailableStatuses)) {
                return response()->json([
                    'success'        => false,
                    'driver_unavailable' => true,
                    'message'        => "Driver {$driver->firstname} {$driver->lastname} is currently {$driver->current_duty_status}. Use force_assign=true to override.",
                    'driver_status'  => $driver->current_duty_status,
                    'driver_name'    => $driver->firstname . ' ' . $driver->lastname,
                ], 422);
            }
        }

        $updateData = [
            'vehicle_id' => $validated['vehicle_id'],
            'status'     => 'assigned',
        ];

        if ($assignment && $assignment->driver) {
            $updateData['driver_id'] = $assignment->driver->id;
        }

        $shipment->update($updateData);
        $shipment->refresh();

        Log::info('assignVehicle: firing realtime event', [
            'shipment_id' => $shipment->id,
            'vehicle_id'  => $validated['vehicle_id'],
            'driver_id'   => $shipment->driver_id,
            'has_assignment' => (bool) $assignment,
            'has_driver'     => $assignment ? (bool) $assignment->driver : false,
        ]);

        $driverIdOverride = $assignment?->driver_id ? (int) $assignment->driver_id : null;
        event(new ShipmentRealtimeUpdated('assigned_vehicle', $shipment, $driverIdOverride));

        // Push notification is handled by VehicleAssignmentController when the VA is created/updated.
        // Here we only send if no VA existed at the time the vehicle was linked to the shipment
        // (i.e. driver was assigned to vehicle BEFORE this shipment was added).
        // VehicleAssignmentController::store() covers the reverse order.
        $notificationSent = false;
        if ($assignment && $assignment->driver) {
            $notificationSent = ExpoNotificationService::sendToDriver(
                $assignment->driver,
                '🚛 New Shipment Assigned',
                "Shipment #{$shipment->id} — Pickup: {$shipment->pickup_address}",
                ['type' => 'vehicle_assigned', 'shipment_id' => $shipment->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Vehicle assigned successfully',
            'shipment' => $shipment->fresh(['vehicle']),
            'notification_sent' => $notificationSent,
        ]);
    } catch (\Exception $e) {
        Log::error('assignVehicle failed', ['error' => $e->getMessage(), 'shipment_id' => $shipment->id]);
        return response()->json([
            'success' => false,
            'message' => 'Error assigning vehicle: ' . $e->getMessage()
        ], 500);
    }
}

  public function destroy($id)
{
 
    try {
        $shipment = \App\Models\Shipments\Shipment::findOrFail($id);
        $shipment->delete();

        event(new ShipmentChanged('deleted', (int) $id));
        event(new ShipmentRealtimeUpdated('deleted', $shipment));

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment deleted successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete shipment: ' . $e->getMessage()
        ], 500);
    }
}


}
