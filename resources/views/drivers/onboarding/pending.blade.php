@extends('layouts.app')
@section('title') Pending Driver Approvals @endsection
@section('content')

<div class="card shadow mb-4">
    {{-- Header --}}
    <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('managedriver.index') }}">Drivers</a></li>
                <li class="breadcrumb-item active">Pending Approvals</li>
            </ol>
        </nav>
        <a href="{{ route('managedriver.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Drivers
        </a>
    </div>

    <div class="card-body">

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Summary bar --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <h5 class="mb-0">Pending Approvals</h5>
            <span class="badge bg-warning text-dark fs-6">{{ $totalPending }} pending</span>
</5>

        

        {{-- Filter --}}
        <form method="GET" action="{{ route('drivers.onboarding.pending') }}" class="row g-2 mb-4">
            <div class="col-auto">
                <select name="driver_type" class="form-select form-select-sm">
                    <option value="">All Driver Types</option>
                    @foreach($driverTypes as $type)
                        <option value="{{ $type->id }}" {{ request('driver_type') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('drivers.onboarding.pending') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>

        {{-- Table --}}
        @if($drivers->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check2-circle" style="font-size:48px;color:#1cc88a"></i>
                <p class="mt-3 mb-0 fw-semibold">No pending applications</p>
                <p class="font-size-13">All driver applications have been reviewed.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="custom-table stripped sidebar-wrapper w-100">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Contact</th>
                        <th>Driver Type</th>
                        <th>CDL Class</th>
                        <th>CDL Expiry</th>
                        <th>Docs</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($drivers as $driver)
                    @php
                        $docsTotal    = $driver->documents->count();
                        $docsVerified = $driver->documents->where('status', 'verified')->count();
                        $docsPending  = $driver->documents->where('status', 'pending')->count();
                        $docsRejected = $driver->documents->where('status', 'rejected')->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold"
                                     class="btn btn-primary font-size-14 display-flex flex-align-center-justify-center" style="width:36px;height:36px;flex-shrink:0">
                                    {{ strtoupper(substr($driver->firstname, 0, 1)) }}{{ strtoupper(substr($driver->lastname, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold font-size-14">{{ $driver->firstname }} {{ $driver->lastname }}</div>
                                    <div class="text-muted font-size-12">{{ $driver->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="font-size-13">{{ $driver->phoneno ?? '—' }}</td>
                        <td class="font-size-13">{{ optional($driver->drivertype)->name ?? '—' }}</td>
                        <td>
                            @if($driver->cdl_class)
                                <span class="badge bg-info text-dark">Class {{ $driver->cdl_class }}</span>
                            @else
                                <span class="text-muted font-size-12">—</span>
                            @endif
                        </td>
                        <td class="font-size-13">
                            @if($driver->cdl_expiry_date)
                                @php $expiring = \Carbon\Carbon::parse($driver->cdl_expiry_date)->diffInDays(now()) @endphp
                                <span class="{{ $expiring <= 60 ? 'text-warning fw-semibold' : '' }}">
                                    {{ \Carbon\Carbon::parse($driver->cdl_expiry_date)->format('M d, Y') }}
                                </span>
                            @else —
                            @endif
                        </td>
                        <td>
                            <div class="font-size-12">
                                @if($docsVerified > 0)
                                    <span class="badge bg-success me-1">{{ $docsVerified }} verified</span>
                                @endif
                                @if($docsPending > 0)
                                    <span class="badge bg-warning text-dark me-1">{{ $docsPending }} pending</span>
                                @endif
                                @if($docsRejected > 0)
                                    <span class="badge bg-danger me-1">{{ $docsRejected }} rejected</span>
                                @endif
                                @if($docsTotal === 0)
                                    <span class="text-muted">No docs</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($driver->onboarding_status === 'under_review')
                                <span class="badge bg-primary">Under Review</span>
                            @else
                                <span class="badge bg-warning text-dark">Docs Submitted</span>
                            @endif
                        </td>
                        <td class="font-size-12 text-wrap">
                            {{ $driver->updated_at->format('M d, Y') }}<br>
                            <span class="text-muted">{{ $driver->updated_at->diffForHumans() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('drivers.onboarding.review', $driver->id) }}"
                               class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i> Review
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($drivers->hasPages())
            <div class="mt-3">{{ $drivers->withQueryString()->links() }}</div>
        @endif
        @endif

    </div>
</div>

@endsection
