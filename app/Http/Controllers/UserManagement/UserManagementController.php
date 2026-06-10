<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use OwenIt\Auditing\Models\Audit;



class UserManagementController extends Controller
{


    public function index()
    {




        $data['name']       = "User Management";
        $data['total']      = User::count();
        $data['admins']     = User::where(function ($query) {
            $query->where('role_id', 23)
                ->orWhereHas('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', ['admin', 'super-admin']);
                });
        })->count();
        $data['new_month']  = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        return view('users.index', $data);
    }

    public function create()
    {
        $data['roles'] = Role::all();

        $data['name'] = 'Add User';
        return view('users.create', $data);
    }
public function getUsers(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');

    $query = User::with('role'); // Eager load the role relationship

    if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('phoneNumber', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('address1', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('address2', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('city', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('state', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('zip', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('email', 'LIKE', '%' . $searchTerm . '%')
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
              ->orWhereRaw("CONCAT_WS(' ', address1, address2, city, state, zip) LIKE ?", ["%{$searchTerm}%"]);
        });
    }

    $users = $query->paginate($perPage);
    
    $users->getCollection()->transform(function($user) {
        $user->role_name = $user->role ? $user->role->name : ''; // Add role name
        $user->actions = [
            'edit' => route('users.edit', $user->id),
            'delete' => route('users.destroy', $user->id),
        ];
        return $user;
    });

    return response()->json($users);
}
    /**
 * Display the specified user.
 *
 * @param int $id
 * @return \Illuminate\Http\JsonResponse
 */
public function show($id)
{
    try {
        /** @var \App\Models\User $customer */
        $customer = User::with(['audits.user', 'role'])->findOrFail($id);

        $audits = $customer->audits->map(function ($audit) {
            $audit->user_name = $audit->user ? $audit->user->name : null;
            return $audit;
        });
        
        $address = $customer->address1;
        if (!empty($customer->address2)) {
            $address .= ', ' . $customer->address2;
        }
        $fullAddress = $address;
        if (!empty($customer->city)) {
            $fullAddress .= ', ' . $customer->city;
        }
        if (!empty($customer->state)) {
            $fullAddress .= ', ' . $customer->state;
        }
        if (!empty($customer->zip)) {
            $fullAddress .= ', ' . $customer->zip;
        }

        return response()->json([
            'firstname' => $customer->first_name,
            'lastname' => $customer->last_name,
            'role' => $customer->role ? $customer->role->name : 'No Role',
            'email' => $customer->email,
            'phoneNumber' => $customer->phoneNumber,
            'address' => $fullAddress,
            'status' => $customer->status,
            'audits' => $audits,
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'User not found'], 404);
    }
}


    public function edit($id)
    {
        $data['roles'] = Role::all();
        $data['results'] = User::findOrFail($id);
        $data['name'] = 'Edit User';


        return view('users.create', $data);
    }

    public function store(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|unique:users,email',
                'status'     => 'required|string|in:active,in-active',
                'password'   => 'required|string|min:8|confirmed',
                'role_id'    => 'required|exists:roles,id',
            ]);

            $user = User::create([
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'email'         => $request->email,
                'phoneNumber'   => $request->phoneNumber,
                'role_id'       => $request->role_id,
                'status'        => $request->status,
                'address1'      => $request->address1,
                'address2'      => $request->address2,
                'city'          => $request->city,
                'state'         => $request->state,
                'zip'           => $request->zip,
                'password'      => Hash::make($request->password),
            ]);

            $role = Role::findById($request->role_id);
            $user->assignRole($role->name);

            $connectyCubeMessage = '';
            if ($request->role_id == 23) {
                try {
                    $user->syncConnectyCubeUser();
                    $connectyCubeMessage = ' User synced with ConnectyCube successfully.';
                } catch (\Exception $e) {
                    Log::error('ConnectyCube sync failed for new user: ' . $e->getMessage());
                    $connectyCubeMessage = ' User created but ConnectyCube sync failed. Please sync manually.';
                }
            }

            if ($request->ajax()) {
                return response()->json(['message' => 'User Created Successfully']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }




    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|unique:users,email,' . $id,
                'status'     => 'required|string|in:active,in-active',
                'password'   => 'nullable|string|min:6|confirmed',
                'role_id'    => 'required|exists:roles,id',
            ]);

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->phoneNumber = $request->phoneNumber;
            $user->status = $request->status;
            $user->address1 = $request->address1;
            $user->address2 = $request->address2;
            $user->state = $request->state;
            $user->zip = $request->zip;
            $user->city = $request->city;
            $user->email = $request->email;
            $user->role_id = $request->role_id;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Remove old role and assign new one
            $role = Role::find($request->role_id);
            if ($role) {
                $user->syncRoles([$role->name]);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'User updated successfully.']);
            }

            return redirect()->route('users')->with('success', 'User updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $request->ajax()
                ? response()->json(['success' => false, 'errors' => $e->errors()], 422)
                : redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {

        $user = User::findOrFail($id);

        if ($user->delete()) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 500);
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        User::whereIn('id', $request->ids)->delete();







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' drivers deleted successfully'
        ]);
    }

    public function getChatUsers()
    {
        try {
            $users = User::where('role_id', 23)
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phoneNumber',
                    'connectycube_id',
                    'connectycube_login',
                    'role_id'
                ])
                ->get();

            $chatUsers = $users->map(function ($user) {
                return $user->getConnectyCubeData();
            });

            return response()->json([
                'success' => true,
                'users' => $chatUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chat users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncWithConnectyCube()
    {
        try {
            $results = User::syncAllWithConnectyCube();

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
            'user_id' => 'required|exists:users,id',
            'connectycube_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            $user->update(['connectycube_id' => $request->connectycube_id]);

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
