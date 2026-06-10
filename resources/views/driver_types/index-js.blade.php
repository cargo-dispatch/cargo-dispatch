@section('scripts')
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>
<script src="{{ asset('assets/js/pagination-utils.js') }}"></script>

<script>
    // Create pagination instance for THIS PAGE ONLY
    const paginationUtils = new PaginationUtils({
        containerSelector: '#pagination',
        maxVisiblePages: 5
    });

    let debounceTimer;
    let currentPage = 1;
    let currentPerPage = 10;
    let currentSearchTerm = '';

    // Search input handler
    $('#searchInput').on('keyup', function () {
        clearTimeout(debounceTimer);
        currentSearchTerm = $(this).val().trim();

        debounceTimer = setTimeout(function () {
            currentPage = 1; // Reset to first page on new search
            loadDrivers(currentPage, currentPerPage, currentSearchTerm); 
        }, 300); 
    });

    // Select all checkbox
    $(document).on('change', '#selectAllCheckbox', function() {
        const isChecked = $(this).prop('checked');
        $('.driver-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    // Individual checkbox
    $(document).on('change', '.driver-checkbox', function() {
        updateBulkDeleteButton();
        
        const allChecked = $('.driver-checkbox:checked').length === $('.driver-checkbox').length;
        $('#selectAllCheckbox').prop('checked', allChecked);
    });

    // Update bulk delete button state
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

    // Main function to load drivers
    function loadDrivers(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
        // Update current values
        currentPage = page;
        currentPerPage = perPage;
        currentSearchTerm = searchTerm || '';

        if (showLoader) {
            $('#userTableBody').hide();
            $('#loaderBody').show();
        }

        $('#userTableBody').empty();
        $('#pagination').empty();

        $.ajax({
            url: "{{ route('drivers.get') }}",
            method: "GET",
            data: {
                page: page,
                per_page: perPage,
                search: searchTerm
            },
            success: function(response) {
                const data = response.data;
                const pagination = response.links;
                const currentPage = response.current_page;
                const lastPage = response.last_page;
                const total = response.total;
                
                // Update per page select
                $('#perPageSelect').val(perPage);
                
                if (data.length === 0) {
                    $('#userTableBody').append(`
                        <tr>
                            <td colspan="4" class="text-center">No drivers found</td>
                        </tr>
                    `);
                } else {
                    data.forEach(function(user) {
                        $('#userTableBody').append(`
                            <tr data-id="${user.id}">
                                <td>
                                    <input type="checkbox" class="driver-checkbox" value="${user.id}">
                                </td>
                                <td>${user.name}</td>
                                <td>
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

                // Render pagination
                paginationUtils.renderPagination(pagination, currentPage, lastPage);
                updateBulkDeleteButton();
                
                // Show results count
                updateResultsCount(data.length, total, page, perPage);
            },
            error: function(error) {
                console.error('Error fetching drivers:', error);
                Swal.fire('Error', 'Failed to load driver Type data.', 'error');
            },
            complete: function() {
                $('#loaderBody').hide();
                $('#userTableBody').show();
            }
        });
    }

    // Update results count display
    function updateResultsCount(showing, total, page, perPage) {
        const start = (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        $('#resultsCount').text(`Showing ${start}-${end} of ${total} results`);
    }

    // Initialize page
    $(document).ready(function() {
        loadDrivers();
        
        // Handle pagination clicks
        $(document).on('click', '#pagination .page-link[data-page]', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            loadDrivers(page, currentPerPage, currentSearchTerm);
        });

        // Handle per page change
        $('#perPageSelect').on('change', function() {
            currentPerPage = $(this).val();
            loadDrivers(1, currentPerPage, currentSearchTerm);
        });
    });

    // Form submission handler
    $('#roleForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const actionUrl = form.attr('action');
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();

        let formData = form.serializeArray();
        const methodInput = form.find('input[name="_method"]');
        if (methodInput.length) {
            formData.push({ name: '_method', value: methodInput.val() });
        }

        submitBtn.prop('disabled', true).html('Processing...');

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: formData,
            success: function (response) {
                Swal.fire('Success', 'Driver Type has been saved successfully!', 'success').then(() => {
                    window.location.href = "{{ route('driver.index') }}";
                });
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;

                    $('.text-danger').remove();
                    $('.is-invalid').removeClass('is-invalid');

                    $.each(errors, function (key, value) {
                        const input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');

                        const parentDiv = input.closest('.col-md-6, .col-md-4, .col-md-12');

                        if (parentDiv.length > 0) {
                            parentDiv.find('.text-danger').remove();
                            parentDiv.append('<span class="text-danger">' + value[0] + '</span>');
                        }
                    });
                } else {
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                    console.error(xhr.responseText);
                }
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete-button', function () {
        const userId = $(this).data('id');
        const deleteUrl = "{{ route('driver.destroy', ':id') }}".replace(':id', userId);

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
                    success: function (response) {
                        Swal.fire('Deleted!', 'Driver Type has been deleted.', 'success');
                        loadDrivers(currentPage, currentPerPage, currentSearchTerm, false);
                    },
                    error: function (error) {
                        console.error('Error deleting driver:', error);
                        Swal.fire('Error', 'Failed to delete the driver type.', 'error');
                    }
                });
            }
        });
    });

    // View details handler
    $(document).on('click', '.view-customer-details', function (e) {
        e.preventDefault();
        const customerId = $(this).data('customer-id');

        showDetailModal({
            route: `${window.APP_URL}/admin/driver/type/details/${customerId}`,
            modalId: 'customerDetailModal',
            detailContainerId: 'detail-container',
            auditContainerId: 'audit-log-container',
            fields: [
                { label: 'Name', key: 'name' },
            ],
            renderExtras: (data) => {
                return '';
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
                    url: "{{ route('driver.bulk-destroy') }}",
                    method: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', `${selectedIds.length} record(s) have been deleted.`, 'success');
                        loadDrivers(currentPage, currentPerPage, currentSearchTerm, false);
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
</script>
@endsection