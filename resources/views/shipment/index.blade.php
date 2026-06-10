@extends('layouts.app')
@section('title') {{ $name }} @endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            <a href="{{ route('shipments.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add Shipment
            </a>
        </div>
    </div>

    <div class="pg-stats cols-4">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Shipments</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-box-seam pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Pending</span>
            <span class="pg-stat-value accent">{{ $pending }}</span>
            <i class="bi bi-hourglass-split pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Active / In Transit</span>
            <span class="pg-stat-value success">{{ $active }}</span>
            <i class="bi bi-truck pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Delivered</span>
            <span class="pg-stat-value muted">{{ $delivered }}</span>
            <i class="bi bi-check2-circle pg-stat-icon"></i>
        </div>
    </div>

    {{-- Status filter tabs --}}
    <div class="pg-toolbar">
        <div class="pg-tabs" id="statusFilters">
            @foreach([
                ''           => 'All',
                'pending'    => 'Pending',
                'active'     => 'Active',
                'assigned'   => 'Assigned',
                'picked_up'  => 'Picked Up',
                'in_transit' => 'In Transit',
                'delivered'  => 'Delivered',
                'cancelled'  => 'Cancelled',
            ] as $val => $label)
            <button type="button" class="pg-tab {{ $val === ($filter_status ?? '') ? 'active' : '' }}" data-status="{{ $val }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search shipments...">
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
            <table class="pg-table"
                data-load-route="{{ route('shipments.get') }}"
                data-filter-status="{{ $filter_status ?? '' }}">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAllCheckbox"></th>
                        <th>Customer</th>
                        <th>Vehicle Type</th>
                        <th>Driver</th>
                        <th>Pickup Address</th>
                        <th>Dropoff Address</th>
                        <th>Pickup DateTime</th>
                        <th>Expected Delivery</th>
                        <th>Status</th>
                        <th>Est. Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="11" class="text-center p-4">
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

@include('modals.comments')
@include('components.detail-modal', ['modalId' => 'customerDetailModal', 'entityName' => $name])
@include('shipment.index-js')

@endsection
