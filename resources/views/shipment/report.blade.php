@extends('layouts.app')
@section('content')

@section('title') {{ $name }} @endsection

<div class="card shadow mb-4">
    <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filter Collapse Card -->
    <div class="card mb-4">
        <div class="card-header sidebar-wrapper">
            <button class="btn theme-btn  " type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                <i class="fas fa-filter me-2"></i>Filter 
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <form id="reportFilterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="start_date">Start Date</label>
                                <input type="date" class="form-control fs-13 sidebar-wrapper sidebar-wrapper" id="start_date" name="start_date" value="{{ date('Y-m-01') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="end_date">End Date</label>
                                <input type="date" class="form-control fs-13 sidebar-wrapper" id="end_date" name="end_date" value="{{ date('Y-m-t') }}">
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="customer_id">Customer</label>
                                <select class="form-control fs-13 sidebar-wrapper" id="customer_id" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->customer_title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                          <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="status">Status</label>
                                <select class="form-control fs-13 sidebar-wrapper" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="complete">Complete</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                       
                      
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="vehicle_type_id">Vehicle Type</label>
                                <select class="form-control fs-13 sidebar-wrapper" id="vehicle_type_id" name="vehicle_type_id">
                                    <option value="">All Vehicle Types</option>
                                    @foreach($vehicleTypes as $vehicleType)
                                        <option value="{{ $vehicleType->id }}">{{ $vehicleType->vehicle_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label class="fs-13" for="vehicle_id">Vehicle</label>
                                <select class="form-control fs-13 sidebar-wrapper" id="vehicle_id" name="vehicle_id">
                                    <option value="">All Vehicles</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_id }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn mbl-btn mb-2 theme-btn  me-2">
<i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                            <button type="button" class="btn mbl-btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo me-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div id="reportResults" style="display: none;">
        <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between">
            <h5 class="mb-0 fs-15">Report Results</h5>
            <div>
                <button type="button" class="btn btn-success me-2" onclick="downloadReport('excel')">
                    <i class="fas fa-file-excel me-2"></i>Download Excel
                </button>
                <button type="button" class="btn btn-danger" onclick="downloadReport('pdf')">
                    <i class="fas fa-file-pdf me-2"></i>Download PDF
                </button>
            </div>
        </div>

        <div class="records-container">
            <div class="records-per-page">
                <label for="perPageSelect" class="records-label mt-lg-1">Show </label>
                <select id="perPageSelect" class="records-select">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                </select>
                <label for="perPageSelect" class="records-label mt-lg-1">entries </label>
            </div>
            <div>
                <h6 class="mt-1" id="reportTitle">Shipment Report</h6>
            </div>
            <div class="search-container">
                <label for="searchInput" class="search-label">Search:</label>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search shipments..." />
            </div>
        </div>

        <div class="dropdown ms-4">
            <!-- <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: hsl(225, 69%, 59%); color: #fff; border: none;">
                Action
            </button> -->
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li><a class="dropdown-item" id="bulkDeleteBtn" style="color:black"><i class="bi bi-trash me-2"></i>Delete Selected</a></li>
            </ul>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAllCheckbox"></th>
                            <th>Customer Name</th>
                            <th>Vehicle Type</th>
                            <th>Pickup Address</th>
                            <th>Dropoff Address</th>
                            <th>Pickup DateTime</th>
                            <th>Expected Delivery</th>
                            <th>Status</th>
                            <th>Estimated Cost</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <!-- Data will be loaded via AJAX here -->
                    </tbody>
                    <tbody id="loaderBody" style="display: none;">
                        <tr>
                            <td colspan="10" class="text-center p-3">
                                <div id="loader" class="custom-loader">
                                    <img src="{{ asset(config('app.logo')) }}" alt="Loading Logo" class="loader-logo">
                                    <div class="loader"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th width="5%"><input type="checkbox" id="selectAllCheckboxFooter" onclick="$('#selectAllCheckbox').click(); return false;"></th>
                            <th>Customer Name</th>
                            <th>Vehicle Type</th>
                            <th>Pickup Address</th>
                            <th>Dropoff Address</th>
                            <th>Pickup DateTime</th>
                            <th>Expected Delivery</th>
                            <th>Status</th>
                            <th>Estimated Cost</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination Placeholder -->
            <div id="pagination" class="mt-3">
                <!-- Pagination will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

@include('modals.comments')

@include('components.detail-modal', [
    'modalId' => 'shipmentDetailModal',
    'entityName' => $name
])
@include('shipment.report-js')

<script src="{{ asset('assets/js/detail-modal.js') }}"></script>
<script src="{{ asset('assets/js/shipment-report.js') }}"></script>

@endsection