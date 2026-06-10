<?php

namespace App\Http\Controllers;

use App\Models\Customers\Customer;
use App\Models\Shipments\Shipment;
use App\Models\ShipmentInvoices\ShipmentInvoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ShipmentInvoiceController extends Controller
{
    /**
     * Display invoice report page
     */
    public function index()
    {
       
       
        $customers = Customer::select('id', 'first_name','last_name')->orderBy('customer_title')->get();
        $name = 'Shipment Invoice Reports';

        return view('shipment.invoice-report', compact('name', 'customers'));
    }

    /**
     * Generate invoice report data
     */
    public function getInvoiceData(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

 $query = Shipment::with(['customer', 'vehicleType', 'driver', 'shipmentInvoice'])
    ->whereDate('pickup_time', '>=', Carbon::parse($request->start_date)->startOfDay())
    ->whereDate('delivery_time', '<=', Carbon::parse($request->end_date)->endOfDay());


        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Only get shipments that have invoices
        $query->whereHas('shipmentInvoice');

        $shipments = $query->get();
      

        return response()->json([
            'success' => true,
            'data' => $shipments,
            'total' => $shipments->count()
        ]);
    }

    /**
     * Generate and download PDF invoice
     */
    public function generatePDF(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'status' => 'nullable|string',
        'customer_id' => 'nullable|exists:customers,id',
        'shipment_id' => 'nullable|exists:shipments,id',
    ]);

    // If specific shipment_id is provided, generate single invoice
    if ($request->shipment_id) {
        return $this->generateSingleInvoice($request->shipment_id);
    }

    // Otherwise generate bulk report for date range
    $query = Shipment::with(['customer', 'vehicleType', 'driver', 'shipmentInvoice'])
        ->whereDate('pickup_time', '>=', Carbon::parse($request->start_date)->startOfDay())
        ->whereDate('delivery_time', '<=', Carbon::parse($request->end_date)->endOfDay());

    if ($request->status) {
        $query->where('status', $request->status);
    }

    if ($request->customer_id) {
        $query->where('customer_id', $request->customer_id);
    }

    $query->whereHas('shipmentInvoice');
    $shipments = $query->get();

    if ($shipments->isEmpty()) {
        return back()->with('error', 'No invoices found for the selected criteria.');
    }

    // Calculate totals - Handle shipmentInvoice as a collection
    $totalCost = $shipments->sum(function($shipment) {
        // shipmentInvoice is a collection, so sum all invoices for this shipment
        return $shipment->shipmentInvoice->sum('total_cost');
    });

    $totalAmount = $shipments->sum(function($shipment) {
        // shipmentInvoice is a collection, so sum all invoices for this shipment
        return $shipment->shipmentInvoice->sum('total_with_profit');
    });

    $data = [
        'shipments' => $shipments,
        'name' => 'Cargo Dispatch',
        'report_title' => 'Invoice Report',
        'start_date' => Carbon::parse($request->start_date)->format('m/d/Y'),
        'end_date' => Carbon::parse($request->end_date)->format('m/d/Y'),
        'generated_date' => Carbon::now()->format('m/d/Y'),
        'total_cost' => $totalCost,
        'total_amount' => $totalAmount,
        'status_filter' => $request->status ? ucfirst(str_replace('_', ' ', $request->status)) : 'All',
        'customer_filter' => $request->customer_id ? Customer::find($request->customer_id)->customer_title : 'All',
    ];

    $pdf = Pdf::loadView('invoice.bulk-report', $data);
    $pdf->setPaper('a4', 'landscape');
    
    return $pdf->download('invoices-report-' . Carbon::now()->format('Y-m-d-His') . '.pdf');
}

    /**
     * Generate single invoice PDF
     */
    public function generateSingleInvoice($shipmentId)
    {
       
        $shipment = Shipment::with([
            'customer',
            'vehicleType',
            'driver',
            'shipmentInvoice'
        ])->findOrFail($shipmentId);

        if (!$shipment->shipmentInvoice) {
            return back()->with('error', 'No invoice found for this shipment.');
        }

        $invoice = $shipment->shipmentInvoice;


        $data = [
            'name' => 'Cargo Dispatch',
            'shipment' => $shipment,
            'description' => $shipment->special_instructions,
            'pickup_address' => $shipment->pickup_address,
            'drop_address' => $shipment->drop_address,
            'invoice_number' => str_pad($shipment->id, 7, '0', STR_PAD_LEFT),
            'today_date' => Carbon::parse($shipment->pickup_time)->format('m/d/Y'),
            'due_date' => Carbon::parse($shipment->pickup_time)->addDays(30)->format('m/d/Y'),
            'customer' => $shipment->customer,
'total_due' => optional($invoice->first())->total_with_profit ?? '00',
            'generated_date' => Carbon::now()->format('d/m/Y'),
'subtotal' => optional($invoice->first())->total_cost ?? '00',
'tax_amount' => optional($invoice->first())->tax_amount ?? 0,
'total' => optional($invoice->first())->total_with_profit ?? 0,
        ];

        $pdf = Pdf::loadView('invoice.single', $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('Invoice-Report-' . str_pad($shipment->id, 7, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Preview invoice before download
     */
    public function previewInvoice($shipmentId)
    {
        $shipment = Shipment::with([
            'customer',
            'vehicleType',
            'driver',
            'shipmentInvoice'
        ])->findOrFail($shipmentId);

        if (!$shipment->shipmentInvoice) {
            return back()->with('error', 'No invoice found for this shipment.');
        }

$invoice = $shipment->shipmentInvoice->first();
       

        $data = [
            'name' => 'Cargo Dispatch',
            'shipment' => $shipment,
             'pickup_address' => $shipment->pickup_address,
             'drop_address' => $shipment->drop_address,
            'invoice_number' => str_pad($shipment->id, 7, '0', STR_PAD_LEFT),
            'today_date' => Carbon::parse($shipment->pickup_time)->format('m/d/Y'),
            'due_date' => Carbon::parse($shipment->pickup_time)->addDays(30)->format('m/d/Y'),
            'customer' => $shipment->customer,
            'generated_date' => Carbon::now()->format('d/m/Y'),
            'subtotal' => $invoice->total_cost,
             'total_due'=>$invoice->total_with_profit,
              'description' => $shipment->special_instructions,
            'tax_amount' => $invoice->tax_amount ?? 0,
            'total' => $invoice->total_with_profit,
        ];

        return view('invoice.template', $data);
    }
}