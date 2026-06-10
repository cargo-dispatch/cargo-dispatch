<?php

namespace App\Http\Controllers\VehicleType;

use App\Http\Controllers\Controller;
use App\Models\VehicleType\VehicleType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleTypeController extends Controller
{
    public function index()
    {

        $data['name']  = "Vehicle Types";
        $data['total'] = VehicleType::count();

        return view('vehicle_types.index', $data);
    }

 public function getVehicleTypes(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');

    $query = VehicleType::query()->latest(); // Added latest() here

    if (!empty($searchTerm)) {
        $query->where(function($q) use ($searchTerm) {
            $q->where('vehicle_type', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('avg_fuel_efficiency', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('driver_cost_per_mile', 'LIKE', '%' . $searchTerm . '%');
        });
    }
    
    $users = $query->paginate($perPage);
    
    $users->getCollection()->transform(function ($user) {
        $user->actions = [
            'edit' => route('vehiclestype.edit', $user->id),
            'delete' => route('vehiclestype.destroy', $user->id),
        ];
        return $user;
    });

    return response()->json($users);
}
    public function show($id)
    {

        $customer = VehicleType::with(['audits.user'])->findOrFail($id);


        $audits = $customer->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });





        return response()->json([
            'name' => $customer->vehicle_type,


            'audits' => $audits,
        ]);
    }
    public function create()
    {

        $data['name'] = "Vehicle Type";

        return view('vehicle_types.create', $data);
    }

    public function edit($edit)
    {


        $data['user'] = VehicleType::findOrFail($edit);


        $data['name'] = "Vehicle Type";

        return view('vehicle_types.create', $data);
    }



    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'vehicle_type'         => 'required|string|max:255|unique:vehicle_types,vehicle_type',
            'image'                => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'avg_fuel_efficiency'  => 'required|numeric|min:0',
            'driver_cost_per_mile' => 'required|numeric|min:0',
            'insurance_per_mile'   => 'nullable|numeric|min:0',
            'maintenance_per_mile' => 'nullable|numeric|min:0',
            'overhead_per_mile'    => 'nullable|numeric|min:0',
            'ifta_per_mile'        => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('vehicle_images', 'public_storage');
            }

            VehicleType::create([
                'vehicle_type'         => $request->vehicle_type,
                'image'                => $imagePath,
                'avg_fuel_efficiency'  => $request->avg_fuel_efficiency,
                'driver_cost_per_mile' => $request->driver_cost_per_mile,
                'insurance_per_mile'   => $request->insurance_per_mile ?? 0.10,
                'maintenance_per_mile' => $request->maintenance_per_mile ?? 0.15,
                'overhead_per_mile'    => $request->overhead_per_mile ?? 0.10,
                'ifta_per_mile'        => $request->ifta_per_mile ?? 0.05,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Vehicle Type Created Successfully']);
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle Type Created Successfully');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error creating vehicle type',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $vehicleType = VehicleType::findOrFail($id);

            $validatedData = $request->validate([
                'vehicle_type'         => 'required|string|max:255',
                'image'                => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'avg_fuel_efficiency'  => 'required|numeric|min:0',
                'driver_cost_per_mile' => 'required|numeric|min:0',
                'insurance_per_mile'   => 'nullable|numeric|min:0',
                'maintenance_per_mile' => 'nullable|numeric|min:0',
                'overhead_per_mile'    => 'nullable|numeric|min:0',
                'ifta_per_mile'        => 'nullable|numeric|min:0',
            ]);

            if ($request->hasFile('image')) {
                if ($vehicleType->image && Storage::disk('public')->exists($vehicleType->image)) {
                    Storage::disk('public')->delete($vehicleType->image);
                }

                $validatedData['image'] = $request->file('image')->store('vehicle_images', 'public');
            } else {
                unset($validatedData['image']);
            }

            $vehicleType->update($validatedData);

            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle Type Updated Successfully');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Vehicle Type not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Vehicle Type: ' . $e->getMessage()], 500);
        }
    }


    public function destroy($id)
    {


        $driver = VehicleType::findOrFail($id);

        $driver->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle Type deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:vehicle_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        VehicleType::whereIn('id', $request->ids)->delete();







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' vehicles type deleted successfully'
        ]);
    }
}
