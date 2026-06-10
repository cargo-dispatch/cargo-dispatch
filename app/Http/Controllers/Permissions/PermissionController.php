<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;


class PermissionController extends Controller
{


public function getPermissions($roleId)
{
    
    $role = Role::findOrFail($roleId);

   
    $permissions = DB::table('role_has_permissions')
        ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
        ->where('role_has_permissions.role_id', $roleId)
        ->pluck('permissions.name'); 
      

    return response()->json([
        'role' => $role->name,
        'permissions' => $permissions
    ]);
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

public function removePermission($roleId, $permissionId)
{
    $role = Role::findOrFail($roleId);
    $permission = Permission::findOrFail($permissionId);

    $role->revokePermissionTo($permission);

    return response()->json(['success' => true]);
}

public function getPermission($moduleId)
{
  
    $permissions = Permission::all();
  
    return response()->json($permissions);
}

public function savePermissions(Request $request)
{
    $roleId = $request->input('role_id');
    $rawPermissions = $request->input('permissions', []); // e.g. ['drivers.view', 'drivers.create']

    // Fetch role
    $role = Role::findOrFail($roleId);

    // Fetch permission IDs from permission names
    $permissionIds = Permission::whereIn('name', $rawPermissions)->pluck('id')->toArray();

    // Sync permissions for the role (clears old, sets new)
    DB::table('role_has_permissions')->where('role_id', $roleId)->delete();

    foreach ($permissionIds as $permissionId) {
        DB::table('role_has_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    return redirect()->route('roles.index')->with('success', 'Permissions updated successfully');
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

}
