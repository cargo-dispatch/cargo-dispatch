@extends('layouts.app')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@section('title') {{ $name }} @endsection

@section('content')


<div class="container-fluid mb-3">
    <div class="row align-items-center">
        <!-- Tabs on the left -->
        <div class="col-auto">
            <ul class="nav nav-tabs mb-0 gap-2" id="shipmentTabs">
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1 active" href="#" data-status="pending">
                        Pending
                        <span class="badge bg-secondary pending-count">
                            {{ $statusCounts['pending'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1" href="#" data-status="active">
                        Active
                        <span class="badge bg-secondary active-count">
                            {{ $statusCounts['active'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1" href="#" data-status="complete">
                        Complete
                        <span class="badge bg-secondary complete-count">
                            {{ $statusCounts['complete'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-11 px-3 py-1 text-danger" href="#" data-status="cancel">
                        Cancel
                        <span class="badge bg-danger cancel-count">
                            {{ $statusCounts['cancel'] ?? 0 }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Dispatch Date Picker -->
        @php
            $tomorrow = \Carbon\Carbon::tomorrow()->format('Y-m-d');
        @endphp
        <div class="col d-flex justify-content-start justify-content-md-center">
            <h5 class="mb-0 fs-15 ps-1 ps-md-0">
                <strong>Next Day Schedule :</strong>
                <input
                    type="text"
                    id="dispatch-date"
                    class="form-control fs-11 sidebar-wrapper"
                    style="display: inline-block; width: 150px; margin-left: 10px;"
                >
            </h5>
        </div>
    </div>
</div>
<div class="table-responsive">
           <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th width="5%">Sr#</th>
            <th>Customers Name</th>
            <th>Vehicle Type</th>
            <th>Vehicles</th>
            <th>Pickup Address</th>
            <th>Dropoff Address</th>
            <th>Pickup DateTime</th>
            <th>Expected Delivery</th>
            <th>Status</th>
            <th>Estimated Cost</th>
          
        </tr>
    </thead>
    <tbody id="userTableBody">
        <!-- Data will be loaded via AJAX here -->
    </tbody>
    <tbody id="loaderBody" class="loader-body">
        <tr>
            <td colspan="11" class="text-center p-3">
                <div id="loader" class="custom-loader">
                    <img src="{{ asset(config('app.logo')) }}" alt="Loading Logo" class="loader-logo">
                    <div class="loader"></div>
                </div>
            </td>
        </tr>
    </tbody>
</table>
</div>

<script>
    flatpickr("#dispatch-date", {
        dateFormat: "Y-m-d",
        minDate: new Date().fp_incr(1), // 1 day from today = tomorrow
        defaultDate: new Date().fp_incr(1)
        
    });
</script>
<script src="{{ asset('assets/js/time-date-format.js') }}"></script>
@include('dispatch.nextDayDispatch.index-js')
@endsection