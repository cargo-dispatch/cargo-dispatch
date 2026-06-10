<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Drivers\Driver;
use App\Models\Maintenance\Maintenance;
use App\Models\Shipments\Shipment;
use App\Models\Vehicles\Vehicle;
use App\Models\VehicleAssignment\VehicleAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $data = [];

        $data['customers'] = Customer::count();
        $data['drivers']   = Driver::count();
        $data['vehicles']  = Vehicle::count();
        $data['name']      = 'Dashboard';

        $currentUser       = Auth::user();
        $token             = $currentUser->createToken('dashboard-token')->plainTextToken;
        $data['userToken'] = $token;

        $data['shipments'] = Shipment::whereIn('status', ['pending', 'active'])
            ->whereDate('pickup_time', '=', Carbon::today())
            ->whereDate('delivery_time', '>=', Carbon::today())
            ->count();

        $data['shipmentsYesterday'] = Shipment::whereIn('status', ['pending', 'active'])
            ->whereDate('pickup_time', '=', Carbon::yesterday())
            ->whereDate('delivery_time', '>=', Carbon::yesterday())
            ->count();

        $data['shipmentPercentageChange'] = $data['shipmentsYesterday'] > 0
            ? round((($data['shipments'] - $data['shipmentsYesterday']) / $data['shipmentsYesterday']) * 100, 1)
            : 0;

        $data['newCustomersThisMonth'] = Customer::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonthCustomers = Customer::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $data['customerPercentageChange'] = $lastMonthCustomers > 0
            ? round((($data['newCustomersThisMonth'] - $lastMonthCustomers) / $lastMonthCustomers) * 100, 1)
            : ($data['newCustomersThisMonth'] > 0 ? 100 : 0);

        $data['newDriversThisMonth'] = Driver::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonthDrivers = Driver::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $data['driverPercentageChange'] = $lastMonthDrivers > 0
            ? round((($data['newDriversThisMonth'] - $lastMonthDrivers) / $lastMonthDrivers) * 100, 1)
            : ($data['newDriversThisMonth'] > 0 ? 100 : 0);

        $data['newVehiclesThisMonth'] = Vehicle::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonthVehicles = Vehicle::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $data['vehiclePercentageChange'] = $lastMonthVehicles > 0
            ? round((($data['newVehiclesThisMonth'] - $lastMonthVehicles) / $lastMonthVehicles) * 100, 1)
            : ($data['newVehiclesThisMonth'] > 0 ? 100 : 0);

        $months = collect();
        for ($i = 6; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('M Y'));
        }

        // FIX: Use delivery_time for earnings (when shipments are completed)
        $earnings = Shipment::select(
            DB::raw("DATE_FORMAT(delivery_time, '%b %Y') as month"),
            DB::raw("SUM(estimated_cost) as total")
        )
            ->where('delivery_time', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->whereIn('status', ['delivered', 'complete']) // support both status values
            ->groupBy('month')
            ->pluck('total', 'month');

        $data['monthlyEarnings'] = $months->map(function ($month) use ($earnings) {
            return ['month' => $month, 'total' => $earnings->get($month, 0)];
        });

        $data['scheduledMaintenance'] = Maintenance::with(['vehicle', 'maintenanceType'])
            ->where('status', 'scheduled')
            ->whereDate('maintenance_date', '<=', now()->addDays(7))
            ->orderBy('maintenance_date', 'asc')
            ->get();

        $data['scheduledMaintenanceExpiry'] = Maintenance::with(['vehicle', 'maintenanceType'])
            ->where('alert_status', 'active')
            ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
            ->orderBy('next_maintenance_date', 'asc')
            ->get();

        $data['maintenanceExpiry'] = [];

        // FIX: Show all statuses in pie chart, not just complete/cancel
        $shipmentStatusData = Shipment::where('created_at', '>=', Carbon::now()->subDays(180))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Don't filter out any statuses - show all 4: pending, active, complete, cancel
        $data['shipmentStatusData'] = $shipmentStatusData->filter(function ($count) {
            return $count > 0;
        });

        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        $shipments = Shipment::whereNotNull('pickup_address')
            ->where('pickup_address', '!=', '')
            ->whereNotNull('pickup_time')
            ->whereMonth('pickup_time', $currentMonth)
            ->whereYear('pickup_time', $currentYear)
            ->get();

        $regionCounts = $shipments->groupBy(function ($shipment) {
            return $this->extractRegionFromAddress($shipment->pickup_address);
        })->map(fn($group) => $group->count())->sortDesc()->take(5);

        $totalShipments = $regionCounts->sum();

        $data['shipmentsByRegion'] = $regionCounts->map(function ($count, $region) use ($totalShipments) {
            return [
                'region'       => $region,
                'count'        => $count,
                'percentage'   => $totalShipments > 0 ? round(($count / $totalShipments) * 100) : 0,
                'country_code' => $this->getCountryCode($region),
            ];
        });

        $data['topStates']    = $this->getTopStates('week');
        $data['topCustomers'] = $this->getTopCustomers('week');
        $data['activityData'] = $this->getActivityData('week');
        
        // FIX: Format initial orders data the same way as AJAX
        $data['ordersData']   = $this->formatOrdersForJson($this->getOrdersData('today'));

        $todayQuery = Shipment::whereDate('pickup_time', Carbon::today());

        // FIX: Add 'total' count for proper "All" tab calculation
        $data['orderStatusCounts'] = [
            'total'      => (clone $todayQuery)->count(), // NEW: Total count for "All" tab
            'pending'    => (clone $todayQuery)->where('status', 'pending')->count(),
            'active'     => (clone $todayQuery)->where('status', 'active')->count(),
            'complete'   => (clone $todayQuery)->where('status', 'complete')->count(),
            'cancel'     => (clone $todayQuery)->where('status', 'cancel')->count(),
            'assigned'   => (clone $todayQuery)->whereNotNull('driver_id')->whereNotNull('vehicle_id')->count(),
            'unassigned' => (clone $todayQuery)->where(function ($q) {
                $q->whereNull('driver_id')->orWhereNull('vehicle_id');
            })->count(),
        ];

        return view('dashboard.index', $data);
    }

    // ── AJAX: Top States ──
    public function getTopStatesAjax(Request $request)
    {
        $period = $request->input('period', 'week');
        return response()->json(['topStates' => $this->getTopStates($period)]);
    }

    // ── AJAX: Top Customers ──
    public function getTopCustomersAjax(Request $request)
    {
        $period = $request->input('period', 'week');
        return response()->json(['topCustomers' => $this->getTopCustomers($period)]);
    }

    private function getTopStates($period = 'week', $limit = 3)
    {
        $query = Shipment::whereNotNull('pickup_address')->where('pickup_address', '!=', '');

        switch ($period) {
            case 'week':
                $query->where('pickup_time', '>=', Carbon::now()->startOfWeek());
                break;
            case 'month':
                $query->where('pickup_time', '>=', Carbon::now()->startOfMonth());
                break;
            case 'year':
                $query->where('pickup_time', '>=', Carbon::now()->startOfYear());
                break;
        }

        $shipments   = $query->get();
        $stateCounts = [];

        foreach ($shipments as $shipment) {
            $state = $this->extractStateFromAddress($shipment->pickup_address);
            if ($state && $state !== 'Unknown') {
                $stateCounts[$state] = ($stateCounts[$state] ?? 0) + 1;
            }
        }

        arsort($stateCounts);
        $topStates      = array_slice($stateCounts, 0, $limit, true);
        $totalShipments = array_sum($stateCounts);
        $result         = [];

        foreach ($topStates as $stateName => $count) {
            $result[] = [
                'name'       => $stateName,
                'avatar'     => $this->getStateAbbreviation($stateName),
                'shipments'  => $count,
                'percentage' => $totalShipments > 0 ? round(($count / $totalShipments) * 100) : 0,
            ];
        }

        // FIX: Return empty array instead of fake data
        // if (empty($result)) {
        //     $result = [
        //         ['name' => 'California', 'avatar' => 'CA', 'shipments' => 210, 'percentage' => 45],
        //         ['name' => 'Texas',      'avatar' => 'TX', 'shipments' => 185, 'percentage' => 32],
        //         ['name' => 'New York',   'avatar' => 'NY', 'shipments' => 156, 'percentage' => 23],
        //     ];
        // }

        return $result;
    }

    private function getTopCustomers($period = 'week', $limit = 3)
    {
        $dateFilter = match ($period) {
            'month' => Carbon::now()->startOfMonth(),
            'year'  => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek(),
        };

        $topCustomers = Customer::select(
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                DB::raw('COUNT(shipments.id) as order_count')
            )
            ->leftJoin('shipments', function ($join) use ($dateFilter) {
                $join->on('customers.id', '=', 'shipments.customer_id')
                     ->where('shipments.pickup_time', '>=', $dateFilter);
            })
            ->groupBy('customers.id', 'customers.first_name', 'customers.last_name')
            ->orderBy('order_count', 'desc')
            ->limit($limit)
            ->get();

        $totalOrders = Shipment::where('pickup_time', '>=', $dateFilter)->count();
        $result      = [];

        foreach ($topCustomers as $customer) {
            $orderCount = $customer->order_count ?? 0;
            $name       = trim($customer->first_name . ' ' . $customer->last_name);
            $result[]   = [
                'name'       => $name,
                'company'    => $name ?: 'Individual Customer',
                'orders'     => $orderCount,
                'percentage' => $totalOrders > 0 ? round(($orderCount / $totalOrders) * 100) : 0,
                'avatar'     => strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)),
            ];
        }

        // FIX: Return empty array instead of fake data
        // if (empty($result)) {
        //     $result = [
        //         ['name' => 'Adem Barnes',   'company' => 'Technology Inc.',  'orders' => 210, 'percentage' => 95, 'avatar' => 'AB'],
        //         ['name' => 'Sarah Johnson', 'company' => 'Global Solutions', 'orders' => 185, 'percentage' => 87, 'avatar' => 'SJ'],
        //         ['name' => 'Michael Chen',  'company' => 'Innovation Labs',  'orders' => 156, 'percentage' => 82, 'avatar' => 'MC'],
        //     ];
        // }

        return $result;
    }

    private function extractStateFromAddress($address)
    {
        if (empty($address)) return null;
        $address = preg_replace('/\s+/', ' ', trim($address));
        if (preg_match('/,\s*([A-Z]{2})\s*\d{5}/', $address, $matches)) {
            $states = [
                'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
                'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
                'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
                'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
                'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi',
                'MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire',
                'NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina',
                'ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania',
                'RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee',
                'TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington',
                'WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
            ];
            return $states[$matches[1]] ?? null;
        }
        return null;
    }

    private function getStateAbbreviation($stateName)
    {
        $states = [
            'Alabama'=>'AL','Alaska'=>'AK','Arizona'=>'AZ','Arkansas'=>'AR','California'=>'CA',
            'Colorado'=>'CO','Connecticut'=>'CT','Delaware'=>'DE','Florida'=>'FL','Georgia'=>'GA',
            'Hawaii'=>'HI','Idaho'=>'ID','Illinois'=>'IL','Indiana'=>'IN','Iowa'=>'IA',
            'Kansas'=>'KS','Kentucky'=>'KY','Louisiana'=>'LA','Maine'=>'ME','Maryland'=>'MD',
            'Massachusetts'=>'MA','Michigan'=>'MI','Minnesota'=>'MN','Mississippi'=>'MS',
            'Missouri'=>'MO','Montana'=>'MT','Nebraska'=>'NE','Nevada'=>'NV','New Hampshire'=>'NH',
            'New Jersey'=>'NJ','New Mexico'=>'NM','New York'=>'NY','North Carolina'=>'NC',
            'North Dakota'=>'ND','Ohio'=>'OH','Oklahoma'=>'OK','Oregon'=>'OR','Pennsylvania'=>'PA',
            'Rhode Island'=>'RI','South Carolina'=>'SC','South Dakota'=>'SD','Tennessee'=>'TN',
            'Texas'=>'TX','Utah'=>'UT','Vermont'=>'VT','Virginia'=>'VA','Washington'=>'WA',
            'West Virginia'=>'WV','Wisconsin'=>'WI','Wyoming'=>'WY',
        ];
        return $states[$stateName] ?? strtoupper(substr($stateName, 0, 2));
    }

    private function getActivityData($period = 'week')
    {
        $labels = [];
        $data   = [];

        switch ($period) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date     = Carbon::now()->subDays($i);
                    $labels[] = $date->format('d M');
                    $data[]   = Shipment::whereDate('created_at', $date->format('Y-m-d'))->count();
                }
                break;
            case 'month':
                // FIX: Show actual current month data, not last 30 days
                $startOfMonth = Carbon::now()->startOfMonth();
                $today = Carbon::now();
                $daysInCurrentMonth = $startOfMonth->diffInDays($today) + 1;
                
                for ($i = 0; $i < $daysInCurrentMonth; $i++) {
                    $date = $startOfMonth->copy()->addDays($i);
                    $labels[] = $date->format('d M');
                    $data[] = Shipment::whereDate('created_at', $date->format('Y-m-d'))->count();
                }
                break;
            case '6months':
                for ($i = 5; $i >= 0; $i--) {
                    $date     = Carbon::now()->subMonths($i);
                    $labels[] = $date->format('M Y');
                    $data[]   = Shipment::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
                }
                break;
            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $date     = Carbon::now()->subMonths($i);
                    $labels[] = $date->format('M Y');
                    $data[]   = Shipment::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
                }
                break;
        }

        return ['labels' => $labels, 'data' => $data, 'total' => array_sum($data)];
    }

    /**
     * Returns raw Eloquent collection for processing
     */
    private function getOrdersData($period = 'today', $status = null)
    {
        $query = Shipment::with(['customer', 'drivers', 'vehicle.vehicleAssignment.driver', 'associatedDrivers.drivers'])
            ->select('id', 'pickup_address', 'drop_address', 'delivery_time', 'pickup_time', 'status', 'created_at', 'customer_id', 'driver_id', 'vehicle_id')
            ->orderBy('pickup_time', 'desc');

        switch ($period) {
            case 'today':
                $query->whereDate('pickup_time', Carbon::today());
                break;
            case 'week':
                $query->where('pickup_time', '>=', Carbon::now()->startOfWeek());
                break;
            case 'month':
                $query->where('pickup_time', '>=', Carbon::now()->startOfMonth());
                break;
        }

        if ($status === 'assigned') {
            $query->whereNotNull('driver_id')->whereNotNull('vehicle_id');
        } elseif ($status === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('driver_id')->orWhereNull('vehicle_id');
            });
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Formats orders into a clean array for JSON / AJAX responses.
     * Explicitly maps driver firstname/lastname so the JS always gets
     * the correct field names regardless of model serialisation quirks.
     */
    private function formatOrdersForJson($orders): array
    {
        return $orders->map(function ($order) {
            // Driver — check direct driver_id, then vehicle assignment, then associated drivers
            $driverData = null;
            if ($order->drivers) {
                $driverData = [
                    'firstname' => $order->drivers->firstname ?? '',
                    'lastname'  => $order->drivers->lastname  ?? '',
                ];
            } elseif ($order->vehicle && $order->vehicle->vehicleAssignment && $order->vehicle->vehicleAssignment->driver) {
                $d = $order->vehicle->vehicleAssignment->driver;
                $driverData = [
                    'firstname' => $d->firstname ?? '',
                    'lastname'  => $d->lastname  ?? '',
                ];
            } elseif ($order->associatedDrivers->isNotEmpty()) {
                $d = $order->associatedDrivers->first()->drivers;
                if ($d) {
                    $driverData = [
                        'firstname' => $d->firstname ?? '',
                        'lastname'  => $d->lastname  ?? '',
                    ];
                }
            }

            // Vehicle
            $vehicleData = null;
            if ($order->vehicle) {
                $vehicleData = [
                    'vehicle_id' => $order->vehicle->vehicle_id ?? 'N/A',
                ];
            }

            return [
                'id'             => $order->id,
                'pickup_address' => $order->pickup_address,
                'drop_address'   => $order->drop_address,
                'status'         => $order->status,
                'driver'         => $driverData,
                'vehicle'        => $vehicleData,
            ];
        })->toArray();
    }

    public function getActivityDataAjax(Request $request)
    {
        $period = $request->input('period', 'week');
        return response()->json($this->getActivityData($period));
    }

    public function getOrdersDataAjax(Request $request)
    {
        $period = $request->input('period', 'today');
        $status = $request->input('status', null);
        $orders = $this->getOrdersData($period, $status);

        $base = Shipment::query();
        switch ($period) {
            case 'today':
                $base->whereDate('pickup_time', Carbon::today());
                break;
            case 'week':
                $base->where('pickup_time', '>=', Carbon::now()->startOfWeek());
                break;
            case 'month':
                $base->where('pickup_time', '>=', Carbon::now()->startOfMonth());
                break;
        }

        // FIX: Include 'total' count
        $statusCounts = [
            'total'      => (clone $base)->count(), // NEW: Total for "All" tab
            'pending'    => (clone $base)->where('status', 'pending')->count(),
            'active'     => (clone $base)->where('status', 'active')->count(),
            'complete'   => (clone $base)->where('status', 'complete')->count(),
            'cancel'     => (clone $base)->where('status', 'cancel')->count(),
            'assigned'   => (clone $base)->whereNotNull('driver_id')->whereNotNull('vehicle_id')->count(),
            'unassigned' => (clone $base)->where(function ($q) {
                $q->whereNull('driver_id')->orWhereNull('vehicle_id');
            })->count(),
        ];

        return response()->json([
            // Use formatOrdersForJson() so driver firstname/lastname are
            // always present as explicit keys in the JSON — no surprises.
            'orders'       => $this->formatOrdersForJson($orders),
            'statusCounts' => $statusCounts,
        ]);
    }

    private function extractRegionFromAddress($address)
    {
        if (empty($address)) return 'Unknown';
        $address = preg_replace('/\s+/', ' ', trim($address));
        if (preg_match('/,\s*([A-Z]{2})\s+\d{5}/', $address, $matches)) {
            $states = [
                'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
                'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
                'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
                'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
                'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi',
                'MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire',
                'NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina',
                'ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania',
                'RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee',
                'TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington',
                'WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming','DC'=>'Washington DC',
            ];
            return $states[$matches[1]] ?? $matches[1];
        }
        $usStates = ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut',
            'Delaware','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas',
            'Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota',
            'Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey',
            'New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon',
            'Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas',
            'Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'];
        foreach ($usStates as $state) {
            if (stripos($address, $state) !== false) return $state;
        }
        return 'Unknown';
    }

    private function getCountryCode($region)
    {
        $region   = strtolower($region);
        $usStates = ['alabama','alaska','arizona','arkansas','california','colorado','connecticut',
            'delaware','florida','georgia','hawaii','idaho','illinois','indiana','iowa','kansas',
            'kentucky','louisiana','maine','maryland','massachusetts','michigan','minnesota',
            'mississippi','missouri','montana','nebraska','nevada','new hampshire','new jersey',
            'new mexico','new york','north carolina','north dakota','ohio','oklahoma','oregon',
            'pennsylvania','rhode island','south carolina','south dakota','tennessee','texas',
            'utah','vermont','virginia','washington','west virginia','wisconsin','wyoming'];
        if (in_array($region, $usStates)) return 'us';
        $map = ['canada'=>'ca','mexico'=>'mx','united kingdom'=>'gb','uk'=>'gb'];
        return $map[$region] ?? 'us';
    }

    public function createSession(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        return response()->json([
            'success'     => true,
            'credentials' => [
                'appId'      => config('connectycube.appId'),
                'authKey'    => config('connectycube.authKey'),
                'authSecret' => config('connectycube.authSecret'),
            ],
            'user' => [
                'id'                    => $user->id,
                'connectycube_id'       => $user->connectycube_id,
                'connectycube_login'    => $user->connectycube_login,
                'connectycube_password' => $user->connectycube_password,
            ],
        ]);
    }

    public function getLoadboardLoads(Request $request)
    {
        try {
            $scraper = new \App\Services\FreightFinderScraper();
            $origin  = $request->input('origin', 'Dallas, TX');
            $pages   = (int) $request->input('pages', 4);
            $pages   = max(1, min($pages, 8));
            $loads   = $scraper->getLoads($origin, 500, $pages);
            return response()->json(['success' => true, 'loads' => $loads, 'total' => count($loads)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getLoadboardDetail(Request $request)
    {
        try {
            $id = $request->input('id');
            if (!$id || !ctype_digit((string) $id)) {
                return response()->json(['success' => false, 'message' => 'Invalid ID'], 422);
            }
            $scraper = new \App\Services\FreightFinderScraper();
            $detail  = $scraper->getDetail($id);
            return response()->json(['success' => true, 'detail' => $detail]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRateIntelligence(Request $request)
    {
        try {
            $origin  = $request->input('origin', 'Dallas, TX');
            $pages   = (int) $request->input('pages', 2);
            $pages   = max(1, min($pages, 6));

            // Allow cache bust via ?bust=1
            if ($request->boolean('bust')) {
                $cacheKey = 'rate_intel_' . md5($origin . $pages);
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            $service = new \App\Services\Ai\RateIntelligenceService(
                new \App\Services\Ai\GeminiClient(),
                new \App\Services\FreightFinderScraper()
            );
            $lanes = $service->getLaneIntelligence($origin, $pages);
            return response()->json(['success' => true, 'lanes' => $lanes]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('getRateIntelligence failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}