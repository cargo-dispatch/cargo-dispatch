<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\CustomerWelcomeMail;
use App\Models\Customers\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index()
    {
        $data = [
            'name'           => 'Customer Management',
            'total'          => Customer::count(),
            'new_this_month' => Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'with_shipments' => Customer::has('shipments')->count(),
            'no_shipments'   => Customer::doesntHave('shipments')->count(),
        ];

        return view('customers.index', $data);
    }


   public function getCustomers(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', '');

    $query = Customer::query()->latest(); // Added latest() here

    if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('customer_title', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('phone', 'LIKE', '%' . $searchTerm . '%')
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                ->orWhereRaw("CONCAT_WS(' ', address1, address2, city, state, zip) LIKE ?", ["%{$searchTerm}%"])
                ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->input('date_from'));
    }
    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->input('date_to'));
    }

    $users = $query->paginate($perPage);
    $users->getCollection()->transform(function ($user) {
        $user->actions = [
            'edit'          => route('customers.edit', $user->id),
            'delete'        => route('customers.destroy', $user->id),
            'toggle_status' => route('customers.toggle-status', $user->id),
        ];

        return $user;
    });

    return response()->json($users);
}
    public function create()
    {

        $data = [

            'name' => 'Customer',
        ];




        return view('customers.create', $data);
    }

    public function edit($edit)
    {
        $data['user'] = Customer::findOrFail($edit); // ← Fix here
        // ← Add this to get vehicle types
        $data['name'] = "Customer";

        return view('customers.create', $data);
    }


    public function show($id)
    {
        $customer = Customer::with(['audits.user'])->findOrFail($id);

     // Temporary: Show audit in viewer's timezone (not hardcoded)
$viewerTimezone = 'Asia/Karachi'; // Change this per user

$audits = $customer->audits->map(function ($audit) use ($viewerTimezone) {
    $audit->created_at_display = $audit->created_at
        ->timezone($viewerTimezone)
        ->format('Y-m-d h:i:s A');
    return $audit;
});

        // Concatenate address parts with newlines
        $addressParts = array_filter([
            $customer->address1,
            $customer->address2,
            $customer->city,
            $customer->state,
            $customer->zip,
        ]);

        $fullAddress = implode("\n", $addressParts);

        return response()->json([
            'Name' => $customer->first_name . ' ' . $customer->last_name,
            'title' => $customer->customer_title,

            'email' => $customer->email,
            'phone' => $customer->phone,

            'full_address' => $fullAddress,
            'audits' => $audits,
        ]);
    }






    public function store(Request $request)
    {



        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'customer_title' => 'required|string|max:255',
            'email'          => 'required|email|unique:customers,email',
            'phone'          => 'required|string|max:20',
            'address1'       => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $tempPassword = Str::random(10);

            $customer = Customer::create([
                'first_name'     => $request->first_name,
                'last_name'      => $request->last_name,
                'address1'       => $request->address1,
                'address2'       => $request->address2,
                'city'           => $request->city,
                'state'          => $request->state,
                'zip'            => $request->zip,
                'customer_title' => $request->customer_title,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'password'       => Hash::make($tempPassword),
                'is_active'      => true,
            ]);

            // Send welcome email with temp credentials
            Mail::to($customer->email)->send(new CustomerWelcomeMail(
                $customer->first_name . ' ' . $customer->last_name,
                $customer->email,
                $tempPassword
            ));

            if ($request->ajax()) {
                return response()->json(['message' => 'Customer created successfully. Login credentials sent to ' . $customer->email]);
            }

            return redirect()->route('customers.index')->with('success', 'Customer created. Credentials emailed to ' . $customer->email);
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
            $customer = Customer::findOrFail($id);

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'customer_title' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|unique:customers,email,' . $customer->id,
                'phone'      => 'required|string|max:20',
                'address1'    => 'required|string|max:500',
                'city'    => 'required|string|max:500',
                'state'    => 'required|string|max:500',
                'zip'    => 'required|string|max:500',

            ]);
            if ($request->filled('password')) {
                $customer->password = Hash::make($request->password);
            }



            $customer->update($validatedData);

            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('customers.index')->with('success', 'Vehicle updated successfully');
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Customer not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Vehicle: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->is_active = !$customer->is_active;
        $customer->save();

        return response()->json([
            'success'   => true,
            'is_active' => $customer->is_active,
            'message'   => $customer->is_active ? 'Customer activated.' : 'Customer deactivated.',
        ]);
    }

    public function destroy($id)
    {


        $driver = Customer::findOrFail($id);

        $driver->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        Customer::whereIn('id', $request->ids)->delete();







        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' vehicles  deleted successfully'
        ]);
    }
}
