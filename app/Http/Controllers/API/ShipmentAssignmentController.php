<?php

namespace App\Http\Controllers\API;

use App\Events\ShipmentRealtimeUpdated;
use App\Http\Controllers\Controller;
use App\Models\Shipments\Shipment;
use App\Models\Drivers\Driver;
use App\Services\Notifications\ExpoNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShipmentAssignmentController extends Controller
{
    /**
     * Assign shipment to a driver and send push notification
     * 
     * POST /api/admin/shipments/{id}/assign-driver
     */
    public function assignDriver(Request $request, $shipmentId)
    {
        $request->validate([
            'driver_id'    => ['required', 'exists:drivers,id'],
            'force_assign' => ['sometimes', 'boolean'],
        ]);

        try {
            $shipment = Shipment::findOrFail($shipmentId);
            $driver = Driver::findOrFail($request->driver_id);

            // Block if driver unavailable (off_duty / sleeper) unless forced
            if (!$request->boolean('force_assign')) {
                $unavailableStatuses = ['off_duty', 'sleeper'];
                if (in_array($driver->current_duty_status, $unavailableStatuses)) {
                    return response()->json([
                        'success'            => false,
                        'driver_unavailable' => true,
                        'message'            => "Driver {$driver->firstname} {$driver->lastname} is currently {$driver->current_duty_status}. Pass force_assign=true to override.",
                        'driver_status'      => $driver->current_duty_status,
                        'driver_name'        => $driver->firstname . ' ' . $driver->lastname,
                    ], 422);
                }
            }

            // Update shipment
            $shipment->update([
                'driver_id' => $driver->id,
                'status' => $shipment->status === 'pending' ? 'assigned' : $shipment->status,
            ]);
            $shipment->refresh();

            // Send push notification
            $notificationSent = ExpoNotificationService::notifyShipmentAssigned($driver, $shipment);
            event(new ShipmentRealtimeUpdated('assigned_driver', $shipment));

            Log::info('Shipment assigned to driver', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driver->id,
                'notification_sent' => $notificationSent,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipment assigned successfully',
                'shipment' => $shipment,
                'notification_sent' => $notificationSent,
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning shipment', [
                'error' => $e->getMessage(),
                'shipment_id' => $shipmentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shipment: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update shipment's driver and notify
     * 
     * PUT /api/admin/shipments/{id}/driver
     */
    public function updateDriver(Request $request, $shipmentId)
    {
        return $this->assignDriver($request, $shipmentId);
    }
}
