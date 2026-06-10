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

    $('#searchInput').on('keyup', function() {
        clearTimeout(debounceTimer);

        const searchTerm = $(this).val().trim();

        debounceTimer = setTimeout(function() {
            loadDrivers(1, 10, searchTerm);
        }, 300);
    });

    $(document).on('change', '#selectAllCheckbox', function() {
        const isChecked = $(this).prop('checked');
        $('.driver-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '.driver-checkbox', function() {
        updateBulkDeleteButton();

        const allChecked = $('.driver-checkbox:checked').length === $('.driver-checkbox').length;

        $('#selectAllCheckbox').prop('checked', allChecked);
    });

    function updateBulkDeleteButton() {
        const selectedCount = $('.driver-checkbox:checked').length;
        const $bulkDeleteBtn = $('#bulkDeleteBtn');

        if (selectedCount > 0) {
            $bulkDeleteBtn.removeClass('disabled');
            $bulkDeleteBtn.html(`<i class="bi bi-trash me-2"></i>Delete Selected (${selectedCount})`);
        } else {
            $bulkDeleteBtn.addClass('disabled');
            $bulkDeleteBtn.html(`<i class="bi bi-trash me-2"></i>Delete Selected`);
        }
    }

    let currentStatus = '';

    // Status filter tabs
    $('#statusFilters').on('click', '.pg-tab', function() {
        $('#statusFilters .pg-tab').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        loadDrivers(1, parseInt($('#perPageSelect').val()) || 10, $('#searchInput').val().trim());
    });

    function loadDrivers(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
        if (showLoader) {
            $('#userTableBody').hide();
            $('#loaderBody').show();
        }

        $('#userTableBody').empty();
        $('#pagination').empty();

        $.ajax({
            url: "{{ route('maintenance.get') }}",
            method: "GET",
            data: {
                page: page,
                per_page: perPage,
                search: searchTerm,
                status: currentStatus
            },
            success: function(response) {
                const data = response.data;
                const pagination = response.links;

                // ✅ Date formatter (NO timezone conversion)
                function formatDateNoConversion(dateStr) {
                    if (!dateStr) return 'N/A';
                    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
                                    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                    const [year, month, day] = dateStr.split('-');
                    return `${months[parseInt(month, 10) - 1]} ${parseInt(day, 10)}, ${year}`;
                }

                if (data.length === 0) {
                    $('#userTableBody').append(`
                        <tr>
                            <td colspan="10" class="text-center">No maintenance records found</td>
                        </tr>
                    `);
                } else {
                    data.forEach(function(record) {
                        const vehicleId = record.vehicle?.vehicle_id || 'N/A';
                        const maintenanceType = record.maintenance_type?.maintenance_types || 'N/A';
                        const driverName = record.driver 
                            ? `${record.driver.firstname} ${record.driver.lastname}`.trim() 
                            : 'N/A';

                        // ✅ Format dates (without timezone conversion)
                        const maintenanceDate = formatDateNoConversion(record.maintenance_date);
                        const nextMaintenanceDate = formatDateNoConversion(record.next_maintenance_date);

                        const cost = record.cost ? `$${parseFloat(record.cost).toFixed(2)}` : '$0.00';
                        const status = record.status || 'N/A';
                        const description = record.description 
                            ? (record.description.length > 50 
                                ? record.description.substring(0, 50) + '...' 
                                : record.description)
                            : 'N/A';

                        // Badge using pg-badge system
                        const badgeMap = { completed: 'pg-badge-active', cancelled: 'pg-badge-cancelled', scheduled: 'pg-badge-pending' };
                        const badgeClass = badgeMap[status] || 'pg-badge-inactive';
                        const dotMap    = { completed: '#22c55e', cancelled: '#ef4444', scheduled: 'var(--hover-color)' };
                        const dotColor  = dotMap[status] || '#9ca3af';

                        $('#userTableBody').append(`
                            <tr data-id="${record.id}">
                                <td><input type="checkbox" class="driver-checkbox" value="${record.id}"></td>
                                <td>${maintenanceType}</td>
                                <td title="${record.description || 'N/A'}">${description}</td>
                                <td>${vehicleId}</td>
                                <td>${driverName}</td>
                                <td>${maintenanceDate}</td>
                                <td>${cost}</td>
                                <td>${nextMaintenanceDate}</td>
                                <td>
                                    <span class="pg-badge ${badgeClass}">
                                        <span class="pg-badge-dot" style="background:${dotColor}"></span>
                                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                                    </span>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" class="text-primary me-1 view-customer-details" 
                                       data-customer-id="${record.id}" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="${record.actions.edit}" class="text-primary me-2" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-link text-danger p-0 delete-button" 
                                            title="Delete" data-id="${record.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }

                const lastPage = pagination.length > 1 ? parseInt(pagination[pagination.length - 2].label) : 1;
                paginationUtils.renderPagination(pagination, page, lastPage);
                updateBulkDeleteButton();
            },
            error: function(error) {
                console.error('Error fetching maintenance records:', error);
                Swal.fire('Error', 'Failed to load maintenance data.', 'error');
            },
            complete: function() {
                $('#loaderBody').hide();
                $('#userTableBody').show();
            }
        });
    }

    $('#roleForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const form = $(this);
        const actionUrl = form.attr('action');
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();

        // Serialize form as an array
        let formData = form.serializeArray();

        // Check if there's a _method field (for PUT)
        const methodInput = form.find('input[name="_method"]');
        if (methodInput.length) {
            formData.push({
                name: '_method',
                value: methodInput.val()
            });
        }

        // Always use POST in AJAX
        const ajaxMethod = 'POST';

        // Show loading state
        submitBtn.prop('disabled', true).html('Processing...');

        $.ajax({
            url: actionUrl,
            method: ajaxMethod,
            data: formData,
            success: function(response) {
                Swal.fire('Success', 'Maintenance has been saved successfully!', 'success').then(() => {
                    window.location.href = "{{ route('maintenance.index') }}";
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText); // Reset button

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;

                    // Remove previous error messages and invalid classes
                    $('.text-danger').remove();
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback.d-block').remove(); // Remove server-side errors

                    // Loop through each error and show it properly
                    $.each(errors, function(key, value) {
                        const input = $(`[name="${key}"]`);
                        const textarea = $(`textarea[name="${key}"]`);
                        const select = $(`select[name="${key}"]`);
                        
                        const targetElement = input.length ? input : textarea.length ? textarea : select;
                        
                        if (targetElement.length) {
                            targetElement.addClass('is-invalid');
                            
                            // Find the appropriate container for error message
                            let parentContainer = targetElement.closest('.col-md-6, .col-md-4, .col-12, .col-md-12');
                            
                            if (!parentContainer.length) {
                                parentContainer = targetElement.closest('.mb-3');
                            }
                            
                            if (parentContainer.length) {
                                // Remove any existing error messages for this field
                                parentContainer.find('.text-danger').remove();
                                
                                // Add new error message
                                parentContainer.append(`<div class="text-danger small mt-1">${value[0]}</div>`);
                            }
                        }
                    });
                } else {
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                    console.error(xhr.responseText);
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const maintenanceDateInput = document.getElementById('maintenance_date');
        const statusSelect = document.getElementById('status_select');

        maintenanceDateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();

            // Compare date only (ignore time)
            selectedDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                // Past date
                statusSelect.value = 'completed';
            } else if (selectedDate > today) {
                // Future date
                statusSelect.value = 'scheduled';
            } else {
                // Today = treat as completed
                statusSelect.value = 'completed';
            }
        });
    });

    // Delete button handler using SweetAlert
    $(document).on('click', '.delete-button', function() {
        const userId = $(this).data('id');
        const deleteUrl = "{{ route('maintenance.destroy', ':id') }}".replace(':id', userId);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    method: "POST",
                    data: {
                        _method: "DELETE",
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', 'Maintenance Type has been deleted.', 'success');

                        // 🟡 Load data again WITHOUT showing loader
                        const page = $('#pagination .active a').data('page') || 1;
                        const perPage = $('#perPageSelect').val();
                        const searchTerm = $('#searchInput').val() || ''; // if you use search

                        loadDrivers(page, perPage, searchTerm, false);
                    },
                    error: function(error) {
                        console.error('Error deleting maintenance type :', error);
                        Swal.fire('Error', 'Failed to delete the maintenance type.', 'error');
                    }
                });
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-customer-details')) {
                const button = e.target.closest('.view-customer-details');
                const customerId = button.getAttribute('data-customer-id');

                showDetailModal({
                    route: `${window.APP_URL}/admin/vehicle/maintenance/details/${customerId}`,
                    modalId: 'customerDetailModal',
                    detailContainerId: 'detail-container',
                    auditContainerId: 'audit-log-container',
                    fields: [
                        {
                            label: 'Vehicle',
                            key: 'Vehicle',
                        },
                        {
                            label: 'Driver',
                            key: 'Driver',
                        },
                        {
                            label: 'Maintenance Type',
                            key: 'Maintenance Type',
                        },
                        {
                            label: 'Maintenance Date',
                            key: 'Maintenance Date',
                        },
                        {
                            label: 'Cost',
                            key: 'Cost',
                        },
                        {
                            label: 'Status',
                            key: 'Status',
                        },
                        {
                            label: 'Next Maintenance Miles',
                            key: 'Next Maintenance Miles',
                        },
                        {
                            label: 'Description',
                            key: 'Description',
                        }
                    ],
                    renderExtras: (data) => {
                        return '';
                    }
                });
            }
        });
    });

    // Bulk delete button handler
    $(document).on('click', '#bulkDeleteBtn', function(e) {
        if ($(this).hasClass('disabled')) {
            e.preventDefault();
            e.stopPropagation();

            Swal.fire({
                icon: 'error',
                title: 'No Selection',
                text: 'Please select at least one record to delete.',
                confirmButtonColor: '#3085d6',
            });
            return false;
        }

        const selectedIds = getSelectedDriverIds();

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} record(s). This cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('maintenance.bulk-destroy') }}",
                    method: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', `${selectedIds.length} record(s) have been deleted.`, 'success');

                        const page = $('#pagination .active a').data('page') || 1;
                        const perPage = $('#perPageSelect').val();
                        const searchTerm = $('#searchInput').val() || '';
                        loadDrivers(page, perPage, searchTerm, false);
                    },
                    error: function(error) {
                        console.error('Error during bulk delete:', error);
                        Swal.fire('Error', 'Failed to delete selected record.', 'error');
                    }
                });
            }
        });
    });

    // Get selected driver IDs
    function getSelectedDriverIds() {
        const selectedIds = [];
        $('.driver-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const perPage = $('#perPageSelect').val();
        const searchTerm = $('#searchInput').val() || '';
        loadDrivers(page, perPage, searchTerm);
    });

    $('#perPageSelect').on('change', function() {
        const perPage = $(this).val();
        const searchTerm = $('#searchInput').val() || '';
        loadDrivers(1, perPage, searchTerm);
    });

    $(document).ready(function() {
        loadDrivers();
    });

</script>
@endsection