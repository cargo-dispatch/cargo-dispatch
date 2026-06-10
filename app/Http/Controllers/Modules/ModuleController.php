<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ModuleRole;
use Illuminate\Http\Request;


use App\Models\Modules\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class ModuleController extends Controller
{
    public function index()
    {
        $data['users'] = Module::all();
        $data['name'] = "Module";
        
        
        return view('modules.index', $data);
    }
    
    public function create()
    {
        $data['name'] = 'Add Module';
        return view('modules.create', $data);
    }
    
    public function edit($id)
    {
        $data['results'] = Module::findOrFail($id);
        $data['name'] = 'Edit Module';
        
        return view('modules.create', $data);
    }
    
   

public function store(Request $request)
{
    try {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        
        $module = Module::create([
            'name' => $request->name,
        ]);

       
        $actions = ['view', 'create', 'update', 'delete'];

       
        foreach ($actions as $action) {
            $permissionName = strtolower($module->name) . '.' . $action;

           
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        return redirect()->route('modules.index')->with('success', 'Module Created and Permissions Generated Successfully');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->validator)->withInput();
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error creating Module: ' . $e->getMessage())->withInput();
    }
}

    public function update(Request $request, $id)
    {
       
        try {
            $user = Module::findOrFail($id);
            
         

            
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
               
            ]);
            
            $user->update([
                'name'     => $request->name,
               
            ]);
            
            return redirect()->route('modules.index')->with('success', 'Module Updated Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating Module: ' . $e->getMessage())->withInput();
        }
    }
    
    public function destroy($id)
{
    $user = Module::findOrFail($id);

    if ($user->delete()) {
        return response()->json(['success' => true, 'message' => 'Module deleted successfully.']);
    }

    return response()->json(['success' => false, 'message' => 'Delete failed.'], 500);
}



    public function show($id)
{
    $permissions = DB::table('module_role')
        ->where('role_id', $id)
        ->get()
        ->keyBy('module_id');

    $role = Role::findOrFail($id);
    $modules = Module::all();
    $name = "Assign Permissions";

    return view('modules.list', compact('role', 'permissions', 'modules', 'name'));
}
    public function getModules($roleId)
{
    $modules = Module::all(); 
    return response()->json($modules);
}









public function getRolePermissions($roleId)
{
    $role = Role::with('permissions')->findOrFail($roleId);

    $modules = Module::with(['permissions' => function ($query) use ($role) {
        $query->whereIn('id', $role->permissions->pluck('id'));
    }])->get();

    return view('roles.permissions_by_module', compact('role', 'modules'));
}
    
}