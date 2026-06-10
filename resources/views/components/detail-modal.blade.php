@php
    $modalId = $modalId ?? 'detailModal';
    $entityName = $entityName ?? 'Entity';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg rounded-4 theme-modal">
            <div class="modal-header py-3 theme-modal-header">
                <h5 class="modal-title d-flex align-items-center fs-4 theme-modal-title" id="{{ $modalId }}Label">
                    <i class="bi bi-info-circle me-2"></i> {{ $entityName }} Details
                </h5>
                <button id="close-modal-btn" type="button" class="btn-close theme-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body theme-modal-body p-4 modal-body-scrollable">
                <div class="container">
                    <div class="row g-3" id="detail-container">
                        <!-- Content injected by JS -->
                    </div>

                    @if(env('SHOW_ACTIVITY_LOG', false) == 'true')
                    <hr class="my-4 theme-modal-hr">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="theme-modal-section-title mb-0"><i class="bi bi-clock-history me-2"></i>Activity Logs</h5>
                        <button class="btn btn-link theme-collapse-arrow" data-bs-toggle="collapse" data-bs-target="#auditLogsSection" aria-expanded="true">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                    <div id="auditLogsSection" class="collapse show mt-2">
                        <table class="table theme-table mb-0">
                            <thead class="theme-table-header">
                                <tr>
                                    <th class="theme-table-th">Action</th>
                                    <th class="theme-table-th">By</th>
                                    <th class="theme-table-th">Dated</th>
                                    <th class="theme-table-th">Changes</th>
                                </tr>
                            </thead>
                            <tbody id="audit-log-container" class="theme-table-body">
                                <!-- Audit logs injected by JS -->
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer theme-modal-footer py-3 rounded-bottom-4">
                <button type="button" class="btn theme-btn-outline" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Add this right after your modal HTML -->
<script>
// Nuclear option for closing modal - works regardless of conflicts
function forceCloseModal() {
    // Get the modal element
    const modal = document.getElementById('{{ $modalId }}');
    
    // Hide the modal directly
    modal.style.display = 'none';
    
    // Remove the 'modal-open' class from body
    document.body.classList.remove('modal-open');
    
    // Remove any modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Reset body padding (in case it was modified)
    document.body.style.paddingRight = '';
    
    // Enable body scrolling
    document.body.style.overflow = '';
    
    // Dispatch a custom event in case other code is listening
    const event = new Event('modalForceClosed');
    modal.dispatchEvent(event);
}

// Theme synchronization for modal
function syncModalTheme() {
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    const modal = document.getElementById('{{ $modalId }}');
    
    if (modal) {
        // Update all theme-dependent elements
        const elements = modal.querySelectorAll('[class*="theme-"]');
        elements.forEach(el => {
            // Force reflow to ensure theme changes are applied
            el.style.display = 'none';
            el.offsetHeight; // Trigger reflow
            el.style.display = '';
        });
    }
}

// Attach event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Header close button
    const headerCloseBtn = document.getElementById('close-modal-btn');
    if (headerCloseBtn) {
        headerCloseBtn.addEventListener('click', forceCloseModal);
    }
    
    // Footer close button
    const footerCloseBtn = document.querySelector('#{{ $modalId }} .theme-btn-outline');
    if (footerCloseBtn) {
        footerCloseBtn.addEventListener('click', forceCloseModal);
    }
    
    // Watch for theme changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-theme') {
                syncModalTheme();
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
    
    // Initial sync
    syncModalTheme();
});

// Emergency close function available globally
window.forceCloseModal = forceCloseModal;
window.syncModalTheme = syncModalTheme;

// Also sync theme when modal is shown
document.getElementById('{{ $modalId }}').addEventListener('show.bs.modal', function() {
    setTimeout(syncModalTheme, 50);
});
</script>

