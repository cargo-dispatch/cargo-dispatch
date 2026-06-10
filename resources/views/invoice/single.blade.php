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
                    <h5>{{ $name }}</h5>
                </div>
                <div class="header-right">
                    <h4>Invoice</h4>
                   
                    <small>info@cargodispatch.co</small><br>
<a href="https://cargodispatch.co" style="color: white; text-decoration: none;">https://cargodispatch.co</a>
                </div>
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="invoice-info-row">
                <div class="invoice-info-col">
                    <div class="info-flex">
                        <div class="info-label">INVOICE NO:</div>
                        <div class="info-value">{{ $invoice_number }}</div>
                    </div>
                </div>
                <div class="invoice-info-col">
                    <div class="info-flex">
                        <div class="info-label">ISSUE DATE:</div>
                        <div class="info-value">{{ $today_date }}</div>
                    </div>
                </div>
                <div class="invoice-info-col">
                    <div class="info-flex">
                        <div class="info-label">DUE DATE:</div>
                        <div class="info-value">{{ $due_date }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- From and To -->
        <div class="invoice-section">
            <div class="invoice-row">
                <div class="invoice-col">
                    <h6>FROM</h6>
                  <p>{{$pickup_address}}</p>
                </div>
                <div class="invoice-col">
                    <h6>TO</h6>
                    <p>{{ $customer->first_name ?? 'N/A' }}</p>
                    <p>{{$drop_address}}</p>
                </div>
                <div class="invoice-col">
                    <h6>Total Distance</h6>
                    <p class="">{{$shipment->distance_miles}} <small>miles</small></p>
                </div>
            </div>
        </div>

        <!-- Description Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>DESCRIPTION</th>
                        
                       
                        <th>Weight</th>
                        <th>Pallets</th>
                        <th>Volume</th>
                        <th>AMOUNT ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{$shipment->customer->first_name}} {{$shipment->customer->last_name}}</td>
                        <td>
                          <small>{{$description}}</small>
                        </td>
                      
                      
                        <td>{{ $shipment->weight }}</td>
                        <td>{{ $shipment->pallets }}</td>
                        <td>{{ $shipment->volume }}</td>
                        <td>{{$total_due}}</td>
                    </tr>
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
                            <td class="label">Subtotal:</td>
                            <td class="value">{{$total_due}}</td>
                        </tr>
                        <tr>
                            <td class="label">Tax ({{ $tax_amount > 0 ? '20%' : '0%' }}):</td>
                            <td class="value">${{ number_format($tax_amount, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label">Total ($):</td>
                            <td class="value">{{$total_due}}</td>
                        </tr>
                    </table>
                    <hr class="divider">
                </div>
            </div>
        </div>
    </div>
</body>
</html>