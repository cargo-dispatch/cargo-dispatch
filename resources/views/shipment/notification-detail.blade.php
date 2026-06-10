@extends('layouts.app')

@section('content')
    @php
        $firstName = $shipment->customer->first_name ?? '';
        $lastName = $shipment->customer->last_name ?? '';
        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
        $displayStatus = $shipment->status;
        if ($notification) {
            $notificationData = (array) $notification->data;
            if (isset($notificationData['shipment_status'])) {
                $displayStatus = $notificationData['shipment_status'];
            }
        }
    @endphp

    <div class="container-fluid">
        <!-- Professional Page Header -->
        <div class="d-flex mbl-clm align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 fs-18 show-enteries">Shipment Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0">
                        <li class="breadcrumb-item fs-11"><a href="{{ route('dashboard') }}" class="">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item fs-11 active">Shipment #{{ $shipment->id }}</li>
                    </ol>
                </nav>
            </div>
            <span class="badge show-enteries status-{{ $shipment->status }} text-uppercase py-2 px-3">
                {{ ucfirst($shipment->status) }}
            </span>
        </div>

        <!-- Minimal Notification Alert -->
        @if(isset($notification))
            <div class=" show-enteries sidebar-wrapper p-3 border-left-3 border-left-primary alert-dismissible fade show mb-4"
                role="alert">
                <div class="d-flex mbl-clm align-items-center">
                    <i class="fas fa-info-circle text-primary mr-3"></i>
                    <div>
                        <strong class="">Notification:</strong> {{ $notification->data['message'] }}
                        <div class="text-muted small mt-1">
                            {{ $notification->created_at->format('F j, Y \a\t g:i A') }}
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        @endif

        <div class="row">
            <!-- Main Content Column -->
            <div class="col-xl-8 col-lg-7">
                <!-- Shipment Information Card -->
                <div class="card shadow-sm mb-4 border-0">
                    <div class="sidebar-wrapper py-3 d-flex justify-content-between align-items-center border-bottom">
                        <h6 class="ps-3 font-weight-semibold">
                            <i class="fas fa-shipping-fast mr-2"></i>Shipment Information
                        </h6>
                        <div>
                            <!-- <a href="{{ route('shipments.edit', $shipment->id) }}" class="btn btn-sm btn-link text-gray-600">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a> -->
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Address Section -->
                      <div class="row mb-4">
    {{-- 📦 Pickup Information Section --}}
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="address-section badge-layout border-0 sidebar-wrapper">
            <div class="d-flex mbl-clm align-items-start mb-3">
                <i class="fas fa-map-marker-alt text-success mt-1 mr-2"
                    style="font-size: 1.2em;"></i>
                <div>
                    <h6 class="font-weight-semibold mb-1">Pickup Information</h6>
                    <p class="mb-2 fs-11">{{ $shipment->pickup_address }}</p>
                    
                    @if($shipment->pickup_time)
                        @php
                            // Parse pickup_time (which contains both date and time)
                            $pickupDateTime = \Carbon\Carbon::parse($shipment->pickup_time);
                            $pickupDate = $pickupDateTime->format('M j, Y');
                            $pickupTime = $pickupDateTime->format('g:i A');
                        @endphp
                        <div class="d-flex flex-wrap">
                            {{-- Pickup Date Badge --}}
                            <span class=" fs-11 sidebar-wrapper mr-2 mb-1">
                                <i class="far fa-calendar-alt mr-1"></i>
                                {{ $pickupDate }}
                            </span>
                            {{-- Pickup Time Badge --}}
                            <span class="sidebar-wrapper fs-11 mb-1">
                                <i class="far fa-clock ms-2"></i>
                                {{ $pickupTime }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- 🏁 Drop Information Section (Delivery) --}}
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="address-section badge-layout border-0 sidebar-wrapper">
            <div class="d-flex mbl-clm align-items-start mb-3">
                <i class="fas fa-flag-checkered text-danger mt-1 mr-2"
                    style="font-size: 1.2em;"></i>
                <div>
                    <h6 class="font-weight-semibold mb-1">Drop Information</h6>
                    <p class="mb-2 fs-11">{{ $shipment->drop_address }}</p>

                    @if($shipment->delivery_time)
                        @php
                            // Parse delivery_time (which contains both date and time)
                            $deliveryDateTime = \Carbon\Carbon::parse($shipment->delivery_time);
                            $dropDate = $deliveryDateTime->format('M j, Y');
                            $dropTime = $deliveryDateTime->format('g:i A');
                        @endphp
                        <div class="d-flex flex-wrap">
                            {{-- Drop Date Badge (Consistent Style) --}}
                            <span class=" sidebar-wrapper fs-11 mr-2 mb-1">
                                <i class="far fa-calendar-alt mr-1"></i>
                                {{ $dropDate }}
                            </span>

                            {{-- Drop Time Badge (Consistent Style) --}}
                            <span class=" sidebar-wrapper fs-11 mb-1">
                                <i class="far fa-clock ms-2"></i>
                                {{ $dropTime }}
                            </span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
                        <!-- Shipment Details -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="sidebar-wrapper badge-layout text-center p-4  show-enteries h-100">
                                    <h6 class="mb-2 fs-13">Shipment ID</h6>
                                    <h5 class="font-weight-semibold fs-15 mb-0">#{{ $shipment->id }}</h5>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="sidebar-wrapper badge-layout text-center p-4  show-enteries h-100">
                                    <h6 class=" mb-2 fs-13">Distance</h6>
                                    <h5 class="font-weight-semibold fs-15 mb-0">
                                        {{ $shipment->distance_text ?? 'N/A' }}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="sidebar-wrapper badge-layout text-center p-4  show-enteries h-100">
                                    <h6 class=" mb-2">Status</h6>
                                    <span class="badge status-{{ $displayStatus }} text-uppercase py-2 show-enteries  px-3">
                                        {{ ucfirst($displayStatus) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Buttons -->
                        <div class="d-flex mb-3">
                            <button class="btn theme-btn btn-sm mr-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#mapCollapse"
                                data-toggle="collapse" data-target="#mapCollapse">
                                <i class="fas fa-map-marked-alt mr-1"></i> View Route
                            </button>
                            <button class="btn theme-btn btn-sm" type="button"
                                data-bs-toggle="collapse" data-bs-target="#estimatedTimeCollapse"
                                data-toggle="collapse" data-target="#estimatedTimeCollapse">
                                <i class="fas fa-clock mr-1"></i> Delivery Estimates
                            </button>
                        </div>

                        @if($shipment->notes)
                            <div class="border-top pt-3 mt-3">
                                <h6 class="font-weight-semibold text-gray-700 mb-2">
                                    <i class="fas fa-sticky-note mr-2 text-gray-600"></i>Additional Notes
                                </h6>
                                <p class="text-gray-800">{{ $shipment->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Map Collapse Section -->
                <div class="collapse mb-4" id="mapCollapse">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 font-weight-semibold text-gray-800">
                                <i class="fas fa-map-marked-alt mr-2 text-gray-600"></i>Route Visualization
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="map" style="height: 400px; width: 100%; border-radius: 4px;"></div>
                            <div class="d-flex justify-content-between mt-3">
                                <div id="duration" class="font-weight-semibold text-gray-700">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Calculating route...
                                </div>
                                <div id="distance" class="text-gray-600">
                                    <i class="fas fa-road mr-1"></i> Distance: --
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="col-xl-4 col-lg-5 ">
                <!-- Customer Profile Card -->
                <div class="card shadow-sm mb-4 border-0 ">
                    <div class=" sidebar-wrapper  py-3 border-bottom ">
                        <h6 class="m-0 font-weight-semibold text-center">
                            <i class="fas fa-user mr-2"></i>Customer Detail
                        </h6>
                    </div>
                    <div class="card-body" style="min-height:371px !important;">
                        <div class="text-center mb-3">
                            <div class="rounded-circle shadow-sm d-flex justify-content-center align-items-center mx-auto"
                                style="width: 100px; height: 100px; background-color: #6c757d; color: white; font-size: 36px; font-weight: bold;">
                                {{ $initials }}
                            </div>
                        </div>
                        <h5 class="text-center font-weight-semibold fs-18 mb-1">
                            {{ $shipment->customer->first_name }} {{ $shipment->customer->last_name }}
                        </h5>
                        <p class="text-center text-gray-600 small mb-3">
                            <i class="fas fa-calendar-alt mr-1"></i> Member since
                            {{ $shipment->customer->created_at->format('M Y') }}
                        </p>

                        <div class="border-top pt-3">
                            @if($shipment->customer->email)
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-envelope fa-fw mr-2"></i>
                                    <a href="mailto:{{ $shipment->customer->email }}"
                                        class="fs-13">{{ $shipment->customer->email }}</a>
                                </div>
                            @endif

                            @if($shipment->customer->phone)
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone fa-fw  mr-2"></i>
                                    <a href="tel:{{ $shipment->customer->phone }}"
                                        class="show-enteries fs-13">{{ $shipment->customer->phone }}</a>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                <!-- Quick Actions Card -->


                <!-- Estimated Times Card -->
                <div class="collapse" id="estimatedTimeCollapse">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 font-weight-semibold text-gray-800">
                                <i class="fas fa-clock mr-2 text-gray-600"></i>Delivery Time Estimates
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <!-- Semi Truck -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;">
                                            <i class="fas fa-truck-moving text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">Semi Truck</h6>
                                            <small class="text-gray-600" id="semi-truck-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Truck -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;">
                                            <i class="fas fa-truck text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">Box Truck</h6>
                                            <small class="text-gray-600" id="box-truck-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Flatbed Truck -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%) !important;">
                                            <i class="fas fa-truck-pickup text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">Flatbed Truck</h6>
                                            <small class="text-gray-600" id="flatbed-truck-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reefer Truck -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;">
                                            <i class="fas fa-snowflake text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">Reefer Truck</h6>
                                            <small class="text-gray-600" id="reefer-truck-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delivery Van -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;">
                                            <i class="fas fa-truck-loading text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">Delivery Van</h6>
                                            <small class="text-gray-600" id="delivery-van-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- LTL Truck -->
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="vehicle-icon bg-light rounded p-2 mr-3"
                                            style="background: linear-gradient(135deg, #6f42c1 0%, #59359a 100%) !important;">
                                            <i class="fas fa-pallet text-white" style="font-size: 1.1em;"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-semibold text-gray-800 mb-0">LTL Truck</h6>
                                            <small class="text-gray-600" id="ltl-truck-time">Calculating...</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0">
                <div class="modal-header bg-gray-800 text-white">
                    <h5 class="modal-title" id="statusModalLabel">Update Shipment Status</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="statusForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="status" class="font-weight-semibold text-gray-700">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="pending" {{ $shipment->status == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="assigned" {{ $shipment->status == 'assigned' ? 'selected' : '' }}>Assigned
                                </option>
                                <option value="in_transit" {{ $shipment->status == 'in_transit' ? 'selected' : '' }}>In
                                    Transit</option>
                                <option value="delivered" {{ $shipment->status == 'delivered' ? 'selected' : '' }}>Delivered
                                </option>
                                <option value="cancelled" {{ $shipment->status == 'cancelled' ? 'selected' : '' }}>Cancelled
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="font-weight-semibold text-gray-700">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Add any additional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-gray-600" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gray-800">
                            <i class="fas fa-save mr-1"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Enhanced Professional CSS - Light and Clean */

        /* Base improvements */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2d3748;
        }

        /* Card enhancements */
        .card {
            border-radius: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease-in-out;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        /* Button improvements */
        .btn {
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease-in-out;
            border-width: 1px;
        }

        .btn-outline-gray-600 {
            border-color: #cbd5e0 !important;
            color: #4a5568 !important;
            background: white !important;
        }

        .btn-outline-gray-600:hover {
            background: #f7fafc !important;
            border-color: #a0aec0 !important;
            color: #2d3748 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-gray-800 {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
            color: white !important;
            border: none !important;
        }

        .btn-gray-800:hover {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
            color: white !important;
        }

        /* Badge improvements */
        .badge {
            font-weight: 500 !important;
            letter-spacing: 0.025em;
            border-radius: 0.375rem !important;
            transition: all 0.2s ease-in-out;
            border: 1px solid;
        }

        /* Status badge colors */
        .status-pending {
            background-color: #fef5e7 !important;
            color: #c05621 !important;
            border-color: #fed7aa !important;
        }

        .status-completed {
            background-color: #f0fff4 !important;
            color: #22543d !important;
            border-color: #9ae6b4 !important;
        }

        .status-delivered {
            background-color: #f0fff4 !important;
            color: #22543d !important;
            border-color: #9ae6b4 !important;
        }

        .status-in_transit {
            background-color: #e6fffa !important;
            color: #234e52 !important;
            border-color: #81e6d9 !important;
        }

        .status-assigned {
            background-color: #eff6ff !important;
            color: #1e40af !important;
            border-color: #93c5fd !important;
        }

        .status-cancelled {
            background-color: #fef2f2 !important;
            color: #991b1b !important;
            border-color: #fca5a5 !important;
        }

        /* Alert enhancements */
        .alert {
            border-radius: 0.5rem !important;
            border: none !important;
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%) !important;
        }

        .alert-light {
            color: #2d3748 !important;
        }

        .border-left-3 {
            border-left: 3px solid !important;
        }

        .border-left-primary {
            border-left-color: #4299e1 !important;
        }

        /* Form improvements */
        .form-control {
            border-radius: 0.375rem !important;
            border: 1px solid #e2e8f0 !important;
            transition: all 0.2s ease-in-out;
            font-size: 0.875rem;
        }

        .form-control:focus {
            border-color: #4299e1 !important;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1) !important;
        }

        /* Modal improvements */
        .modal-content {
            border-radius: 0.75rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .modal-header {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        /* List group improvements */
        .list-group-item {
            border: none !important;
            transition: all 0.2s ease-in-out;
            margin-bottom: 0.25rem;
        }

        .list-group-item:hover {
            background-color: #f7fafc !important;
            border-radius: 0.375rem !important;
        }

        /* Vehicle icon containers */
        .vehicle-icon {
            transition: all 0.2s ease-in-out;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .list-group-item:hover .vehicle-icon {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Avatar improvements */
        .img-profile {
            border: 3px solid #e2e8f0 !important;
            transition: all 0.2s ease-in-out;
        }

        .img-profile:hover {
            border-color: #cbd5e0 !important;
            transform: scale(1.02);
        }

        /* Address section improvements */
        .address-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 0.5rem;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease-in-out;
        }

        .address-section:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            transform: translateY(-1px);
        }

        /* Stats cards */
        .stats-card {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%) !important;
            transition: all 0.2s ease-in-out;
        }

        .stats-card:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            transform: translateY(-2px);
        }

        /* Breadcrumb improvements */
        .breadcrumb {
            background: transparent !important;
            padding: 0 !important;
        }

        .breadcrumb-item a {
            color: #4a5568 !important;
            text-decoration: none;
            transition: color 0.2s ease-in-out;
        }

        .breadcrumb-item a:hover {
            color: #2d3748 !important;
        }

        /* Map container */
        #map {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.5rem !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Collapse animations */
        .collapse {
            transition: all 0.3s ease-in-out;
        }

        /* Loading states */
        .fa-spinner {
            color: #4299e1 !important;
        }

        /* Utility classes */
        .font-weight-semibold {
            font-weight: 600 !important;
        }

        .text-gray-500 {
            color: #a0aec0 !important;
        }

        .text-gray-600 {
            color: #718096 !important;
        }

        .text-gray-700 {
            color: #4a5568 !important;
        }

        .text-gray-800 {
            color: #2d3748 !important;
        }

        .bg-gray-800 {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%) !important;
        }

        .bg-light {
            background-color: #f9fafb !important;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1rem;
            }

            .btn-sm {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }

            .stats-card {
                margin-bottom: 0.75rem;
            }

            .address-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
        }

        /* Print styles */
        @media print {
            .card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }

            .btn {
                display: none !important;
            }

            .modal {
                display: none !important;
            }
        }

        /* Focus improvements for accessibility */
        .btn:focus,
        .form-control:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1) !important;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Additional enhancements */
        .close:hover {
            opacity: 0.75;
        }

        /* Custom gap utility for older Bootstrap versions */
        .gap-2>*+* {
            margin-top: 0.5rem;
        }

        /* Dark mode fixes */
        [data-theme="dark"] .card { background-color: #1e2130 !important; border-color: #2d3555 !important; }
        [data-theme="dark"] .card-header { background: #252a3d !important; border-bottom-color: #2d3555 !important; color: #e2e8f0 !important; }
        [data-theme="dark"] .card-body { background-color: #1e2130 !important; }
        [data-theme="dark"] .address-section { background: #252a3d !important; border-color: #2d3555 !important; }
        [data-theme="dark"] .list-group-item { background-color: transparent !important; color: #e2e8f0 !important; }
        [data-theme="dark"] .list-group-item:hover { background-color: #252a3d !important; }
        [data-theme="dark"] .text-gray-600, [data-theme="dark"] .text-gray-700, [data-theme="dark"] .text-gray-800 { color: #cbd5e1 !important; }
        [data-theme="dark"] small { color: #94a3b8 !important; }
        [data-theme="dark"] h5, [data-theme="dark"] h6 { color: #e2e8f0 !important; }
        [data-theme="dark"] .alert { background: #252a3d !important; color: #e2e8f0 !important; }
        [data-theme="dark"] .bg-light { background-color: #252a3d !important; }

    </style>

    <script>
        // Global variable to store the Google Maps API key
        // Fix: Use proper Laravel Blade syntax to access config
        const GOOGLE_MAPS_API_KEY = '{{ config("services.google.maps_api_key") }}';

        // Debug: Log the API key (remove this in production)
        console.log('Google Maps API Key loaded:', GOOGLE_MAPS_API_KEY ? 'Key exists' : 'No key found');
        console.log('API Key value:', GOOGLE_MAPS_API_KEY); // Add this to see the actual value

        // Your existing JavaScript functions remain the same
        function updateStatus(shipmentId) {
            $('#statusModal').modal('show');

            $('#statusForm').off('submit').on('submit', function (e) {
                e.preventDefault();

                const formData = {
                    status: $('#status').val(),
                    notes: $('#notes').val(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: `/shipments/${shipmentId}/update-status`,
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating status: ' + response.message);
                        }
                    },
                    error: function (xhr) {
                        alert('Error updating status. Please try again.');
                        console.error(xhr.responseText);
                    }
                });
            });
        }

        function initMap() {
            const pickup = "{{ addslashes($shipment->pickup_address) }}";
            const drop = "{{ addslashes($shipment->drop_address) }}";

            // Show loading states
            document.getElementById("duration").innerHTML =
                `<i class="fas fa-spinner fa-spin mr-2"></i> Calculating route...`;
            ['semi-truck', 'box-truck', 'flatbed-truck', 'reefer-truck', 'delivery-van', 'ltl-truck'].forEach(vehicle => {
                document.getElementById(`${vehicle}-time`).textContent = 'Calculating...';
            });

            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 7,
                center: {
                    lat: 0,
                    lng: 0
                },
                mapTypeId: "roadmap",
            });

            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: false,
                polylineOptions: {
                    strokeColor: "#4b5563",
                    strokeOpacity: 1.0,
                    strokeWeight: 4,
                },
                suppressInfoWindows: true
            });

            directionsService.route({
                origin: pickup,
                destination: drop,
                travelMode: google.maps.TravelMode.DRIVING,
                provideRouteAlternatives: false,
            },
                (response, status) => {
                    if (status === "OK") {
                        directionsRenderer.setDirections(response);

                        const route = response.routes[0];
                        const leg = route.legs[0];
                        const distanceKm = leg.distance.value / 1000; // Convert meters to km

                        // Display duration and distance
                        document.getElementById("duration").innerHTML =
                            `<i class="fas fa-clock mr-2"></i> Estimated time: ${leg.duration.text}`;
                        document.getElementById("distance").innerHTML =
                            `<i class="fas fa-road mr-1"></i> Distance: ${leg.distance.text}`;

                        // Calculate times for different vehicles
                        calculateVehicleTimes(distanceKm, leg.duration.value);

                        // Custom markers
                        new google.maps.Marker({
                            position: leg.start_location,
                            map: map,
                            title: "Pickup Location",
                            icon: {
                                url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
                            }
                        });

                        new google.maps.Marker({
                            position: leg.end_location,
                            map: map,
                            title: "Drop Location",
                            icon: {
                                url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
                            }
                        });

                        // Center map on the route
                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(leg.start_location);
                        bounds.extend(leg.end_location);
                        map.fitBounds(bounds);
                    } else {
                        console.error("Directions request failed due to " + status);
                        document.getElementById("map").innerHTML =
                            `<div class="alert alert-light border p-3">Unable to display map: ${status}</div>`;
                        document.getElementById("duration").innerHTML =
                            `<div class="text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Could not calculate route</div>`;
                        ['semi-truck', 'box-truck', 'flatbed-truck', 'reefer-truck', 'delivery-van', 'ltl-truck'].forEach(vehicle => {
                            document.getElementById(`${vehicle}-time`).textContent = 'Unavailable';
                        });
                    }
                }
            );
        }

        function calculateVehicleTimes(distanceKm, baseDurationSeconds) {
            // Vehicle speed factors (adjust these based on your needs)
            const vehicleSpeeds = {
                'semi-truck': 0.75, // 75% of car speed (slower due to size/weight)
                'box-truck': 0.85, // 85% of car speed
                'flatbed-truck': 0.8, // 80% of car speed
                'reefer-truck': 0.7, // 70% of car speed (slower due to refrigeration)
                'delivery-van': 0.95, // 95% of car speed
                'ltl-truck': 0.65 // 65% of car speed (slower due to multiple stops)
            };

            // Base speed calculation (car speed)
            const baseSpeedKph = (distanceKm / (baseDurationSeconds / 3600));

            // Calculate and display times for each vehicle
            Object.entries(vehicleSpeeds).forEach(([vehicle, factor]) => {
                const vehicleSpeed = baseSpeedKph * factor;
                const vehicleTimeHours = distanceKm / vehicleSpeed;
                const vehicleTimeFormatted = formatDuration(vehicleTimeHours * 3600);
                document.getElementById(`${vehicle}-time`).textContent = vehicleTimeFormatted;
            });
        }

        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.round((seconds % 3600) / 60);

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            }
            return `${minutes}m`;
        }

        // Load Google Maps API with error handling
        function loadGoogleMaps() {
            // Check if API key exists
            if (!GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY === '' || GOOGLE_MAPS_API_KEY === 'null') {
                console.error('Google Maps API key is missing!');
                document.getElementById("map").innerHTML =
                    '<div class="alert alert-danger border p-3"><i class="fas fa-exclamation-triangle mr-2"></i>Google Maps API key is not configured. Please check your environment configuration.</div>';

                // Update loading states to show error
                document.getElementById("duration").innerHTML =
                    '<div class="text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>API key required</div>';
                ['semi-truck', 'box-truck', 'flatbed-truck', 'reefer-truck', 'delivery-van', 'ltl-truck'].forEach(vehicle => {
                    document.getElementById(`${vehicle}-time`).textContent = 'API key required';
                });
                return;
            }

            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&callback=initMap&libraries=places`;
            script.async = true;
            script.defer = true;

            // Fix: Remove the console.log with template literal syntax error
            console.log('Loading Google Maps with API key');

            // Handle script loading errors
            script.onerror = function () {
                console.error('Failed to load Google Maps script');
                document.getElementById("map").innerHTML =
                    '<div class="alert alert-danger border p-3"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load Google Maps. Please check your API key and internet connection.</div>';

                document.getElementById("duration").innerHTML =
                    '<div class="text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Map loading failed</div>';
                ['semi-truck', 'box-truck', 'flatbed-truck', 'reefer-truck', 'delivery-van', 'ltl-truck'].forEach(vehicle => {
                    document.getElementById(`${vehicle}-time`).textContent = 'Loading failed';
                });
            };

            document.head.appendChild(script);

            // Log the final URL being loaded (remove in production)
            console.log('Loading Google Maps from:', script.src);
        }

        // Initialize when the page loads
        window.onload = function () {
            loadGoogleMaps();
        };
    </script>
@endsection