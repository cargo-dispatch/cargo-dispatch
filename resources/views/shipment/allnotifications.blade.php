@extends('layouts.app')
@section('content')
{{-- Setup APP_URL for JavaScript --}}
<script>
    window.appUrl = "{{ config('app.url') }}";
</script>

{{-- CSRF Token --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid container-sm-custom">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-primary-color font-family-jost font-weight-600">
            All Notifications
        </h1>
    </div>

    @if ($notifications->count() > 0)
    <!-- Notifications Card -->
    <div class="card shadow mb-4 bg-sidebar border-bottom-1 border-radius-12">
        <!-- Card Header -->
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" 
             class="bg-sidebar text-primary-color border-bottom-1 border-radius-12-top">
            <h6 class="pb-3 m-0 font-weight-bold text-primary-color font-family-jost">
                Shipment Notifications ({{ $notifications->total() }} total)
            </h6>
            <button class="theme-btn theme-btn-sm" onclick="markAllAsRead()" style="font-size: 0.875rem; padding: 8px 16px;">
                <i class="fas fa-check-double me-1"></i> Mark All as Read
            </button>
        </div>

        <!-- Card Body -->
        <div class="card-body bg-sidebar padding-0">
            <!-- Table -->
            <div class="table-responsive">
                <table class="table custom-table theme-table mb-0">
                    <thead class="theme-table-header">
                        <tr>
                            <th class="theme-table-th">Message</th>
                            <th class="theme-table-th">Pickup</th>
                            <th class="theme-table-th">Drop</th>
                            <th class="theme-table-th">Date</th>
                           
                        </tr>
                    </thead>
                    <tbody class="theme-table-body">
                        @foreach ($notifications as $notification)
                        <tr style="cursor: pointer;" 
                            onclick="window.location.href="">
                            <td class="theme-table-td">
                                <div class="d-flex align-items-center">
                                    @if($notification->read_at == null)
                                    <span class="badge bg-warning text-dark me-2" style="font-size: 0.7rem;">New</span>
                                    @endif
                                    <span style="color: var(--text-color);">
                                        {{ $notification->data['message'] ?? 'Notification' }}
                                    </span>
                                </div>
                            </td>
                            <td class="theme-table-td" style="color: var(--text-color);">
                                {{ $notification->data['pickup'] ?? '-' }}
                            </td>
                            <td class="theme-table-td" style="color: var(--text-color);">
                                {{ $notification->data['drop'] ?? '-' }}
                            </td>
                            <td class="theme-table-td" style="color: var(--text-color);">
                                {{ $notification->created_at->format('M d, Y h:i A') }}
                            </td>
                           
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if ($notifications->hasPages())
        <div class="card-footer bg-sidebar text-primary-color border-top-1 border-radius-12-bottom">
            <div class="row align-items-center">
                <!-- Showing text -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0" style="color: var(--text-color); font-family: 'Jost', sans-serif;">
                        Showing {{ $notifications->firstItem() }} to {{ $notifications->lastItem() }} of {{ $notifications->total() }} results
                    </p>
                </div>

                <!-- Pagination -->
                <div class="col-md-6">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-md-end justify-content-center mb-0">
                            {{-- Previous --}}
                            @if ($notifications->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link" 
                                      style="background: var(--btn-bg); color: var(--search-placeholder); border: 1px solid var(--btn-border);">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            </li>
                            @else
                            <li class="page-item">
                                <a class="page-link" 
                                   href="{{ $notifications->previousPageUrl() }}" 
                                   style="background: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--btn-border);"
                                   onmouseover="this.style.background='var(--btn-hover-bg)'; this.style.color='var(--btn-hover-text)';"
                                   onmouseout="this.style.background='var(--btn-bg)'; this.style.color='var(--btn-text)';">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            @endif

                            {{-- Page numbers --}}
                            @foreach(range(max(1, $notifications->currentPage() - 2), min($notifications->lastPage(), $notifications->currentPage() + 2)) as $page)
                                @if ($page == $notifications->currentPage())
                                <li class="page-item active">
                                    <span class="page-link" 
                                          style="background: var(--hover-color); color: #000; border: 1px solid var(--hover-color); font-weight: 600;">
                                        {{ $page }}
                                    </span>
                                </li>
                                @else
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="{{ $notifications->url($page) }}" 
                                       style="background: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--btn-border);"
                                       onmouseover="this.style.background='var(--btn-hover-bg)'; this.style.color='var(--btn-hover-text)';"
                                       onmouseout="this.style.background='var(--btn-bg)'; this.style.color='var(--btn-text)';">
                                        {{ $page }}
                                    </a>
                                </li>
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if ($notifications->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" 
                                   href="{{ $notifications->nextPageUrl() }}" 
                                   style="background: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--btn-border);"
                                   onmouseover="this.style.background='var(--btn-hover-bg)'; this.style.color='var(--btn-hover-text)';"
                                   onmouseout="this.style.background='var(--btn-bg)'; this.style.color='var(--btn-text)';">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            @else
                            <li class="page-item disabled">
                                <span class="page-link" 
                                      style="background: var(--btn-bg); color: var(--search-placeholder); border: 1px solid var(--btn-border);">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        @endif
    </div>

    @else
    <!-- Empty State -->
    <div class="card shadow" style="background: var(--main-wrapper-bg); border: 1px solid var(--chart-grid); border-radius: 12px;">
        <div class="card-body text-center py-5">
            <i class="fas fa-bell-slash fa-3x mb-3" style="color: var(--search-placeholder);"></i>
            <h5 class="mb-2" style="color: var(--text-color); font-family: 'Jost', sans-serif; font-weight: 600;">
                No Notifications
            </h5>
            <p class="text-muted mb-0" style="color: var(--search-placeholder); font-family: 'Jost', sans-serif;">
                You currently have no notifications.
            </p>
        </div>
    </div>
    @endif
</div>

{{-- JavaScript for Mark All as Read --}}
<script>
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('{{ route("notifications.markAllRead") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to mark notifications as read');
        });
    }
}
</script>

<style>
/* Additional inline styles for pagination hover effects */
.page-link {
    transition: all 0.3s ease-in-out !important;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .theme-table th,
    .theme-table td {
        font-size: 0.875rem;
        padding: 10px 8px;
    }
    
    .theme-btn-sm {
        font-size: 0.75rem !important;
        padding: 6px 10px !important;
    }
}
</style>

@endsection