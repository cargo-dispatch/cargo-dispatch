<?php

namespace App\Http\Controllers\RoleController;

use App\Http\Controllers\Controller;
use App\Models\Modules\Module;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{
    public function index()
    {


        $roles = Role::all();
        $modules = Module::all();
     
        $name = 'Roles';
        return view('roles.roles', compact('roles','modules', 'name'));
    }

    public function create()
    {
        $name = 'Add Role';
        return view('roles.create', compact('name'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name'
        ]);

        Role::create(['name' => $request->name]);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }
    public function edit(Role $role)
    {
        $name = 'Edit Role';
        $results = $role;
        return view('roles.create', compact('results', 'name'));
    }




    public function permissions(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $name = "Assign Permissions";
        
        // Pass the $role to the view
        return view('modules.list', compact('role', 'permissions', 'rolePermissions', 'name'));
    }
    

public function assignPermissions(Request $request, Role $role)
{
    $request->validate([
        'permissions' => 'array'
    ]);

    $role->syncPermissions($request->permissions ?? []);

    return redirect()->route('roles.index')
        ->with('success', 'Permissions assigned successfully.');
}
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id
        ]);

        $role->update(['name' => $request->name]);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully');
    }
    public function destroy(Role $role)
{
    // Check if role is in use
    if ($role->users->count() > 0) {
        if (request()->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'This role cannot be deleted because it is assigned to users'
            ]);
        }
        
        return redirect()->route('roles.index')
            ->with('error', 'This role cannot be deleted because it is assigned to users');
    }
    
    // Delete the role
    $role->delete();
    
    // Return appropriate response based on request type
    if (request()->ajax()) {
        return response()->json(['success' => true]);
    }
    
    return redirect()->route('roles.index')
        ->with('success', 'Role deleted successfully');
}
public function showPermissionsForm(Role $role)
{
    return view('roles.assign_permissions', compact('role'));
}

public function getPermissions($roleId)
{
    $role = Role::find($roleId);
    $name = $role ? $role->name : null;

    $modules = Module::all();

    $permissions = DB::table('module_role')
        ->where('role_id', $roleId)
        ->get();

    return response()->json([
        'role' => $name, // add role name here
        'role_id' => $roleId,
        'modules' => $modules,
        'permissions' => $permissions
    ]);
}
public function removePermission($roleId, $permissionId)
{
    $role = Role::findOrFail($roleId);
    $permission = Permission::findOrFail($permissionId);

    $role->revokePermissionTo($permission);

    return response()->json(['success' => true]);
}


public function getPermission()
{
    $permissions = Permission::all(); // only fetch what you need
    return response()->json($permissions);
}

public function assignPermission(Request $request, Role $role)
{
    $request->validate([
        'permission_id' => 'required|exists:permissions,id',
    ]);

    $permission = Permission::find($request->permission_id);

    if (!$role->hasPermissionTo($permission)) {
        $role->givePermissionTo($permission);
    }

    return response()->json(['message' => 'Permission assigned successfully.']);
}


}
