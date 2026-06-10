@extends('layouts.app')

@section('content')
<script src="{{ asset('assets/js/time-date-format.js') }}?v={{ time() }}"></script><div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column justify-content-between align-items-start">
                <div>
                    <h4 class="text-warning fs-18 mb-1"><i class="fas fa-file-invoice me-2" style="color: rgb(0, 120, 248);"></i>Invoice Reports</h4>
                    <p class="show-enteries small mb-0">Generate and manage shipment invoices</p>
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

    <!-- Include your date format script -->


    <!-- Filters Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
               
                <div class="card-body p-2 py-3">
                    <form id="invoiceReportForm" action="{{ route('shipments-invoice.generate') }}" method="POST" target="_blank">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Date Range -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">Start Date</label>
                                <input type="date" class="form-control fs-13 sidebar-wrapper form-control-sm border" id="start_date" name="start_date" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">End Date</label>
                                <input type="date" class="form-control fs-13 sidebar-wrapper form-control-sm border" id="end_date" name="end_date" required>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                                <select class="form-select form-control fs-13 sidebar-wrapper form-select-sm border" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancel">Cancel</option>
                                    <option value="complete">Complete</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>

                            <!-- Customer Filter -->
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold text-muted mb-1">Customer</label>
                                <select class="form-control fs-13 sidebar-wrapper"  id="customer_id" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->first_name }} {{$customer->last_name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="">
                                <div class="d-flex flex-wrap flex-col gap-2">
                                    <button type="button" class="btn mbl-btn theme-btn btn-sm px-3" id="previewBtn" >
                                        <i class="fas fa-eye me-1"></i>
                                      Report
                                        <div class="button-loader d-none ms-1">
                                            <div class="spinner-border spinner-border-sm" role="status"></div>
                                        </div>
                                    </button>

                                    <button type="submit" class="btn mbl-btn btn-sm theme-btn btn-outline-danger px-2 border">
                                        <i class="fas fa-file-pdf me-1"></i>
                                        All Invoices PDF
                                    </button>

                                    <button type="reset" class="btn btn-sm mbl-btn show-enteries theme-btn btn-outline-secondary px-3 border" id="resetBtn">
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

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statsSection" style="display: none;">
        <div class="col-12">
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card badge-layout sidebar-wrapper card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <span class=" small">Total Shipments</span>
                                    <h5 class="mb-0 mt-1 fw-semibold  counter" id="totalShipments">0</h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded">
                                        <i class="fas fa-shipping-fast" style="color: rgb(0, 120, 248);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card badge-layout sidebar-wrapper border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <span class=" small">Total Revenue</span>
                                    <h5 class="mb-0 mt-1 fw-semibold text-success" id="totalRevenue">$0.00</h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded" style="background-color: rgba(40, 167, 69, 0.1);">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card badge-layout sidebar-wrapper border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <span class=" small">Total Cost</span>
                                    <h5 class="mb-0 mt-1 fw-semibold text-warning" id="totalCost">$0.00</h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded" style="background-color: rgba(255, 193, 7, 0.1);">
                                        <i class="fas fa-money-bill-wave text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card badge-layout sidebar-wrapper border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <span class=" small">Profit Margin</span>
                                    <h5 class="mb-0 mt-1 fw-semibold text-info" id="profitMargin">0%</h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded" style="background-color: rgba(23, 162, 184, 0.1);">
                                        <i class="fas fa-chart-line text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Results -->
    <div class="row" id="previewSection" style="display: none;">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header sidebar-wrapper bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark">
                            <i class="fas fa-table me-2" style="color: rgb(0, 120, 248);"></i>
                            Preview Results
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border small" id="totalCount">
                                <span id="recordCount">0</span> records
                            </span>
                            <!-- <button class="btn btn-sm btn-outline-secondary border" id="exportCsv">
                                <i class="fas fa-download me-1"></i> CSV
                            </button> -->
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="custom-table stripped sidebar-wrapper">
                            <thead class="">
                                <tr>
                                    <th class="ps-3 border-0 small fw-semibold text-muted py-2">INVOICE #</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">CUSTOMER</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">PICKUP TIME</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">DELIVERY TIME</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">ROUTE</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">DISTANCE</th>
                                    <th class="border-0 small fw-semibold text-muted py-2">STATUS</th>
                                    <th class="text-end border-0 small fw-semibold text-muted py-2">COST</th>
                                    <th class="text-end border-0 small fw-semibold text-muted py-2">AMOUNT</th>
                                    <th class="text-center border-0 small fw-semibold text-muted py-2">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Data will be populated via AJAX -->
                            </tbody>
                            <tfoot class="bg-light sidebar-wrapper">
                                <tr>
                                    <td colspan="7" class="ps-3 border-0 small fw-semibold text-muted py-2 text-end">Grand Total:</td>
                                    <td class="text-end border-0 small fw-semibold text-success py-2" id="grandTotalCost">$0.00</td>
                                    <td class="text-end border-0 small fw-semibold text-success py-2" id="grandTotalAmount">$0.00</td>
                                    <td class="text-center border-0 py-2">-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



@include('invoice.index-js-invoice')
@endsection