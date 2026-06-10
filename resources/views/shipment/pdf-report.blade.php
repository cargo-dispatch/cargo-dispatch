<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipment Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header-logo {
            width: 70px;
            height: auto;
            margin-right: 15px;
        }
        .company-info {
            text-align: left;
            margin-bottom: 0;
        }
        .report-info {
            margin-bottom: 20px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
        .filters {
            margin-bottom: 20px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            color: white;
            font-size: 9px;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-active { background-color: #17a2b8; }
        .status-complete { background-color: #28a745; }
        .status-cancelled { background-color: #dc3545; }
        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
        }
        .summary-section {
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }
        .summary-label {
            font-weight: bold;
            color: #333;
        }
        .summary-value {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/img/logo.png') }}" alt="Logo" class="header-logo">
        <div class="company-info">
            <h1>{{ config('app.name', 'Your Company') }}</h1>
            <p>Shipment Report</p>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Information</h3>
        <div class="filters">
            <div class="filter-item">
                <strong>Generated On:</strong> {{ date('m-d-y H:i:s') }}
            </div>
            @if(isset($filters['start_date']) && $filters['start_date'])
                <div class="filter-item">
                    <strong>Start Date:</strong> {{ $filters['start_date'] }}
                </div>
            @endif
            @if(isset($filters['end_date']) && $filters['end_date'])
                <div class="filter-item">
                    <strong>End Date:</strong> {{ $filters['end_date'] }}
                </div>
            @endif
            @if(isset($filters['status']) && $filters['status'])
                <div class="filter-item">
                    <strong>Status:</strong> {{ ucfirst($filters['status']) }}
                </div>
            @endif
            @if(isset($filters['customer_id']) && $filters['customer_id'])
                <div class="filter-item">
                    <strong>Customer ID:</strong> {{ $filters['customer_id'] }}
                </div>
            @endif
            @if(isset($filters['vehicle_type_id']) && $filters['vehicle_type_id'])
                <div class="filter-item">
                    <strong>Vehicle Type ID:</strong> {{ $filters['vehicle_type_id'] }}
                </div>
            @endif
            @if(isset($filters['vehicle_id']) && $filters['vehicle_id'])
                <div class="filter-item">
                    <strong>Vehicle ID:</strong> {{ $filters['vehicle_id'] }}
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <h3>Report Summary</h3>
        <div class="summary-item">
            <span class="summary-label">Total Shipments:</span>
            <span class="summary-value">{{ $shipments->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Revenue:</span>
            <span class="summary-value">${{ number_format($shipments->sum('estimated_cost'), 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Distance:</span>
            <span class="summary-value">{{ number_format($shipments->sum('distance_km'), 2) }} KM</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Completed:</span>
            <span class="summary-value">{{ $shipments->where('status', 'complete')->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Pending:</span>
            <span class="summary-value">{{ $shipments->where('status', 'pending')->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Active:</span>
            <span class="summary-value">{{ $shipments->where('status', 'active')->count() }}</span>
        </div>
    </div>

    <!-- Shipments Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Vehicle Type</th>
                <th>Vehicle</th>
                <th>Pickup Address</th>
                <th>Drop Address</th>
                <th>Pickup Time</th>
                <th>Delivery Time</th>
                <th>Status</th>
                <th>Weight</th>
                <th>Volume</th>
                <th>Pallets</th>
                <th>Cost</th>
                <th>Distance (KM)</th>
              
            </tr>
        </thead>
        <tbody>
            @forelse($shipments as $shipment)
                <tr>
                    <td>{{ $shipment->id }}</td>
                    <td>{{ $shipment->customer ? $shipment->customer->customer_title : 'N/A' }}</td>
                    <td>{{ $shipment->vehicleType ? $shipment->vehicleType->vehicle_type : 'N/A' }}</td>
                    <td>{{ $shipment->vehicle ? $shipment->vehicle->vehicle_id : 'N/A' }}</td>
                    <td>{{ Str::limit($shipment->pickup_address, 30) }}</td>
                    <td>{{ Str::limit($shipment->drop_address, 30) }}</td>
                    <td>{{ $shipment->pickup_time ? \Carbon\Carbon::parse($shipment->pickup_time)->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td>{{ $shipment->delivery_time ? \Carbon\Carbon::parse($shipment->delivery_time)->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td>
                        <span class="status status-{{ $shipment->status }}">
                            {{ ucfirst($shipment->status) }}
                        </span>
                    </td>
                    <td class="text-right">{{ $shipment->weight ? number_format($shipment->weight, 2) : 'N/A' }}</td>
                    <td class="text-right">{{ $shipment->volume ? number_format($shipment->volume, 2) : 'N/A' }}</td>
                    <td class="text-center">{{ $shipment->pallets ?? 'N/A' }}</td>
                    <td class="text-right">${{ number_format($shipment->estimated_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($shipment->distance_km, 2) }}</td>
                   
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="text-center">No shipments found matching the criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ date('F j, Y \a\t g:i A') }} | Total Records: {{ $shipments->count() }}</p>
        <p>This report was generated automatically by {{ config('app.name', 'Your Company') }} system.</p>
    </div>
</body>
</html>