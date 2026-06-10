<div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg rounded-4 theme-modal">
            <!-- Modal Header -->
            <div class="modal-header py-3 theme-modal-header">
                <h5 class="modal-title d-flex align-items-center fs-4 theme-modal-title">
                    <i class="bi bi-chat-left-text me-2"></i> Shipment Remarks
                </h5>
                <button type="button" class="btn-close theme-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body theme-modal-body p-3 p-md-4">
                <!-- Add Comment Section -->
                <div class="form-group mb-4">
                    <div class="card border-0 theme-card shadow-sm">
                        <div class="card-body p-3">
                            <label for="newComment" class="form-label fw-semibold theme-label mb-2">
                                <i class="bi bi-pencil-square me-2"></i>Add New Remarks
                            </label>
                            <textarea class="form-control theme-input border" id="newComment" rows="2" placeholder="Write new remarks..." style="resize: vertical;"></textarea>
                            <div class="d-flex justify-content-end mt-2">
                                <button id="saveComment" class="btn theme-btn btn-sm">
                                    <i class="bi bi-save me-1"></i> Save Remarks
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3 theme-modal-hr">

                <!-- Previous Remarks Section -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="theme-modal-section-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>Previous Remarks
                        </h6>
                        <span class="badge theme-badge" id="commentsCount">0 remarks</span>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table theme-table table-sm mb-0" id="commentsTable">
                            <thead class="theme-table-header position-sticky top-0">
                                <tr>
                                    <th class="theme-table-th py-2">Remarks</th>
                                    <th class="theme-table-th py-2">Added By</th>
                                    <th class="theme-table-th py-2">Type</th>
                                    <th class="theme-table-th py-2">Time</th>
                                </tr>
                            </thead>
                            <tbody id="commentsTableBody" class="theme-table-body">
                                <tr id="noCommentsRow" style="display: none;">
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                                        No remarks found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer theme-modal-footer py-3 rounded-bottom-4">
                <!-- <button type="button" class="btn theme-btn-outline btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Close
                </button> -->
            </div>
        </div>
    </div>
</div>
