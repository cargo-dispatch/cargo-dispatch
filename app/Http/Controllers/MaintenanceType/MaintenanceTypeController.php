<?php

namespace App\Http\Controllers\MaintenanceType;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceType\MaintenanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class MaintenanceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
    {
          $data['name']  = "Maintenance Types";
          $data['total'] = MaintenanceType::count();

        return view('maintenance_type.index',$data);
      
    }
 public function getUsers(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');
    


        $query = MaintenanceType::query();



      

            if (!empty($searchTerm)) {
            $query->where('maintenance_types', 'LIKE', '%' . $searchTerm . '%');
        }
            $users = $query->paginate($perPage);
            $users->getCollection()->transform(function($user) {
            $user->actions = [
                'edit' => route('maintenance_type.edit', $user->id),
                'delete' => route('maintenance_type.destroy', $user->id),
            ];
            return $user;
        });
       
    
        return response()->json($users);
    }

    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       $data['name'] = "Maintenance Type";
    
    return view('maintenance_type.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'maintenance_types' => 'required|string|max:100',
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
        MaintenanceType::create([
            'maintenance_types' => $request->maintenance_types,
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'maintenance_types Created Successfully']);
        }

        return redirect()->route('maintenance_type.index')->with('success', 'maintenance_types Created Successfully');

    } catch (\Exception $e) {

        if ($request->ajax()) {
            return response()->json([
                'message' => 'Error creating maintenance_types',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error creating maintenance_types: ' . $e->getMessage())->withInput();



    }
}

    /**
     * Display the specified resource.
     */
 public function show($id)
{
  
    $customer = MaintenanceType::with(['audits.user'])->findOrFail($id);
   

    $audits = $customer->audits->map(function ($audit) {
        $audit->user_name = $audit->user ? $audit->user->name : null;
        return $audit;
    });

   

   

    return response()->json([
        'maintenance_types' => $customer->maintenance_types,
      
        
       
        'audits' => $audits,
    ]);
}


    /**
     * Show the form for editing the specified resource.
     */
 public function edit($edit){
 
    $data['user'] = MaintenanceType::findOrFail($edit);


    $data['name'] = "Maintenance Type";
    
    return view('maintenance_type.create', $data);
}
    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
{
    try {

        $user = MaintenanceType::findOrFail($id);



   



        $validatedData = $request->validate([
            'maintenance_types' => 'required|string|max:255',
        ]);

        $user->update($validatedData);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('maintenance_type.index')->with('success', 'Maintenance type Updated Successfully');

    } catch (ValidationException $e) {
        if ($request->ajax()) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        throw $e;
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Maintenance type not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error updating Maintenance type: ' . $e->getMessage()], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
   public function destroy($id)
    {


        $maintenance_type = MaintenanceType::findOrFail($id);



     


     


         $maintenance_type->delete();
        
        return redirect()->route('maintenance_type.index')
            ->with('success', ' Maintenance type deleted successfully');
    }



        public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:maintenance_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        MaintenanceType::whereIn('id', $request->ids)->delete();



    



        return response()->json([
            'success' => true, 
            'message' => count($request->ids) . ' maintenance types deleted successfully'
        ]);
    }   
}
