@extends('layouts.app')
@section('title', $name ?? 'Dispatch - Cargo Dispatch')

@push('styles')
<link href="{{ asset('assets/css/dispatch.css') }}" rel="stylesheet">
@endpush

@section('content')

<div class="container-fluid mb-3">
    <div class="row align-items-center">
        <!-- Tabs on the left -->
        <div class="col-auto">
            <ul class="nav nav-tabs mb-0 gap-2" id="shipmentTabs">
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1 active" href="#" data-status="pending">
                        Pending
                        <span class="badge bg-secondary pending-count">
                            {{ $statusCounts['pending'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1" href="#" data-status="active">
                        Active
                        <span class="badge bg-secondary active-count">
                            {{ $statusCounts['active'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1" href="#" data-status="complete">
                        Complete
                        <span class="badge bg-secondary complete-count">
                            {{ $statusCounts['complete'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1 text-danger" href="#" data-status="cancel">
                        Cancel
                        <span class="badge bg-danger cancel-count">
                            {{ $statusCounts['cancel'] ?? 0 }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Dispatch slightly to the left but still responsive -->
        <div class="col d-flex justify-content-start justify-content-md-center">
            <h5 class="mb-0 fs-15 ps-1 ps-md-0">
                <strong>Dispatch :</strong>
                <span id="localDateTime" class="text-muted"></span>
            </h5>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th width="5%">Sr#</th>
                <th>Customers Name</th>
                <th>Vehicle Type</th>
                <th>Vehicles</th>
                <th>Pickup Address</th>
                <th>Dropoff Address</th>
                <th>Pickup DateTime</th>
                <th>Expected Delivery</th>
                <th>Status</th>
                <th>Estimated Cost</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            <!-- Data will be loaded via AJAX here -->
        </tbody>
        <tbody id="loaderBody" class="loader-body">
            <tr>
                <td colspan="11" class="text-center p-3">
                    <div id="loader" class="custom-loader">
                        <img src="{{ asset(config('app.logo')) }}" alt="Loading Logo" class="loader-logo">
                        <div class="loader"></div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@include('components.detail-modal', [
    'modalId' => 'customerDetailModal',
    'entityName' => 'Today Dispatch'
])

@include('modals.route')

<script src="{{ asset('assets/js/detail-modal.js') }}?v={{ filemtime(public_path('assets/js/detail-modal.js')) }}"></script>

<!-- Map Modal -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalLabel">Route Map</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Car Info Section -->
                <div id="carInfo"></div>
                
                <!-- Map Container -->
                <div id="map" class="map-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/dispatch-ui.js') }}"></script>

<!-- Load Google Maps JavaScript API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places"></script>

@include('dispatch.index-js')
@endsection