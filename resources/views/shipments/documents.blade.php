@extends('layouts.app')

@section('content')
<div class="card shadow mb-4">
    <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Shipment Documents</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="ms-4 mt-3 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm doc-filter-btn" data-filter="all">
                <i class="bi bi-file-text me-1"></i> All Documents
            </button>
            <button class="btn btn-sm doc-filter-btn active" data-filter="BOL">
                <i class="bi bi-file-pdf me-1"></i> Bills of Lading (BOL)
            </button>
            <button class="btn btn-sm doc-filter-btn" data-filter="POD">
                <i class="bi bi-file-check me-1"></i> Proofs of Delivery (POD)
            </button>
            <button class="btn btn-sm doc-filter-btn" data-filter="pending">
                <i class="bi bi-hourglass me-1"></i> Pending
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="documentsContainer" class="row">
            <!-- Documents will be loaded here via JS -->
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="text-center py-5" style="display: none;">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No documents found</p>
        </div>
    </div>
</div>

<!-- Document Detail Modal -->
<div class="modal fade" id="documentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gold">
                <h5 class="modal-title" id="documentModalTitle">Document Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="documentModalBody">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="documentDownloadBtn" href="#" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<style>
    :root {
        --theme-primary: #FFD700;
        --theme-border: #d3d3d3;
        --theme-bg: #ffffff;
        --doc-text: #333333;
    }

    [data-theme="dark"] {
        --theme-primary: #FFD700;
        --theme-border: #444444;
        --theme-bg: #2d3748;
        --doc-text: #ffffff;
    }

    .doc-filter-btn {
        border: 2px solid var(--theme-primary);
        color: var(--doc-text);
        background-color: transparent;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .doc-filter-btn:hover {
        background-color: var(--theme-primary);
        color: #000;
    }

    .doc-filter-btn.active {
        background-color: var(--theme-primary);
        color: #000;
        font-weight: 600;
    }

    .document-card {
        background-color: var(--theme-bg);
        border: 1px solid var(--theme-border);
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .document-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .document-header {
        background: linear-gradient(135deg, var(--theme-primary), #FFC700);
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .document-header h6 {
        margin: 0;
        color: #000;
        font-weight: bold;
    }

    .document-header .badge {
        font-size: 11px;
    }

    .document-body {
        padding: 16px;
    }

    .shipment-info {
        background-color: rgba(255, 215, 0, 0.05);
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 12px;
        border-left: 4px solid var(--theme-primary);
    }

    .shipment-info strong {
        color: var(--theme-primary);
    }

    .ocr-section {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--theme-border);
    }

    .ocr-section h6 {
        color: var(--theme-primary);
        font-weight: bold;
        margin-bottom: 12px;
    }

    .extracted-field {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--theme-border);
    }

    .extracted-field:last-child {
        border-bottom: none;
    }

    .extracted-field-label {
        color: var(--doc-text);
        font-weight: 500;
    }

    .extracted-field-value {
        color: var(--theme-primary);
        font-weight: 600;
        text-align: right;
    }

    .confidence-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        margin-top: 8px;
    }

    .confidence-high {
        background-color: #d1fae5;
        color: #065f46;
    }

    .confidence-medium {
        background-color: #fef3c7;
        color: #92400e;
    }

    .confidence-low {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .action-buttons button {
        flex: 1;
        padding: 8px;
        font-size: 12px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-btn {
        background-color: var(--theme-primary);
        color: #000;
    }

    .view-btn:hover {
        opacity: 0.9;
    }

    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-extracted {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-failed {
        background-color: #fee2e2;
        color: #991b1b;
    }
</style>

<script>
    $(document).ready(function () {
        const API_BASE = "{{ config('app.url') }}/api/admin/shipments/documents";
        const token = $('meta[name="csrf-token"]').attr('content');
        let allDocuments = [];

        // Fetch documents
        function loadDocuments(filter = 'BOL') {
            $.ajax({
                url: API_BASE,
                method: 'GET',
                data: { filter: filter === 'all' ? '' : filter },
                headers: { 'X-CSRF-TOKEN': token },
                success: function (response) {
                    allDocuments = response.data || [];
                    renderDocuments(allDocuments);
                },
                error: function () {
                    $('#documentsContainer').html('<p class="text-danger">Error loading documents</p>');
                }
            });
        }

        // Render documents
        function renderDocuments(documents) {
            const container = $('#documentsContainer');
            container.empty();

            if (documents.length === 0) {
                $('#emptyState').show();
                return;
            }

            $('#emptyState').hide();

            documents.forEach(function (doc) {
                const card = createDocumentCard(doc);
                container.append(card);
            });
        }

        // Create document card HTML
        function createDocumentCard(doc) {
            const statusClass = `status-${doc.extraction_status}`;
            const statusLabel = doc.extraction_status === 'extracted' ? 'Processed' : doc.extraction_status.charAt(0).toUpperCase() + doc.extraction_status.slice(1);
            
            const confidenceClass = getConfidenceClass(doc.extraction_confidence);
            const confidenceLabel = doc.extraction_confidence ? `${Math.round(doc.extraction_confidence)}%` : 'N/A';

            let fieldsHtml = '';
            if (doc.extracted_fields && Object.keys(doc.extracted_fields).length > 0) {
                fieldsHtml = '<div class="ocr-section">';
                fieldsHtml += '<h6>📋 Extracted Information</h6>';
                
                Object.entries(doc.extracted_fields).forEach(([key, value]) => {
                    fieldsHtml += `
                        <div class="extracted-field">
                            <span class="extracted-field-label">${formatLabel(key)}</span>
                            <span class="extracted-field-value">${value || 'N/A'}</span>
                        </div>
                    `;
                });
                
                if (doc.extraction_confidence) {
                    fieldsHtml += `<span class="confidence-badge ${confidenceClass}">OCR Confidence: ${confidenceLabel}</span>`;
                }
                fieldsHtml += '</div>';
            }

            return `
                <div class="col-md-6 mb-4">
                    <div class="document-card">
                        <div class="document-header">
                            <div>
                                <h6>${doc.document_type}</h6>
                                <small style="color: rgba(0,0,0,0.6);">Shipment #${doc.shipment_id}</small>
                            </div>
                            <span class="badge ${statusClass}">${statusLabel}</span>
                        </div>
                        <div class="document-body">
                            <div class="shipment-info">
                                <strong>Driver:</strong> ${doc.driver_name || 'N/A'}<br>
                                <strong>Uploaded:</strong> ${formatDate(doc.created_at)}<br>
                                <strong>Location:</strong> ${doc.document_type === 'BOL' ? doc.pickup_address : doc.drop_address}
                            </div>
                            ${fieldsHtml}
                            <div class="action-buttons">
                                <button class="view-btn" onclick="showDocumentModal(${doc.id}, '${doc.file_path}', '${doc.document_type}')">
                                    👁️ View Details
                                </button>
                                <a href="${doc.file_path}" download class="btn btn-secondary" style="flex:1; padding:8px; text-decoration: none; text-align: center;">
                                    ⬇️ Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Filter buttons
        $('.doc-filter-btn').on('click', function () {
            $('.doc-filter-btn').removeClass('active');
            $(this).addClass('active');
            const filter = $(this).data('filter');
            loadDocuments(filter);
        });

        // Show document modal
        window.showDocumentModal = function (docId, filePath, docType) {
            const doc = allDocuments.find(d => d.id === docId);
            if (!doc) return;

            let html = `
                <div class="mb-3">
                    <h6 class="text-muted">Shipment Details</h6>
                    <p><strong>Shipment ID:</strong> ${doc.shipment_id}</p>
                    <p><strong>Driver:</strong> ${doc.driver_name || 'N/A'}</p>
                    <p><strong>Document Type:</strong> ${docType}</p>
                    <p><strong>Status:</strong> 
                        <span class="badge status-${doc.extraction_status}">
                            ${doc.extraction_status.charAt(0).toUpperCase() + doc.extraction_status.slice(1)}
                        </span>
                    </p>
                </div>
            `;

            if (doc.extracted_fields && Object.keys(doc.extracted_fields).length > 0) {
                html += '<h6 class="text-muted">OCR Extracted Data</h6>';
                html += '<table class="table table-sm">';
                
                Object.entries(doc.extracted_fields).forEach(([key, value]) => {
                    html += `
                        <tr>
                            <td class="text-muted">${formatLabel(key)}</td>
                            <td><strong>${value || 'N/A'}</strong></td>
                        </tr>
                    `;
                });

                if (doc.extraction_confidence) {
                    const confidenceClass = getConfidenceClass(doc.extraction_confidence);
                    html += `
                        <tr>
                            <td class="text-muted">OCR Confidence</td>
                            <td>
                                <span class="confidence-badge ${confidenceClass}">
                                    ${Math.round(doc.extraction_confidence)}%
                                </span>
                            </td>
                        </tr>
                    `;
                }

                html += '</table>';
            }

            $('#documentModalTitle').text(`${docType} - Shipment #${doc.shipment_id}`);
            $('#documentModalBody').html(html);
            $('#documentDownloadBtn').attr('href', filePath);
            
            new bootstrap.Modal(document.getElementById('documentModal')).show();
        };

        // Helper functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatLabel(key) {
            return key
                .split('_')
                .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                .join(' ');
        }

        function getConfidenceClass(confidence) {
            if (confidence >= 85) return 'confidence-high';
            if (confidence >= 70) return 'confidence-medium';
            return 'confidence-low';
        }

        // Initial load
        loadDocuments('BOL');
    });
</script>
@endsection
