@extends('layouts.app')
@section('content')

@section('title') {{ $name ?? 'CMS Pages' }} @endsection

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $name ?? 'CMS Pages' }}</li>
                </ol>
            </nav>
        </div>

        <div class="centered-add-user" id="formContainer">
            <a href="{{ route('cms.create') }}" class="btn btn-success d-flex align-items-center me-2">
                <i class="bi bi-plus-circle me-2"></i> Add {{$name}}
            </a>
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
            <h3 class="mt-1">{{ $name ?? 'CMS Pages' }}</h3>
        </div>

        <div class="search-container">
            <label for="searchInput" class="search-label">Search:</label>
            <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by title..." />
        </div>
    </div>

    <div class="dropdown ms-4">
        <button class="btn dropdown-toggle d-flex align-items-center button-dropdown-blue" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            Action
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item dropdown-item-dark" id="bulkDeleteBtn"><i class="bi bi-trash me-2"></i>Delete Selected</a></li>
        </ul>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%"> <input type="checkbox" id="selectAllCheckbox"> </th>
                        <th class="sortable" data-column="id" data-order="asc">
                            ID
                            <span>
                                <img src="{{ asset('assets/img/swap.png') }}" alt="Sort Icon" width="16" height="16">
                            </span>
                        </th>
                        <th>Image</th>
                        <th class="sortable" data-column="title" data-order="asc">
                            Title
                            <span>
                                <img src="{{ asset('assets/img/swap.png') }}" alt="Sort Icon" width="16" height="16">
                            </span>
                        </th>
                        <th>Type</th>
                        <th>Slug</th>
                        <th>Meta Tags</th>
             
                      
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"> <!-- Data will be loaded via AJAX here --> </tbody>
                <tbody id="loaderBody" class="loader-body">
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
                        <th width="5%"> <input type="checkbox" id="selectAllCheckbox"> </th>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Slug</th>
                        <th>Meta Tags</th>
                  
                    
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div id="pagination-info" class="mt-3">
            <!-- "Showing X to Y of Z entries" will be dynamically inserted here -->
        </div>

        <!-- Pagination Placeholder -->
        <div id="pagination" class="mt-3">
            <!-- Pagination will be dynamically inserted here -->
        </div>
    </div>
</div>

@include('components.detail-modal', [
'modalId' => 'customerDetailModal',
'entityName' => $name
])
<script src="{{ asset('assets/js/detail-modal.js') }}"></script>

@include('cms.index-js')

@endsection