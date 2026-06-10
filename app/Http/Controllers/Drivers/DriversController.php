<?php

namespace App\Http\Controllers\Drivers;

use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\DriverType\DriverType;
use App\Models\ManageDriver\ManageDriver;
use App\Services\Integrations\Contracts\EldProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DriversController extends Controller
{

    public function showMap()
{
    return view('drivers.map'); // make sure this Blade file exists
}
public function apiLocations()
{
    $drivers = Driver::select(
        'id',
        'firstname',
        'lastname',
        'phoneno',
        'emergencycontactno',
        'current_latitude as latitude',
        'current_longitude as longitude',
        'current_latitude',
        'current_longitude',
        'current_duty_status',
        'last_location_update',
        'profile_photo'
    )
    ->whereNotNull('current_latitude')
    ->whereNotNull('current_longitude')
    ->get()
    ->map(function ($d) {
        $d->photo_url = $d->profile_photo
            ? asset('storage/' . $d->profile_photo)
            : null;
        unset($d->profile_photo);
        return $d;
    });

    return response()->json($drivers);
}


     public function index()
{
  
    $controllerName = str_replace('Controller', '', class_basename(static::class)); // "Drivers"
    $user = Auth::user();
   
    $roleId = $user->role_id ?? null;

    $showModule = false;

    if ($roleId) {
        $module = DB::table('modules')
                    ->where('name', $controllerName)
                    ->first();

        if ($module) {
            $showModule = DB::table('module_role')
                            ->where('role_id', $roleId)
                            ->where('module_id', $module->id)
                            ->where('view', 1)
                            ->exists();
        }
    }

    $data['name']       = $controllerName;
    $data['showModule'] = $showModule;
    $data['total']      = Driver::count();
    $data['driving']    = Driver::where('current_duty_status', 'driving')->count();
    $data['on_duty']    = Driver::where('current_duty_status', 'on_duty_not_driving')->count();
    $data['off_duty']   = Driver::whereIn('current_duty_status', ['off_duty', 'sleeper', null])->count();

    return view('drivers.index', $data);
}
public function getUsers(Request $request, EldProviderInterface $eldProvider)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');
    $sortColumn = $request->input('sort_column', 'id');
    $sortOrder = $request->input('sort_order', 'desc'); // Changed default to 'desc' for latest first

    $query = Driver::with('drivertype')->latest(); // Added latest() here

    if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('email', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('firstname', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('lastname', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('phoneno', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('incentive', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('emergencycontactno', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('licenseno', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('licensetype', 'LIKE', '%' . $searchTerm . '%')
              ->orWhereHas('drivertype', function ($cq) use ($searchTerm) {
                  $cq->where('name', 'LIKE', '%' . $searchTerm . '%');
              })
              ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$searchTerm}%"]);
        });
    }

    // Filter by driver type
    $driverType = $request->input('driver_type');
    if (!empty($driverType)) {
        $query->where('drivertype', $driverType);
    }

    // Filter by duty status
    $dutyStatus = $request->input('duty_status');
    Log::debug('[getUsers] duty_status filter', ['value' => $dutyStatus]);
    if (!empty($dutyStatus)) {
        $query->where('current_duty_status', $dutyStatus);
    }

    // Apply user sorting only if it's not the default sort
    if ($sortColumn !== 'created_at' || $sortOrder !== 'desc') {
        $query->orderBy($sortColumn, $sortOrder);
    }

    $users = $query->paginate($perPage);
    Log::debug('[getUsers] result count', ['total' => $users->total(), 'duty_status' => $dutyStatus ?? 'none']);

    // Pull current HOS snapshot from ELD provider so we can decorate each driver
    $hosSnapshot = $eldProvider->getDriverStatuses()->keyBy('driver_id');

    $users->getCollection()->transform(function ($user) use ($hosSnapshot) {
        $hos = $hosSnapshot->get($user->id);

        $user->eld = $hos ? [
            'current_status' => $hos->get('current_status'),
            'hos' => $hos->get('hos'),
        ] : null;

        $user->actions = [
            'edit' => route('managedriver.edit', $user->id),
            'delete' => route('managedriver.destroy', $user->id),
            'credentials' => route('credentials.index', $user->id),
        ];
        return $user;
    });

    return response()->json($users);
}

// Get all driver types for filter dropdown
public function getDriverTypes()
{
    $types = DriverType::all()->map(function ($type) {
        return [
            'id' => $type->id,
            'name' => $type->name,
        ];
    });

    return response()->json($types);
}

public function show($id)
{
    try {
        $driver = Driver::with(['audits.user', 'drivertype', 'documents'])->findOrFail($id);

        $audits = $driver->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });

        $documents = $driver->documents->map(function ($doc) {
            return [
                'id'            => $doc->id,
                'type'          => $doc->type,
                'type_label'    => \App\Models\Drivers\DriverDocument::$typeLabels[$doc->type] ?? ucfirst(str_replace('_', ' ', $doc->type)),
                'original_name' => $doc->original_name,
                'status'        => $doc->status,
                'expires_at'    => $doc->expires_at,
                'view_url'      => route('drivers.onboarding.view-doc', $doc->id),
            ];
        });

        return response()->json([
            // Basic
            'id'                   => $driver->id,
            'firstname'            => $driver->firstname,
            'lastname'             => $driver->lastname,
            'email'                => $driver->email,
            'phoneno'              => $driver->phoneno,
            'emergencycontactno'   => $driver->emergencycontactno,
            'driver_type'          => optional($driver->drivertype)->name ?? '—',
            'status'               => $driver->status,
            'onboarding_status'    => $driver->onboarding_status,
            // CDL & compliance
            'cdl_number'           => $driver->cdl_number,
            'cdl_state'            => $driver->cdl_state,
            'cdl_class'            => $driver->cdl_class,
            'cdl_expiry_date'      => $driver->cdl_expiry_date,
            'cdl_endorsements'     => $driver->cdl_endorsements,
            'medical_card_expiry'  => $driver->medical_card_expiry,
            'drug_test_status'     => $driver->drug_test_status,
            'years_experience'     => $driver->years_experience,
            // Legacy fields
            'licaence_type'        => $driver->licensetype,
            'licaenceno'           => $driver->licenseno,
            // Documents
            'documents'            => $documents,
            'audits'               => $audits,
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Driver not found.'], 404);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An unexpected error occurred.', 'message' => $e->getMessage()], 500);
    }
}

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:active,inactive,suspended',
    ]);

    $driver = Driver::findOrFail($id);
    $driver->update(['status' => $request->status]);

    return response()->json([
        'success' => true,
        'status'  => $driver->status,
        'message' => "Driver status updated to {$driver->status}.",
    ]);
}





public function create()
{
    return view('drivers.create', [
        'name'         => 'Manage Driver',
        'drivers'      => DriverType::all(),
        'vehicleTypes' => \App\Models\VehicleType\VehicleType::all(),
    ]);
}

public function edit($edit){
    return view('drivers.create', [
        'name'         => 'Driver',
        'drivers'      => DriverType::all(),
        'vehicleTypes' => \App\Models\VehicleType\VehicleType::all(),
        'user'         => Driver::findOrFail($edit),
    ]);
}

public function store(Request $request)
{
 
    // Validate first
    $validator = Validator::make($request->all(), [
        'firstname' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'phoneno' => 'required|string|max:20',
        'incentive' => 'nullable|numeric|min:0',
        'pay_type' => 'nullable|in:per_mile,per_load,percentage,hourly',
        'pay_rate' => 'nullable|numeric|min:0',
        'emergencycontactno' => 'required|string|max:20',
        'email' => 'required|email|max:255|unique:drivers,email',
        'drivertype' => 'required|string',
        'licensetype' => 'required|string',
         'password' => 'required|string|min:6',
        'licenseno' => 'required|string|max:50',
    ]);

    // If validation fails
    if ($validator->fails()) {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    try {
        // Create the driver
      $driver=  Driver::create([
            'firstname'              => $request->firstname,
            'lastname'               => $request->lastname,
            'phoneno'                => $request->phoneno,
            'emergencycontactno'     => $request->emergencycontactno,
            'email'                  => $request->email,
            'incentive'              => $request->incentive,
            'pay_type'               => $request->pay_type ?? 'per_mile',
            'pay_rate'               => $request->pay_rate,
            'drivertype'             => $request->drivertype,
            'licensetype'            => $request->licensetype,
            'licenseno'              => $request->licenseno,
            'password'               => Hash::make($request->password),
            // Personal / address
            'date_of_birth'          => $request->date_of_birth ?: null,
            'ssn_last4'              => $request->ssn_last4,
            'years_experience'       => $request->years_experience ?: null,
            'address'                => $request->address,
            'city'                   => $request->city,
            'state'                  => $request->state,
            'zip'                    => $request->zip,
            // CDL
            'cdl_number'             => $request->cdl_number,
            'cdl_state'              => $request->cdl_state,
            'cdl_class'              => $request->cdl_class,
            'cdl_expiry_date'        => $request->cdl_expiry_date ?: null,
            'cdl_endorsements'       => $request->cdl_endorsements ?? [],
            // Medical
            'medical_card_expiry'    => $request->medical_card_expiry ?: null,
            'drug_test_date'         => $request->drug_test_date ?: null,
            'drug_test_status'       => $request->drug_test_status,
            'preferred_truck_type_id'=> $request->preferred_truck_type_id ?: null,
        ]);
         try {
                $driver->syncConnectyCubeUser();
                $connectyCubeMessage = 'Driver created and synced with ConnectyCube successfully.';
            } catch (\Exception $e) {
                Log::error('ConnectyCube sync failed for new driver: ' . $e->getMessage());
                $connectyCubeMessage = 'Driver created but ConnectyCube sync failed. Please sync manually.';
            }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Driver Created Successfully']);
        }

        return redirect()->route('drivers.index')->with('success', 'Driver Created Successfully');

    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating driver.',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error creating driver: ' . $e->getMessage())->withInput();
    }
}



public function update(Request $request, $id)
{
    $user = Driver::findOrFail($id);

    // Validation rules
    $rules = [
        'firstname' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'phoneno' => 'required|string|max:20',
       'emergencycontactno' => 'required|string|max:20',
        'email' => 'required|email|max:255|unique:drivers,email,' . $user->id,
     
        'drivertype' => 'required|string',
        'incentive' => 'nullable|numeric|min:0',
        'pay_type' => 'nullable|in:per_mile,per_load,percentage,hourly',
        'pay_rate' => 'nullable|numeric|min:0',
        'licensetype' => 'required|string',
        'licenseno' => 'required|string|max:50',
    ];

    // Include password validation if it's filled
    if ($request->filled('password')) {
        $rules['password'] = 'required|string|min:6';
    }

    // Run validator
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Prepare update data
    $updateData = [
        'firstname'              => $request->firstname,
        'lastname'               => $request->lastname,
        'phoneno'                => $request->phoneno,
        'emergencycontactno'     => $request->emergencycontactno,
        'email'                  => $request->email,
        'incentive'              => $request->incentive,
        'pay_type'               => $request->pay_type ?? 'per_mile',
        'pay_rate'               => $request->pay_rate,
        'drivertype'             => $request->drivertype,
        'licensetype'            => $request->licensetype,
        'licenseno'              => $request->licenseno,
        // Personal / address
        'date_of_birth'          => $request->date_of_birth ?: null,
        'ssn_last4'              => $request->ssn_last4,
        'years_experience'       => $request->years_experience ?: null,
        'address'                => $request->address,
        'city'                   => $request->city,
        'state'                  => $request->state,
        'zip'                    => $request->zip,
        // CDL
        'cdl_number'             => $request->cdl_number,
        'cdl_state'              => $request->cdl_state,
        'cdl_class'              => $request->cdl_class,
        'cdl_expiry_date'        => $request->cdl_expiry_date ?: null,
        'cdl_endorsements'       => $request->cdl_endorsements ?? [],
        // Medical
        'medical_card_expiry'    => $request->medical_card_expiry ?: null,
        'drug_test_date'         => $request->drug_test_date ?: null,
        'drug_test_status'       => $request->drug_test_status,
        'preferred_truck_type_id'=> $request->preferred_truck_type_id ?: null,
    ];

    // Only update password if provided
    if ($request->filled('password')) {
        $updateData['password'] = Hash::make($request->password);
    }

    // Try to update
    try {
        $user->update($updateData);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver updated successfully'
            ]);
        }

        return redirect()->route('drivers.index')->with('success', 'Driver Updated Successfully');
    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating driver',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error updating driver: ' . $e->getMessage())->withInput();
    }
}


public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        
        return redirect()->route('driver.index')
            ->with('success', 'Driver deleted successfully');
    }

      public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:drivers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Driver::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true, 
            'message' => count($request->ids) . ' drivers deleted successfully'
        ]);
    }




 public function getChatDrivers()
    {
        $drivers = Driver::select([
            'id',
            'firstname',
            'lastname',
            'email',
           
            'connectycube_id',
            'connectycube_login'
        ])->get();

        $chatDrivers = $drivers->map(function ($driver) {
            return $driver->getConnectyCubeData();
        });

        return response()->json([
            'success' => true,
            'drivers' => $chatDrivers
        ]);
    }
 public function syncWithConnectyCube()
    {
        try {
            $results = Driver::syncAllWithConnectyCube();
            
            return response()->json([
                'success' => true,
                'message' => "Sync completed: {$results['success']} successful, {$results['failed']} failed",
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
      public function updateConnectyCubeId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'connectycube_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $driver = Driver::findOrFail($request->driver_id);
            $driver->update(['connectycube_id' => $request->connectycube_id]);

            return response()->json([
                'success' => true,
                'message' => 'ConnectyCube ID updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ConnectyCube ID: ' . $e->getMessage()
            ], 500);
        }
    }
}
