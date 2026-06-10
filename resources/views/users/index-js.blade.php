<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>
<script src="{{ asset('assets/js/pagination-utils.js') }}"></script>

<script>
    // Initialize pagination utility
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

    function loadDrivers(page = 1, perPage = 10, searchTerm = '', showLoader = true) {
        if (showLoader) {
            $('#userTableBody').hide();
            $('#loaderBody').show();
        }

        $('#userTableBody').empty();
        $('#pagination').empty();

        $.ajax({
            url: "{{ route('users.get') }}",
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
                        <td colspan="7" class="text-center">No Customer is found</td>
                    </tr>
                `);
                } else {
                    data.forEach(function(user) {
                        // Handle profile image with fallback
                        let profileImageHtml = '';
                        if (user.profile_image) {
                            // First try local storage path
                            const localPath = `{{ asset('storage/') }}/${user.profile_image}`;
                            const externalURL = user.profile_image.startsWith('http') ? user.profile_image : ''; // only use if it's a full URL

                            profileImageHtml = `
        <img
            src="${localPath}"
            alt="Profile"
            class="profile-image-rounded"
            onerror="this.onerror=null; this.src='${externalURL || '{{ asset('assets/images/default-avatar.png') }}'}';"
        >`;
                        } else {
                            // Default avatar or initials
                            const initials = `${user.first_name?.charAt(0) || ''}${user.last_name?.charAt(0) || ''}`.toUpperCase();
                            profileImageHtml = `<div class="profile-image-placeholder">${initials}</div>`;
                        }

                        $('#userTableBody').append(`
                        <tr data-id="${user.id}">
                            <td>
                                <input type="checkbox" class="driver-checkbox" value="${user.id}">
                            </td>
                        
                            <td class="profile-image-cell">
                                ${profileImageHtml}
                            </td>
                            <td>${user.first_name} ${user.last_name}</td>
                            <td>${user.email}</td>
                            <td>${user.role_name}</td>
                            <td>${user.phoneNumber}</td>
                            <td>
                                ${user.address1
                                    ? `${user.address1}${user.address2 ? ', ' + user.address2 : ''}, ${user.city}, ${user.state} ${user.zip}`
                                    : ''}
                            </td>
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

                // Use reusable pagination
                paginationUtils.renderPagination(pagination, currentPage, lastPage);
                updateBulkDeleteButton();
            },
            error: function(error) {
                console.error('Error fetching users:', error);
                Swal.fire('Error', 'Failed to load user data.', 'error');
            },
            complete: function() {
                $('#loaderBody').hide();
                $('#userTableBody').show();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-customer-details')) {
                const button = e.target.closest('.view-customer-details');
                const customerId = button.getAttribute('data-customer-id');

                showDetailModal({
                    route: `${window.APP_URL}/admin/users/details/${customerId}`,
                    modalId: 'customerDetailModal',
                    detailContainerId: 'detail-container',
                    auditContainerId: 'audit-log-container',
                    fields: [{
                            label: 'First Name',
                            key: 'firstname'
                        },
                        {
                            label: 'Last Name ',
                            key: 'lastname'
                        },
                        {
                            label: 'Role ',
                            key: 'role'
                        },
                        {
                            label: 'Email',
                            key: 'email'
                        },
                        {
                            label: 'Phone Number ',
                            key: 'phoneNumber'
                        },
                        {
                            label: 'Address',
                            key: 'address'
                        },
                        {
                            label: 'Status',
                            key: 'status'
                        },
                    ],
                    renderExtras: (data) => {
                        return '';
                    }
                });
            }
        });
    });

    $(document).ready(function() {
        // Live error clearing on typing or value change
        $('#roleForm').on('input change', 'input, select, textarea', function() {
            const field = $(this);
            field.removeClass('is-invalid');
            field.closest('.col-md-3, .col-md-4, .col-md-6, .col-md-12').find('.text-danger').remove();
        });

        $('#roleForm').on('submit', function(e) {
            const password = $('#password').val();
            const confirmPassword = $('#password_confirmation').val();

            if (password !== confirmPassword) {
                e.preventDefault();
                $('#password-match-error').show();
                return;
            } else {
                $('#password-match-error').hide();
            }

            e.preventDefault(); // Prevent default to handle AJAX

            const form = $(this)[0];
            const formData = new FormData(form);
            const actionUrl = $(this).attr('action');
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();

            submitBtn.prop('disabled', true).html('Processing...');

            $.ajax({
                url: actionUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire('Success', 'User has been saved successfully!', 'success').then(() => {
                        window.location.href = "{{ route('users.index') }}";
                    });
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalBtnText);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;

                        // Remove old errors and styles
                        $('.text-danger').remove();
                        $('.is-invalid').removeClass('is-invalid');

                        $.each(errors, function(key, value) {
                            const input = $('[name="' + key + '"]');
                            input.addClass('is-invalid');

                            const parentDiv = input.closest('.col-md-3, .col-md-4, .col-md-6, .col-md-12');
                            if (parentDiv.length > 0) {
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
    });

    $(document).on('click', '.delete-button', function() {
        const userId = $(this).data('id');
        const deleteUrl = "{{ route('users.destroy', ':id') }}".replace(':id', userId);

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
                        Swal.fire('Deleted!', 'User has been deleted.', 'success');

                        const page = $('#pagination .active a').data('page') || 1;
                        const perPage = $('#perPageSelect').val();
                        const searchTerm = $('#searchInput').val() || '';

                        loadDrivers(page, perPage, searchTerm, false);
                    },
                    error: function(error) {
                        console.error('Error deleting user:', error.responseJSON.message);
                        Swal.fire('Error', error.responseJSON.message, 'error');
                    }
                });
            }
        });
    });

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

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} user(s). This cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('users.bulk-destroy') }}",
                    method: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', selectedIds.length + ' user(s) have been deleted.', 'success');

                        const page = $('#pagination .active a').data('page') || 1;
                        const perPage = $('#perPageSelect').val();
                        const searchTerm = $('#searchInput').val() || '';
                        loadDrivers(page, perPage, searchTerm, false);
                    },
                    error: function(error) {
                        console.error('Error during bulk delete:', error);
                        Swal.fire('Error', 'Failed to delete selected users.', 'error');
                    }
                });
            }
        });
    });

    function getSelectedDriverIds() {
        const selectedIds = [];
        $('.driver-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-shipment-details')) {
                const button = e.target.closest('.view-shipment-details');
                const shipmentId = button.getAttribute('data-shipment-id');
                showDetailModal({
                    route: `shipments/shipments/details/${shipmentId}`,
                    modalId: 'userDetailModal',
                    detailContainerId: 'detail-container',
                    auditContainerId: 'audit-log-container',
                    fields: [{
                            label: 'Customer',
                            key: 'customer.customer_title'
                        },
                        {
                            label: 'Vehicle Type',
                            key: 'vehicle_type.vehicle_type'
                        },
                        {
                            label: 'Pickup Address',
                            key: 'pickup_address'
                        },
                        {
                            label: 'Drop Address',
                            key: 'drop_address'
                        },
                        {
                            label: 'Pickup Time',
                            key: 'pickup_time'
                        },
                        {
                            label: 'Delivery Time',
                            key: 'delivery_time'
                        },
                        {
                            label: 'Estimated Cost',
                            key: 'estimated_cost'
                        },
                    ],
                    renderExtras: (data) => {
                        return '';
                    }
                });
            }
        });
    });

    // Initialize pagination events for this page
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

    // Profile image styles are in public/assets/css/drivers.css
</script>