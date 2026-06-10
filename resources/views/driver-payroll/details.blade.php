@extends('layouts.app')
@section('title') {{ $name }} @endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-dark mb-1">
                        <i class="fas fa-user-tie me-2 text-user-icon-color"></i>
                        Driver Payroll Details
                    </h4>
                    <p class="text-muted small mb-0">
                        {{ $driver->firstname }} {{ $driver->lastname }} - {{ $start_date }} to {{ $end_date }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('driver-payroll.pdf', ['driver' => $driver->id]) }}?start_date={{ $start_date }}&end_date={{ $end_date }}" 
                       class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small">Total Shipments</h6>
                    <h3 class="mb-0 text-primary">{{ $total_shipments }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small">Total Miles</h6>
                    <h3 class="mb-0 text-info">{{ number_format($total_miles, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small">Per Mile Rate</h6>
                    <h3 class="mb-0 text-warning">${{ number_format($per_mile_rate, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small">Total Earnings</h6>
                    <h3 class="mb-0 text-success">${{ number_format($total_earnings, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0">Shipment Details</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Pickup Address</th>
                            <th>Drop Address</th>
                            <th>Distance (mi)</th>
                            <th>Status</th>
                            <th class="text-end">Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($shipment->pickup_time)->format('M d, Y') }}</td>
                            <td>{{ $shipment->customer->first_name ?? '' }} {{ $shipment->customer->last_name ?? '' }}</td>
                            <td>{{ Str::limit($shipment->pickup_address, 30) }}</td>
                            <td>{{ Str::limit($shipment->drop_address, 30) }}</td>
                            <td>{{ number_format($shipment->distance_miles, 2) }}</td>
                            <td>
                                <span class="badge bg-success">{{ ucfirst($shipment->status) }}</span>
                            </td>
                            <td class="text-end text-success fw-semibold">
                                ${{ number_format($shipment->distance_miles * $per_mile_rate, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No shipments found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection