<?php

namespace App\Http\Controllers\API;

use App\Events\ShipmentStatusUpdated;
use App\Events\ShipmentRealtimeUpdated;
use App\Events\DriverStatusUpdated;
use App\Events\LocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\Remarks\Remarks;
use App\Models\Shipments\Shipment;
use App\Models\User;
use App\Services\Notifications\ExpoNotificationService;
use App\Services\Integrations\Contracts\EldProviderInterface;
use App\Notifications\NewShipmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DriverAuthController extends Controller
{
    public function __construct()
    {
        $this->credentials = [
            'app_id' => (int) config('services.connectycube.app_id'),
            'auth_key' => (string) config('services.connectycube.auth_key'),
            'auth_secret' => (string) config('services.connectycube.auth_secret'),
        ];
    }

    public function login(Request $request, EldProviderInterface $eldProvider)
    {
        // return response()->json(['message' => $request->all()]);
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $driver = Driver::where('email', $request->email)->first();

        if (! $driver || ! Hash::check($request->password, $driver->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if ($driver->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please contact your dispatcher.'
            ], 403);
        }

        if ($request->has('latitude') && $request->has('longitude')) {
            $driver->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'last_location_update' => Carbon::now(),
            ]);
        }

        // Refresh mock ELD/HOS snapshot so the driver app shows current duty status
        $eldSnapshot = $eldProvider->getDriverStatuses()->firstWhere('driver_id', $driver->id);

        $eld = is_array($eldSnapshot)
            ? [
                'current_status' => $eldSnapshot['current_status'] ?? null,
                'hos' => $eldSnapshot['hos'] ?? null,
            ]
            : null;

        $token = $driver->createToken('driver-token')->plainTextToken;

        $today = Carbon::today();

        $vehicleAssignments = $driver->vehicleAssignments()->with([
            'vehicle.vehicleType',
            'vehicle.shipments' => function ($query) use ($today) {
                $query->whereDate('pickup_time', '<=', $today)
                    ->whereDate('delivery_time', '>=', $today);
            },
            'vehicle.shipments.vehicleType',
            'vehicle.shipments.customer',
            'vehicle.shipments.drivers'
        ])
            ->whereHas('vehicle.shipments', function ($query) use ($today) {
                $query->whereDate('pickup_time', '<=', $today)
                    ->whereDate('delivery_time', '>=', $today);
            })
            ->get();
        // return response()->json([
        //     'shipments' => $vehicleAssignments
        // ]);

        // ✅ Format shipments properly
        $formattedShipments = [];

        foreach ($vehicleAssignments as $assignment) {
            $vehicle = $assignment->vehicle;
            if (!$vehicle) continue;

            foreach ($vehicle->shipments as $shipment) {
                $formattedShipments[] = [
                    'id' => $shipment->id,
                    'pickup_address' => $shipment->pickup_address,
                    'drop_address' => $shipment->drop_address,
                    'estimated_cost' => $shipment->estimated_cost,
                    'weight' => $shipment->weight,
                    'volume' => $shipment->volume,
                    'status' => $shipment->status,
                    'pallets' => $shipment->pallets,
                    'customer_name' => optional($shipment->customer)->first_name,
                    'driver_name' => optional($shipment->drivers)->username,
                    'vehicle_number' => optional($shipment->vehicle)->vehicle_id,
                    'vehicle_type' => optional($shipment->vehicleType)->vehicle_type,
                    'pickup_time' => $shipment->pickup_time,
                    'delivery_time' => $shipment->delivery_time,
                    'special_instructions' => $shipment->special_instructions,
                ];
            }
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $driver->id,
                'firstname' => $driver->firstname,
                'lastname' => $driver->lastname,
                'licenseno' => $driver->licenseno,
                'licensetype' => $driver->licensetype,
                'phoneno' => $driver->phoneno,
                'email' => $driver->email,
                'username' => $driver->username,
                'emergencycontactno' => $driver->emergencycontactno,
                'current_latitude' => $driver->current_latitude,
                'current_longitude' => $driver->current_longitude,
                'last_location_update' => $driver->last_location_update,
                'eld' => $eld,
                'shipments' => $formattedShipments,
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::broker('drivers')->sendResetLink(
                $request->only('email')
            );

            return $status == Password::RESET_LINK_SENT
                ? response()->json(['success' => true, 'message' => 'We have emailed your password reset link.'])
                : response()->json(['success' => false, 'message' => 'We could not find a driver with that email address.'], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('drivers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($driver, $password) {
                $driver->password = Hash::make($password);
                $driver->setRememberToken(Str::random(60));
                $driver->save();
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 422);
    }

    public function getCurrentLocation(Request $request)
{
  
    $driver = Auth::user();
     
    
    return response()->json([
        'success' => true,
        'current_latitude' => $driver->current_latitude,
        'current_longitude' => $driver->current_longitude,
        'last_location_update' => $driver->last_location_update,
    ]);
}
    public function updateProfile(Request $request)
    {
        $driver = Driver::find($request->id);

        $validated = $request->validate([
            'id'                  => 'required',
            'firstname'           => 'sometimes|required|string|max:255',
            'lastname'            => 'sometimes|required|string|max:255',
            'email'               => 'required|email|unique:drivers,email,' . $driver->id,
            'phoneno'             => 'sometimes|required|string|max:20',
            'emergencycontactno'  => 'nullable|string|max:20',
            'licenseno'           => 'sometimes|required|string|max:50',
            'licensetype'         => 'sometimes|required|string|max:50',
            'profile_photo'       => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($driver->profile_photo) {
                \Storage::disk('public')->delete($driver->profile_photo);
            }
            $path = $request->file('profile_photo')->store('driver_photos', 'public');
            $validated['profile_photo'] = $path;
        }

        unset($validated['id']);
        $driver->update($validated);

        $fresh = $driver->fresh();
        $fresh->profile_photo_url = $fresh->profile_photo
            ? \Storage::disk('public')->url($fresh->profile_photo)
            : null;

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $fresh,
            'success' => true,
        ]);
    }

    public function profile(Request $request, EldProviderInterface $eldProvider)
    {
        $driver = Auth::user();
        $today = Carbon::today();

        // Refresh mock ELD/HOS snapshot so driver profile shows current duty status
        $eldSnapshot = $eldProvider->getDriverStatuses()->firstWhere('driver_id', $driver->id);
        $eld = is_array($eldSnapshot)
            ? [
                'current_status' => $eldSnapshot['current_status'] ?? null,
                'hos' => $eldSnapshot['hos'] ?? null,
            ]
            : null;

        $vehicleAssignments = $driver->vehicleAssignments()->with([
            'vehicle.vehicleType',
            'vehicle.shipments' => function ($query) use ($today) {
                $query->whereDate('pickup_time', '<=', $today)
                    ->whereDate('delivery_time', '>=', $today);
            },
            'vehicle.shipments.vehicleType',
            'vehicle.shipments.customer',
            'vehicle.shipments.drivers'
        ])
            ->whereHas('vehicle.shipments', function ($query) use ($today) {
                $query->whereDate('pickup_time', '<=', $today)
                    ->whereDate('delivery_time', '>=', $today);
            })
            ->get();

        $formattedShipments = [];

        foreach ($vehicleAssignments as $assignment) {
            $vehicle = $assignment->vehicle;
            if (!$vehicle) continue;

            foreach ($vehicle->shipments as $shipment) {
                // Parse equipment_required from JSON string to array
                $equipmentRequired = [];
                if ($shipment->equipment_required) {
                    try {
                        // If it's already an array, use it directly
                        if (is_array($shipment->equipment_required)) {
                            $equipmentRequired = $shipment->equipment_required;
                        }
                        // If it's a JSON string, decode it
                        else if (is_string($shipment->equipment_required)) {
                            $equipmentRequired = json_decode($shipment->equipment_required, true) ?? [];
                        }
                    } catch (\Exception $e) {
                        // If decoding fails, try to parse as comma-separated string
                        $equipmentRequired = array_map('trim', explode(',', $shipment->equipment_required));
                    }
                }

                $formattedShipments[] = [
                    'id' => $shipment->id,
                    'pickup_address' => $shipment->pickup_address,
                    'drop_address' => $shipment->drop_address,
                    'estimated_cost' => $shipment->estimated_cost,
                    'weight' => $shipment->weight,
                    'volume' => $shipment->volume,
                    'status' => $shipment->status,
                    'pallets' => $shipment->pallets,
                    'customer_name' => optional($shipment->customer)->first_name,
                    'driver_name' => optional($shipment->drivers)->username,
                    'vehicle_number' => optional($shipment->vehicle)->vehicle_id,
                    'vehicle_type' => optional($shipment->vehicleType)->vehicle_type,
                    'pickup_time' => $shipment->pickup_time,
                    'delivery_time' => $shipment->delivery_time,
                    'special_instructions' => $shipment->special_instructions,
                    'equipment_required' => $equipmentRequired, // Add this line
                ];
            }
        }

        return response()->json([
            'user' => [
                'id' => $driver->id,
                'firstname' => $driver->firstname,
                'lastname' => $driver->lastname,
                'email' => $driver->email,
                'phoneno' => $driver->phoneno,
                'emergencycontactno' => $driver->emergencycontactno,
                'licenseno' => $driver->licenseno,
                'licensetype' => $driver->licensetype,
                'eld' => $eld,
                'shipments' => $formattedShipments,
            ],
        ]);
    }

    public function myShipments(Request $request, EldProviderInterface $eldProvider)
    {
        $driver = Auth::user();

        // Refresh mock ELD/HOS snapshot
        $eldSnapshot = $eldProvider->getDriverStatuses()->firstWhere('driver_id', $driver->id);
        $eld = is_array($eldSnapshot)
            ? [
                'current_status' => $eldSnapshot['current_status'] ?? null,
                'hos'            => $eldSnapshot['hos'] ?? null,
            ]
            : null;

        // Get vehicle IDs assigned to this driver (current assignments)
        $vehicleIds = \App\Models\VehicleAssignment\VehicleAssignment::where('driver_id', $driver->id)
            ->pluck('vehicle_id')
            ->toArray();

        // Fetch shipments explicitly assigned to this driver.
        // Vehicle-based fallback is intentionally removed — it caused shipments
        // assigned to other drivers on the same vehicle to appear for this driver.
        $shipments = \App\Models\Shipments\Shipment::with([
                'customer',
                'vehicle.vehicleType',
                'shipmentInvoice',
            ])
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', ['pending'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedShipments = $shipments->map(function ($shipment) {
            $vehicle = $shipment->vehicle;

            $equipmentRequired = [];
            if ($shipment->equipment_required) {
                if (is_array($shipment->equipment_required)) {
                    $equipmentRequired = $shipment->equipment_required;
                } elseif (is_string($shipment->equipment_required)) {
                    $equipmentRequired = json_decode($shipment->equipment_required, true) ?? [];
                }
            }

            $inv = $shipment->shipmentInvoice->first();

            return [
                'id'                   => $shipment->id,
                'pickup_address'       => $shipment->pickup_address,
                'drop_address'         => $shipment->drop_address,
                'estimated_cost'       => $shipment->estimated_cost,
                'weight'               => $shipment->weight,
                'volume'               => $shipment->volume,
                'status'               => $shipment->status,
                'pallets'              => $shipment->pallets,
                'customer_name'        => optional($shipment->customer)->customer_title,
                'pickup_time'          => $shipment->pickup_time,
                'delivery_time'        => $shipment->delivery_time,
                'special_instructions'   => $shipment->special_instructions,
                'distance_miles'         => $shipment->distance_miles,
                'equipment_required'     => $equipmentRequired,
                'load_type'              => $shipment->load_type,
                'reference_number'       => $shipment->reference_number,
                'pickup_contact_name'    => $shipment->pickup_contact_name,
                'pickup_contact_phone'   => $shipment->pickup_contact_phone,
                'delivery_contact_name'  => $shipment->delivery_contact_name,
                'delivery_contact_phone' => $shipment->delivery_contact_phone,
                'deadhead_miles'         => $shipment->deadhead_miles,
                'detention_hours'        => $shipment->detention_hours,
                'lumper_fee'             => $shipment->lumper_fee,
                'per_diem_days'          => $shipment->per_diem_days,
                'scale_fees'             => $shipment->scale_fees,
                'tarp_required'          => $shipment->tarp_required,
                'permit_fee'             => $shipment->permit_fee,
                'invoice'              => $inv ? [
                    'driver_pay'        => $inv->driver_pay,
                    'driver_cost'       => $inv->driver_cost,
                    'fuel_cost'         => $inv->fuel_cost,
                    'fuel_price'        => $inv->fuel_price,
                    'tolls_fee'         => $inv->tolls_fee,
                    'extra_charges'     => $inv->extra_charges,
                    'total_cost'        => $inv->total_cost,
                    'total_with_profit' => $inv->total_with_profit,
                    'invoice_note'      => $inv->invoice_note,
                ] : null,
                'vehicle' => $vehicle ? [
                    'unit_number'           => $vehicle->vehicle_id,
                    'license_plate'         => $vehicle->license_plate_number,
                    'vin'                   => $vehicle->vin,
                    'make_model'            => $vehicle->make_model,
                    'year'                  => $vehicle->year_of_manufacture,
                    'color'                 => $vehicle->color,
                    'type'                  => optional($vehicle->vehicleType)->vehicle_type,
                    'ownership_status'      => $vehicle->ownership_status,
                    'cargo_weight_capacity' => $vehicle->cargo_weight,
                    'cargo_volume_capacity' => $vehicle->cargo_volume,
                    'load_type'             => $vehicle->load_type_compatibility,
                ] : null,
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'                 => $driver->id,
                    'firstname'          => $driver->firstname,
                    'lastname'           => $driver->lastname,
                    'email'              => $driver->email,
                    'phoneno'            => $driver->phoneno,
                    'emergencycontactno' => $driver->emergencycontactno,
                    'licenseno'          => $driver->licenseno,
                    'licensetype'        => $driver->licensetype,
                    'eld'                => $eld,
                ],
                'shipments' => $formattedShipments,
            ],
        ]);
    }

    // GET /api/driver/vehicle — returns the driver's currently assigned vehicle
    public function myVehicle(Request $request)
    {
        $driver = Auth::user();

        $assignment = $driver->vehicleAssignments()
            ->with(['vehicle.vehicleType'])
            ->latest()
            ->first();

        if (!$assignment || !$assignment->vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'No vehicle currently assigned.',
                'vehicle' => null,
            ]);
        }

        $v = $assignment->vehicle;

        return response()->json([
            'success' => true,
            'vehicle' => [
                'unit_number'           => $v->vehicle_id,
                'license_plate'         => $v->license_plate_number,
                'vin'                   => $v->vin,
                'make_model'            => $v->make_model,
                'year'                  => $v->year_of_manufacture,
                'color'                 => $v->color,
                'type'                  => optional($v->vehicleType)->vehicle_type,
                'ownership_status'      => $v->ownership_status,
                'cargo_weight_capacity' => $v->cargo_weight,
                'cargo_volume_capacity' => $v->cargo_volume,
                'load_type'             => $v->load_type_compatibility,
                'registration_expiry'   => $v->registration_expiry_date,
                'insurance_expiry'      => $v->insurance_expiry_date,
                'insurance_details'     => $v->insurance_details,
                'assigned_since'        => $assignment->created_at,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('driver')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('driver.login'));
    }

  public function updateLocation(Request $request)
{
    Log::info('updateLocation endpoint hit', [
        'ip'       => $request->ip(),
        'user_agent'=> $request->userAgent(),
        'payload'  => $request->all(),
    ]);

    $request->validate([
        'latitude'  => ['required', 'numeric', 'between:-90,90'],
        'longitude' => ['required', 'numeric', 'between:-180,180'],
    ]);

    $driver = Auth::user();

    if (!$driver) {
        Log::warning('No authenticated user in updateLocation');
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    Log::info('Authenticated driver', [
        'driver_id' => $driver->id,
        'email'     => $driver->email,
    ]);

    $updated = $driver->update([
        'current_latitude'    => $request->latitude,
        'current_longitude'   => $request->longitude,
        'last_location_update'=> now(),
    ]);

    event(new LocationUpdated([
        'type'       => 'driver',
        'id'         => $driver->id,
        'lat'        => (float) $request->latitude,
        'lng'        => (float) $request->longitude,
        'status'     => $driver->current_duty_status ?? 'off_duty',
        'name'       => trim($driver->firstname . ' ' . $driver->lastname),
        'updated_at' => now()->toIso8601String(),
    ]));

    // Broadcast live location to customer and driver apps via shipment channel
    $activeShipment = \App\Models\Shipments\Shipment::where('driver_id', $driver->id)
        ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
        ->latest('updated_at')
        ->first();

    if ($activeShipment) {
        // Inject fresh coordinates so ShipmentRealtimeUpdated payload includes them
        $driver->current_latitude  = $request->latitude;
        $driver->current_longitude = $request->longitude;
        $activeShipment->setRelation('driver', $driver);
        event(new ShipmentRealtimeUpdated('location_updated', $activeShipment));
    }

    Log::info('Location update result', [
        'success'     => $updated,
        'new_lat'     => $driver->fresh()->current_latitude,
        'new_lng'     => $driver->fresh()->current_longitude,
    ]);

    return response()->json([
        'message' => 'Location updated successfully.',
        'updated' => $updated,
    ]);
}
    public function updateShipmentStatus(Request $request)
    {
        $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'status' => 'required|in:pending,assigned,picked_up,in_transit,delivered,cancelled,active,complete,cancel',
        ]);

        // Update shipment status with fresh data
        $shipment = Shipment::with('customer')->findOrFail($request->shipment_id);
        $shipment->status = $request->status;
        $shipment->save();
        event(new ShipmentRealtimeUpdated('status_updated', $shipment));

        if ($shipment->driver_id) {
            $assignedDriver = Driver::find($shipment->driver_id);
            if ($assignedDriver) {
                ExpoNotificationService::notifyShipmentUpdated($assignedDriver, $shipment, $shipment->status);
            }
        }

        // Update all related notifications
        $this->updateShipmentNotifications($shipment->id, $request->status);

        // Create new notification with updated status
        $activeUsers = User::where('status', 'active')->get();
        foreach ($activeUsers as $user) {
            $notification = new NewShipmentNotification($shipment);
            $notification->isStatusUpdate = true; // Mark as status update
            $user->notify($notification);
        }

        return response()->json([
            'message' => 'Shipment status updated successfully.',
            'shipment' => $shipment,
            'status' => $shipment->status
        ]);
    }
    public function getDutyStatus()
    {
        $driver = Auth::user();
        return response()->json([
            'current_duty_status' => $driver->current_duty_status ?? 'off_duty',
        ]);
    }

    public function updateDutyStatus(Request $request)
    {
        Log::info('updateDutyStatus hit', [
            'body' => $request->all(),
            'driver_id' => Auth::id(),
        ]);

        $request->validate([
            'duty_status' => 'required|in:off_duty,sleeper,driving,on_duty_not_driving',
        ]);

        $driver = Auth::user();

        Log::info('updateDutyStatus driver', [
            'class'  => get_class($driver),
            'id'     => $driver->id,
            'table'  => $driver->getTable(),
            'before' => $driver->current_duty_status,
        ]);

        $driver->update([
            'current_duty_status' => $request->duty_status,
        ]);

        $fresh = $driver->fresh();

        Log::info('updateDutyStatus after', [
            'after' => $fresh->current_duty_status,
        ]);

        event(new DriverStatusUpdated($fresh));

        return response()->json([
            'message' => 'Duty status updated.',
            'current_duty_status' => $fresh->current_duty_status,
        ]);
    }

    protected function updateShipmentNotifications($shipmentId, $status)
    {
        DB::table('notifications')
            ->where('type', NewShipmentNotification::class)
            ->where('data->shipment_id', $shipmentId)
            ->update([
                'data->shipment_status' => $status,
                'updated_at' => now()
            ]);
    }

    public function getComments($id)
    {
        // 1) fetch shipment, ensure it belongs to the logged‑in driver
        $shipment = Shipment::where('id', $id)
            ->with(['remarks.commenter'])
            ->findOrFail($id);

        // 2) transform for consistency
        $comments = $shipment->remarks->map(function ($remark) {
            $name = match ($remark->commenter_type) {
                Driver::class  => trim(($remark->commenter->firstname ?? '') . ' ' . ($remark->commenter->lastname ?? '')),
                'App\Models\Customers\Customer' => trim(($remark->commenter->first_name ?? '') . ' ' . ($remark->commenter->last_name ?? '')),
                default        => trim(($remark->commenter->firstname ?? $remark->commenter->first_name ?? '') . ' ' . ($remark->commenter->lastname ?? $remark->commenter->last_name ?? '')),
            };

            return [
                'id'             => $remark->id,
                'commenter_id'   => $remark->commenter_id,
                'commenter_type' => $remark->commenter_type,
                'commenter_name' => $name,
                'comments'       => $remark->comments,
                'created_at'     => $remark->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success'  => true,
            'comments' => $comments,
        ]);
    }

    // POST /api/driver/shipments/{id}/comments
    public function postComment(Request $request, $id)
    {
        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $remark = Remarks::create([
            'commenter_id' => Auth::id(),
            'shipment_id' => $id,
            'commenter_type' => Driver::class,
            'comments' => $request->comments,
        ]);

        $remark->load('commenter');

        return response()->json([
            'success' => true,
            'message' => 'Comment added',
            'redirect_url' => url()->previous(), // or specific route like route('shipments.show', $id)
            'comment' => [
                'id' => $remark->id,
                'commenter_id' => $remark->commenter_id,
                'commenter_name' => ($remark->commenter->firstname ?? '') . ' ' . ($remark->commenter->lastname ?? ''),
                'comments' => $remark->comments,
                'created_at' => $remark->created_at->toDateTimeString(),
            ],
        ], 201);
    }


    /**
     * Get chat history for driver
     */

    /**
     * Get ConnectyCube application-level token
     */
    private function getAppLevelToken(\GuzzleHttp\Client $client): string
    {
        try {
            $response = $client->post('https://api.connectycube.com/session', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'application_id' => config('services.connectycube.app_id'),
                    'auth_key' => config('services.connectycube.auth_key'),
                    'nonce' => rand(),
                    'timestamp' => time(),
                    'signature' => $this->generateAppSignature(),
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['session']['token'] ?? '';
        } catch (\Exception $e) {
            Log::error('Failed to get ConnectyCube app token', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to authenticate with chat service');
        }
    }
    

    /**
     * Generate signature for ConnectyCube auth
     */
    private function generateAppSignature(): string
    {
        $nonce = rand();
        $timestamp = time();
        $authSecret = config('services.connectycube.auth_secret');

        return hash_hmac(
            'sha1',
            "application_id=" . config('services.connectycube.app_id') .
                "&auth_key=" . config('services.connectycube.auth_key') .
                "&nonce=$nonce&timestamp=$timestamp",
            $authSecret
        );
    }



    // Add this method to your DriverAuthController

    private $credentials = [];

    /**
     * Get all admin users for chat
     */
    public function getAdminUsers()
    {
        try {
            $admins = $this->adminUsersQuery()->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'connectycube_id',
                'connectycube_login'
            ])
                ->whereNotNull('connectycube_id')
                ->get();

            $chatUsers = $admins->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'connectycube_id' => (int)$admin->connectycube_id,
                    'name' => trim($admin->first_name . ' ' . $admin->last_name),
                    'login' => $admin->connectycube_login,
                    'email' => $admin->email,
                    'role' => 'admin',
                    'avatar' => $this->generateAvatar($admin->first_name, $admin->last_name)
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $chatUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get admin users for chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users'
            ], 500);
        }
    }

    /**
     * Get ConnectyCube session token for current user
     */
    public function getSessionToken()
    {
        try {
            $user = Auth::user();

            if (!$user->connectycube_id || !$user->connectycube_login) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not synced with ConnectyCube'
                ], 400);
            }
            if (empty($user->connectycube_password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat credentials are incomplete for this user'
                ], 400);
            }

            // Use the same login/password flow that works for other chat API calls.
            $token = $this->getConnectyCubeToken();
            $firstName = $user->first_name ?? $user->firstname ?? '';
            $lastName = $user->last_name ?? $user->lastname ?? '';

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => (int) $user->connectycube_id,
                    'name' => trim($firstName . ' ' . $lastName),
                    'login' => $user->connectycube_login
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get ConnectyCube session token: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'connectycube_login' => Auth::user()->connectycube_login ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat session'
            ], 500);
        }
    }

    /**
     * Get chat dialogs for current user
     */
    public function getDialogs()
    {
        try {
            $token = $this->getConnectyCubeToken();
            $client = new Client();

            $response = $client->get('https://api.connectycube.com/chat/dialogs', [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                return response()->json([
                    'success' => true,
                    'dialogs' => $result['items'] ?? []
                ]);
            }

            throw new \Exception('Failed to fetch dialogs');
        } catch (\Exception $e) {
            Log::error('Failed to get chat dialogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations'
            ], 500);
        }
    }

    /**
     * Get messages for a specific dialog
     */
    public function getMessages(Request $request)
    {
        try {
            $request->validate([
                'dialog_id' => 'required|string'
            ]);

            $token = $this->getConnectyCubeToken();
            $client = new Client();

            $response = $client->get("https://api.connectycube.com/chat/Message", [ // ✅ Fixed URL
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'chat_dialog_id' => $request->dialog_id,
                    'sort_desc' => 'date_sent',
                    'limit' => 50
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                return response()->json([
                    'success' => true,
                    'messages' => $result['items'] ?? []
                ]);
            }

            throw new \Exception('Failed to fetch messages');
        } catch (\Exception $e) {
            Log::error('Failed to get chat messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages'
            ], 500);
        }
    }
    /**
     * Create a new chat dialog
     */
    public function createDialog(Request $request)
    {
        // Force JSON response headers early
        header('Content-Type: application/json');

        try {
            // Log the incoming request
            Log::info('=== CREATE DIALOG REQUEST START ===');
            Log::info('Request data:', $request->all());
            Log::info('Request headers:', $request->headers->all());

            // Validate the request
            $validation = $request->validate([
                'occupant_ids' => 'required|array',
                'name' => 'string|max:255',
                'type' => 'in:1,2,3'
            ]);
            Log::info('Validation passed:', $validation);

            // Get token
            Log::info('Getting ConnectyCube token...');
            $token = $this->getConnectyCubeToken();
            Log::info('Token received:', ['token' => substr($token, 0, 20) . '...']);

            $client = new Client();
            $dialogData = [
                'type' => $request->type ?? 3,
                'occupants_ids' => $request->occupant_ids,
            ];

            if ($request->name) {
                $dialogData['name'] = $request->name;
            }

            Log::info('Dialog data to send:', $dialogData);
            Log::info('API endpoint: https://api.connectycube.com/chat/Dialog');

            // Make the API call
            $response = $client->post('https://api.connectycube.com/chat/Dialog', [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $dialogData
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            Log::info('ConnectyCube API Response:', [
                'status_code' => $statusCode,
                'response_body' => $responseBody
            ]);

            $result = json_decode($responseBody, true);

            if ($statusCode === 201) {
                $successResponse = [
                    'success' => true,
                    'dialog' => $result
                ];
                Log::info('Returning success response:', $successResponse);
                return response()->json($successResponse);
            }

            $errorResponse = [
                'success' => false,
                'message' => 'Failed to create dialog',
                'error' => $result,
                'status_code' => $statusCode
            ];
            Log::error('API call failed:', $errorResponse);
            return response()->json($errorResponse, 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $validationError = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
            Log::error('Validation failed:', $validationError);
            return response()->json($validationError, 422);
        } catch (\Exception $e) {
            $errorDetails = [
                'success' => false,
                'message' => 'Failed to create conversation',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            Log::error('=== CREATE DIALOG ERROR ===', $errorDetails);
            return response()->json($errorDetails, 500);
        } finally {
            Log::info('=== CREATE DIALOG REQUEST END ===');
        }
    }
    /**
     * Send a message
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'dialog_id' => 'required|string',
                'message' => 'required|string|max:5000',
                'recipient_id' => 'required|integer'
            ]);

            $token = $this->getConnectyCubeToken();
            $client = new Client();

            $messageData = [
                'chat_dialog_id' => $request->dialog_id,
                'message' => $request->message,
                'recipient_id' => $request->recipient_id,
                'send_to_chat' => 1
            ];

            // ✅ FIXED: Use /chat/Message (capital M)
            $response = $client->post('https://api.connectycube.com/chat/Message', [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $messageData
            ]);

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 201) {
                return response()->json([
                    'success' => true,
                    'message' => $result
                ]);
            }

            throw new \Exception('Failed to send message: ' . json_encode($result));
        } catch (\Exception $e) {
            Log::error('Failed to send chat message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    public function updateTypingStatus(Request $request)
    {
        $request->validate([
            'dialog_id' => 'required|string',
            'to_connectycube_id' => 'required|integer',
            'is_typing' => 'required|boolean',
        ]);

        $authUser = Auth::user();
        $fromConnectyCubeId = (int) ($authUser->connectycube_id ?? 0);

        if ($fromConnectyCubeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Current user is not linked with ConnectyCube'
            ], 400);
        }

        $toConnectyCubeId = (int) $request->to_connectycube_id;
        $key = $this->typingCacheKey($request->dialog_id, $fromConnectyCubeId, $toConnectyCubeId);

        if ($request->boolean('is_typing')) {
            Cache::put($key, [
                'from_connectycube_id' => $fromConnectyCubeId,
                'is_typing' => true,
                'updated_at' => now()->toIso8601String(),
            ], now()->addSeconds(8));
        } else {
            Cache::forget($key);
        }

        return response()->json(['success' => true]);
    }

    public function getTypingStatus(Request $request)
    {
        $request->validate([
            'dialog_id' => 'required|string',
            'peer_connectycube_id' => 'required|integer',
        ]);

        $authUser = Auth::user();
        $viewerConnectyCubeId = (int) ($authUser->connectycube_id ?? 0);
        $peerConnectyCubeId = (int) $request->peer_connectycube_id;

        if ($viewerConnectyCubeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Current user is not linked with ConnectyCube'
            ], 400);
        }

        $key = $this->typingCacheKey($request->dialog_id, $peerConnectyCubeId, $viewerConnectyCubeId);
        $typingPayload = Cache::get($key);

        return response()->json([
            'success' => true,
            'is_typing' => (bool) ($typingPayload['is_typing'] ?? false),
            'from_connectycube_id' => (int) ($typingPayload['from_connectycube_id'] ?? $peerConnectyCubeId),
            'updated_at' => $typingPayload['updated_at'] ?? null,
        ]);
    }

    public function getRealtimeConfig()
    {
        return response()->json([
            'success' => true,
            'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ]);
    }

    private function typingCacheKey(string $dialogId, int $fromConnectyCubeId, int $toConnectyCubeId): string
    {
        return "chat_typing:{$dialogId}:{$fromConnectyCubeId}:{$toConnectyCubeId}";
    }

    /**
     * Get current user's chat profile
     */
    public function getCurrentUser()
    {
        try {
            $user = Auth::user();

            if (!$user->connectycube_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not synced with ConnectyCube'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'connectycube_id' => (int)$user->connectycube_id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'login' => $user->connectycube_login,
                    'email' => $user->email,
                    'avatar' => $this->generateAvatar($user->first_name, $user->last_name)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user profile'
            ], 500);
        }
    }

    /**
     * Generate signature for ConnectyCube API
     */
    private function generateSignature($timestamp, $nonce)
    {
        $params = [
            'application_id' => (int)$this->credentials['app_id'],
            'auth_key' => $this->credentials['auth_key'],
            'timestamp' => (int)$timestamp,
            'nonce' => (int)$nonce,
        ];

        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string = rtrim($string, '&');

        return hash_hmac('sha1', $string, $this->credentials['auth_secret']);
    }

    /**
     * Get ConnectyCube token for API calls
     */
    private function getConnectyCubeToken()
    {
        $user = Auth::user();
        $client = new Client();
        $timestamp = time();
        $nonce = rand(1, 1000000);

        $sessionData = [
            'application_id' => (int)$this->credentials['app_id'],
            'auth_key' => $this->credentials['auth_key'],
            'timestamp' => (int)$timestamp,
            'nonce' => (int)$nonce,
            'signature' => $this->generateSignature($timestamp, $nonce),
            'user' => [
                'login' => $user->connectycube_login,  // ✅ Add login
                'password' => $user->connectycube_password ?? 'default_password'  // ✅ Add password
            ]
        ];

        $response = $client->post('https://api.connectycube.com/session', [
            'headers' => [
                'Content-Type' => 'application/json',
                'CB-API-Version' => '1.1'
            ],
            'json' => $sessionData
        ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 201 && isset($result['session']['token'])) {
            return $result['session']['token'];
        }

        throw new \Exception('Failed to get ConnectyCube token');
    }
    /**
     * Get all online users for chat by checking their last_request_at timestamp
     */
    public function getOnlineUsers()
    {
        try {
            // Step 1: Get all potential chat users (e.g., admins or all users)
            $admins = $this->adminUsersQuery()->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'connectycube_id',
                'connectycube_login'
            ])
                ->whereNotNull('connectycube_id')
                ->get();

            if ($admins->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'users' => []
                ]);
            }

            $connectyCubeIds = $admins->pluck('connectycube_id')->toArray();
            $token = $this->getConnectyCubeToken();
            $client = new Client();

            // Step 2: Fetch user details from ConnectyCube to get their online status
            // The 'last_request_at' field indicates the last time a user was active.
            // A recent timestamp (e.g., within the last few seconds) means they're online.
            $response = $client->get('https://api.connectycube.com/users', [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'filter[]' => "number id in " . implode(',', $connectyCubeIds),
                    'per_page' => count($connectyCubeIds)
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            $onlineThreshold = time() - 30; // 60 seconds is a common threshold for "online"

            $onlineUsers = collect($result['items'] ?? [])->filter(function ($user) use ($onlineThreshold) {
                // Check if last_request_at is set and is within the last 60 seconds
                return isset($user['user']['last_request_at']) && strtotime($user['user']['last_request_at']) >= $onlineThreshold;
            })->map(function ($user) {
                // Map the ConnectyCube user data back to your desired format
                $connectyCubeUser = $user['user'];
                $localUser = User::where('connectycube_id', $connectyCubeUser['id'])->first();

                if ($localUser) {
                    return [
                        'id' => $localUser->id,
                        'connectycube_id' => (int)$connectyCubeUser['id'],
                        'name' => trim($localUser->first_name . ' ' . $localUser->last_name),
                        'login' => $localUser->connectycube_login,
                        'email' => $localUser->email,
                        'role' => 'admin',
                        'avatar' => $this->generateAvatar($localUser->first_name, $localUser->last_name)
                    ];
                }
                return null;
            })->filter()->values(); // Remove any null values and re-index the array

            return response()->json([
                'success' => true,
                'users' => $onlineUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get online users for chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load online users'
            ], 500);
        }
    }
    /**
     * Generate avatar initials
     */
    private function generateAvatar($firstName, $lastName)
    {
        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
        return "https://ui-avatars.com/api/?name={$initials}&background=random&size=40";
    }

    private function adminUsersQuery()
    {
        return User::query()->where(function ($query) {
            $query->where('role_id', 23)
                ->orWhereHas('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', ['admin', 'super-admin']);
                });
        });
    }

    public function batchCheckStatus(Request $request)
    {
        try {
            $users = collect();

            if ($request->has('connectycube_ids')) {
                // Check by ConnectyCube IDs
                $connectycubeIds = $request->connectycube_ids;

                $drivers = Driver::whereIn('connectycube_id', $connectycubeIds)->get();
                $admins = $this->adminUsersQuery()
                    ->whereIn('connectycube_id', $connectycubeIds)
                    ->get();

                $users = $drivers->merge($admins);
            } elseif ($request->has('user_ids')) {
                // Check by local user IDs
                $userIds = $request->user_ids;

                $drivers = Driver::whereIn('id', $userIds)->get();
                $admins = $this->adminUsersQuery()->whereIn('id', $userIds)->get();

                $users = $drivers->merge($admins);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either user_ids or connectycube_ids must be provided'
                ], 400);
            }

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No users found',
                    'users' => []
                ]);
            }

            // Get online statuses
            $connectyCubeIds = $users->pluck('connectycube_id')->filter()->toArray();
            $onlineStatuses = $this->getConnectyCubeUserStatuses($connectyCubeIds);

            $usersWithStatus = $users->map(function ($user) use ($onlineStatuses) {
                $isOnline = isset($onlineStatuses[$user->connectycube_id]) ?
                    $onlineStatuses[$user->connectycube_id] : false;

                // Check if it's a driver or admin
                $isDriver = isset($user->firstname);

                return [
                    'id' => $user->id,
                    'connectycube_id' => (int)$user->connectycube_id,
                    'name' => $isDriver ?
                        trim($user->firstname . ' ' . $user->lastname) :
                        trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'role_type' => $isDriver ? 'driver' : 'admin',
                    'is_online' => $isOnline,
                    'status' => $isOnline ? 'online' : 'offline'
                ];
            });

            return response()->json([
                'success' => true,
                'total_checked' => $usersWithStatus->count(),
                'online_count' => $usersWithStatus->where('is_online', true)->count(),
                'users' => $usersWithStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Batch status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Batch status check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    private function getConnectyCubeUserStatuses(array $connectycubeIds)
    {
        try {
            if (empty($connectycubeIds)) {
                return [];
            }

            $token = $this->getConnectyCubeToken();
            $client = new \GuzzleHttp\Client();

            // Current time for comparison
            $currentTime = time();
            $onlineThreshold = 30; // 2 minutes threshold for "online"

            // Fetch users from ConnectyCube API
            $response = $client->get('https://api.connectycube.com/users', [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'filter[]' => 'number id in ' . implode(',', $connectycubeIds),
                    'per_page' => count($connectycubeIds)
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('ConnectyCube API request failed');
            }

            $result = json_decode($response->getBody(), true);
            $statuses = [];

            foreach ($result['items'] ?? [] as $item) {
                $user = $item['user'];
                $userId = (int)$user['id'];

                // Check if user was active recently
                $lastRequest = $user['last_request_at'] ?? null;
                $isOnline = false;

                if ($lastRequest) {
                    $lastRequestTime = strtotime($lastRequest);
                    $isOnline = ($currentTime - $lastRequestTime) <= $onlineThreshold;
                }

                $statuses[$userId] = $isOnline;
            }

            return $statuses;
        } catch (\Exception $e) {
            Log::error('Failed to get ConnectyCube statuses: ' . $e->getMessage());
            return array_fill_keys($connectycubeIds, false); // Return all as offline on error
        }
    }
    private function checkSingleUserStatus($connectycubeId)
    {
        try {
            $token = $this->getConnectyCubeToken();
            $client = new \GuzzleHttp\Client();

            $response = $client->get("https://api.connectycube.com/users/{$connectycubeId}", [
                'headers' => [
                    'CB-Token' => $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $result = json_decode($response->getBody(), true);
            $user = $result['user'] ?? null;

            if (!$user) {
                return false;
            }

            // Check last activity
            $lastRequest = $user['last_request_at'] ?? null;
            if (!$lastRequest) {
                return false;
            }

            $lastRequestTime = strtotime($lastRequest);
            $currentTime = time();
            $onlineThreshold = 30; // 2 minutes

            return ($currentTime - $lastRequestTime) <= $onlineThreshold;
        } catch (\Exception $e) {
            Log::error('Failed to check single user status: ' . $e->getMessage());
            return false;
        }
    }

    public function getUsersStatus()
    {

        try {
            // Get all drivers
            $drivers = Driver::select([
                'id',
                'firstname',
                'lastname',
                'email',
                'connectycube_id',
                'connectycube_login',
                'connectycube_password',
                'phoneno',
                DB::raw("'driver' as role_type")
            ])->whereNotNull('connectycube_id')->get();

            // Get all admins
            $admins = $this->adminUsersQuery()->select([
                'id',
                'first_name as firstname',
                'last_name as lastname',
                'email',
                'connectycube_id',
                'connectycube_login',
                'connectycube_password',
                DB::raw("'admin' as role_type")
            ])
                ->whereNotNull('connectycube_id')
                ->get();

            $allUsers = $drivers->merge($admins);

            if ($allUsers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No users found',
                    'users' => []
                ]);
            }

            // Get ConnectyCube user statuses
            $connectyCubeIds = $allUsers->pluck('connectycube_id')->filter()->toArray();
            $onlineStatuses = $this->getConnectyCubeUserStatuses($connectyCubeIds);

            // Map users with their online status
            $usersWithStatus = $allUsers->map(function ($user) use ($onlineStatuses) {
                $isOnline = isset($onlineStatuses[$user->connectycube_id]) ?
                    $onlineStatuses[$user->connectycube_id] : false;

                return [
                    'id' => $user->id,
                    'connectycube_id' => (int)$user->connectycube_id,
                    'name' => trim($user->firstname . ' ' . ($user->lastname ?? '')),
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname ?? '',
                    'role_type' => $user->role_type,
                    'is_online' => $isOnline,
                    'status' => $isOnline ? 'online' : 'offline',
                    'phone' => $user->phoneno ?? null
                ];
            });

            // Separate online and offline users
            $onlineUsers = $usersWithStatus->filter(fn($user) => $user['is_online'])->values();
            $offlineUsers = $usersWithStatus->filter(fn($user) => !$user['is_online'])->values();

            return response()->json([
                'success' => true,
                'total_users' => $usersWithStatus->count(),
                'online_count' => $onlineUsers->count(),
                'offline_count' => $offlineUsers->count(),
                'users' => $usersWithStatus,
                'online_users' => $onlineUsers,
                'offline_users' => $offlineUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get users status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get users status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register driver's Expo push notification token
     */
    public function registerPushToken(Request $request)
    {
        $request->validate([
            'expo_push_token' => ['required', 'string'],
        ]);

        $driver = Auth::user();

        if (!$driver) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $driver->update([
            'expo_push_token' => $request->expo_push_token,
            'last_push_token_update' => now(),
        ]);

        Log::info('Driver push token registered', [
            'driver_id' => $driver->id,
            'token' => substr($request->expo_push_token, 0, 10) . '...',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token registered successfully',
        ]);
    }
}
