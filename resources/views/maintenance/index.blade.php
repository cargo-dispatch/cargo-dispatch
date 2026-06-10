@extends('layouts.app')
@section('title') {{ $name }} @endsection
@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            <a href="{{ route('maintenance.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add Maintenance
            </a>
        </div>
    </div>

    <div class="pg-stats cols-4">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Records</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-wrench pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Scheduled</span>
            <span class="pg-stat-value" style="color:var(--hover-color)">{{ $scheduled }}</span>
            <i class="bi bi-calendar-check pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Completed</span>
            <span class="pg-stat-value" style="color:#22c55e">{{ $completed }}</span>
            <i class="bi bi-check-circle pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Cancelled</span>
            <span class="pg-stat-value" style="color:#ef4444">{{ $cancelled }}</span>
            <i class="bi bi-x-circle pg-stat-icon"></i>
        </div>
    </div>

    <div class="pg-toolbar">
        <div class="pg-tabs" id="statusFilters">
            <button type="button" class="pg-tab active" data-status="">All</button>
            <button type="button" class="pg-tab" data-status="scheduled">Scheduled</button>
            <button type="button" class="pg-tab" data-status="completed">Completed</button>
            <button type="button" class="pg-tab" data-status="cancelled">Cancelled</button>
        </div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search maintenance...">
            </div>
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
                        <th>Maintenance Type</th>
                        <th>Description</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Maintenance Date</th>
                        <th>Cost</th>
                        <th>Next Service Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="10" class="text-center p-4">
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
@include('maintenance.index-js')

@endsection
