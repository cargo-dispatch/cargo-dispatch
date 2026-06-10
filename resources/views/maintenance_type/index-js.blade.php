@section('scripts')
<!-- Include SweetAlert library -->
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>
<script src="{{ asset('assets/js/pagination-utils.js') }}"></script>


<script>
          const paginationUtils = new PaginationUtils({
        containerSelector: '#pagination',
        maxVisiblePages: 5
    });
let debounceTimer;

$('#searchInput').on('keyup', function () {
    clearTimeout(debounceTimer);

    const searchTerm = $(this).val().trim();

    debounceTimer = setTimeout(function () {
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

function loadDrivers(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
    if (showLoader) {
        $('#userTableBody').hide();          // Hide the data tbody
        $('#loaderBody').show();             // Show the loader tbody
    }

    $('#userTableBody').empty();         // Clear previous data
    $('#pagination').empty();            // Clear pagination

    $.ajax({
        url: "{{ route('maintenance_type.get') }}",
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

            if (data.length === 0) {
                $('#userTableBody').append(`
                    <tr>
                        <td colspan="4" class="text-center">No maintenance type found</td>
                    </tr>
                `);
            } else {
                data.forEach(function(user) {
                    $('#userTableBody').append(`
                        <tr data-id="${user.id}">
                            <td>
                                <input type="checkbox" class="driver-checkbox" value="${user.id}">
                            </td>
                       
                            <td>${user.maintenance_types}</td>
                            <td>
                              <a href="javascript:void(0)" class="text-primary me-1 view-customer-details" data-customer-id="${user.id}" title="View Customer Details">
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
        error: function(error) {
            console.error('Error fetching drivers:', error);
            Swal.fire('Error', 'Failed to load driver Type data.', 'error');
        },
        complete: function() {
            $('#loaderBody').hide();      // Always hide loader
            $('#userTableBody').show();   // Show data tbody
        }
    });
}

$('#roleForm').on('submit', function (e) {
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
        formData.push({ name: '_method', value: methodInput.val() });
    }

    // Always use POST in AJAX
    const ajaxMethod = 'POST';

    // Show loading state
    submitBtn.prop('disabled', true).html('Processing...');

    $.ajax({
        url: actionUrl,
        method: ajaxMethod,
        data: formData,
        success: function (response) {
            Swal.fire('Success', 'Maintenance Type has been saved successfully!', 'success').then(() => {
                window.location.href = "{{ route('maintenance_type.index') }}";
            });
        },
       error: function (xhr) {
    submitBtn.prop('disabled', false).html(originalBtnText); // Reset button

    if (xhr.status === 422) {
        const errors = xhr.responseJSON.errors;

        // Remove previous error messages
        $('.text-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        // Loop through each error and show it properly
        $.each(errors, function (key, value) {
            const input = $('[name="' + key + '"]');
            input.addClass('is-invalid');

            // Find the closest form-group or column to append the error
            const parentDiv = input.closest('.col-md-6, .col-md-4, .col-md-12');

            if (parentDiv.length > 0) {
                parentDiv.find('.text-danger').remove(); // Clean up existing messages
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

// Delete button handler using SweetAlert
$(document).on('click', '.delete-button', function () {
  
    const userId = $(this).data('id');
    const deleteUrl = "{{ route('maintenance_type.destroy', ':id') }}".replace(':id', userId);

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
                    Swal.fire('Deleted!', 'Maintenance Type has been deleted.', 'success');

                    // 🟡 Load data again WITHOUT showing loader
                    const page = $('#pagination .active a').data('page') || 1;
                    const perPage = $('#perPageSelect').val();
                    const searchTerm = $('#searchInput').val() || ''; // if you use search

                    loadDrivers(page, perPage, searchTerm, false);
                },
                error: function (error) {
                    console.error('Error deleting maintenance type :', error);
                    Swal.fire('Error', 'Failed to delete the maintenance type.', 'error');
                }
            });
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {

    document.addEventListener('click', function (e) {
        if (e.target.closest('.view-customer-details')) {
            const button = e.target.closest('.view-customer-details');
            const customerId = button.getAttribute('data-customer-id');


            showDetailModal({
                route: `${window.APP_URL}/admin/vehicle/maintenance/type/details/${customerId}`,
                modalId: 'customerDetailModal',
                detailContainerId: 'detail-container',
                auditContainerId: 'audit-log-container',
                fields: [
                    { label: 'Maintenance Type', key: 'maintenance_types' },
                ],
                renderExtras: (data) => {
                    return '';
                }
            });
        }
    });
});

// Bulk delete button handler
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
url: "{{ route('maintenance_type.bulk-destroy') }}",
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


       PaginationUtils.initPaginationEvents(function(page) {
        const perPage = $('#perPageSelect').val();
        const searchTerm = $('#searchInput').val() || '';
        loadDrivers(page, perPage, searchTerm);
    });

    $('#perPageSelect').on('change', function() {
        const perPage = $(this).val();
        loadDrivers(1, perPage);
    });

    $(document).ready(function() {
        loadDrivers();
    });

</script>
@endsection