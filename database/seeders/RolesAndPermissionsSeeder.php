<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Permission::firstOrCreate(['name' => 'view']);
Permission::firstOrCreate(['name' => 'create']);
Permission::firstOrCreate(['name' => 'edit']);
Permission::firstOrCreate(['name' => 'delete']);

// Create or update roles
$userRole = Role::firstOrCreate(['name' => 'user']);
$userRole->syncPermissions(['view']);

$adminRole = Role::firstOrCreate(['name' => 'admin']);
$adminRole->syncPermissions(['view', 'create', 'edit', 'delete']);

$superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
$superAdminRole->syncPermissions(['view', 'create', 'edit']);

// Assign role to user
$user = \App\Models\User::find(119);
$user->syncRoles(['admin']); 
    }
}
