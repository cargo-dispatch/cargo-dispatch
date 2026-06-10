<?php

namespace App\Http\Controllers\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Vehicles\Vehicle;
use Illuminate\Support\Facades\Validator;

use App\Models\VehicleType\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\ValidationException;

class VehiclesController extends Controller
{
    public function index()
    {
        $data = [
            'name'           => 'Vehicles',
            'total' => Vehicle::count(),
        ];

        return view('vehicles.index', $data);
    }

   public function getVehicles(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');

    $query = Vehicle::query()->latest(); // Added latest() here

    if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('vehicle_id', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('vin', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('license_plate_number', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('make_model', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('year_of_manufacture', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('color', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('cargo_weight', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('cargo_volume', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('load_type_compatibility', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('registration_expiry_date', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('insurance_details', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    $users = $query->paginate($perPage);
    $users->getCollection()->transform(function ($user) {
        $user->actions = [
            'edit' => route('vehicles.edit', $user->id),
            'delete' => route('vehicles.destroy', $user->id),
        ];
        $user->image_url = ($user->vehicleType && $user->vehicleType->image)
            ? asset('storage/' . $user->vehicleType->image)
            : asset('images/default-truck.png');
        return $user;
    });

    return response()->json($users);
}

    public function show($id)
    {
        $customer = Vehicle::with(['audits.user'])->findOrFail($id);

        $audits = $customer->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });

        // Concatenate address parts with newlines


        return response()->json([
            'vehicle_id' => $customer->vehicle_id,
            'vin' => $customer->vin,
            'make_model' => $customer->make_model,
            'year_of_manufacture' => $customer->year_of_manufacture,
            'ownership' => $customer->ownership_status,
            'weight' => $customer->cargo_weight,
            'volume' => $customer->cargo_volume,
            'load' => $customer->load_type_compatibility,
            'expiry_date' => $customer->registration_expiry_date,
            'insurance_detail' => $customer->insurance_details,
            'insurance_expiry' => $customer->insurance_expiry_date,


            'audits' => $audits,
        ]);
    }


    public function create()
    {

        $data = [
            'results' => VehicleType::all(),
            'name' => 'Vehicle',
        ];




        return view('vehicles.create', $data);
    }

    public function edit($edit)
    {
        $data['user'] = Vehicle::findOrFail($edit); // ← Fix here
        $data['results'] = VehicleType::all();      // ← Add this to get vehicle types
        $data['name'] = "Vehicle";

        return view('vehicles.create', $data);
    }


    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'vehicle_id'               => 'required|string|max:255',
            'license_plate_number'     => 'required|string|max:255',
            'vin'                      => 'required|string|max:255',
            'make_model'               => 'required|string|max:255',
            'year_of_manufacture'      => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'vehicle_type_id'          => 'required|exists:vehicle_types,id',
            'color'                    => 'nullable|string|max:50',
            'fuel_type'                => 'nullable|string|max:50',
            'status'                   => 'nullable|in:available,in_use,maintenance,out_of_service',
            'ownership_status'         => 'nullable|in:Owned,Leased,Rented',
            'cargo_weight'             => 'nullable|numeric|min:0',
            'cargo_volume'             => 'nullable|numeric|min:0',
            'load_type_compatibility'  => 'nullable|string|max:255',
            'registration_expiry_date' => 'nullable|date',
            'insurance_details'        => 'nullable|string|max:500',
            'insurance_expiry_date'    => 'nullable|date',
        ]);


        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            Vehicle::create([
                'vehicle_id'               => $request->vehicle_id,
                'license_plate_number'     => $request->license_plate_number,
                'vin'                      => $request->vin,
                'make_model'               => $request->make_model,
                'year_of_manufacture'      => $request->year_of_manufacture,
                'vehicle_type_id'          => $request->vehicle_type_id,
                'color'                    => $request->color,
                'fuel_type'                => $request->fuel_type ?? 'Diesel',
                'status'                   => $request->status ?? 'available',
                'ownership_status'         => $request->ownership_status,
                'cargo_weight'             => $request->cargo_weight,
                'cargo_volume'             => $request->cargo_volume,
                'load_type_compatibility'  => $request->load_type_compatibility,
                'registration_expiry_date' => $request->registration_expiry_date,
                'insurance_details'        => $request->insurance_details,
                'insurance_expiry_date'    => $request->insurance_expiry_date,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Vehicle Created Successfully']);
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle Created Successfully');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error creating vehicle',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);

            $validatedData = $request->validate([
                'vehicle_id'               => 'required|string|max:255',
                'license_plate_number'     => 'required|string|max:255',
                'vin'                      => 'required|string|max:255',
                'make_model'               => 'required|string|max:255',
                'year_of_manufacture'      => 'required|integer|min:1990|max:' . (date('Y') + 1),
                'vehicle_type_id'          => 'required|exists:vehicle_types,id',
                'color'                    => 'nullable|string|max:50',
                'fuel_type'                => 'nullable|string|max:50',
                'status'                   => 'nullable|in:available,in_use,maintenance,out_of_service',
                'ownership_status'         => 'nullable|in:Owned,Leased,Rented',
                'cargo_weight'             => 'nullable|numeric|min:0',
                'cargo_volume'             => 'nullable|numeric|min:0',
                'load_type_compatibility'  => 'nullable|string|max:255',
                'registration_expiry_date' => 'nullable|date',
                'insurance_details'        => 'nullable|string|max:500',
                'insurance_expiry_date'    => 'nullable|date',
                'image'                    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->hasFile('image')) {
                if ($vehicle->image && Storage::disk('public')->exists($vehicle->image)) {
                    Storage::disk('public')->delete($vehicle->image);
                }

                $validatedData['image'] = $request->file('image')->store('vehicle_images', 'public');
            }

            $vehicle->update($validatedData);

            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Vehicle: ' . $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {


        $driver = Vehicle::findOrFail($id);

        $driver->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        Vehicle::whereIn('id', $request->ids)->delete();







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' vehicles  deleted successfully'
        ]);
    }
}
