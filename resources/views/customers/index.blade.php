@extends('layouts.app')
@section('title') {{ $name }} @endsection
@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            <a href="{{ route('customers.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add Customer
            </a>
        </div>
    </div>

    <div class="pg-stats cols-4">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Customers</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-people pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">New This Month</span>
            <span class="pg-stat-value accent">{{ $new_this_month }}</span>
            <i class="bi bi-person-plus pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">With Shipments</span>
            <span class="pg-stat-value success">{{ $with_shipments }}</span>
            <i class="bi bi-box-seam pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">No Shipments</span>
            <span class="pg-stat-value muted">{{ $no_shipments }}</span>
            <i class="bi bi-person-dash pg-stat-icon"></i>
        </div>
    </div>

    <div class="pg-toolbar">
        <div class="pg-tabs"></div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search customers...">
            </div>
            <input type="text" id="dateFrom" class="pg-date-input flatpickr-input" placeholder="From date" readonly>
            <input type="text" id="dateTo"   class="pg-date-input flatpickr-input" placeholder="To date"   readonly>
            <button type="button" id="clearFilters" class="pg-btn-secondary">
                <i class="bi bi-x-circle"></i> Clear
            </button>
        </div>
    </div>

    <div class="pg-table-card">
        <div class="pg-table-topbar">
            <div class="pg-per-page">
                Show
                <select id="perPageSelect">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                entries
            </div>
            <button class="pg-btn-danger disabled" id="bulkDeleteBtn">
                <i class="bi bi-trash"></i> Delete Selected
            </button>
        </div>

        <div class="table-responsive">
            <table class="pg-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAllCheckbox"></th>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="7" class="text-center p-4">
                            <div class="custom-loader">
                                <img src="{{ asset(config('app.logo')) }}" alt="Loading" class="loader-logo">
                                <div class="loader"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pg-pagination-wrap">
            <div id="pagination"></div>
        </div>
    </div>

</div>

@include('components.detail-modal', ['modalId' => 'customerDetailModal', 'entityName' => $name])
<script src="{{ asset('assets/js/detail-modal.js') }}?v={{ time() }}"></script>
@include('customers.index-js')

@endsection
