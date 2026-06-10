@section('scripts')
<!-- Include SweetAlert library -->
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>

<script>
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
        url: "{{ route('cms.get') }}",
        method: "GET",
        data: {
            page: page,
            per_page: perPage,
            search: searchTerm
        },
        success: function(response) {
            const data = response.data;
            const pagination = response.links;

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
                            <td>${user.id}</td>
                            <td>${user.image}</td>
                            <td>${user.title}</td>
                            <td>${user.type}</td>
                            <td>${user.slug}</td>
                            <td>${user.meta_tags}</td>
                          
                          
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

            renderPagination(pagination);
            updateBulkDeleteButton();
        },
        error: function(error) {
            console.error('Error fetching drivers:', error);
            Swal.fire('Error', 'Failed to load driver data.', 'error');
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
            Swal.fire('Success', 'Cms Page has been saved successfully!', 'success').then(() => {
                window.location.href = "{{ route('cms.index') }}";
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
    const deleteUrl = "{{ route('cms.destroy', ':id') }}".replace(':id', userId);

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
                    Swal.fire('Deleted!', 'Cms has been deleted.', 'success');

                    // 🟡 Load data again WITHOUT showing loader
                    const page = $('#pagination .active a').data('page') || 1;
                    const perPage = $('#perPageSelect').val();
                    const searchTerm = $('#searchInput').val() || ''; // if you use search

                    loadDrivers(page, perPage, searchTerm, false);
                },
                error: function (error) {
                    console.error('Error cms driver:', error);
                    Swal.fire('Error', 'Failed to cms the driver.', 'error');
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
                route: `${window.APP_URL}/cms/type/details/${customerId}`,
                modalId: 'customerDetailModal',             
                detailContainerId: 'detail-container',       
                auditContainerId: 'audit-log-container',     
               fields: [
    { label: 'Title', key: 'title' },
    { label: 'Type', key: 'type' },
    { label: 'Meta Tags', key: 'meta_tags' },
    { label: 'Meta Keywords', key: 'meta_keywords' },
    { label: 'Content', key: 'content' },
   
   // Add this line
],
                renderExtras: (data) => {
                    // Optional, return any extra HTML you want shown
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
            text: 'Please select at least one user to delete.',
            confirmButtonColor: '#3085d6',
        });
        return false;
    }

   const selectedIds = getSelectedDriverIds();
       const message = selectedIds.length === 1 
        ? '1 page  deleted.' 
        : `${selectedIds.length} pages deleted.`;
    Swal.fire({
        title: 'Are you sure?',
        text:message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('cms.bulk-destroy') }}",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids: selectedIds
                },
                success: function(response) {
              

    Swal.fire('Deleted!', message, 'success');

                    
                  
                    const page = $('#pagination .active a').data('page') || 1;
                    const perPage = $('#perPageSelect').val();
                    const searchTerm = $('#searchInput').val() || '';
                    loadDrivers(page, perPage, searchTerm, false);
                },
                error: function(error) {
                    console.error('Error during bulk delete:', error);
                    Swal.fire('Error', error.responseJSON.message, 'error');
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

function renderPagination(links) {
    let paginationHtml = '';
    const currentPage = links.find(link => link.active).label;
    const lastPage = links[links.length - 2].label; // Assuming second last link is last page number
    const current = parseInt(currentPage);
    const total = parseInt(lastPage);
    const maxVisible = 3;

    // Prev Button
    if (current > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current - 1}">Prev</a>
            </li>
        `;
    }

    // First Page
    paginationHtml += `
        <li class="page-item ${current === 1 ? 'active' : ''}">
            <a class="page-link" href="#" data-page="1">1</a>
        </li>
    `;

    // Dots before current range
    if (current > maxVisible) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    // Middle pages
    let start = Math.max(2, current - 1);
    let end = Math.min(total - 1, current + 1);

    for (let i = start; i <= end; i++) {
        paginationHtml += `
            <li class="page-item ${i === current ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Dots after current range
    if (current < total - maxVisible + 1) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    // Last Page
    if (total > 1) {
        paginationHtml += `
            <li class="page-item ${current === total ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${total}">${total}</a>
            </li>
        `;
    }

    // Next Button
    if (current < total) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current + 1}">Next</a>
            </li>
        `;
    }

    $('#pagination').html(`
        <div class="pagination-container d-flex justify-content-end">
            <ul class="pagination">${paginationHtml}</ul>
        </div>
    `);

    addPaginationStyles();
}

function getPageNumber(url) {
    const urlParams = new URLSearchParams(url.split('?')[1]);
    return urlParams.get('page');
}

$(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    const perPage = $('#perPageSelect').val();
    loadDrivers(page, perPage);
});

$('#perPageSelect').on('change', function () {
    const perPage = $(this).val();
    loadDrivers(1, perPage);
});

$(document).ready(function () {
    loadDrivers();
});

function addPaginationStyles() {
    $('.pagination li').addClass('page-item');
    $('.pagination a').addClass('page-link');
    $('.pagination .active a').css('font-weight', 'bold');
    const styles = `
        <style>
           .pagination .page-link {
        color: #007bff;
        border-radius: 4px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        cursor: default;
        background-color: #f8f9fa;
    }
        </style>
    `;
    $('head').append(styles);
}
</script>
@endsection