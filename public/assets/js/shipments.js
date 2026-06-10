/* Shipments index page logic */

const paginationUtils = new PaginationUtils({
    containerSelector: '#pagination',
    maxVisiblePages: 5
});

let debounceTimer;
let shipmentLoadInProgress = false;
let activeStatus = $('table.pg-table').data('filter-status') || '';

if (activeStatus) {
    $('#statusFilters .pg-tab').removeClass('active');
    $(`#statusFilters .pg-tab[data-status="${activeStatus}"]`).addClass('active');
}

$(document).on('click', '#statusFilters .pg-tab', function () {
    $('#statusFilters .pg-tab').removeClass('active');
    $(this).addClass('active');
    activeStatus = $(this).data('status');
    loadShipments(1, parseInt($('#perPageSelect').val()) || 10, $('#searchInput').val().trim());
});

const fpFrom = flatpickr('#dateFrom', {
    dateFormat: 'Y-m-d',
    allowInput: false,
    appendTo: document.body,
    onChange: function () {
        loadShipments(1, parseInt($('#perPageSelect').val()) || 10, $('#searchInput').val().trim());
    }
});

const fpTo = flatpickr('#dateTo', {
    dateFormat: 'Y-m-d',
    allowInput: false,
    appendTo: document.body,
    onChange: function () {
        loadShipments(1, parseInt($('#perPageSelect').val()) || 10, $('#searchInput').val().trim());
    }
});

$('#clearFilters').on('click', function () {
    activeStatus = '';
    fpFrom.clear();
    fpTo.clear();
    $('#statusFilters .pg-tab').removeClass('active');
    $('#statusFilters .pg-tab[data-status=""]').addClass('active');
    $('#searchInput').val('');
    loadShipments(1, parseInt($('#perPageSelect').val()) || 10, '');
});

$('#searchInput').on('keyup', function () {
    clearTimeout(debounceTimer);
    const searchTerm = $(this).val().trim();
    debounceTimer = setTimeout(function () {
        loadShipments(1, 10, searchTerm);
    }, 300);
});

$(document).on('change', '#selectAllCheckbox', function () {
    const isChecked = $(this).prop('checked');
    $('.driver-checkbox').prop('checked', isChecked);
    updateBulkDeleteButton();
});

$(document).on('change', '.driver-checkbox', function () {
    updateBulkDeleteButton();
    const allChecked = $('.driver-checkbox:checked').length === $('.driver-checkbox').length;
    $('#selectAllCheckbox').prop('checked', allChecked);
});

function updateBulkDeleteButton() {
    const selectedCount = $('.driver-checkbox:checked').length;
    const $btn = $('#bulkDeleteBtn');
    if (selectedCount > 0) {
        $btn.removeClass('disabled').html(`<i class="bi bi-trash me-2"></i>Delete Selected (${selectedCount})`);
    } else {
        $btn.addClass('disabled').html(`<i class="bi bi-trash me-2"></i>Delete Selected`);
    }
}

function loadShipments(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
    if (shipmentLoadInProgress) return;
    shipmentLoadInProgress = true;

    if (showLoader) {
        $('#userTableBody').hide();
        $('#loaderBody').show();
    }

    $('#userTableBody').empty();
    $('#pagination').empty();

    const loadRoute = $('table.pg-table').data('load-route') || window.SHIPMENT_CONFIG.loadRoute;

    $.ajax({
        url: loadRoute,
        method: 'GET',
        data: {
            page: page,
            per_page: perPage,
            search: searchTerm,
            status: activeStatus,
            date_from: $('#dateFrom').val(),
            date_to: $('#dateTo').val(),
        },
        success: function (response) {
            const data = response.data;
            const pagination = response.links;
            const currentPage = response.current_page;
            const lastPage = response.last_page;

            if (data.length === 0) {
                $('#userTableBody').append(`
                    <tr><td colspan="11" class="text-center pg-empty-cell">No records found</td></tr>
                `);
            } else {
                data.forEach(function (user) {
                    let status = user.status ?? 'N/A';
                    let badgeClass = '';
                    switch (status) {
                        case 'pending':    badgeClass = 'badge bg-warning text-dark'; break;
                        case 'active':
                        case 'assigned':   badgeClass = 'badge bg-info text-dark'; break;
                        case 'picked_up':  badgeClass = 'badge bg-warning text-dark'; break;
                        case 'in_transit': badgeClass = 'badge bg-primary'; break;
                        case 'delivered':
                        case 'complete':   badgeClass = 'badge bg-success'; break;
                        case 'cancelled':  badgeClass = 'badge bg-danger'; break;
                        default:           badgeClass = 'badge bg-secondary';
                    }

                    const statusHTML = `<span class="${badgeClass} badge-prominent">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;

                    $('#userTableBody').append(`
                        <tr data-id="${user.id}">
                            <td><input type="checkbox" class="driver-checkbox" value="${user.id}"></td>
                            <td>${user.customer ? (((user.customer.first_name || '') + ' ' + (user.customer.last_name || '')).trim() || user.customer.customer_title || 'N/A') : 'N/A'}</td>
                            <td>${user.vehicle_type?.vehicle_type ?? 'N/A'}</td>
                            <td>${user.driver_name ? `<span class="badge bg-info text-dark">${user.driver_name}</span>` : '<span class="text-muted">—</span>'}</td>
                            <td>${user.pickup_address ?? 'N/A'}</td>
                            <td>${user.drop_address ?? 'N/A'}</td>
                            <td>${formatDateTime(user.pickup_time)}</td>
                            <td>${formatDateTime(user.delivery_time)}</td>
                            <td>${statusHTML}</td>
                            <td>${user.estimated_cost ?? 'N/A'}</td>
                            <td>
                                <a href="javascript:void(0)" class="text-primary me-2 open-comments" data-shipment-id="${user.id}" title="Remarks">
                                    <i class="fa-regular fa-comment-dots"></i>
                                </a>
                                <a href="javascript:void(0)" class="text-primary me-1 view-customer-details" data-customer-id="${user.id}" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="${user.actions.edit}" class="text-primary me-2" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" class="btn btn-link text-danger p-0 delete-button" title="Delete" data-id="${user.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }

            paginationUtils.renderPagination(pagination, currentPage, lastPage);
            updateBulkDeleteButton();
        },
        error: function (error) {
            console.error('Error fetching shipment:', error);
            Swal.fire('Error', error.responseJSON?.message || 'Server error', 'error');
        },
        complete: function () {
            shipmentLoadInProgress = false;
            $('#loaderBody').hide();
            $('#userTableBody').show();
        }
    });
}

// ── Comments modal ────────────────────────────────────────────────────────────

let currentShipmentId = null;

$(document).on('click', '.open-comments', function () {
    currentShipmentId = $(this).data('shipment-id');
    $('#newComment').val('');
    $('#commentsTable tbody').html('<tr><td colspan="4" class="text-center pg-empty-cell">Loading...</td></tr>');

    $.ajax({
        url: `${window.APP_URL}/admin/shipments/${currentShipmentId}/comments`,
        type: 'GET',
        success: function (response) {
            if (response.comments.length > 0) {
                let rows = '';
                response.comments.forEach(comment => {
                    rows += `<tr>
                        <td>${comment.text}</td>
                        <td>${comment.author_name ?? comment.author_id}</td>
                        <td><span class="badge bg-secondary">${comment.author_type}</span></td>
                        <td>${comment.created_at}</td>
                    </tr>`;
                });
                $('#commentsTable tbody').html(rows);
            } else {
                $('#commentsTable tbody').html(`<tr><td colspan="4" class="text-center pg-empty-cell">No comments yet.</td></tr>`);
            }
            new bootstrap.Modal(document.getElementById('commentsModal')).show();
        },
        error: function () {
            $('#commentsTable tbody').html(`<tr><td colspan="4" class="text-danger text-center">Failed to load comments.</td></tr>`);
        }
    });
});

$('#saveComment').on('click', function () {
    const text = $('#newComment').val().trim();
    if (!text) return alert('Please enter a comment.');

    $.ajax({
        url: `${window.APP_URL}/admin/shipments/${currentShipmentId}/comments`,
        type: 'POST',
        data: { text },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function () {
            $('#newComment').val('');
            $(`.open-comments[data-shipment-id="${currentShipmentId}"]`).click();
        },
        error: function () { alert('Failed to save comment.'); }
    });
});

function forceCloseCommentsModal() {
    const modal = document.getElementById('commentsModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.querySelector('.modal-backdrop')?.remove();
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// ── Delete ────────────────────────────────────────────────────────────────────

$(document).on('click', '.delete-button', function () {
    const userId = $(this).data('id');
    const deleteUrl = window.SHIPMENT_CONFIG.destroyRoute.replace(':id', userId);

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: deleteUrl,
                method: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    Swal.fire('Deleted!', 'Shipment has been deleted.', 'success');
                    const page = $('#pagination .active a').data('page') || 1;
                    loadShipments(page, $('#perPageSelect').val(), $('#searchInput').val() || '', false);
                },
                error: function (error) { Swal.fire('Error', error.responseJSON?.message || 'Server error', 'error'); }
            });
        }
    });
});

$(document).on('click', '#bulkDeleteBtn', function (e) {
    if ($(this).hasClass('disabled')) {
        e.preventDefault(); e.stopPropagation();
        Swal.fire({ icon: 'error', title: 'No Selection', text: 'Please select at least one shipment to delete.', confirmButtonColor: '#3085d6' });
        return false;
    }

    const selectedIds = $('.driver-checkbox:checked').map(function () { return $(this).val(); }).get();

    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${selectedIds.length} shipment(s). This cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: window.SHIPMENT_CONFIG.bulkDestroyRoute,
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), ids: selectedIds },
                success: function () {
                    Swal.fire('Deleted!', `${selectedIds.length} shipment(s) have been deleted.`, 'success');
                    const page = $('#pagination .active a').data('page') || 1;
                    loadShipments(page, $('#perPageSelect').val(), $('#searchInput').val() || '', false);
                },
                error: function () { Swal.fire('Error', 'Failed to delete selected shipments.', 'error'); }
            });
        }
    });
});

// ── Detail modal ──────────────────────────────────────────────────────────────

document.addEventListener('click', function (e) {
    if (!e.target.closest('.view-customer-details')) return;
    const shipmentId = e.target.closest('.view-customer-details').getAttribute('data-customer-id');

    showDetailModal({
        route: `${window.APP_URL}/admin/shipment/details/${shipmentId}`,
        modalId: 'customerDetailModal',
        detailContainerId: 'detail-container',
        auditContainerId: 'audit-log-container',
        fields: [
            { label: 'Customer',             key: 'customer.customer_title' },
            { label: 'Vehicle Type',         key: 'vehicle_type.vehicle_type' },
            { label: 'Pickup Address',       key: 'pickup_address' },
            { label: 'Drop Address',         key: 'drop_address' },
            { label: 'Pickup Time',          key: 'pickup_time' },
            { label: 'Delivery Time',        key: 'delivery_time' },
            { label: 'Estimated Cost',       key: 'estimated_cost' },
            { label: 'Weight (kg)',          key: 'weight' },
            { label: 'Volume (m³)',          key: 'volume' },
            { label: 'Pallets',              key: 'pallets' },
            { label: 'Special Instructions', key: 'special_instructions' },
            { label: 'Status',               key: 'status' },
        ],
        renderExtras: function (data) {
            let distanceHtml = data.distance_miles
                ? `<div class="row mb-3"><div class="col-12"><strong>Distance:</strong> <span class="text-primary">${data.distance_miles} miles</span></div></div>`
                : `<div class="row mb-3"><div class="col-12"><div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Distance data not available.</div></div></div>`;

            let documentsHtml = '';
            if (data.documents?.length) {
                documentsHtml = `<div class="row mt-4 mb-3"><div class="col-12">
                    <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-pdf me-2"></i>Documents</h6>
                    ${data.documents.map(doc => `
                        <div class="card mb-2 shipment-doc-card">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <strong>${doc.type}</strong><br>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>Driver: ${doc.driver_name}<br>
                                            <i class="bi bi-calendar me-1"></i>${doc.uploaded_at}
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                    </div>
                                </div>
                                ${doc.extracted_fields ? `
                                    <div class="mt-2 pt-2 border-top">
                                        <small><strong>Extracted Fields:</strong></small>
                                        <div class="shipment-doc-fields">
                                            ${Object.entries(doc.extracted_fields).map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`).join('')}
                                        </div>
                                    </div>` : ''}
                                <div class="mt-2">
                                    <a href="${doc.file_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>`).join('')}
                </div></div>`;
            }

            return distanceHtml + documentsHtml;
        }
    });
});

// ── Pagination & init ─────────────────────────────────────────────────────────

PaginationUtils.initPaginationEvents(function (page) {
    loadShipments(page, $('#perPageSelect').val(), $('#searchInput').val() || '');
});

$('#perPageSelect').on('change', function () {
    loadShipments(1, $(this).val());
});

$(document).ready(function () {
    loadShipments();
});

window.addEventListener('refreshShipmentData', function () {
    const page = $('#pagination .active a').data('page') || 1;
    loadShipments(page, $('#perPageSelect').val() || 10, $('#searchInput').val() || '', false);
});
