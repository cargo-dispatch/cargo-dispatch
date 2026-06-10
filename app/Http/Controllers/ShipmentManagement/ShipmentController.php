<?php

namespace App\Http\Controllers\ShipmentManagement;

use App\Events\ShipmentChanged;
use App\Events\ShipmentRealtimeUpdated;
use App\Helpers\AddressValidationHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Remarks\Remarks;
use App\Models\Shipments\Shipment;
use App\Models\ShipmentDocuments\ShipmentDocument;
use App\Models\VehicleType\VehicleType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Notifications\DatabaseNotification;
use App\Helpers\DistanceHelper;
use App\Http\Controllers\ShipmentInvoiceController;
use Spatie\SimpleExcel\SimpleExcelWriter;

use App\Models\Drivers\Driver;
use App\Models\Vehicles\Vehicle;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Services\ShipmentInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ShipmentController extends Controller
{
    public function index()
    {
        $data = [
            'name'          => 'Shipments',
            'filter_status' => null,
            'total'         => Shipment::count(),
            'active'        => Shipment::whereIn('status', ['active', 'assigned', 'picked_up', 'in_transit'])->count(),
            'delivered'     => Shipment::where('status', 'delivered')->count(),
            'pending'       => Shipment::where('status', 'pending')->count(),
        ];

        return view('shipment.index', $data);
    }

    public function completedShipments()
    {
        $data = [
            'name'          => 'Completed Shipments',
            'filter_status' => 'delivered',
            'total'         => Shipment::where('status', 'delivered')->count(),
            'active'        => Shipment::whereIn('status', ['active', 'assigned', 'picked_up', 'in_transit'])->count(),
            'delivered'     => Shipment::where('status', 'delivered')->count(),
            'pending'       => Shipment::where('status', 'pending')->count(),
        ];

        return view('shipment.index', $data);
    }

    public function shipmentReport()
    {

       
        $customers = Customer::select('id', 'customer_title')->orderBy('customer_title')->get();
        $vehicleTypes = VehicleType::select('id', 'vehicle_type')->orderBy('vehicle_type')->get();
        $vehicles = Vehicle::select('id','vehicle_id')->get();


        $name = 'Shipment Reports';

        return view('shipment.report', compact('name', 'customers', 'vehicleTypes', 'vehicles'));
    }
    public function getShipments(Request $request)
    {
       

        $perPage    = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');
        $status     = $request->input('status', '');
        $dateFrom   = $request->input('date_from', '');
        $dateTo     = $request->input('date_to', '');

        $query = Shipment::with(['customer', 'vehicleType', 'drivers', 'vehicle.vehicleAssignment.driver', 'associatedDrivers.drivers'])
            ->orderBy('created_at', 'desc');

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('status', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('pickup_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('drop_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('pickup_time', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('delivery_time', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                        $cq->where('customer_title', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('vehicleType', function ($vq) use ($searchTerm) {
                        $vq->where('vehicle_type', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('pickup_time', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('pickup_time', '<=', $dateTo);
        }

        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            // Resolve driver name: direct driver_id → drivers, then vehicle assignment, then associated drivers
            $driverName = null;
            if ($user->drivers) {
                $driverName = trim($user->drivers->firstname . ' ' . $user->drivers->lastname);
            } elseif ($user->vehicle && $user->vehicle->vehicleAssignment && $user->vehicle->vehicleAssignment->driver) {
                $d = $user->vehicle->vehicleAssignment->driver;
                $driverName = trim($d->firstname . ' ' . $d->lastname);
            } elseif ($user->associatedDrivers->isNotEmpty()) {
                $d = $user->associatedDrivers->first()->drivers;
                if ($d) $driverName = trim($d->firstname . ' ' . $d->lastname);
            }
            $user->driver_name = $driverName;

            $user->actions = [
                'edit' => route('shipments.edit', $user->id),
                'delete' => route('shipments.destroy', $user->id),
            ];

            return $user;
        });

        return response()->json($users);
    }

    public function getCompletedShipments(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');

        $query = Shipment::with(['customer', 'vehicleType', 'documents.driver'])
            ->whereIn('status', ['delivered', 'complete'])
            ->orderBy('created_at', 'desc');
         
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('status', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('pickup_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('drop_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('pickup_time', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('delivery_time', 'LIKE', '%' . $searchTerm . '%')

                    // 🔍 Search in related customers table
                    ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                        $cq->where('customer_title', 'LIKE', '%' . $searchTerm . '%');
                    })

                    // 🔍 Search in related vehicle_types table
                    ->orWhereHas('vehicleType', function ($vq) use ($searchTerm) {
                        $vq->where('vehicle_type', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            // Map documents with properly formatted OCR data
            $user->documents = $user->documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type,
                    'driver_name' => $doc->driver ? $doc->driver->firstname . ' ' . $doc->driver->lastname : 'N/A',
                    'file_url' => asset('storage/' . $doc->file_path),
                    'ocr_status' => $doc->extraction_status,
                    'ocr_confidence' => $doc->extraction_confidence,
                    'extracted_fields' => $doc->extracted_fields,
                    'uploaded_at' => \Carbon\Carbon::parse($doc->created_at)->format('M d, Y h:i A'),
                ];
            })->toArray();
            
            $user->actions = [
                'edit' => route('shipments.edit', $user->id),
                'delete' => route('shipments.destroy', $user->id),
            ];

            return $user;
        });

        return response()->json($users);
    }

    public function getComments($id)
    {
        $shipment = Shipment::findOrFail($id);

        $comments = $shipment->remarks()->latest()->get()->map(function ($remark) {
            // Manually resolve the name if commenter_type is 'dispatcher'
            $authorName = 'Unknown';
            if ($remark->commenter_type === 'dispatcher') {
                $user = \App\Models\User::find($remark->commenter_id);
                $authorName = $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
            } else {
                $authorName = optional($remark->commenter)->name ?? 'Unknown';
            }

            return [
                'text' => $remark->comments,
                'created_at' => $remark->created_at->diffForHumans(),
                'author_id' => $remark->commenter_id,
                'author_type' => class_basename($remark->commenter_type),
                'author_name' => $authorName
            ];
        });

        return response()->json(['comments' => $comments]);
    }


    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $shipment = Shipment::findOrFail($id);

        $remark = new Remarks();
        $remark->shipment_id = $shipment->id;
        $remark->comments = $request->input('text');
        $remark->commenter_id = Auth::id();             // User ID
        $remark->commenter_type = 'dispatcher';         // 👈 custom alias
        $remark->created_at = Carbon::now(config('app.timezone'));

        $remark->save();

        return response()->json(['message' => 'Comment added']);
    }

    public function show($id)
    {
      
     
        $shipment = Shipment::with(['customer', 'vehicleType', 'audits.user'])->findOrFail($id);

        // Fetch documents for this shipment
        $documents = ShipmentDocument::where('shipment_id', $shipment->id)
            ->with(['driver'])
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type,
                    'driver_name' => $doc->driver ? $doc->driver->firstname . ' ' . $doc->driver->lastname : 'N/A',
                    'file_url' => asset('storage/' . $doc->file_path),
                    'ocr_status' => $doc->extraction_status,
                    'ocr_confidence' => $doc->extraction_confidence,
                    'extracted_fields' => $doc->extracted_fields,
                    'uploaded_at' => Carbon::parse($doc->created_at)->format('M d, Y h:i A'),
                ];
            });

        $audits = $shipment->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });

        $nameMappings = [];

        $customers = Customer::pluck('customer_title', 'id')->toArray();
        if (!empty($customers)) {
            $nameMappings['customer_id'] = $customers;
        }
        

        $vehicleTypes = VehicleType::pluck('vehicle_type', 'id')->toArray();
        if (!empty($vehicleTypes)) {
            $nameMappings['vehicle_type_id'] = $vehicleTypes;
        }
      

        return response()->json([
            'id' => $shipment->id,
            'customer' => [
                'customer_name' => $shipment->customer ? trim($shipment->customer->first_name . ' ' . $shipment->customer->last_name) : null,
                'customer_title' => $shipment->customer?->customer_title,
            ],
            'vehicle_type' => [
                'vehicle_type' => $shipment->vehicleType?->vehicle_type,
            ],
            'pickup_address' => $shipment->pickup_address,
            'drop_address' => $shipment->drop_address,
            'pickup_time' => $shipment->pickup_time ? Carbon::parse($shipment->pickup_time)->format('D, M d Y / h:i A') : null,
            'delivery_time' => $shipment->delivery_time ? Carbon::parse($shipment->delivery_time)->format('D, M d Y / h:i A') : null,
            'estimated_cost' => $shipment->estimated_cost ? '$' . $shipment->estimated_cost : '$0',

            // Add distance information
            'distance_km' => $shipment->distance_km,
            'distance_miles' => $shipment->distance_miles,
            'weight' => $shipment->weight,
            'volume' => $shipment->volume,
            'pallets' => $shipment->pallets,
            'special_instructions' => $shipment->special_instructions,
            'status'                 => $shipment->status,
            'load_type'              => $shipment->load_type,
            'reference_number'       => $shipment->reference_number,
            'pickup_contact_name'    => $shipment->pickup_contact_name,
            'pickup_contact_phone'   => $shipment->pickup_contact_phone,
            'delivery_contact_name'  => $shipment->delivery_contact_name,
            'delivery_contact_phone' => $shipment->delivery_contact_phone,

            // Documents
            'documents' => $documents,

            // Optional fields
            'distance_km' => $shipment->distance_km,
            'distance_miles' => $shipment->distance_miles,
            'audits' => $audits,
            'name_mappings' => $nameMappings,

            'audits' => $audits,
            'name_mappings' => $nameMappings,
        ]);
    }
    public function calculateDistance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_address' => 'required|string',
            'drop_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $distanceData = DistanceHelper::getDistanceFromGoogle(
                $request->pickup_address,
                $request->drop_address
            );

            if ($distanceData) {
                return response()->json([
                    'success' => true,
                    'distance_km' => $distanceData['kilometers'],
                    'distance_miles' => round($distanceData['kilometers'] * 0.621371, 2),
                    'distance_text' => $distanceData['text']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to calculate distance'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating distance: ' . $e->getMessage()
            ], 500);
        }
    }


    public function create()
    {
        $settings = \App\Models\GeneralSetting\GeneralSetting::first();

        $data = [
            'customers'    => Customer::all(),
            'vehicleTypes' => VehicleType::all(),
            'fuelPrice'    => $settings->fuel_price ?? 3.80,
            'profitPct'    => $settings->company_profit ?? 20,
            'name'         => 'Shipment',
        ];

        return view('shipment.create', $data);
    }
  public function edit($id)
{
    // Fetch the shipment
    $shipment = Shipment::findOrFail($id);
    
    // Get user's timezone (you might store this in user settings)
    // For now, we'll use the application's timezone or UTC
    $userTimezone = config('app.timezone', 'UTC');
    
    // Convert pickup_time from UTC to user's timezone
    if ($shipment->pickup_time) {
        $shipment->pickup_time_local = \Carbon\Carbon::parse($shipment->pickup_time)
            ->timezone($userTimezone)
            ->format('Y-m-d\TH:i');
    } else {
        $shipment->pickup_time_local = null;
    }
    
    // Convert delivery_time from UTC to user's timezone
    if ($shipment->delivery_time) {
        $shipment->delivery_time_local = \Carbon\Carbon::parse($shipment->delivery_time)
            ->timezone($userTimezone)
            ->format('Y-m-d\TH:i');
    } else {
        $shipment->delivery_time_local = null;
    }
    
    $settings     = \App\Models\GeneralSetting\GeneralSetting::first();
    $customers    = Customer::all();
    $vehicleTypes = VehicleType::all();

    return view('shipment.create', [
        'shipment'     => $shipment,
        'customers'    => $customers,
        'vehicleTypes' => $vehicleTypes,
        'fuelPrice'    => $settings->fuel_price ?? 3.80,
        'profitPct'    => $settings->company_profit ?? 20,
        'name'         => 'Shipment',
    ]);
}

    public function store(Request $request)
    {
      

        $equipment = $request->input('equipment_required', []);

        // If it's not an array, make it one
        if (!is_array($equipment)) {
            $equipment = [$equipment];
        }

        // Merge it back into the request
        $request->merge(['equipment_required' => $equipment]);

        $validator = Validator::make($request->all(), [
            'customer_id'           => 'required|exists:customers,id',
            'vehicle_type_id'       => 'required|exists:vehicle_types,id',
            'weight'                => 'required|numeric|min:0',
            'volume'                => 'required|numeric|min:0',
            'pallets'               => 'required|integer|min:1',

            'pickup_address'        => 'required|string|max:500',
            'drop_address'          => 'required|string|max:500',
            'pickup_time'           => 'required|date',
            'delivery_time'         => 'required|date|after_or_equal:pickup_time',
            'special_instructions'  => 'nullable|string|max:1000',

            'equipment_required'      => 'required|array|min:1',
            'equipment_required.*'    => 'in:liftgate,hazmat,temperature_control',
            'load_type'               => 'required|in:FTL,LTL',
            'reference_number'        => 'nullable|string|max:100',
            'pickup_contact_name'     => 'nullable|string|max:100',
            'pickup_contact_phone'    => 'nullable|string|max:30',
            'delivery_contact_name'   => 'nullable|string|max:100',
            'delivery_contact_phone'  => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Log addresses for debugging
            Log::info('Attempting to create shipment', [
                'pickup_address' => $request->pickup_address,
                'drop_address' => $request->drop_address
            ]);

            // Calculate distance using helper
            $distanceData = DistanceHelper::getDistanceFromGoogle(
                $request->pickup_address,
                $request->drop_address
            );

            // Log the result safely
            if ($distanceData === null) {
                Log::warning('Distance calculation returned null', [
                    'pickup' => $request->pickup_address,
                    'drop' => $request->drop_address
                ]);
            } else {
                Log::info('Distance calculation successful', $distanceData);
            }

            $shipment = Shipment::create([
                'customer_id'               => $request->customer_id,
                'vehicle_type_id'           => $request->vehicle_type_id,
                'weight'                    => $request->weight,
                'volume'                    => $request->volume,
                'pallets'                   => $request->pallets,
                'pickup_address'            => $request->pickup_address,
                'drop_address'              => $request->drop_address,
                'pickup_time'               => $request->pickup_time,
                'delivery_time'             => $request->delivery_time,
                'special_instructions'      => $request->special_instructions,
                'estimated_cost'            => 0,

                'requires_liftgate'         => in_array('liftgate', $request->equipment_required),
                'is_hazmat'                 => in_array('hazmat', $request->equipment_required),
                'temperature_controlled'    => in_array('temperature_control', $request->equipment_required),
                'equipment_required'        => $request->equipment_required,
                'load_type'                 => $request->load_type ?? 'FTL',
                'reference_number'          => $request->reference_number,
                'pickup_contact_name'       => $request->pickup_contact_name,
                'pickup_contact_phone'      => $request->pickup_contact_phone,
                'delivery_contact_name'     => $request->delivery_contact_name,
                'delivery_contact_phone'    => $request->delivery_contact_phone,

                'deadhead_miles'            => $request->deadhead_miles ?: null,
                'detention_hours'           => $request->detention_hours ?: null,
                'lumper_fee'                => $request->lumper_fee ?: null,
                'per_diem_days'             => $request->per_diem_days ?: null,
                'scale_fees'                => $request->scale_fees ?: null,
                'tarp_required'             => $request->boolean('tarp_required'),
                'permit_fee'                => $request->permit_fee ?: null,

                'distance_km'               => $distanceData['kilometers'] ?? null,
                'distance_miles'            => $distanceData ? round($distanceData['kilometers'] * 0.621371, 2) : null,
                'distance_text'             => $distanceData['text'] ?? null,
            ]);

            $tollsFee = (float) ($request->tolls_fee ?? 0);
            $invoiceService = new ShipmentInvoiceService();
            $invoice = $invoiceService->createInvoice($shipment, $tollsFee);

            // event(new ShipmentChanged('created', $shipment->id));
            event(new ShipmentRealtimeUpdated('created', $shipment));

            Log::info('Shipment created successfully', [
                'shipment_id' => $shipment->id,
                'distance_calculated' => $distanceData !== null
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Shipment Created Successfully',
                    'shipment_id' => $shipment->id
                ]);
            }

            return redirect()->route('shipments.index')->with('success', 'Shipment Created Successfully');
        } catch (\Exception $e) {
            Log::error('Exception in shipment creation', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'pickup_address' => $request->pickup_address ?? 'N/A',
                'drop_address' => $request->drop_address ?? 'N/A'
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error creating shipment.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }
  public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'customer_id'           => 'required|exists:customers,id',
        'vehicle_type_id'       => 'required|exists:vehicle_types,id',
        'weight'                => 'required|numeric|min:0',
        'volume'                => 'required|numeric|min:0',
        'pallets'               => 'required|integer|min:1',
        'pickup_address'        => 'required|string|max:500',
        'drop_address'          => 'required|string|max:500',
        'pickup_time'           => 'required|date',
        'delivery_time'         => 'required|date|after_or_equal:pickup_time',
        'special_instructions'  => 'nullable|string|max:1000',
        'equipment_required'      => 'required|array|min:1',
        'equipment_required.*'    => 'in:liftgate,hazmat,temperature_control',
        'load_type'               => 'required|in:FTL,LTL',
        'reference_number'        => 'nullable|string|max:100',
        'pickup_contact_name'     => 'nullable|string|max:100',
        'pickup_contact_phone'    => 'nullable|string|max:30',
        'delivery_contact_name'   => 'nullable|string|max:100',
        'delivery_contact_phone'  => 'nullable|string|max:30',
    ]);

    if ($validator->fails()) {
        if ($request->ajax()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
        $shipment = Shipment::findOrFail($id);

        $distanceData = null;

        // Check if pickup or drop address has changed
        if (
            $shipment->pickup_address !== $request->pickup_address ||
            $shipment->drop_address !== $request->drop_address
        ) {
            Log::info('Addresses changed. Recalculating distance.', [
                'old_pickup' => $shipment->pickup_address,
                'new_pickup' => $request->pickup_address,
                'old_drop' => $shipment->drop_address,
                'new_drop' => $request->drop_address,
            ]);

            $distanceData = DistanceHelper::getDistanceFromGoogle(
                $request->pickup_address,
                $request->drop_address
            );
        }

        // ✅ Convert local time to UTC before saving
        $pickupTimeUTC = \Carbon\Carbon::parse($request->pickup_time)->setTimezone('UTC');
        $deliveryTimeUTC = \Carbon\Carbon::parse($request->delivery_time)->setTimezone('UTC');

        $shipment->update([
            'customer_id'              => $request->customer_id,
            'vehicle_type_id'          => $request->vehicle_type_id,
            'weight'                   => $request->weight,
            'volume'                   => $request->volume,
            'pallets'                  => $request->pallets,
            'pickup_address'           => $request->pickup_address,
            'drop_address'             => $request->drop_address,
            'pickup_time'              => $pickupTimeUTC,      // ✅ Saved as UTC
            'delivery_time'            => $deliveryTimeUTC,    // ✅ Saved as UTC
            'special_instructions'     => $request->special_instructions,

            // Boolean flags
            'requires_liftgate'        => in_array('liftgate', $request->equipment_required),
            'is_hazmat'                => in_array('hazmat', $request->equipment_required),
            'temperature_controlled'   => in_array('temperature_control', $request->equipment_required),

            // ✅ Save full array
            'equipment_required'       => $request->equipment_required,
            'load_type'                => $request->load_type ?? 'FTL',
            'reference_number'         => $request->reference_number,
            'pickup_contact_name'      => $request->pickup_contact_name,
            'pickup_contact_phone'     => $request->pickup_contact_phone,
            'delivery_contact_name'    => $request->delivery_contact_name,
            'delivery_contact_phone'   => $request->delivery_contact_phone,

            'deadhead_miles'           => $request->deadhead_miles ?: null,
            'detention_hours'          => $request->detention_hours ?: null,
            'lumper_fee'               => $request->lumper_fee ?: null,
            'per_diem_days'            => $request->per_diem_days ?: null,
            'scale_fees'               => $request->scale_fees ?: null,
            'tarp_required'            => $request->boolean('tarp_required'),
            'permit_fee'               => $request->permit_fee ?: null,

            // Distance info
            'distance_km'              => $distanceData['kilometers'] ?? $shipment->distance_km,
            'distance_miles'           => $distanceData ? round($distanceData['kilometers'] * 0.621371, 2) : $shipment->distance_miles,
            'distance_text'            => $distanceData['text'] ?? $shipment->distance_text,
        ]);
        
        $shipment->refresh();
        $invoiceService = new ShipmentInvoiceService();
        $invoice = $invoiceService->createInvoice($shipment);
        event(new ShipmentRealtimeUpdated('updated', $shipment));

        if ($request->ajax()) {
            return response()->json(['message' => 'Shipment Updated Successfully']);
        }

        return redirect()->route('shipments.index')->with('success', 'Shipment Updated Successfully');
    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json([
                'message' => 'Error updating shipment.',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }
}


    public function shipmentsDetail($id, $notificationId = null)
    {
        $shipment = Shipment::findOrFail($id);
     

        // If notification ID is present, mark it as read
        if ($notificationId) {
            $notification = DatabaseNotification::find($notificationId);
            if ($notification && $notification->notifiable_id === Auth::id()) {
                $notification->markAsRead(); // or $notification->delete(); if you want to remove it
            }
        }

        return view('shipment.notifications', compact('shipment'));
    }

    public function destroy($id)
    {


        $shipment = Shipment::findOrFail($id);
        $driverIdOverride = $this->resolveDriverId($shipment);

        $shipment->delete();

        event(new ShipmentChanged('deleted', (int) $id));
        event(new ShipmentRealtimeUpdated('deleted', $shipment, $driverIdOverride));

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Shipment deleted successfully']);
        }
        return redirect()->route('vehicles.index');
    }
    public function shipmentsInvoice()
    {
        $customers = Customer::select('id', 'customer_title')->orderBy('customer_title')->get();




        $name = 'Shipment Invoice Reports';

        return view('shipment.invoice-report', compact('name', 'customers'));
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:shipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipments = Shipment::whereIn('id', $request->ids)->get();
        Shipment::whereIn('id', $request->ids)->delete();

        foreach ($shipments as $shipment) {
            event(new ShipmentRealtimeUpdated('deleted', $shipment, $this->resolveDriverId($shipment)));
        }







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' shipment  deleted successfully'
        ]);
    }
    // Returns the driver_id for a shipment, falling back to the driver assigned to the shipment's vehicle.
    private function resolveDriverId(Shipment $shipment): ?int
    {
        if ($shipment->driver_id) return (int) $shipment->driver_id;
        if (!$shipment->vehicle_id) return null;
        $assignment = VehicleAssignment::where('vehicle_id', $shipment->vehicle_id)->latest()->first();
        return $assignment ? (int) $assignment->driver_id : null;
    }

    public function calendar()
    {
       
        $data = [
            'name' => 'Shipment Calendar',
        ];

        return view('shipment.calendar', $data);
    }

    /**
     * Get shipment data for calendar display
     */
  public function getCalendarData(Request $request)
{
    // dd($request->all());
   

    $query = Shipment::with(['customer', 'vehicleType']);

    // Apply filters if provided
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    // Date range filter for calendar view
    if ($request->filled('start') && $request->filled('end')) {
        $query->where(function ($q) use ($request) {
            $q->whereBetween('pickup_time', [$request->start, $request->end])
                ->orWhereBetween('delivery_time', [$request->start, $request->end]);
        });
    }

    $shipments = $query->get();

    $events = [];

    foreach ($shipments as $shipment) {
        // Add pickup event
        $events[] = [
            'id' => 'pickup-' . $shipment->id,
            'title' => '🚚 Pickup: ' . $shipment->customer->customer_title,
            'start' => $shipment->pickup_time,
            'className' => 'fc-event-pickup fc-event-' . $shipment->status,
            'extendedProps' => [
                'shipmentId' => $shipment->id,
                'eventType' => 'pickup',
                'status' => $shipment->status,
                'customerId' => $shipment->customer_id, // ADD THIS LINE
                'customer' => $shipment->customer->customer_title,
                'vehicleType' => $shipment->vehicleType->vehicle_type ?? 'N/A',
                'address' => $shipment->pickup_address,
                'weight' => $shipment->weight,
                'volume' => $shipment->volume,
                'pallets' => $shipment->pallets,
                'cost' => $shipment->estimated_cost,
                'instructions' => $shipment->special_instructions,
                'pickup_address' => $shipment->pickup_address,
                'drop_address' => $shipment->drop_address,
                'pickup_time' => $shipment->pickup_time,
                'delivery_time' => $shipment->delivery_time,
            ]
        ];

        // Add delivery event
        $events[] = [
            'id' => 'delivery-' . $shipment->id,
            'title' => '📦 Delivery: ' . $shipment->customer->customer_title,
            'start' => $shipment->delivery_time,
            'className' => 'fc-event-delivery fc-event-' . $shipment->status,
            'extendedProps' => [
                'shipmentId' => $shipment->id,
                'eventType' => 'delivery',
                'status' => $shipment->status,
                'customerId' => $shipment->customer_id, // ADD THIS LINE
                'customer' => $shipment->customer->customer_title,
                'vehicleType' => $shipment->vehicleType->vehicle_type ?? 'N/A',
                'address' => $shipment->drop_address,
                'weight' => $shipment->weight,
                'volume' => $shipment->volume,
                'pallets' => $shipment->pallets,
                'cost' => $shipment->estimated_cost,
                'instructions' => $shipment->special_instructions,
                'pickup_address' => $shipment->pickup_address,
                'drop_address' => $shipment->drop_address,
                'pickup_time' => $shipment->pickup_time,
                'delivery_time' => $shipment->delivery_time,
            ]
        ];
    }
    // dd($events);

    return response()->json($events);
}

    /**
     * Get customers for filter dropdown
     */
    public function getCustomersForFilter()
    {
        $customers = Customer::select('id', 'customer_title')
            ->orderBy('customer_title')
            ->get();

        return response()->json($customers);
    }

    /**
     * Get shipment counts for dashboard/calendar header
     */
    public function getShipmentCounts(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $query = Shipment::whereBetween('created_at', [$startDate, $endDate]);

        $counts = [
            'total' => $query->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'active' => $query->where('status', 'active')->count(),
            'complete' => $query->where('status', 'complete')->count(),
        ];

        return response()->json($counts);
    }

    /**
     * Get shipment details for modal display
     */
    public function getShipmentForCalendar($id)
{
    $shipment = Shipment::with(['customer', 'vehicleType'])->findOrFail($id);

    return response()->json([
        'id' => $shipment->id,
        'customer' => [
            'id' => $shipment->customer->id,
            'customer_title' => $shipment->customer->customer_title,
        ],
        'vehicle_type' => [
            'id' => $shipment->vehicleType->id ?? null,
            'vehicle_type' => $shipment->vehicleType->vehicle_type ?? 'N/A',
        ],
        'pickup_address' => $shipment->pickup_address,
        'drop_address' => $shipment->drop_address,
        
        // Format without timezone conversion - use the raw database value
        'pickup_time' => $shipment->pickup_time ? 
            \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $shipment->getAttributes()['pickup_time'])->format('M d, Y h:i A') 
            : null,
        'delivery_time' => $shipment->delivery_time ? 
            \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $shipment->getAttributes()['delivery_time'])->format('M d, Y h:i A') 
            : null,
        
        'pickup_time_raw' => $shipment->getAttributes()['pickup_time'] ?? null,
        'delivery_time_raw' => $shipment->getAttributes()['delivery_time'] ?? null,
        
        'estimated_cost' => $shipment->estimated_cost,
        'weight' => $shipment->weight,
        'volume' => $shipment->volume,
        'pallets' => $shipment->pallets,
        'status' => $shipment->status,
        'special_instructions' => $shipment->special_instructions,
        'requires_liftgate' => $shipment->requires_liftgate,
        'is_hazmat' => $shipment->is_hazmat,
        'temperature_controlled' => $shipment->temperature_controlled,
        'created_at' => $shipment->created_at->format('M d, Y h:i A'),
        'updated_at' => $shipment->updated_at->format('M d, Y h:i A'),
    ]);
}

    public function shipmentReportData(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');

        $query = Shipment::with(['customer', 'vehicleType', 'vehicle'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('pickup_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('delivery_time', '<=', $request->end_date);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vehicle_type_id')) {
            $query->where('vehicle_type_id', $request->vehicle_type_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Search
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('pickup_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('drop_address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where('customer_title', 'LIKE', '%' . $searchTerm . '%');
                    });
            });
        }

        $shipments = $query->paginate($perPage);

        return response()->json([
            'current_page' => $shipments->currentPage(),
            'data' => $shipments->items(),
            'from' => $shipments->firstItem(),
            'last_page' => $shipments->lastPage(),
            'per_page' => $shipments->perPage(),
            'to' => $shipments->lastItem(),
            'total' => $shipments->total(),
        ]);
    }

    /**
     * Download shipment report
     */
   

public function downloadReport(Request $request)
{
    $format = $request->input('format', 'excel');

    $query = Shipment::with(['customer', 'vehicleType', 'vehicle'])
        ->orderBy('created_at', 'desc');

    if ($request->filled('start_date')) {
        $query->whereDate('pickup_time', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('delivery_time', '<=', $request->end_date);
    }

    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('vehicle_type_id')) {
        $query->where('vehicle_type_id', $request->vehicle_type_id);
    }

    if ($request->filled('vehicle_id')) {
        $query->where('vehicle_id', $request->vehicle_id);
    }

    $shipments = $query->get();

    // ✅ Format the date range nicely
    $filters = $request->all();

    if ($request->filled('start_date')) {
        try {
            $filters['start_date'] = Carbon::parse($request->start_date)->format('M d, Y');
        } catch (\Exception $e) {
            $filters['start_date'] = $request->start_date;
        }
    }

    if ($request->filled('end_date')) {
        try {
            $filters['end_date'] = Carbon::parse($request->end_date)->format('M d, Y');
        } catch (\Exception $e) {
            $filters['end_date'] = $request->end_date;
        }
    }

    // ✅ Pass formatted filters to export methods
    if ($format === 'pdf') {
        return $this->downloadPDF($shipments, $filters);
    }

    return $this->downloadExcel($shipments, $filters);
}

    /**
     * Generate and download PDF report
     */
    private function downloadPDF($shipments, $filters)
    {
        $html = view('shipment.pdf-report', compact('shipments', 'filters'))->render();

        $pdf = PDF::loadHTML($html)->setPaper('A4', 'landscape'); // or 'portrait' if preferred

        $filename = 'shipment_report_' . date('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename); // <-- Real PDF download
    }

    /**
     * Generate and download Excel report
     */
    private function downloadExcel($shipments, $filters)
    {
        $fileName = 'shipments-report-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(
            function () use ($shipments) {
                $writer = SimpleExcelWriter::create('php://output', 'xlsx')
                    ->addHeader([
                        'ID',
                        'Customer',
                        'Vehicle Type',
                        'Vehicle',
                        'Pickup Address',
                        'Drop Address',
                        'Pickup Time',
                        'Delivery Time',
                        'Status',
                        'Weight',
                        'Volume',
                        'Pallets',
                        'Estimated Cost',
                        'Distance (KM)',
                        'Distance (Miles)',
                        'Special Instructions',
                        'Created At'
                    ]);

                foreach ($shipments as $shipment) {
                    $writer->addRow([
                        'ID' => $shipment->id,
                        'Customer' => $shipment->customer?->customer_title ?? 'N/A',
                        'Vehicle Type' => $shipment->vehicleType?->vehicle_type ?? 'N/A',
                        'Vehicle' => $shipment->vehicle?->vehicle_name ?? 'N/A',
                        'Pickup Address' => $shipment->pickup_address,
                        'Drop Address' => $shipment->drop_address,
                        'Pickup Time' => $shipment->pickup_time?->format('Y-m-d H:i:s') ?? 'N/A',
                        'Delivery Time' => $shipment->delivery_time?->format('Y-m-d H:i:s') ?? 'N/A',
                        'Status' => ucfirst($shipment->status),
                        'Weight' => $shipment->weight,
                        'Volume' => $shipment->volume,
                        'Pallets' => $shipment->pallets,
                        'Estimated Cost' => '$' . number_format($shipment->estimated_cost, 2),
                        'Distance (KM)' => $shipment->distance_km,
                        'Distance (Miles)' => $shipment->distance_miles,
                        'Special Instructions' => $shipment->special_instructions,
                        'Created At' => $shipment->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                $writer->close();
            },
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
    /**
     * Get statistics for report dashboard
     */
    public function getReportStatistics(Request $request)
    {
        $query = Shipment::query();

        // Apply date filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $totalShipments = $query->count();
        $pendingShipments = $query->where('status', 'pending')->count();
        $activeShipments = $query->where('status', 'active')->count();
        $completedShipments = $query->where('status', 'complete')->count();

        $totalRevenue = $query->sum('estimated_cost');
        $totalDistance = $query->sum('distance_km');

        return response()->json([
            'total_shipments' => $totalShipments,
            'pending_shipments' => $pendingShipments,
            'active_shipments' => $activeShipments,
            'completed_shipments' => $completedShipments,
            'total_revenue' => $totalRevenue,
            'total_distance' => $totalDistance,
            'completion_rate' => $totalShipments > 0 ? round(($completedShipments / $totalShipments) * 100, 2) : 0
        ]);
    }

    private function calculateEstimatedCost($request, $distanceData): float
    {
        try {
            // ── Distance ─────────────────────────────────────────────────────
            $distanceMiles = isset($distanceData['kilometers'])
                ? round($distanceData['kilometers'] * 0.621371, 2)
                : 0;

            if ($distanceMiles <= 0) return 500.00; // minimum fallback

            // ── Rate per mile by vehicle type (US FTL industry standard) ─────
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

            // ── Line haul (core cost) ─────────────────────────────────────────
            $lineHaul = $distanceMiles * $rate;

            // ── Fuel cost (real diesel price by state from FuelProvider) ──────
            $stateCode  = $this->extractStateCode($request->pickup_address ?? '');
            $fuelProvider = new \App\Services\Integrations\Providers\FuelProvider();
            $dieselPrice  = $fuelProvider->getStateDieselPrice($stateCode); // $/gallon
            $truckMpg     = 6.5;
            $fuelCost     = ($distanceMiles / $truckMpg) * $dieselPrice;

            // ── Fuel surcharge (industry standard ~28% of line haul) ──────────
            $fuelSurcharge = $lineHaul * 0.28;

            // ── Weight surcharge (LTL-style, only if heavy — over 40,000 lbs) ─
            $weightLbs     = (float) ($request->weight ?? 0) * 2.20462; // kg to lbs
            $weightSurcharge = $weightLbs > 40000 ? ($weightLbs - 40000) * 0.002 : 0;

            // ── Pallet handling ───────────────────────────────────────────────
            $palletCost = (int) ($request->pallets ?? 0) * 15.00;

            // ── Equipment surcharges (updated to market rates) ────────────────
            $equipmentSurcharge = 0;
            foreach ((array) $request->equipment_required as $eq) {
                match ($eq) {
                    'liftgate'            => $equipmentSurcharge += 125.00,
                    'hazmat'              => $equipmentSurcharge += 300.00,
                    'temperature_control' => $equipmentSurcharge += 200.00,
                    default               => null,
                };
            }

            // ── Minimum charge ────────────────────────────────────────────────
            $minimumCharge = 350.00;

            // ── Total with 12% broker margin ──────────────────────────────────
            $subtotal = $lineHaul + $fuelCost + $fuelSurcharge + $weightSurcharge + $palletCost + $equipmentSurcharge;
            $total    = round($subtotal * 1.12, 2);

            return max($total, $minimumCharge);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cost calculation failed: ' . $e->getMessage());
            return 500.00;
        }
    }

    private function extractStateCode(string $address): string
    {
        // Match 2-letter state code — e.g. "Dallas, TX" or "Dallas, Texas, USA"
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

        // Try 2-letter abbreviation first: ", TX" or " TX " or ", TX,"
        if (preg_match('/\b([A-Z]{2})\b/', $address, $m)) {
            $code = strtoupper($m[1]);
            if (in_array($code, array_values($stateNames))) return $code;
        }

        // Try full state name
        $lower = strtolower($address);
        foreach ($stateNames as $name => $code) {
            if (str_contains($lower, $name)) return $code;
        }

        return 'default';
    }
}
