@extends('layouts.app')
@section('title') {{ $name }} @endsection

@section('content')
<script src="{{ asset('assets/js/time-date-format.js') }}?v={{ time() }}"></script>
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-col">
                <div>
                    <h4 class=" text-warning fs-18 mb-1"><i class="fas fa-file-invoice me-2"></i>Driver Payroll Reports</h4>
                    <p class="show-enteries small mb-0">Generate and manage payroll</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-calendar me-1"></i>
                        <span id="dateRangeDisplay">Last 30 Days</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2 py-3">
                    <form id="invoiceReportForm" action="" method="POST" target="_blank">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Date Range -->
                            <div class="col-md-2">
                                <x-calendar-input
                                    name="start_date"
                                    label="Start Date"
                                    required
                                    class="fs-13 mb-0"
                                    inputClass="form-control-sm" />
                            </div>

                            <div class="col-md-2">
                                <x-calendar-input
                                    name="end_date"
                                    label="End Date"
                                    required
                                    class="fs-13 mb-0"
                                    inputClass="form-control-sm" />
                            </div>

                            <!-- Driver Filter -->
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Driver</label>
                                <div class="position-relative">
                                    <select class="fs-13 form-control sidebar-wrapper" id="driver_id" name="driver_id" required>
                                        <option value="">Select Drivers</option>
                                        @foreach ($drivers as $driver)
                                        <option class=" sidebar-wrapper" value="{{ $driver->id }}">{{ $driver->firstname }} {{ $driver->lastname }}</option>
                                        @endforeach
                                    </select>
                                    <div class="position-absolute end-0 top-50 translate-middle-y me-2">
                                        <i class="fas fa-user driver-select-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-3">
                                <div class="d-flex flex-col gap-2">
                                    <button type="button" class="btn mbl-btn theme-btn" id="previewBtn">
                                        <i class="fas fa-eye me-1"></i>
                                        Report
                                        <div class="button-loader d-none ms-1">
                                            <div class="spinner-border spinner-border-sm" role="status"></div>
                                        </div>
                                    </button>

                                    <button type="button" class="btn mbl-btn btn-sm btn-outline-secondary px-3 border" id="resetBtn">
                                        <i class="fas fa-redo me-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Results -->
    <div class="row display-none" id="previewSection">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header sidebar-wrapper border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 show-enteries">
                            <i class="fas fa-table me-2"></i>
                            Preview Results
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border small" id="totalCount">
                                <span id="recordCount">0</span> records
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive table-responsive-500">
                        <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="ps-3 border-0 small fw-semibold py-2">DRIVER NAME</th>
                                    <th class="border-0 small fw-semibold py-2">CONTACT NO</th>
                                    <th class="border-0 small fw-semibold py-2">START DATE</th>
                                    <th class="border-0 small fw-semibold py-2">END DATE</th>
                                    <th class="text-center border-0 small fw-semibold py-2">TOTAL SHIPMENTS</th>
                                    <th class="text-center border-0 small fw-semibold py-2">TOTAL MILES</th>
                                    <th class="text-center border-0 small fw-semibold py-2">PER MILE RATE</th>
                                    <th class="text-end border-0 small fw-semibold py-2">DRIVER EARNINGS</th>
                                    <th class="text-center border-0 small fw-semibold py-2">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Data will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .is-invalid {
        border-color: #dc3545 !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(.375em + .1875rem) center;
        background-size: calc(.75em + .375rem) calc(.75em + .375rem);
    }
    
    .is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
</style>
@include('driver-payroll.index-js')
@endsection