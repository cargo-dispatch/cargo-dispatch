@extends('layouts.app')
@section('title') {{ $name }} @endsection
@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            <a href="{{ route('users.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add User
            </a>
        </div>
    </div>

    <div class="pg-stats cols-3">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Users</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-person-circle pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Admins</span>
            <span class="pg-stat-value pg-stat-onduty">{{ $admins }}</span>
            <i class="bi bi-shield-check pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">New This Month</span>
            <span class="pg-stat-value pg-stat-driving">{{ $new_month }}</span>
            <i class="bi bi-person-plus pg-stat-icon"></i>
        </div>
    </div>

    <div class="pg-toolbar">
        <div class="pg-tabs"></div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search users...">
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
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="8" class="text-center p-4">
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
<script src="{{ asset('assets/js/detail-modal.js') }}?v={{ filemtime(public_path('assets/js/detail-modal.js')) }}"></script>
@include('users.index-js')

@endsection
