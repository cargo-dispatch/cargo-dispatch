<!DOCTYPE html>
<html>
<head>
    <title>Driver Payroll Report - {{ $driver->firstname }} {{ $driver->lastname }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
        }

        .header {
            background-color: #6E6E6E;
            color: white;
            padding: 20px 0;
            height: 180px;
            overflow: hidden;
        }

        .header-content {
            display: table;
            width: 100%;
            padding: 0 40px;
        }

        .header-left {
            display: table-cell;
            width: 33%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 67%;
            text-align: right;
            vertical-align: top;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 8px;
        }

        .header-left h5 {
            font-weight: bold;
            margin-top: 8px;
            font-size: 16px;
        }

        .header-right h4 {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .header-right small {
            font-size: 11px;
            line-height: 1.6;
        }

        .invoice-info {
            background-color: rgb(74, 87, 95);
            color: white;
            border-bottom: 2px solid #ccc;
        }

        .invoice-info-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .invoice-info-col {
            display: table-cell;
            width: 33.33%;
            padding: 12px 20px;
            vertical-align: middle;
        }

        .info-flex {
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            font-size: 13px;
            width: 50%;
            text-align: left;
        }

        .info-value {
            display: table-cell;
            font-size: 13px;
            width: 50%;
            text-align: right;
        }

        .invoice-section {
            border-bottom: 2px solid #ccc;
            padding: 20px 40px 15px;
        }

        .invoice-row {
            display: table;
            width: 100%;
        }

        .invoice-col {
            display: table-cell;
            width: 33.33%;
            padding-right: 20px;
            vertical-align: top;
            position: relative;
        }

        .invoice-col:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            border-right: 2px dotted grey;
        }

        .invoice-col h6 {
            font-weight: bold;
            color: black;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .invoice-col p {
            margin: 4px 0;
            font-size: 13px;
        }

        .table-container {
            padding: 20px 40px 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-weight: bold;
            font-size: 13px;
        }

        table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            font-size: 13px;
        }

        .totals-section {
            padding: 15px 40px 30px;
        }

        .totals-wrapper {
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 50%;
        }

        .totals-right {
            display: table-cell;
            width: 50%;
        }

        .totals-table {
            width: 100%;
            border: none;
        }

        .totals-table td {
            padding: 6px 0;
            border: none;
            font-size: 13px;
        }

        .totals-table .label {
            font-weight: bold;
        }

        .totals-table .value {
            text-align: right;
        }

        .totals-table .total-row .label,
        .totals-table .total-row .value {
            font-weight: bold;
            font-size: 16px;
        }

        .divider {
            border: none;
            border-top: 1px dotted black;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <img src="{{ public_path('assets/img/logo.png') }}" alt="Logo" class="logo">
                    <h5>{{ $company_name }}</h5>
                </div>
                <div class="header-right">
                    <h4>Driver Payroll Report</h4>
                    <small>info@cargodispatch.co</small><br>
                    <small>https://cargodispatch.co</small>
                </div>
            </div>
        </div>

        <!-- Payroll Info -->
        <div class="invoice-info">
            <div class="invoice-info-row">
         
                <div class="invoice-info-col">
                    <div class="info-flex">
                        <div class="info-label">PERIOD START:</div>
                        <div class="info-value">{{ $period_start }}</div>
                    </div>
                </div>
                <div class="invoice-info-col">
                    <div class="info-flex">
                        <div class="info-label">PERIOD END:</div>
                        <div class="info-value">{{ $period_end }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Driver Info -->
        <div class="invoice-section">
            <div class="invoice-row">
                <div class="invoice-col">
                    <h6>Driver Information</h6>
                    <p><strong>Name:</strong> {{ $driver->firstname }} {{ $driver->lastname }}</p>
                    <p><strong>Email:</strong> {{ $driver->email }}</p>
                    <p><strong>Phone:</strong> {{ $driver->phoneno }}</p>
                </div>
                <div class="invoice-col">
                    <h6>Payment Details</h6>
                    <p><strong>Per Mile Rate:</strong> ${{ number_format($per_mile_rate, 2) }}</p>
                    <p><strong>Total Miles:</strong> {{ number_format($total_miles, 2) }} mi</p>
                    <p><strong>Total Shipments:</strong> {{ $total_shipments }}</p>
                </div>
                <div class="invoice-col">
                    <h6>Earnings Summary</h6>
                    <p><strong>Total Earnings:</strong> ${{ number_format($total_earnings, 2) }}</p>
                    <p><strong>Status:</strong> Complete</p>
                </div>
            </div>
        </div>

        <!-- Shipments Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Route</th>
                        <th>Distance (mi)</th>
                        <th>Amount ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shipments as $shipment)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($shipment->pickup_time)->format('M d, Y') }}</td>
                        <td>{{ $shipment->customer->first_name ?? '' }} {{ $shipment->customer->last_name ?? '' }}</td>
                        <td class="font-size-11">
                            {{ substr($shipment->pickup_address, 0, 30) }}{{ strlen($shipment->pickup_address) > 30 ? '...' : '' }}
                            <br> <br>
                            {{ substr($shipment->drop_address, 0, 30) }}{{ strlen($shipment->drop_address) > 30 ? '...' : '' }}
                        </td>
                        <td>{{ number_format($shipment->distance_miles, 2) }}</td>
                        <td>${{ number_format($shipment->distance_miles * $per_mile_rate, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-wrapper">
                <div class="totals-left"></div>
                <div class="totals-right">
                    <table class="totals-table">
                        <tr>
                            <td class="label">Total Miles Driven:</td>
                            <td class="value">{{ number_format($total_miles, 2) }} mi</td>
                        </tr>
                        <tr>
                            <td class="label">Rate Per Mile:</td>
                            <td class="value">${{ number_format($per_mile_rate, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Total Shipments:</td>
                            <td class="value">{{ $total_shipments }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">Total Earnings ($):</td>
                            <td class="value">${{ number_format($total_earnings, 2) }}</td>
                        </tr>
                    </table>
                    <hr class="divider">
                </div>
            </div>
        </div>
    </div>
</body>
</html>