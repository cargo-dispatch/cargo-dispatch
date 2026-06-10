@extends('layouts.app')
@section('title') {{ $name }} @endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/drivers.css') }}">
@endpush

@section('content')

<div class="pg-page">

    <div class="pg-header">
        <div class="pg-header-title"></div>
        <div class="pg-header-actions">
            @php $pendingCount = \App\Models\Drivers\Driver::whereIn('onboarding_status', ['docs_submitted','under_review'])->count(); @endphp
            @if($pendingCount > 0)
            <a href="{{ route('drivers.onboarding.pending') }}" class="pg-btn-warning">
                <i class="bi bi-clock-history"></i> Pending Approvals
                <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
            </a>
            @endif
            <button class="pg-btn-secondary" data-bs-toggle="modal" data-bs-target="#inviteDriverModal">
                <i class="bi bi-envelope-plus"></i> Invite Driver
            </button>
            <a href="{{ route('managedriver.create') }}" class="pg-btn-add">
                <i class="bi bi-plus-lg"></i> Add Driver            </a>
        </div>
    </div>

    <div class="pg-stats cols-4">
        <div class="pg-stat-card">
            <span class="pg-stat-label">Total Drivers</span>
            <span class="pg-stat-value">{{ $total }}</span>
            <i class="bi bi-person-badge pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Driving</span>
            <span class="pg-stat-value pg-stat-driving">{{ $driving }}</span>
            <i class="bi bi-truck pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">On Duty</span>
            <span class="pg-stat-value pg-stat-onduty">{{ $on_duty }}</span>
            <i class="bi bi-clock pg-stat-icon"></i>
        </div>
        <div class="pg-stat-card">
            <span class="pg-stat-label">Off Duty</span>
            <span class="pg-stat-value pg-stat-offduty">{{ $off_duty }}</span>
            <i class="bi bi-moon pg-stat-icon"></i>
        </div>
    </div>

    <div class="pg-toolbar">
        <div class="pg-tabs">
            <button class="pg-tab filter-btn active" data-filter="duty_status" data-value="">All</button>
            <button class="pg-tab filter-btn filter-duty-btn" data-filter="duty_status" data-value="driving">
                <i class="pg-duty-dot pg-duty-driving"></i> Driving
            </button>
            <button class="pg-tab filter-btn filter-duty-btn" data-filter="duty_status" data-value="on_duty_not_driving">
                <i class="pg-duty-dot pg-duty-onduty"></i> On Duty
            </button>
            <button class="pg-tab filter-btn filter-duty-btn" data-filter="duty_status" data-value="off_duty">
                <i class="pg-duty-dot pg-duty-offduty"></i> Off Duty
            </button>
            <button class="pg-tab filter-btn filter-duty-btn" data-filter="duty_status" data-value="sleeper">
                <i class="pg-duty-dot pg-duty-sleeper"></i> Sleeper
            </button>

            {{-- Driver Type dropdown --}}
            <div class="position-relative" id="driverTypeWrap">
                <button class="pg-tab filter-btn" id="driverTypeFilterBtn" type="button">
                    <i class="bi bi-funnel"></i> Driver Type <i class="bi bi-chevron-down pg-chevron-sm"></i>
                </button>
                <div id="driverTypeDropdown" class="filter-dropdown">
                    <div class="filter-dropdown-scroll">
                        <a href="javascript:void(0)" class="filter-option" data-filter="driver_type" data-value="">All Driver Types</a>
                        <div id="driverTypeOptions"></div>
                    </div>
                </div>
            </div>

            <button class="pg-tab" id="clearFiltersBtn">
                <i class="bi bi-x-circle"></i> Clear
            </button>
        </div>
        <div class="pg-toolbar-right">
            <div class="pg-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search drivers...">
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
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Emergency No</th>
                        <th>Email</th>
                        <th>Driver Type</th>
                        <th>Incentive/Mile</th>
                        <th>Duty / HOS</th>
                        <th>License No</th>
                        <th>License Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
                <tbody id="loaderBody" class="loader-body">
                    <tr>
                        <td colspan="12" class="text-center p-4">
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
            <div id="pagination-info"></div>
            <div id="pagination"></div>
        </div>
    </div>

</div>

{{-- ── Invite Driver Modal ── --}}
<div class="modal fade" id="inviteDriverModal" tabindex="-1" aria-labelledby="inviteDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('drivers.onboarding.invite') }}" id="inviteDriverForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteDriverModalLabel">
                        <i class="bi bi-envelope-plus me-2 text-success"></i> Invite Driver
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li class="font-size-13">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold font-size-13">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="firstname" class="form-control form-control-sm" value="{{ old('firstname') }}" placeholder="John" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold font-size-13">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="lastname" class="form-control form-control-sm" value="{{ old('lastname') }}" placeholder="Doe" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold font-size-13">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email') }}" placeholder="driver@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold font-size-13">Phone Number</label>
                            <input type="text" name="phoneno" class="form-control form-control-sm" value="{{ old('phoneno') }}" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold font-size-13">Driver Type</label>
                            <select name="driver_type_id" class="form-select form-select-sm">
                                <option value="">— Select Type —</option>
                                @foreach(\App\Models\DriverType\DriverType::all() as $type)
                                    <option value="{{ $type->id }}" {{ old('driver_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0 font-size-12">
                        <i class="bi bi-info-circle me-1"></i>
                        The driver will receive an email with a registration link valid for <strong>7 days</strong>.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm" id="sendInviteBtn">
                        <i class="bi bi-send me-1"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Driver Detail Modal ── --}}
<div class="modal fade" id="driverDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Driver Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body modal-body-max-height" id="driverModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.detail-modal', ['modalId' => 'customerDetailModal', 'entityName' => $name])

<script>
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('inviteDriverModal')).show();
    });
@endif

document.getElementById('inviteDriverForm').addEventListener('submit', function () {
    var btn = document.getElementById('sendInviteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';
});

window.DRIVER_DETAILS_URL = "{{ route('drivers.details', ':id') }}";
window.DRIVER_STATUS_URL  = "{{ route('drivers.update-status', ':id') }}";
</script>

<script src="{{ asset('assets/js/detail-modal.js') }}?v={{ filemtime(public_path('assets/js/detail-modal.js')) }}"></script>
@include('drivers.index-js')

@endsection
