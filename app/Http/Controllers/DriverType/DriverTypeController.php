<?php

namespace App\Http\Controllers\DriverType;

use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;

use App\Models\DriverType\DriverType;


use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverTypeController extends Controller
{
    public function index()
    {

        $data['name']  = "Driver Types";
        $data['total'] = DriverType::count();

        return view('driver_types.index', $data);
    }
  public function getUsers(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');

    $query = DriverType::query();

    // Order by latest first (assuming you have timestamps or an id column)
    $query->orderBy('id', 'desc'); // or use 'created_at' if you have timestamps

    if (!empty($searchTerm)) {
        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
    }

    $users = $query->paginate($perPage);

    $users->getCollection()->transform(function ($user) {
        $user->actions = [
            'edit' => route('driver.edit', $user->id),
            'delete' => route('driver.destroy', $user->id),
        ];
        return $user;
    });

    return response()->json($users);
}
    public function show($id)
    {

        $customer = DriverType::with(['audits.user'])->findOrFail($id);


        $audits = $customer->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });





        return response()->json([
            'name' => $customer->name,



            'audits' => $audits,
        ]);
    }

    public function create()
    {

        $data['name'] = "Driver";

        return view('driver_types.create', $data);
    }
    public function edit($edit)
    {

        $data['user'] = DriverType::findOrFail($edit);


        $data['name'] = "Driver";

        return view('driver_types.create', $data);
    }










    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DriverType::create([
                'name' => $request->name,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Driver Created Successfully']);
            }

            return redirect()->route('driver.index')->with('success', 'Driver Created Successfully');
        } catch (\Exception $e) {

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error creating driver',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error creating driver: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $user = DriverType::findOrFail($id);







            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user->update($validatedData);

            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('driver.index')->with('success', 'Driver Updated Successfully');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Driver not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Driver: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {


        $driver = DriverType::findOrFail($id);



        $driver = DriverType::findOrFail($id);





        $driver->delete();

        return redirect()->route('driver.index')
            ->with('success', 'Driver deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:driver_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        DriverType::whereIn('id', $request->ids)->delete();







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' drivers deleted successfully'
        ]);
    }
}
