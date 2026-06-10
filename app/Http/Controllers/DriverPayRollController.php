<?php

namespace App\Http\Controllers;

use App\Models\AssociatedDriver\AssociatedDriver;
use App\Models\Drivers\Driver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverPayRollController extends Controller
{
    public function index()
    {
        $data = [
            'name' => 'Driver Pay Roll',
            'drivers' => Driver::all(),
        ];
        return view('driver-payroll.index', $data);
    }

 public function getData(Request $request)
{
    // dd($request->all());
    // Validate required inputs
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'driver_id' => 'required|exists:drivers,id'
    ]);
  

    $start = $request->start_date;
    $end = $request->end_date;
    $driverId = $request->driver_id;
    $status = $request->status ?? 'complete';

    // Get driver info
    $driver = Driver::find($driverId);
     
    
    if (!$driver) {
        return response()->json([
            'success' => false,
            'message' => 'Driver not found'
        ], 404);
    }

    // Parse dates with proper time boundaries
    $startDate = Carbon::parse($start)->startOfDay();
    $endDate = Carbon::parse($end)->endOfDay();

    // Debug: Log the dates for verification
    \Log::info('Payroll Filter', [
        'driver_id' => $driverId,
        'start_date' => $startDate->toDateTimeString(),
        'end_date' => $endDate->toDateTimeString()
    ]);

    // Get shipments for this driver within the date range
    // Option 1: Only shipments delivered in date range (for completed work)
    $associatedDrivers = AssociatedDriver::with([
        'shipments.customer',
        'shipments.shipmentInvoice'
    ])
    ->where('driver_id', $driverId)
    ->whereHas('shipments', function ($query) use ($startDate, $endDate, $status) {
        $query->where('status', $status)
              ->whereNotNull('delivery_time')
              ->whereBetween('delivery_time', [$startDate, $endDate]);
    })
    ->get();
    // dd($associatedDrivers);

    // Alternative: If you want to include shipments based on pickup time instead
    /*
    $associatedDrivers = AssociatedDriver::with([
        'shipments.customer',
        'shipments.shipmentInvoice'
    ])
    ->where('driver_id', $driverId)
    ->whereHas('shipments', function ($query) use ($startDate, $endDate, $status) {
        $query->where('status', $status)
              ->whereNotNull('pickup_time')
              ->whereBetween('pickup_time', [$startDate, $endDate]);
    })
    ->get();
    */

    // Debug: Log the count of found shipments
    \Log::info('Found shipments', [
        'driver_id' => $driverId,
        'count' => $associatedDrivers->count()
    ]);

    // Calculate totals
    $totalShipments = 0;
    $totalMiles = 0;
    $totalAmount = 0;

    foreach ($associatedDrivers as $associated) {
        $shipment = $associated->shipments;
        
        if ($shipment) {
            $totalShipments++;
            $totalMiles += floatval($shipment->distance_miles ?? 0);
            
            $invoice = $shipment->shipmentInvoice ? $shipment->shipmentInvoice->first() : null;
            if ($invoice) {
                $totalAmount += floatval($invoice->total_with_profit ?? 0);
            }
        }
    }

    // Calculate driver earnings based on incentive per mile
    $perMileRate = floatval($driver->incentive ?? 0);
    $driverEarnings = $totalMiles * $perMileRate;

    // Return aggregated data
    $result = [
        'driver' => [
            'id' => $driver->id,
            'firstname' => $driver->firstname,
            'lastname' => $driver->lastname,
            'phoneno' => $driver->phoneno,
            'email' => $driver->email,
            'incentive' => $perMileRate,
        ],
        'period' => [
            'start_date' => $start,
            'end_date' => $end,
        ],
        'totals' => [
            'total_shipments' => $totalShipments,
            'total_miles' => round($totalMiles, 2),
            'per_mile_rate' => $perMileRate,
            'driver_earnings' => round($driverEarnings, 2),
            'total_revenue' => round($totalAmount, 2),
        ]
    ];

    return response()->json([
        'success' => true,
        'data' => [$result]
    ]);
}
    public function downloadPDF($driverId, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status', 'complete');

        // Get driver info
        $driver = Driver::find($driverId);
        
        if (!$driver) {
            abort(404, 'Driver not found');
        }

        // Parse dates
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get shipments for this driver in the date range
        $associatedDrivers = AssociatedDriver::with([
            'shipments.customer',
            'shipments.shipmentInvoice'
        ])
        ->where('driver_id', $driverId)
        ->whereHas('shipments', function ($query) use ($start, $end, $status) {
            $query->where('status', $status)
                  ->whereBetween('delivery_time', [$start, $end]);
        })
        ->get();

        // Get all shipments
        $shipments = $associatedDrivers->map(function ($associated) {
            return $associated->shipments;
        })->filter();

        // Calculate totals
        $totalShipments = $shipments->count();
        $totalMiles = $shipments->sum('distance_miles');
        $perMileRate = floatval($driver->incentive ?? 0);
        $totalEarnings = $totalMiles * $perMileRate;

        // Prepare data for PDF
        $data = [
            'driver' => $driver,
            'shipments' => $shipments,
            'company_name' => 'Cargo Dispatch',
            'report_date' => Carbon::now()->format('M d, Y'),
            'period_start' => Carbon::parse($startDate)->format('M d, Y'),
            'period_end' => Carbon::parse($endDate)->format('M d, Y'),
            'total_shipments' => $totalShipments,
            'total_miles' => $totalMiles,
            'per_mile_rate' => $perMileRate,
            'total_earnings' => $totalEarnings,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('driver-payroll.pdf', $data);
        
        $fileName = 'driver-payroll-' . $driver->firstname . '-' . $driver->lastname . '-' . Carbon::now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($fileName);
    }

    public function viewDetails($driverId, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status', 'complete');

        // Get driver info
        $driver = Driver::find($driverId);
        
        if (!$driver) {
            abort(404, 'Driver not found');
        }

        // Parse dates
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Get shipments for this driver in the date range
        $associatedDrivers = AssociatedDriver::with([
            'shipments.customer',
            'shipments.shipmentInvoice'
        ])
        ->where('driver_id', $driverId)
        ->whereHas('shipments', function ($query) use ($start, $end, $status) {
            $query->where('status', $status)
                  ->whereBetween('delivery_time', [$start, $end]);
        })
        ->get();

        // Get all shipments with details
        $shipments = $associatedDrivers->map(function ($associated) {
            return $associated->shipments;
        })->filter();

        // Calculate totals
        $totalShipments = $shipments->count();
        $totalMiles = $shipments->sum('distance_miles');
        $perMileRate = floatval($driver->incentive ?? 0);
        $totalEarnings = $totalMiles * $perMileRate;

        $data = [
            'driver' => $driver,
            'shipments' => $shipments,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_shipments' => $totalShipments,
            'total_miles' => $totalMiles,
            'per_mile_rate' => $perMileRate,
            'total_earnings' => $totalEarnings,
            'name' => 'Driver Payroll Details'
        ];

        return view('driver-payroll.details', $data);
    }
}