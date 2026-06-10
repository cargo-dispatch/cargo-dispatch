<?php

namespace App\Http\Controllers\API;

use App\Events\NewShipmentCreated;
use App\Events\ShipmentRealtimeUpdated;
use App\Helpers\DistanceHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Role\Role;
use App\Models\Shipments\Shipment;
use App\Models\User;
use App\Models\VehicleType\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Notifications\NewShipmentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = Customer::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            if (!$user->is_active) {
                return response()->json(['message' => 'Your account has been deactivated. Please contact support.'], 403);
            }

            $token = $user->createToken('user-token')->plainTextToken;
            $vehicleType = VehicleType::all();


            return response()->json([
                'token' => $token,


                'user' => [
                    'id' => $user->id,
                    'firstname' => $user->first_name,
                    'lastname' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address1' => $user->address1,
                    'address2' => $user->address2,
                    'city' => $user->city,
                    'state' => $user->state,
                    'zip' => $user->zip,
                    'customer_title' => $user->customer_title,
                    'user_name' => $user->user_name,

                    'vehicleType' => $vehicleType,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Customer Login Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, $password) {
                $customer->password = Hash::make($password);
                $customer->setRememberToken(Str::random(60));
                $customer->save();
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 422);
    }

    public function createShipment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // Basic shipment validations
                'weight' => 'required|numeric|min:0.01|max:100000',
                'volume' => 'required|numeric|min:0.01|max:10000',
                'pallets' => 'nullable|integer|min:0|max:100',

                // Schedule validations
                'pickup_time' => 'required|date|after:now',
                'delivery_time' => 'required|date|after:pickup_time',

                // Address validations
                'pickup_address' => 'required|string|min:10|max:500',
                'drop_address' => 'required|string|min:10|max:500',

                // Vehicle and driver
                'vehicle_type_id' => 'required|exists:vehicle_types,id',
                'driver_id' => 'nullable|exists:drivers,id',

                // Additional info
                'equipment_required' => 'required|array|min:1',
                'equipment_required.*' => 'in:liftgate,hazmat,temperature_control',
                'special_instructions' => 'nullable|string|max:1000',
                'load_type' => 'nullable|in:FTL,LTL',
                'reference_number' => 'nullable|string|max:100',
                'pickup_contact_name' => 'nullable|string|max:100',
                'pickup_contact_phone' => 'nullable|string|max:30',
                'delivery_contact_name' => 'nullable|string|max:100',
                'delivery_contact_phone' => 'nullable|string|max:30',

                // Customer info
                'customer_id' => 'required|exists:customers,id',
                'created_by_type' => 'required|in:customer,admin',
                'created_by_id' => 'required|integer'
            ], [
                // Custom error messages
                'weight.required' => 'Weight is required',
                'weight.min' => 'Weight must be at least 0.01 kg',
                'volume.required' => 'Volume is required',
                'volume.min' => 'Volume must be at least 0.01 cubic feet',
                'pickup_time.required' => 'Pickup date and time is required',
                'pickup_time.after' => 'Pickup time must be in the future',
                'delivery_time.required' => 'Delivery date and time is required',
                'delivery_time.after' => 'Delivery time must be after pickup time',
                'pickup_address.required' => 'Pickup address is required',
                'pickup_address.min' => 'Pickup address must be at least 10 characters',
                'drop_address.required' => 'Drop address is required',
                'drop_address.min' => 'Drop address must be at least 10 characters',
                'vehicle_type_id.required' => 'Vehicle type is required',
                'vehicle_type_id.exists' => 'Selected vehicle type is invalid',
                'customer_id.required' => 'Customer is required',
                'customer_id.exists' => 'Selected customer is invalid'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Don't convert equipment_required - let Laravel handle it with cast
            // The array will be automatically converted to JSON by the model cast

            // Validate and clean addresses
            $fromAddress = trim($data['pickup_address']);
            $toAddress = trim($data['drop_address']);

            // Basic address validation
            if (strlen($fromAddress) < 10 || strlen($toAddress) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Addresses must be complete and detailed (minimum 10 characters)',
                    'errors' => [
                        'pickup_address' => strlen($fromAddress) < 10 ? ['Pickup address is too short'] : [],
                        'drop_address' => strlen($toAddress) < 10 ? ['Drop address is too short'] : []
                    ]
                ], 422);
            }

            // Get distance data with better error handling
            $distance = DistanceHelper::getDistanceFromGoogle($fromAddress, $toAddress);

            // Calculate estimated cost based on distance and other factors
            $estimatedCost = $this->calculateEstimatedCost($request, $distance);

            // Prepare shipment data
            $shipmentData = [
                'customer_id' => $request->customer_id,
                'vehicle_type_id' => $request->vehicle_type_id,
                'weight' => $request->weight,
                'volume' => $request->volume,
                'pallets' => $request->pallets ?? 0,
                'pickup_address' => $request->pickup_address,
                'drop_address' => $request->drop_address,
                'pickup_time' => $request->pickup_time,
                'delivery_time' => $request->delivery_time,
                'special_instructions' => $request->special_instructions,
                'driver_id' => $request->driver_id,
                'equipment_required'     => $request->equipment_required,
                'load_type'              => $request->load_type ?? 'FTL',
                'reference_number'       => $request->reference_number,
                'pickup_contact_name'    => $request->pickup_contact_name,
                'pickup_contact_phone'   => $request->pickup_contact_phone,
                'delivery_contact_name'  => $request->delivery_contact_name,
                'delivery_contact_phone' => $request->delivery_contact_phone,
                'deadhead_miles'         => $request->deadhead_miles ?: null,
                'detention_hours'        => $request->detention_hours ?: null,
                'lumper_fee'             => $request->lumper_fee ?: null,
                'per_diem_days'          => $request->per_diem_days ?: null,
                'scale_fees'             => $request->scale_fees ?: null,
                'tarp_required'          => $request->boolean('tarp_required'),
                'permit_fee'             => $request->permit_fee ?: null,
                'createdBy' => ucfirst($request->created_by_type),
                'estimated_cost' => $estimatedCost, // Add calculated estimated cost
                'status' => $request->status ?? 'pending',
            ];

            // Add distance data if available
            if ($distance) {
                $shipmentData['distance_km'] = $distance['kilometers'];
                $shipmentData['distance_miles'] = round($distance['kilometers'] * 0.621371, 2);
                $shipmentData['distance_text'] = $distance['text'];

                Log::info('Distance calculated for shipment', [
                    'pickup' => $fromAddress,
                    'drop' => $toAddress,
                    'distance' => $distance
                ]);
            } else {
                Log::warning('Could not calculate distance for shipment', [
                    'pickup' => $fromAddress,
                    'drop' => $toAddress
                ]);

                // Don't fail the shipment creation, just log it
                $shipmentData['distance_km'] = null;
                $shipmentData['distance_miles'] = null;
                $shipmentData['distance_text'] = 'Distance calculation failed';
            }

            // Create the shipment
            $shipment = Shipment::create($shipmentData);
            $shipment->load('customer', 'vehicleType');

            // Set flag for notification type
            $shipment->is_status_update = false;

            // Notify all active admin users
            $activeUsers = User::where('status', 'active')->get();

            foreach ($activeUsers as $user) {
                $notification = new NewShipmentNotification($shipment);
                $notification->isStatusUpdate = false;
                $user->notify($notification);
            }

            // Broadcast event
            $shipment->refresh();
            event(new NewShipmentCreated($shipment));
            event(new ShipmentRealtimeUpdated('created', $shipment));

            Log::info('Shipment created successfully', [
                'shipment_id' => $shipment->id,
                'customer_id' => $shipment->customer_id,
                'created_by' => $request->created_by_type,
                'estimated_cost' => $estimatedCost
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => $shipment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Shipment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Shipment creation failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

       public function updateShipment(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|string|in:pending,active,complete',
            'weight' => 'sometimes|required|numeric|min:0',
            'volume' => 'sometimes|required|numeric|min:0',
            'pallets' => 'nullable|integer|min:0',
            'pickup_time' => 'sometimes|required|date',
            'delivery_time' => 'sometimes|required|date|after:pickup_time',
            'pickup_address' => 'sometimes|required|string',
            'drop_address' => 'sometimes|required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'equipment_required' => 'nullable|array',
            'equipment_required.*' => 'string|in:liftgate,hazmat,temperature_control',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment->update($request->all());
        $shipment->refresh();
        event(new ShipmentRealtimeUpdated('updated', $shipment));

        return response()->json($shipment);
    }

    /**
     * Calculate estimated cost for a shipment
     */
    private function calculateEstimatedCost(Request $request, $distance)
    {
        try {
            // ── Distance ──────────────────────────────────────────────────────
            $distanceMiles = isset($distance['kilometers'])
                ? round($distance['kilometers'] * 0.621371, 2)
                : 0;

            if ($distanceMiles <= 0) return 500.00;

            // ── Rate per mile by vehicle type (US FTL industry standard) ──────
            $vehicleType = \App\Models\VehicleType\VehicleType::find($request->vehicle_type_id);
            $ratePerMile = [
                'van'           => 2.50,
                'dry van'       => 2.85,
                'box truck'     => 2.60,
                'refrigerated'  => 3.80,
                'reefer'        => 3.80,
                'flatbed'       => 3.20,
                'step deck'     => 3.50,
                'lowboy'        => 4.20,
                'tanker'        => 3.90,
                'container'     => 3.10,
            ];
            $rate = $vehicleType
                ? ($ratePerMile[strtolower($vehicleType->vehicle_type)] ?? 2.85)
                : 2.85;

            // ── Line haul ─────────────────────────────────────────────────────
            $lineHaul = $distanceMiles * $rate;

            // ── Fuel cost (real diesel price by state from FuelProvider) ──────
            $stateCode    = $this->extractStateCode($request->pickup_address ?? '');
            $fuelProvider = new \App\Services\Integrations\Providers\FuelProvider();
            $dieselPrice  = $fuelProvider->getStateDieselPrice($stateCode);
            $fuelCost     = ($distanceMiles / 6.5) * $dieselPrice;

            // ── Fuel surcharge (~28% of line haul) ────────────────────────────
            $fuelSurcharge = $lineHaul * 0.28;

            // ── Weight surcharge (over 40,000 lbs only) ───────────────────────
            $weightLbs       = (float) ($request->weight ?? 0) * 2.20462;
            $weightSurcharge = $weightLbs > 40000 ? ($weightLbs - 40000) * 0.002 : 0;

            // ── Pallet handling ───────────────────────────────────────────────
            $palletCost = (int) ($request->pallets ?? 0) * 15.00;

            // ── Equipment surcharges ──────────────────────────────────────────
            $equipmentSurcharge = $this->calculateEquipmentSurcharge($request->equipment_required);

            // ── Total with 12% broker margin ──────────────────────────────────
            $subtotal = $lineHaul + $fuelCost + $fuelSurcharge + $weightSurcharge + $palletCost + $equipmentSurcharge;
            $total    = round($subtotal * 1.12, 2);

            Log::info('Estimated cost calculated', [
                'distance_miles'     => $distanceMiles,
                'rate_per_mile'      => $rate,
                'line_haul'          => $lineHaul,
                'fuel_cost'          => $fuelCost,
                'fuel_surcharge'     => $fuelSurcharge,
                'weight_surcharge'   => $weightSurcharge,
                'pallet_cost'        => $palletCost,
                'equipment_surcharge'=> $equipmentSurcharge,
                'total'              => $total,
            ]);

            return max($total, 350.00);

        } catch (\Exception $e) {
            Log::error('Error calculating estimated cost: ' . $e->getMessage());
            return 500.00;
        }
    }

    private function extractStateCode(string $address): string
    {
        $stateNames = [
            'alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR',
            'california'=>'CA','colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE',
            'florida'=>'FL','georgia'=>'GA','hawaii'=>'HI','idaho'=>'ID',
            'illinois'=>'IL','indiana'=>'IN','iowa'=>'IA','kansas'=>'KS',
            'kentucky'=>'KY','louisiana'=>'LA','maine'=>'ME','maryland'=>'MD',
            'massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS',
            'missouri'=>'MO','montana'=>'MT','nebraska'=>'NE','nevada'=>'NV',
            'new hampshire'=>'NH','new jersey'=>'NJ','new mexico'=>'NM','new york'=>'NY',
            'north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH','oklahoma'=>'OK',
            'oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI','south carolina'=>'SC',
            'south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT',
            'vermont'=>'VT','virginia'=>'VA','washington'=>'WA','west virginia'=>'WV',
            'wisconsin'=>'WI','wyoming'=>'WY',
        ];

        if (preg_match('/\b([A-Z]{2})\b/', $address, $m)) {
            $code = strtoupper($m[1]);
            if (in_array($code, array_values($stateNames))) return $code;
        }

        $lower = strtolower($address);
        foreach ($stateNames as $name => $code) {
            if (str_contains($lower, $name)) return $code;
        }

        return 'default';
    }

    /**
     * Get rate multiplier based on vehicle type
     */
    private function getVehicleRateMultiplier($vehicleType)
    {
        $vehicleType = strtolower($vehicleType);

        $multipliers = [
            'van' => 1.0,
            'box truck' => 1.5,
            'refrigerated' => 2.0,
            'flatbed' => 1.8,
            'step deck' => 2.2,
            'lowboy' => 2.5,
            'tanker' => 2.8,
            'dry van' => 1.2,
            'reefer' => 2.0,
            'container' => 3.0,
        ];

        return $multipliers[$vehicleType] ?? 1.0;
    }

    /**
     * Calculate equipment surcharge
     */
    private function calculateEquipmentSurcharge($equipmentRequired)
    {
        $surcharge = 0;

        if (is_array($equipmentRequired)) {
            foreach ($equipmentRequired as $equipment) {
                switch ($equipment) {
                    case 'liftgate':
                        $surcharge += 125.00;
                        break;
                    case 'hazmat':
                        $surcharge += 300.00;
                        break;
                    case 'temperature_control':
                        $surcharge += 200.00;
                        break;
                }
            }
        }

        return $surcharge;
    }
 

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . auth()->id(),
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:255',
            'customer_title' => 'nullable|string|max:255',
            'user_name' => 'nullable|string|max:255',
        ]);

        $customer = auth()->user();
        $customer->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'customer' => $customer
        ], 200);
    }



    public function getShipment($id)
    {
        $shipment = Shipment::findOrFail($id);
        return response()->json($shipment);
    }

    public function getShipmentTracking($id)
    {
        $user = Auth::user();
        $shipment = Shipment::with([
            'vehicle.vehicleAssignments.driver:id,firstname,lastname,current_latitude,current_longitude,phoneno',
            'associatedDrivers.drivers:id,firstname,lastname,current_latitude,current_longitude,phoneno',
        ])->where('customer_id', $user->id)->findOrFail($id);

        $driverLat = null;
        $driverLng = null;
        $driverName = null;
        $driverPhone = null;

        if ($shipment->associatedDrivers && $shipment->associatedDrivers->isNotEmpty()) {
            $d = $shipment->associatedDrivers->first()->drivers;
            if ($d) {
                $driverLat = $d->current_latitude;
                $driverLng = $d->current_longitude;
                $driverName = trim($d->firstname . ' ' . $d->lastname);
                $driverPhone = $d->phoneno;
            }
        } elseif ($shipment->vehicle && $shipment->vehicle->vehicleAssignments && $shipment->vehicle->vehicleAssignments->isNotEmpty()) {
            $d = $shipment->vehicle->vehicleAssignments->first()->driver;
            if ($d) {
                $driverLat = $d->current_latitude;
                $driverLng = $d->current_longitude;
                $driverName = trim($d->firstname . ' ' . $d->lastname);
                $driverPhone = $d->phoneno;
            }
        }

        return response()->json([
            'id'               => $shipment->id,
            'status'           => $shipment->status,
            'driver_latitude'  => $driverLat,
            'driver_longitude' => $driverLng,
            'driver_name'      => $driverName,
            'driver_phone'     => $driverPhone,
        ]);
    }
    public function myShipments(Request $request)
    {
        $user = $request->user();

        $query = $user->shipments()->with([
            'vehicleType:id,vehicle_type',
            'vehicle:id,vehicle_id',
            'vehicle.vehicleAssignments.driver:id,firstname,lastname,current_latitude,current_longitude',
            'associatedDrivers.drivers:id,firstname,lastname,current_latitude,current_longitude',
        ]);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Eager load all necessary relationships including driver location
        $shipments = $query
            ->latest()
            ->get()
            ->map(function ($shipment) use ($user) {
                // Get driver info from vehicle assignments (if vehicle is assigned to a driver)
                $vehicleDriverName = null;
                $vehicleDriverId = null;
                $vehicleDriverLat = null;
                $vehicleDriverLng = null;

                if ($shipment->vehicle && $shipment->vehicle->vehicleAssignments && $shipment->vehicle->vehicleAssignments->isNotEmpty()) {
                    $firstAssignment = $shipment->vehicle->vehicleAssignments->first();
                    if ($firstAssignment->driver) {
                        $vehicleDriverName = trim($firstAssignment->driver->firstname . ' ' . $firstAssignment->driver->lastname);
                        $vehicleDriverId = $firstAssignment->driver_id;
                        $vehicleDriverLat = $firstAssignment->driver->current_latitude;
                        $vehicleDriverLng = $firstAssignment->driver->current_longitude;
                    }
                }

                // Get driver info from associated drivers (first one)
                $associatedDriverName = null;
                $associatedDriverId = null;
                $associatedDriverLat = null;
                $associatedDriverLng = null;

                if ($shipment->associatedDrivers && $shipment->associatedDrivers->isNotEmpty()) {
                    $firstDriver = $shipment->associatedDrivers->first();
                    if ($firstDriver->drivers) {
                        $associatedDriverName = trim($firstDriver->drivers->firstname . ' ' . $firstDriver->drivers->lastname);
                        $associatedDriverId = $firstDriver->driver_id;
                        $associatedDriverLat = $firstDriver->drivers->current_latitude;
                        $associatedDriverLng = $firstDriver->drivers->current_longitude;
                    }
                }

                // Priority: Associated driver > Vehicle assigned driver
                $driverName = $associatedDriverName ?? $vehicleDriverName;
                $driverId = $associatedDriverId ?? $vehicleDriverId;
                $driverLatitude = $associatedDriverLat ?? $vehicleDriverLat;
                $driverLongitude = $associatedDriverLng ?? $vehicleDriverLng;

                return [
                    'id' => $shipment->id,
                    'customer_id' => $shipment->customer_id,
                    'driver_id' => $driverId,
                    'vehicle_id' => $shipment->vehicle_id,
                    'vehicle_type_id' => $shipment->vehicle_type_id,
                    'pickup_address' => $shipment->pickup_address,
                    'drop_address' => $shipment->drop_address,
                    'pickup_time' => $shipment->pickup_time,
                    'delivery_time' => $shipment->delivery_time,
                    'weight' => $shipment->weight,
                    'volume' => $shipment->volume,
                    'vehicle_number' => $shipment->vehicle ? $shipment->vehicle->vehicle_id : null,
                    'estimated_cost' => $shipment->estimated_cost,
                    'pallets' => $shipment->pallets,
                    'vehicle_type' => $shipment->vehicleType ? $shipment->vehicleType->vehicle_type : null,
                    'special_instructions' => $shipment->special_instructions,
                    'customer_name' => $user->first_name . ' ' . $user->last_name,
                    'status' => $shipment->status,
                    'equipment_required' => $shipment->equipment_required,
                    'created_at' => $shipment->created_at,
                    'updated_at' => $shipment->updated_at,
                    'driver_name' => $driverName,
                    'driver_latitude' => $driverLatitude,
                    'driver_longitude' => $driverLongitude,
                    'deleted_at' => $shipment->deleted_at,
                    'createdBy' => $shipment->createdBy,
                    'distance_km' => $shipment->distance_km,
                    'distance_miles' => $shipment->distance_miles,
                    'distance_text' => $shipment->distance_text,
                    // Include all associated drivers with their locations
                    'associated_drivers' => $shipment->associatedDrivers->map(function ($associatedDriver) {
                        return [
                            'driver_id' => $associatedDriver->driver_id,
                            'driver_name' => $associatedDriver->drivers ?
                                trim($associatedDriver->drivers->firstname . ' ' . $associatedDriver->drivers->lastname) :
                                null,
                            'driver_latitude' => $associatedDriver->drivers?->current_latitude,
                            'driver_longitude' => $associatedDriver->drivers?->current_longitude,
                            'created_at' => $associatedDriver->created_at,
                        ];
                    }),
                    // Include vehicle assignments info with driver locations
                    'vehicle_assignments' => $shipment->vehicle ?
                        $shipment->vehicle->vehicleAssignments->map(function ($assignment) {
                            return [
                                'driver_id' => $assignment->driver_id,
                                'driver_name' => $assignment->driver ?
                                    trim($assignment->driver->firstname . ' ' . $assignment->driver->lastname) :
                                    null,
                                'driver_latitude' => $assignment->driver?->current_latitude,
                                'driver_longitude' => $assignment->driver?->current_longitude,
                                'assigned_at' => $assignment->created_at,
                            ];
                        }) : [],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $shipments->values(),
        ]);
    }
    public function getComments($id)
    {
        // Find the shipment belonging to the logged-in customer with remarks and commenter info
        $shipment = Shipment::where('customer_id', Auth::id())
            ->with([
                'remarks' => function ($query) {
                    $query->with([
                        'commenter' => function ($q) {
                            $q->select('id', 'first_name', 'last_name');
                        }
                    ])
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->findOrFail($id);  // only one shipment by ID

        // Transform remarks to a consistent structure
        $comments = $shipment->remarks->map(function ($remark) {
            return [
                'id' => $remark->id,
                'commenter_id' => $remark->commenter_id,
                'commenter_type' => $remark->commenter_type,
                'comments' => $remark->comments,
                'created_at' => $remark->created_at,
                'updated_at' => $remark->updated_at,
                'commenter_name' => $remark->commenter
                    ? ($remark->commenter->first_name . ' ' . $remark->commenter->last_name)
                    : 'Unknown'
            ];
        });

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    public function postComment(Request $request, $id)
    {

        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        // Ensure customer can only comment on their own shipments
        $shipment = Shipment::where('customer_id', Auth::id())->findOrFail($id);

        $remark = $shipment->remarks()->create([
            'commenter_id' => Auth::id(),
            'commenter_type' => \App\Models\Customers\Customer::class,
            'comments' => $request->comments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => $remark->load('commenter'),
        ]);
    }
    public function deleteShipment($id)
    {
        try {
            // Get the authenticated customer
            $customer = Auth::user();

            // Find the shipment
            $shipment = Shipment::where('id', $id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$shipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found or you do not have permission to delete it'
                ], 404);
            }

            // Check if shipment can be deleted (only allow deletion of pending shipments)
            if (!in_array($shipment->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending or confirmed shipments can be deleted'
                ], 400);
            }

            // Soft delete the shipment
            $shipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shipment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete shipment: ' . $e->getMessage()
            ], 500);
        }
    }
}
