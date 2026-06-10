<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            background-color: #6E6E6E;
            color: white;
            padding: 20px 30px;
            margin-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }

        .header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header-right p {
            margin: 3px 0;
            font-size: 11px;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px 30px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .filter-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .filter-label {
            display: table-cell;
            width: 25%;
            font-weight: bold;
        }

        .filter-value {
            display: table-cell;
            width: 75%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #343a40;
            color: white;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-weight: bold;
            font-size: 11px;
        }

        table td {
            padding: 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-active {
            background-color: #17a2b8;
            color: #fff;
        }

        .badge-complete {
            background-color: #28a745;
            color: #fff;
        }

        .badge-cancel {
            background-color: #dc3545;
            color: #fff;
        }

        .summary-section {
            background-color: #e9ecef;
            padding: 15px 30px;
            margin-top: 20px;
            border: 2px solid #6E6E6E;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: bold;
            font-size: 14px;
        }

        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #6E6E6E;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <img src="{{ public_path('assets/img/logo.png') }}" alt="Logo" style="width:60px;height:auto;margin-bottom:8px;display:block;">
                <h2>{{ $name }}</h2>
                <p>{{ $report_title }}</p>
            </div>
            <div class="header-right">
                <p><strong>Generated:</strong> {{ $generated_date }}</p>
                <p><strong>Period:</strong> {{ $start_date }} to {{ $end_date }}</p>
                <p><strong>Total Records:</strong> {{ $shipments->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Filters Applied -->
    <div class="filter-section">
      
        <div class="filter-row">
            <div class="filter-label">Date Range:</div>
            <div class="filter-value">{{ $start_date }} to {{ $end_date }}</div>
        </div>
       
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Pickup Location</th>
                <th>Drop Location</th>
                <th>Distance</th>
                <th>Status</th>
          
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shipments as $shipment)
            @php
                $invoice = $shipment->shipmentInvoice;
            @endphp
            <tr>
                <td>{{ str_pad($shipment->id, 7, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $shipment->customer->customer_title ?? 'N/A' }}</td>
                <td>{{ $start_date }} </td>
                <td>{{ Str::limit($shipment->pickup_address, 35) }}</td>
                <td>{{ Str::limit($shipment->drop_address, 35) }}</td>
                <td>{{ $shipment->distance_miles ?? 'N/A' }} mi</td>
                <td class="text-center">
                    <span class="badge badge-{{ $shipment->status }}">
                        {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                    </span>
                </td>
              
                <td class="text-right">{{$total_amount}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <h3 style="margin-bottom: 15px;">Summary</h3>
        <div class="summary-row">
            <div class="summary-label">Total Shipments:</div>
            <div class="summary-value">{{ $shipments->count() }}</div>
        </div>
        
        <div class="summary-row" style="border-top: 2px solid #6E6E6E; padding-top: 8px; margin-top: 8px;">
            <div class="summary-label">Total Amount (with Tax & Profit):</div>
            <div class="summary-value" style="color: #28a745; font-size: 16px;">
                ${{ number_format($total_amount, 2) }}
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>{{ $name }} - Invoice Report</p>
        <p>Generated on {{ $generated_date }}</p>
        <p>info@cargodispatch.co </p>
    </div>
</body>
</html>