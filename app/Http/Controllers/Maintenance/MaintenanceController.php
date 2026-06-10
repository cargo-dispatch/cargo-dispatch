<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\Maintenance\Maintenance;
use App\Models\MaintenanceType\MaintenanceType;
use App\Models\Vehicles\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index()
    {
         $data['name']       = 'Vehicle Maintenance';
         $data['total']      = Maintenance::count();
         $data['scheduled']  = Maintenance::where('status', 'scheduled')->count();
         $data['completed']  = Maintenance::where('status', 'completed')->count();
         $data['cancelled']  = Maintenance::where('status', 'cancelled')->count();
        return view('maintenance.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
      $data = [

        'name' => 'Vehicle Maintenance',
        'maintenance_types' => MaintenanceType::all(),
        'vehicles' => Vehicle::all(),
       
        'drivers' => Driver::all(),
      ];
     
     
        return view('maintenance.create', $data);
    }
    

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    // Basic validation rules
    $rules = [
        'vehicle_id' => 'required|exists:vehicles,id',
        'driver_id' => 'nullable|exists:drivers,id',
        'maintenance_type_id' => 'required|exists:maintenance_types,id',
        'maintenance_date' => 'required|date',
        'cost' => 'nullable|numeric|min:0',
        'status' => 'required|in:scheduled,completed,cancelled',
        'next_maintenance_date' => 'nullable|date|after_or_equal:today',
        'next_maintenance_miles' => 'nullable|numeric|min:0',
        'description' => 'required|string|max:1000',
    ];

    // Custom validation messages
    $messages = [
        'vehicle_id.required' => 'Please select a vehicle.',
        'vehicle_id.exists' => 'Selected vehicle does not exist.',
        'maintenance_type_id.required' => 'Please select a maintenance type.',
        'maintenance_type_id.exists' => 'Selected maintenance type does not exist.',
        'maintenance_date.required' => 'Maintenance date is required.',
        'maintenance_date.date' => 'Please provide a valid date.',
        'status.required' => 'Please select a status.',
        'status.in' => 'Invalid status selected.',
        'description.required' => 'Descriptionis required.',
     
        'next_maintenance_date.after_or_equal' => 'Next maintenance date cannot be in the past.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
        if ($request->ajax()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    $validated = $validator->validated();

    // Additional business logic validation
    $maintenanceDate = \Carbon\Carbon::parse($validated['maintenance_date']);
    $today = \Carbon\Carbon::today();

    if ($maintenanceDate->isPast() && $validated['status'] === 'scheduled') {
        if ($request->ajax()) {
            return response()->json([
                'errors' => ['status' => ['Past maintenance dates cannot be scheduled.']]
            ], 422);
        }

        return redirect()->back()->withErrors(['status' => 'Past maintenance dates cannot be scheduled.'])->withInput();
    }

    if ($maintenanceDate->isFuture() && $validated['status'] === 'completed') {
        if ($request->ajax()) {
            return response()->json([
                'errors' => ['status' => ['Future maintenance cannot be marked as completed.']]
            ], 422);
        }

        return redirect()->back()->withErrors(['status' => 'Future maintenance cannot be marked as completed.'])->withInput();
    }

    try {
        // Create maintenance record
        $maintenance = Maintenance::create([
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'] ?? null,
            'maintenance_type_id' => $validated['maintenance_type_id'],
            'maintenance_date' => $validated['maintenance_date'],
            'cost' => $validated['cost'] ?? 0.00,
            'status' => $validated['status'] ,
            'next_maintenance_date' => $validated['next_maintenance_date'] ?? null,
            'next_maintenance_miles_reading' => $validated['next_maintenance_miles'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Maintenance Created Successfully']);
        }

        return redirect()->route('maintenance.index')->with('success', 'Maintenance Created Successfully');

    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json([
                'message' => 'Error creating maintenance',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error creating maintenance: ' . $e->getMessage())->withInput();
    }
} /**
     * Display the specified resource.
     */
public function show($id)
{
    $maintenance = Maintenance::with([
        'audits.user',
        'vehicle',
        'driver',
        'maintenanceType'
    ])->findOrFail($id);
 

    $audits = $maintenance->audits->map(function ($audit) {
        $audit->user_name = $audit->user ? $audit->user->name : null;
        $audit->created_at = \Carbon\Carbon::parse($audit->created_at)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
        return $audit;
    });

    return response()->json([
        'Vehicle' => $maintenance->vehicle ? 
            $maintenance->vehicle->vehicle_id . ' - ' . ($maintenance->vehicle->model ?? 'N/A') : 'N/A',
        'Driver' => $maintenance->driver ? 
            $maintenance->driver->firstname . ' ' . $maintenance->driver->lastname : 'Not Assigned',
        'Maintenance Type' => $maintenance->maintenanceType ? 
            $maintenance->maintenanceType->maintenance_types : 'N/A',
        'Maintenance Date' => $maintenance->maintenance_date ? 
            $maintenance->maintenance_date->format('Y-m-d') : 'N/A',
        'Cost' => $maintenance->cost ? '$' . number_format($maintenance->cost, 2) : '$0.00',
        'Status' => ucfirst($maintenance->alert_status ?? 'N/A'),
        'Next Maintenance Miles' => $maintenance->next_maintenance_miles_reading ?? 'N/A',
        'Description' => $maintenance->description ?? 'No description provided',
        'audits' => $audits,
    ]);
}
public function getUsers(Request $request)
{
    $perPage    = $request->get('per_page', 10);
    $searchTerm = $request->get('search', '');
    $status     = $request->get('status', '');

    $query = Maintenance::with(['vehicle', 'driver', 'maintenanceType'])
        ->latest();

    if (!empty($status)) {
        $query->where('status', $status);
    }

    // Add search functionality
    if (!empty($searchTerm)) {
        $query->where(function($q) use ($searchTerm) {
            $q->whereHas('vehicle', function($vehicleQuery) use ($searchTerm) {
                $vehicleQuery->where('vehicle_id', 'like', "%{$searchTerm}%");
            })
            ->orWhereHas('driver', function($driverQuery) use ($searchTerm) {
                $driverQuery->where('firstname', 'like', "%{$searchTerm}%")
                          ->orWhere('lastname', 'like', "%{$searchTerm}%");
            })
            ->orWhereHas('maintenanceType', function($typeQuery) use ($searchTerm) {
                $typeQuery->where('maintenance_types', 'like', "%{$searchTerm}%");
            })
            ->orWhere('description', 'like', "%{$searchTerm}%")
            ->orWhere('alert_status', 'like', "%{$searchTerm}%");
        });
    }

    $maintenances = $query->paginate($perPage);

    // Transform data for the frontend
    $transformedData = $maintenances->getCollection()->map(function($maintenance) {
        return [
            'id' => $maintenance->id,
            'vehicle' => [
                'vehicle_id' => $maintenance->vehicle->vehicle_id ?? null,
            ],
            'driver' => $maintenance->driver ? [
                'firstname' => $maintenance->driver->firstname,
                'lastname' => $maintenance->driver->lastname,
            ] : null,
            'maintenance_type' => [
                'maintenance_types' => $maintenance->maintenanceType->maintenance_types ?? null,
            ],
            'maintenance_date' => $maintenance->maintenance_date,
            'cost' => $maintenance->cost,
            'alert_status' => $maintenance->alert_status,
            'next_maintenance_miles_reading' => $maintenance->next_maintenance_miles_reading,
            'description' => $maintenance->description,
            'next_maintenance_date' => $maintenance->next_maintenance_date,
            'status' => $maintenance->status,
            'actions' => [
                'edit' => route('maintenance.edit', $maintenance->id),
            ]
        ];
    });

    // Build pagination links array
    $links = [];
    
    // Previous link
    $links[] = [
        'url' => $maintenances->previousPageUrl(),
        'label' => '&laquo; Previous',
        'active' => false
    ];
    
    // Page number links
    for ($i = 1; $i <= $maintenances->lastPage(); $i++) {
        $links[] = [
            'url' => $maintenances->url($i),
            'label' => (string) $i,
            'active' => $i === $maintenances->currentPage()
        ];
    }
    
    // Next link
    $links[] = [
        'url' => $maintenances->nextPageUrl(),
        'label' => 'Next &raquo;',
        'active' => false
    ];

    return response()->json([
        'data' => $transformedData,
        'links' => $links,
        'meta' => [
            'current_page' => $maintenances->currentPage(),
            'last_page' => $maintenances->lastPage(),
            'per_page' => $maintenances->perPage(),
            'total' => $maintenances->total(),
        ]
    ]);
}

    /**
     * Show the form for editing the specified resource.
     */
public function edit($id,Request $request)
{
    $maintenance = Maintenance::findOrFail($id);
    
    
    $data = [
        'name' => 'Vehicle Maintenance',
        'user' => $maintenance,  // Changed from 'maintenance' to 'user'
        'maintenance' => $maintenance,  // Keep this for accessing maintenance data
        'maintenance_types' => MaintenanceType::all(),
        'vehicles' => Vehicle::all(),
        'drivers' => Driver::all(),
    ];
    
    return view('maintenance.create', $data);
}

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{
    try {
        $maintenance = Maintenance::findOrFail($id);

        // Validation rules (same as store)
        $rules = [
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'maintenance_type_id' => 'required|exists:maintenance_types,id',
            'maintenance_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:scheduled,completed,cancelled',
            'next_maintenance_date' => 'nullable|date|after_or_equal:today',
            'next_maintenance_miles' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ];

        $messages = [
            'vehicle_id.required' => 'Please select a vehicle.',
            'vehicle_id.exists' => 'Selected vehicle does not exist.',
            'maintenance_type_id.required' => 'Please select a maintenance type.',
            'maintenance_type_id.exists' => 'Selected maintenance type does not exist.',
            'maintenance_date.required' => 'Maintenance date is required.',
            'maintenance_date.date' => 'Please provide a valid date.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
            'next_maintenance_date.after_or_equal' => 'Next maintenance date cannot be in the past.',
        ];

        $validated = $request->validate($rules, $messages);

        // Business logic validation
        $maintenanceDate = \Carbon\Carbon::parse($validated['maintenance_date']);
        $today = \Carbon\Carbon::today();

        if ($maintenanceDate->isPast() && $validated['status'] === 'scheduled') {
            return response()->json([
                'errors' => ['status' => ['Past maintenance dates cannot be scheduled.']]
            ], 422);
        }

        if ($maintenanceDate->isFuture() && $validated['status'] === 'completed') {
            return response()->json([
                'errors' => ['status' => ['Future maintenance cannot be marked as completed.']]
            ], 422);
        }

        // ✅ Update record (same fields as in store)
        $maintenance->update([
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'] ?? null,
            'maintenance_type_id' => $validated['maintenance_type_id'],
            'maintenance_date' => $validated['maintenance_date'],
            'cost' => $validated['cost'] ?? 0.00,
            'status' => $validated['status'],
            'next_maintenance_date' => $validated['next_maintenance_date'] ?? null,
            'next_maintenance_miles_reading' => $validated['next_maintenance_miles'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);
         if ($request->input('redirect') === 'dashboard') {
        return redirect()->route('dashboard')->with('success', 'Maintenance type updated successfully!');
    }

        if ($request->ajax()) {
            return response()->json(['message' => 'Maintenance Updated Successfully']);
        }

        return redirect()->route('maintenance.index')->with('success', 'Maintenance Updated Successfully');

    } catch (ValidationException $e) {
        if ($request->ajax()) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        throw $e;
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Maintenance not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error updating maintenance: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating maintenance: ' . $e->getMessage()], 500);
    }
}

public function disable($id)
{
   
   
    try {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->alert_status = 'disabled';
        $maintenance->save();

        return response()->json(['message' => 'Maintenance alert disabled successfully.']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to disable alert.'], 500);
    }
}



    /**
     * Remove the specified resource from storage.
     */
      public function destroy($id)
    {


        $maintenance_type = Maintenance::findOrFail($id);



     


     


         $maintenance_type->delete();
        
        return redirect()->route('maintenance.index')
            ->with('success', ' Maintenance  deleted successfully');
    }
}
