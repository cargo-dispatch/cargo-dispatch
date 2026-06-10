@section('scripts')
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>
<script src="{{ asset('assets/js/pagination-utils.js') }}"></script>

<script>
const paginationUtils = new PaginationUtils({
    containerSelector: '#pagination',
    maxVisiblePages: 5
});

let debounceTimer;
let activeFilter = 'all';

// ── Search ──
$('#searchInput').on('keyup', function () {
    clearTimeout(debounceTimer);
    const searchTerm = $(this).val().trim();
    debounceTimer = setTimeout(() => loadVehicles(1, $('#perPageSelect').val(), searchTerm), 300);
});

// ── Per-page ──
$('#perPageSelect').on('change', function () {
    loadVehicles(1, $(this).val());
});

// ── Filter tabs ──
$(document).on('click', '.pg-tab', function () {
    $('.pg-tab').removeClass('active');
    $(this).addClass('active');
    activeFilter = $(this).data('filter');
    loadVehicles(1, $('#perPageSelect').val(), $('#searchInput').val().trim());
});

// ── Select all ──
$(document).on('change', '#selectAllCheckbox', function () {
    $('.driver-checkbox').prop('checked', $(this).prop('checked'));
    updateBulkDeleteButton();
});

$(document).on('change', '.driver-checkbox', function () {
    updateBulkDeleteButton();
    const allChecked = $('.driver-checkbox:checked').length === $('.driver-checkbox').length;
    $('#selectAllCheckbox').prop('checked', allChecked);
});

function updateBulkDeleteButton() {
    const count = $('.driver-checkbox:checked').length;
    const $btn  = $('#bulkDeleteBtn');
    if (count > 0) {
        $btn.removeClass('disabled').html(`<i class="bi bi-trash"></i> Delete Selected (${count})`);
    } else {
        $btn.addClass('disabled').html(`<i class="bi bi-trash"></i> Delete Selected`);
    }
}

function getSelectedDriverIds() {
    return $('.driver-checkbox:checked').map(function () { return $(this).val(); }).get();
}

// ── Status badge helper ──
function statusBadge(status) {
    if (!status) return '<span class="pg-badge pg-badge-inactive"><span class="pg-badge-dot"></span>N/A</span>';
    const map = {
        active:      ['pg-badge-active',      'Active'],
        inactive:    ['pg-badge-inactive',     'Inactive'],
        maintenance: ['pg-badge-maintenance',  'Maintenance'],
        attention:   ['pg-badge-attention',    'Attention'],
    };
    const [cls, label] = map[status.toLowerCase()] ?? ['pg-badge-inactive', status];
    return `<span class="pg-badge ${cls}"><span class="pg-badge-dot"></span>${label}</span>`;
}

// ── Load vehicles ──
function loadVehicles(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
    if (showLoader) {
        $('#userTableBody').hide();
        $('#loaderBody').show();
    }
    $('#userTableBody').empty();
    $('#pagination').empty();

    $.ajax({
        url: "{{ route('vehicles.get') }}",
        method: 'GET',
        data: { page, per_page: perPage, search: searchTerm, filter: activeFilter },
        success: function (response) {
            const data       = response.data;
            const pagination = response.links;
            const currentPage = response.current_page;
            const lastPage    = response.last_page;

            if (data.length === 0) {
                $('#userTableBody').append(`
                    <tr><td colspan="9" class="text-center pg-empty-cell">No vehicles found</td></tr>
                `);
            } else {
                data.forEach(function (v) {
                    const thumb = v.image_url
                        ? `<img src="${v.image_url}" class="pg-vehicle-thumb" alt="">`
                        : `<i class="bi bi-truck" style="font-size:22px;color:var(--text-muted);"></i>`;

                    $('#userTableBody').append(`
                        <tr data-id="${v.id}">
                            <td><input type="checkbox" class="driver-checkbox" value="${v.id}" style="accent-color:var(--hover-color);"></td>
                            <td><span class="pg-vehicle-id">${v.vehicle_id ?? '—'}</span></td>
                            <td>${thumb}</td>
                            <td>
                                <div class="pg-make-model">${v.make_model ?? '—'}</div>
                            </td>
                            <td>${v.year_of_manufacture ?? '—'}</td>
                            <td>${v.ownership_status ?? '—'}</td>
                            <td>${v.registration_expiry_date ?? '—'}</td>
                            <td>${v.insurance_expiry_date ?? '—'}</td>
                            <td style="display:flex;align-items:center;gap:6px;">
                                <a href="javascript:void(0)" class="pg-btn-view view-customer-details" data-customer-id="${v.id}">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="${v.actions.edit}" class="pg-btn-icon" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" class="pg-btn-icon danger delete-button" title="Delete" data-id="${v.id}">
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
        error: function () {
            Swal.fire('Error', 'Failed to load vehicle data.', 'error');
        },
        complete: function () {
            $('#loaderBody').hide();
            $('#userTableBody').show();
        }
    });
}

// ── Detail modal ──
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.view-customer-details');
        if (!btn) return;
        const id = btn.getAttribute('data-customer-id');
        showDetailModal({
            route: `${window.APP_URL}/admin/vehicles/vehicles/details/${id}`,
            modalId: 'customerDetailModal',
            detailContainerId: 'detail-container',
            auditContainerId:  'audit-log-container',
            fields: [
                { label: 'Vehicle ID',                   key: 'vehicle_id' },
                { label: 'Vehicle Identification Number', key: 'vin' },
                { label: 'Make/Model',                   key: 'make_model' },
                { label: 'Manufacturing Year',           key: 'year_of_manufacture' },
                { label: 'Ownership Status',             key: 'ownership' },
                { label: 'Cargo Weight',                 key: 'weight' },
                { label: 'Load',                         key: 'load' },
                { label: 'Registration Expiry Date',     key: 'expiry_date' },
                { label: 'Insurance Detail',             key: 'insurance_detail' },
                { label: 'Insurance Expiry Date',        key: 'insurance_expiry' },
            ],
            renderExtras: () => ''
        });
    });
});

// ── Delete single ──
$(document).on('click', '.delete-button', function () {
    const id        = $(this).data('id');
    const deleteUrl = "{{ route('vehicles.destroy', ':id') }}".replace(':id', id);

    Swal.fire({
        title: 'Are you sure?', text: "You won't be able to revert this!",
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: deleteUrl, method: 'POST',
            data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                Swal.fire('Deleted!', 'Vehicle has been deleted.', 'success');
                loadVehicles($('#pagination .active a').data('page') || 1, $('#perPageSelect').val(), $('#searchInput').val(), false);
            },
            error: function () { Swal.fire('Error', 'Failed to delete the vehicle.', 'error'); }
        });
    });
});

// ── Bulk delete ──
$(document).on('click', '#bulkDeleteBtn', function (e) {
    if ($(this).hasClass('disabled')) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'No Selection', text: 'Please select at least one vehicle to delete.' });
        return;
    }
    const ids = getSelectedDriverIds();
    Swal.fire({
        title: 'Are you sure?', text: `You are about to delete ${ids.length} vehicle(s). This cannot be undone!`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ route('vehicles.bulk-destroy') }}", method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), ids },
            success: function () {
                Swal.fire('Deleted!', `${ids.length} vehicle(s) have been deleted.`, 'success');
                loadVehicles(1, $('#perPageSelect').val(), $('#searchInput').val(), false);
            },
            error: function (xhr) { Swal.fire('Error', xhr.responseJSON?.message ?? 'Bulk delete failed.', 'error'); }
        });
    });
});

// ── Pagination ──
PaginationUtils.initPaginationEvents(function (page) {
    loadVehicles(page, $('#perPageSelect').val(), $('#searchInput').val());
});

// ── Init ──
$(document).ready(function () { loadVehicles(); });
</script>
@endsection
