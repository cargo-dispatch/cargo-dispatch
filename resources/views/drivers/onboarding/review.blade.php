@extends('layouts.app')
@section('title') Review Driver — {{ $driver->firstname }} {{ $driver->lastname }} @endsection
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('drivers.onboarding.pending') }}">Pending Approvals</a></li>
            <li class="breadcrumb-item active">{{ $driver->firstname }} {{ $driver->lastname }}</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">

    {{-- ── LEFT: Driver profile card ──────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-3"
                     class="btn btn-primary font-size-26" style="width:72px;height:72px;">
                    {{ strtoupper(substr($driver->firstname,0,1)) }}{{ strtoupper(substr($driver->lastname,0,1)) }}
                </div>
                <h5 class="mb-1">{{ $driver->firstname }} {{ $driver->lastname }}</h5>
                <p class="text-muted mb-2 font-size-13">{{ $driver->email }}</p>
                <p class="text-muted mb-3 font-size-13">{{ $driver->phoneno ?? '—' }}</p>

                @if($driver->onboarding_status === 'under_review')
                    <span class="badge bg-primary px-3 py-2">Under Review</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2">Docs Submitted</span>
                @endif
            </div>

            <div class="card-body border-top pt-3">
                <table class="table table-sm mb-0 font-size-13">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:45%">Driver Type</td>
                        <td>{{ optional($driver->drivertype)->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">CDL Number</td>
                        <td>{{ $driver->cdl_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">CDL State</td>
                        <td>{{ $driver->cdl_state ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">CDL Class</td>
                        <td>
                            @if($driver->cdl_class)
                                <span class="badge bg-info text-dark">Class {{ $driver->cdl_class }}</span>
                            @else —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">CDL Expiry</td>
                        <td class="{{ $driver->isCdlExpiringSoon() ? 'text-warning fw-semibold' : '' }}">
                            {{ $driver->cdl_expiry_date ? \Carbon\Carbon::parse($driver->cdl_expiry_date)->format('M d, Y') : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Medical Card</td>
                        <td class="{{ $driver->isMedicalExpiringSoon() ? 'text-warning fw-semibold' : '' }}">
                            {{ $driver->medical_card_expiry ? \Carbon\Carbon::parse($driver->medical_card_expiry)->format('M d, Y') : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Drug Test</td>
                        <td>
                            @if($driver->drug_test_status === 'passed')
                                <span class="badge bg-success">Passed</span>
                            @elseif($driver->drug_test_status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ $driver->drug_test_status ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Experience</td>
                        <td>{{ $driver->years_experience !== null ? $driver->years_experience.' yrs' : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Date of Birth</td>
                        <td>{{ $driver->date_of_birth ? \Carbon\Carbon::parse($driver->date_of_birth)->format('M d, Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Address</td>
                        <td>
                            @if($driver->address)
                                {{ $driver->address }}<br>
                                {{ $driver->city }}, {{ $driver->state }} {{ $driver->zip }}
                            @else —
                            @endif
                        </td>
                    </tr>
                    @if($driver->cdl_endorsements)
                    <tr>
                        <td class="text-muted fw-semibold">Endorsements</td>
                        <td>
                            @foreach((array)$driver->cdl_endorsements as $end)
                                <span class="badge bg-secondary me-1">{{ $end }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Documents + actions ─────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Documents --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header sidebar-wrapper py-3">
                <h6 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Submitted Documents</h6>
            </div>
            <div class="card-body">
                @if($driver->documents->isEmpty())
                    <p class="text-muted text-center py-3">No documents uploaded.</p>
                @else
                <div class="row g-3" id="docsContainer">
                    @foreach($driver->documents as $doc)
                    <div class="col-md-6" id="doc-card-{{ $doc->id }}">
                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <p class="fw-semibold mb-0 font-size-13">
                                            {{ $docTypes[$doc->type] ?? ucfirst(str_replace('_',' ',$doc->type)) }}
                                        </p>
                                        <p class="text-muted mb-0 font-size-11">{{ $doc->original_name }}</p>
                                    </div>
                                    <span id="doc-badge-{{ $doc->id }}"
                                          class="badge {{ $doc->status === 'verified' ? 'bg-success' : ($doc->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                        {{ ucfirst($doc->status) }}
                                    </span>
                                </div>
                                @if($doc->expires_at)
                                    <p class="text-muted mb-2 font-size-11">
                                        <i class="bi bi-calendar me-1"></i>
                                        Expires {{ \Carbon\Carbon::parse($doc->expires_at)->format('M d, Y') }}
                                    </p>
                                @endif
                                @if($doc->rejection_reason)
                                    <div id="doc-rejection-{{ $doc->id }}" class="alert alert-danger py-1 px-2 mb-2 alert-danger-small">
                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $doc->rejection_reason }}
                                    </div>
                                @else
                                    <div id="doc-rejection-{{ $doc->id }}" class="d-none alert alert-danger py-1 px-2 mb-2 alert-danger-small"></div>
                                @endif
                            </div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                @php $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION)); @endphp
                                <a href="{{ route('drivers.onboarding.view-doc', $doc->id) }}" target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-{{ in_array($ext,['jpg','jpeg','png']) ? 'image' : 'file-earmark-pdf' }} me-1"></i>
                                    View
                                </a>
                                @if($doc->status !== 'verified')
                                <button class="btn btn-sm btn-success doc-action-btn"
                                        data-doc-id="{{ $doc->id }}" data-action="verify">
                                    <i class="bi bi-check-lg me-1"></i> Verify
                                </button>
                                @endif
                                @if($doc->status !== 'rejected')
                                <button class="btn btn-sm btn-outline-danger doc-action-btn"
                                        data-doc-id="{{ $doc->id }}" data-action="reject">
                                    <i class="bi bi-x-lg me-1"></i> Reject
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Final decision --}}
        <div class="card shadow-sm">
            <div class="card-header sidebar-wrapper py-3">
                <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Final Decision</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- APPROVE --}}
                    <div class="col-md-6">
                        <div class="border border-success rounded p-3 h-100">
                            <h6 class="text-success mb-2"><i class="bi bi-check-circle me-2"></i>Approve Driver</h6>
                            <p class="text-muted mb-3" style="font-size:13px">
                                Driver will be set to <strong>Active</strong> and receive login credentials via email.
                            </p>
                            <form method="POST" action="{{ route('drivers.onboarding.approve', $driver->id) }}"
                                  onsubmit="return confirm('Approve {{ $driver->firstname }} {{ $driver->lastname }} and send credentials?')">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-lg me-2"></i> Approve &amp; Send Credentials
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- REJECT --}}
                    <div class="col-md-6">
                        <div class="border border-danger rounded p-3 h-100">
                            <h6 class="text-danger mb-2"><i class="bi bi-x-circle me-2"></i>Reject Application</h6>
                            <p class="text-muted mb-3" style="font-size:13px">
                                Driver will be notified via email and SMS with the reason.
                            </p>
                            <form method="POST" action="{{ route('drivers.onboarding.reject', $driver->id) }}"
                                  id="rejectForm">
                                @csrf
                                <textarea name="reason" class="form-control form-control-sm mb-2"
                                          rows="3" placeholder="Explain why this application is rejected (required)…"
                                          minlength="10" required></textarea>
                                <button type="submit" class="btn btn-danger w-100"
                                        onclick="return document.getElementById('rejectForm').querySelector('textarea').value.trim().length >= 10 || (alert('Please provide a rejection reason (min 10 chars).'), false)">
                                    <i class="bi bi-x-lg me-2"></i> Reject &amp; Notify Driver
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Reject doc modal --}}
<div class="modal fade" id="rejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Reject Document</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label font-size-13">Reason <span class="text-danger">*</span></label>
                <textarea id="docRejectReason" class="form-control form-control-sm" rows="3"
                          placeholder="e.g. Image unclear, expired document…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDocRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
const VERIFY_URL = "{{ route('drivers.onboarding.verify-doc', ['docId' => '__ID__']) }}";
const CSRF       = "{{ csrf_token() }}";

let pendingRejectDocId = null;

document.querySelectorAll('.doc-action-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const docId  = this.dataset.docId;
        const action = this.dataset.action;

        if (action === 'verify') {
            sendDocAction(docId, 'verify', null);
        } else {
            pendingRejectDocId = docId;
            document.getElementById('docRejectReason').value = '';
            $('#rejectDocModal').modal('show');
        }
    });
});

document.getElementById('confirmDocRejectBtn').addEventListener('click', function () {
    const reason = document.getElementById('docRejectReason').value.trim();
    if (!reason) { alert('Please enter a reason.'); return; }
    $('#rejectDocModal').modal('hide');
    sendDocAction(pendingRejectDocId, 'reject', reason);
});

function sendDocAction(docId, action, reason) {
    const url  = VERIFY_URL.replace('__ID__', docId);
    const body = new FormData();
    body.append('_token', CSRF);
    body.append('action', action);
    if (reason) body.append('reason', reason);

    fetch(url, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Error updating document.'); return; }

            const badge = document.getElementById('doc-badge-' + docId);
            const card  = document.getElementById('doc-card-' + docId);
            const rejDiv = document.getElementById('doc-rejection-' + docId);

            // Update badge
            badge.className = 'badge ' + (data.status === 'verified' ? 'bg-success' : 'bg-danger');
            badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

            // Show/hide rejection reason
            if (data.status === 'rejected' && reason) {
                rejDiv.classList.remove('d-none');
                rejDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + reason;
            } else {
                rejDiv.classList.add('d-none');
            }

            // Remove the button that was just used
            card.querySelectorAll('.doc-action-btn').forEach(b => {
                if (b.dataset.action === action) b.remove();
            });
        })
        .catch(() => alert('Network error. Please try again.'));
}
</script>
@endsection

@endsection
