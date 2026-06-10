@extends('layouts.app')
@section('title') {{ $name }} @endsection
@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            <a href="{{ route('vehicles.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add Vehicle
            </a>
        </div>
    </div>

    <div class="pg-stats">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Vehicles</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-truck pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Active Vehicles</span>
            <span class="pg-stat-value accent" id="stat-active">—</span>
            <i class="bi bi-speedometer2 pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Under Maintenance</span>
            <span class="pg-stat-value muted" id="stat-maintenance">—</span>
            <i class="bi bi-tools pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Required Attention</span>
            <span class="pg-stat-value accent" id="stat-attention">—</span>
            <i class="bi bi-exclamation-triangle pg-stat-icon"></i>
        </div>
    </div>

    <div class="pg-toolbar">
        <div class="pg-tabs">
            <button class="pg-tab active" data-filter="all">All ({{ $total }})</button>
            <button class="pg-tab" data-filter="active">Active</button>
            <button class="pg-tab" data-filter="inactive">Inactive</button>
            <button class="pg-tab" data-filter="maintenance">Maintenance</button>
            <button class="pg-tab" data-filter="attention">Attention</button>
        </div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search vehicles...">
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
                        <th>Vehicle ID</th>
                        <th>Type</th>
                        <th>Make &amp; Model</th>
                        <th>Year</th>
                        <th>Ownership</th>
                        <th>Reg. Expiry</th>
                        <th>Insurance Expiry</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="9" class="text-center p-4">
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
@include('vehicles.index-js')

@endsection
