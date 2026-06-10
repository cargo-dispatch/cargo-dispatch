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
        let currentSortColumn = 'id';
        let currentSortOrder = 'asc';
        let currentPage = 1;
        let currentPerPage = 10;
        let currentSearchTerm = '';
        let currentDriverType = '';
        let currentDutyStatus = '';

        // Filter styles are in public/assets/css/drivers.css

        // Load driver types for the dropdown
        function loadDriverTypes() {
            $.ajax({
                url: "{{ route('managedriver.driver-types') }}",
                method: "GET",
                success: function(response) {
                    const typesHtml = response.map(type => 
                        `<a href="javascript:void(0)" class="filter-option" data-filter="driver_type" data-value="${type.id}">${type.name}</a>`
                    ).join('');
                    $('#driverTypeOptions').html(typesHtml);
                },
                error: function(error) {
                    console.error('Error loading driver types:', error);
                }
            });
        }

        // Toggle driver type dropdown
        $(document).on('click', '#driverTypeFilterBtn', function(e) {
            e.stopPropagation();
            $('#driverTypeDropdown').toggle();
        });

        // Handle filter option clicks
        $(document).on('click', '.filter-option', function(e) {
            e.preventDefault();
            const filterType = $(this).data('filter');
            const value = $(this).data('value');
            
            // Update state
            if (filterType === 'driver_type') {
                currentDriverType = value;
                $('#driverTypeFilterBtn').toggleClass('active', !!value);
            }
            
            // Hide dropdown
            $('#driverTypeDropdown').hide();
            
            // Reload with filters
            currentPage = 1;
            loadDrivers(currentPage, currentPerPage, currentSearchTerm, true, currentSortColumn, currentSortOrder);
        });

        // Handle duty status filter buttons
        $('.filter-btn[data-filter="duty_status"]').on('click', function(e) {
            e.preventDefault();
            const value = $(this).data('value');
            
            if (currentDutyStatus === value) {
                currentDutyStatus = '';
                $(this).removeClass('active');
            } else {
                currentDutyStatus = value;
                $('.filter-btn[data-filter="duty_status"]').removeClass('active');
                $(this).addClass('active');
            }
            
            currentPage = 1;
            loadDrivers(currentPage, currentPerPage, currentSearchTerm, true, currentSortColumn, currentSortOrder);
        });

        // Clear all filters
        $('#clearFiltersBtn').on('click', function() {
            currentDriverType = '';
            currentDutyStatus = '';
            currentSearchTerm = '';
            $('#searchInput').val('');
            $('.filter-btn').removeClass('active');
            $('#driverTypeFilterBtn').removeClass('active');
            currentPage = 1;
            loadDrivers(currentPage, currentPerPage, '', true, currentSortColumn, currentSortOrder);
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#driverTypeFilterBtn, #driverTypeDropdown').length) {
                $('#driverTypeDropdown').hide();
            }
        });

        $('#searchInput').on('keyup', function () {
            clearTimeout(debounceTimer);
            currentSearchTerm = $(this).val().trim();
            debounceTimer = setTimeout(function () {
                currentPage = 1;
                loadDrivers(currentPage, currentPerPage, currentSearchTerm);
            }, 300);
        });

        // Select/deselect all checkbox handler
        $(document).on('change', '#selectAllCheckbox', function () {
            const isChecked = $(this).prop('checked');
            $('.driver-checkbox').prop('checked', isChecked);
            updateBulkDeleteButton();
        });

        // Individual checkbox handler
        $(document).on('change', '.driver-checkbox', function () {
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

        function loadDrivers(page = 1, perPage = 10, searchTerm = '', showLoader = true, sortColumn = 'id', sortOrder = 'asc') {
            // Update current state
            currentPage = page;
            currentPerPage = perPage;
            currentSearchTerm = searchTerm;
            currentSortColumn = sortColumn;
            currentSortOrder = sortOrder;
            
            if (showLoader) {
                $('#userTableBody').hide();
                $('#loaderBody').show();
            }

            $('#userTableBody').empty();
            $('#pagination').empty();

            const ajaxData = {
                page: page,
                per_page: perPage,
                search: searchTerm,
                sort_column: sortColumn,
                sort_order: sortOrder
            };

            // Add filter parameters if set
            if (currentDriverType) {
                ajaxData.driver_type = currentDriverType;
            }
            if (currentDutyStatus) {
                ajaxData.duty_status = currentDutyStatus;
            }

            console.log('[Drivers] loadDrivers →', JSON.stringify(ajaxData));

            $.ajax({
                url: "{{ route('managedriver.get') }}",
                method: "GET",
                cache: false,
                data: ajaxData,
                success: function (response) {
                    console.log('[Drivers] loadDrivers ← count:', response.data ? response.data.length : 'N/A', 'duty_filter:', ajaxData.duty_status || 'none');
                    const data = response.data;
                    const pagination = response.links;
                    const currentPage = response.current_page;
                    const lastPage = response.last_page;

                    if (data.length === 0) {
                        $('#userTableBody').append(`
                            <tr>
                                <td colspan="11" class="text-center">No drivers found</td>
                            </tr>
                        `);
                    } else {
                        data.forEach(function (user) {
                            const eld = user.eld || null;

                            // DB current_duty_status is authoritative (set by mobile app).
                            // Only fall back to mock ELD if the DB field is truly absent (null/undefined).
                            const liveStatus = (user.current_duty_status != null) ? user.current_duty_status : (eld ? eld.current_status : null);
                            const dutyBadgeColors = {
                                driving: 'bg-success',
                                on_duty_not_driving: 'bg-warning text-dark',
                                off_duty: 'bg-secondary',
                                sleeper: 'bg-info text-dark',
                            };
                            const dutyBadgeClass = dutyBadgeColors[liveStatus] || 'bg-secondary';
                            const dutyLabel = liveStatus ? liveStatus.replace(/_/g, ' ').toUpperCase() : 'OFF DUTY';

                            let hosHtml = `<div style="font-size:11px;"><span class="badge ${dutyBadgeClass} duty-status-badge mb-1" style="font-size:10px;">${dutyLabel}</span></div>`;

                            if (eld && eld.hos) {
                                const driveMin = eld.hos.drive_remaining_minutes || 0;
                                const dutyMin  = eld.hos.on_duty_remaining_minutes || 0;
                                hosHtml = `
                                    <div style="font-size:11px;">
                                        <span class="badge ${dutyBadgeClass} duty-status-badge mb-1" style="font-size:10px;">${dutyLabel}</span><br>
                                        <span class="text-muted">Drive: ${minutesToHoursLabel(driveMin)}</span><br>
                                        <span class="text-muted">On-duty: ${minutesToHoursLabel(dutyMin)}</span>
                                    </div>
                                `;
                            }

                            const statusColors = {
                                active: 'success', inactive: 'secondary',
                                suspended: 'danger', pending_review: 'warning',
                                rejected: 'danger', invited: 'info'
                            };
                            const st = user.status || 'inactive';
                            const stColor = statusColors[st] || 'secondary';
                            const statusBadge = `<span class="badge badge-${stColor} status-badge" data-id="${user.id}" style="cursor:pointer;font-size:11px" title="Click to change status">${st.replace('_',' ')}</span>`;

                            $('#userTableBody').append(`
                                <tr data-id="${user.id}" data-driver-id="${user.id}">
                                    <td>
                                        <input type="checkbox" class="driver-checkbox" value="${user.id}">
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" class="driver-name-link fw-semibold" data-id="${user.id}" style="text-decoration:none">
                                            ${user.firstname}
                                        </a>
                                    </td>
                                    <td>${user.lastname}</td>
                                    <td>${user.phoneno}</td>
                                    <td>${user.emergencycontactno}</td>
                                    <td>${user.email}</td>
                                    <td>${user.drivertype ? user.drivertype.name : 'N/A'}</td>
                                    <td>${user.incentive ? `$${user.incentive}` : '$0.00'}</td>
                                    <td>${hosHtml}</td>
                                    <td>${user.licenseno}</td>
                                    <td>${user.licensetype}</td>
                                    <td>
                                        <div class="d-flex align-items-center flex-wrap gap-1">
                                        ${statusBadge}
                                        <a href="${user.actions.edit}" class="text-primary ms-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="javascript:void(0)" class="text-primary ms-1 view-customer-details" data-customer-id="${user.id}" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-link text-danger p-0 ms-1 delete-button" title="Delete" data-id="${user.id}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });
                    }

                    paginationUtils.renderPagination(pagination, currentPage, lastPage);
                    updateBulkDeleteButton();
                    
                    // Update per page select to reflect current value
                    $('#perPageSelect').val(perPage);
                },
                error: function (error) {
                    console.error('Error fetching drivers:', error);
                    Swal.fire('Error', 'Failed to load driver data.', 'error');
                },
                complete: function () {
                    $('#loaderBody').hide();
                    $('#userTableBody').show();
                }
            });
        }

        function minutesToHoursLabel(minutes) {
            const m = parseInt(minutes || 0, 10);
            const h = Math.floor(m / 60);
            const rem = m % 60;
            if (h <= 0) return `${rem}m`;
            return `${h}h ${rem}m`;
        }

        // FIXED PAGINATION EVENT HANDLER
        function initPaginationEvents() {
            // Remove any existing event handlers first
            $(document).off('click', '.page-link[data-page]');
            
            // Add new event handler
            $(document).on('click', '.page-link[data-page]', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                loadDrivers(page, currentPerPage, currentSearchTerm, true, currentSortColumn, currentSortOrder);
            });
        }

        // Per page change handler
        $('#perPageSelect').on('change', function() {
            currentPerPage = $(this).val();
            loadDrivers(1, currentPerPage, currentSearchTerm, true, currentSortColumn, currentSortOrder);
        });

        // Initialize page
        $(document).ready(function() {
            loadDrivers();
            loadDriverTypes();
            initPaginationEvents();
        });

        // Open driver detail modal — name click OR eye icon OR status badge
        $(document).on('click', '.driver-name-link, .view-customer-details, .status-badge', function () {
            const id = $(this).data('id') || $(this).data('customer-id');
            openDriverModal(id);
        });

        function openDriverModal(id) {
            $('#driverModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
            $('#driverDetailModal').modal('show');

            const url = window.DRIVER_DETAILS_URL.replace(':id', id);

            $.ajax({
                url: url,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(d) {
                    const statusColors = { active:'success', inactive:'secondary', suspended:'danger', pending_review:'warning', rejected:'danger', invited:'info' };
                    const st = d.status || 'inactive';

                    // Documents
                    let docsHtml = '<p class="text-muted mb-0" style="font-size:13px">No documents uploaded.</p>';
                    if (d.documents && d.documents.length > 0) {
                        docsHtml = '<div class="row g-2">';
                        d.documents.forEach(function(doc) {
                            const dc = { verified:'success', rejected:'danger', pending:'warning' };
                            docsHtml += `
                                <div class="col-sm-6">
                                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div style="font-size:12px;font-weight:600">${doc.type_label}</div>
                                            <span class="badge badge-${dc[doc.status]||'secondary'}">${doc.status}</span>
                                            ${doc.expires_at ? `<div style="font-size:11px;color:#888">Exp: ${doc.expires_at}</div>` : ''}
                                        </div>
                                        <a href="${doc.view_url}" target="_blank" class="btn btn-sm btn-outline-secondary ml-2">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </div>`;
                        });
                        docsHtml += '</div>';
                    }

                    const endorse = (d.cdl_endorsements||[]).length
                        ? d.cdl_endorsements.map(function(e){ return '<span class="badge badge-secondary mr-1">'+e+'</span>'; }).join('')
                        : '<span class="text-muted">None</span>';

                    const statusOptions = ['active','inactive','suspended'].map(function(s) {
                        return '<option value="'+s+'"'+(s===st?' selected':'')+'>'+s.charAt(0).toUpperCase()+s.slice(1)+'</option>';
                    }).join('');

                    $('#driverModalBody').html(
                        '<div class="row">'+
                            // Profile + status row
                            '<div class="col-12 d-flex align-items-center mb-3 pb-3 border-bottom">'+
                                '<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center font-weight-bold mr-3" style="width:50px;height:50px;font-size:18px;flex-shrink:0">'+
                                    (d.firstname||'').charAt(0).toUpperCase()+(d.lastname||'').charAt(0).toUpperCase()+
                                '</div>'+
                                '<div class="flex-grow-1">'+
                                    '<h6 class="mb-0">'+d.firstname+' '+d.lastname+'</h6>'+
                                    '<small class="text-muted">'+d.email+'</small>'+
                                '</div>'+
                                '<select id="statusSelect" style="width:150px;min-width:150px;height:36px;line-height:36px;padding:4px 8px;border:1px solid #ced4da;border-radius:4px;font-size:14px;">'+statusOptions+'</select>'+
                                '<button class="btn btn-sm btn-primary" id="saveStatusBtn" data-id="'+d.id+'">Save</button>'+
                            '</div>'+
                            // Contact
                            '<div class="col-md-6">'+
                                '<p class="text-uppercase font-weight-bold mb-2" style="font-size:11px;color:#4e73df">Contact</p>'+
                                '<table class="table table-sm">'+
                                    '<tr><td class="text-muted">Phone</td><td>'+(d.phoneno||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Emergency</td><td>'+(d.emergencycontactno||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Driver Type</td><td>'+(d.driver_type||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Experience</td><td>'+(d.years_experience!=null?d.years_experience+' yrs':'—')+'</td></tr>'+
                                '</table>'+
                            '</div>'+
                            // CDL
                            '<div class="col-md-6">'+
                                '<p class="text-uppercase font-weight-bold mb-2" style="font-size:11px;color:#4e73df">CDL & Compliance</p>'+
                                '<table class="table table-sm">'+
                                    '<tr><td class="text-muted">CDL #</td><td>'+(d.cdl_number||d.licaenceno||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">State / Class</td><td>'+(d.cdl_state||'—')+' / '+(d.cdl_class?'Class '+d.cdl_class:(d.licaence_type||'—'))+'</td></tr>'+
                                    '<tr><td class="text-muted">CDL Expiry</td><td>'+(d.cdl_expiry_date||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Medical Expiry</td><td>'+(d.medical_card_expiry||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Drug Test</td><td>'+(d.drug_test_status||'—')+'</td></tr>'+
                                    '<tr><td class="text-muted">Endorsements</td><td>'+endorse+'</td></tr>'+
                                '</table>'+
                            '</div>'+
                            // Documents
                            '<div class="col-12">'+
                                '<p class="text-uppercase font-weight-bold mb-2" style="font-size:11px;color:#4e73df">Documents</p>'+
                                docsHtml+
                            '</div>'+
                        '</div>'
                    );

                    // Status save
                    $('#saveStatusBtn').off('click').on('click', function () {
                        const driverId = $(this).data('id');
                        const newStatus = $('#statusSelect').val();
                        const $btn = $(this);
                        $btn.prop('disabled', true).text('Saving…');

                        $.ajax({
                            url: window.DRIVER_STATUS_URL.replace(':id', driverId),
                            method: 'POST',
                            data: { _token: $('meta[name="csrf-token"]').attr('content'), status: newStatus },
                            success: function(res) {
                                const colors = { active:'success', inactive:'secondary', suspended:'danger', pending_review:'warning' };
                                $('tr[data-id="'+driverId+'"]').find('.status-badge')
                                    .attr('class', 'badge badge-'+( colors[newStatus]||'secondary')+' status-badge')
                                    .attr('data-id', driverId)
                                    .text(newStatus.replace('_',' '));
                                $btn.prop('disabled', false).text('Save');
                                Swal.fire({ icon:'success', title:'Updated', text: res.message, timer:1500, showConfirmButton:false });
                            },
                            error: function() {
                                $btn.prop('disabled', false).text('Save');
                                Swal.fire('Error', 'Failed to update status.', 'error');
                            }
                        });
                    });
                },
                error: function(xhr) {
                    console.error('Driver details error:', xhr.status, xhr.responseText);
                    $('#driverModalBody').html('<p class="text-danger text-center py-4">Failed to load driver details. ('+xhr.status+')</p>');
                }
            });
        }

        // Sortable column click handler
        $(document).on('click', 'th.sortable', function () {
            const column = $(this).data('column');
            let order = $(this).data('order');

            order = order === 'asc' ? 'desc' : 'asc';

            $('.sort-icon').html('');
            $(this).find('.sort-icon').html(order === 'asc' ? '&#9650;' : '&#9660;');

            currentSortColumn = column;
            currentSortOrder = order;

            $(this).data('order', order);

            loadDrivers(1, currentPerPage, currentSearchTerm, true, currentSortColumn, currentSortOrder);
        });

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

            const ajaxMethod = 'POST';

            submitBtn.prop('disabled', true).html('Processing...');

            $.ajax({
                url: actionUrl,
                method: ajaxMethod,
                data: formData,
                success: function (response) {
                    Swal.fire('Success', 'Driver has been saved successfully!', 'success').then(() => {
                        window.location.href = "{{ route('managedriver.index') }}";
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
                            input.next('.text-danger').remove();
                            input.after('<span class="text-danger">' + value[0] + '</span>');
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
            const deleteUrl = "{{ route('managedriver.destroy', ':id') }}".replace(':id', userId);

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
                            Swal.fire('Deleted!', 'Driver has been deleted.', 'success');
                            loadDrivers(currentPage, currentPerPage, currentSearchTerm, false, currentSortColumn, currentSortOrder);
                        },
                        error: function (error) {
                            console.error('Error deleting driver:', error);
                            Swal.fire('Error', error.responseJSON.message, 'error');
                        }
                    });
                }
            });
        });

        // Bulk delete button handler
        $(document).on('click', '#bulkDeleteBtn', function (e) {
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                e.stopPropagation();

                Swal.fire({
                    icon: 'error',
                    title: 'No Selection',
                    text: 'Please select at least one driver to delete.',
                    confirmButtonColor: '#3085d6',
                });
                return false;
            }

            const selectedIds = getSelectedDriverIds();

            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${selectedIds.length} driver(s). This cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('managedriver.bulk-destroy') }}",
                        method: "POST",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            ids: selectedIds
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', `${selectedIds.length} driver(s) have been deleted.`, 'success');
                            loadDrivers(currentPage, currentPerPage, currentSearchTerm, false, currentSortColumn, currentSortOrder);
                        },
                        error: function (error) {
                            console.error('Error during bulk delete:', error);
                            Swal.fire('Error', error.responseJSON.message, 'error');
                        }
                    });
                }
            });
        });

        function getSelectedDriverIds() {
            const selectedIds = [];
            $('.driver-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });
            return selectedIds;
        }

        // ── Real-time duty status updates from mobile ──────────────────────────
        function handleDriverStatusUpdated(data) {
            const driverId = String(data.driver_id);
            const newStatus = data.current_duty_status;
            const dutyBadgeColors = {
                driving: 'bg-success',
                on_duty_not_driving: 'bg-warning text-dark',
                off_duty: 'bg-secondary',
                sleeper: 'bg-info text-dark',
            };
            const colorClass = dutyBadgeColors[newStatus] || 'bg-secondary';
            const label = newStatus ? newStatus.replace(/_/g, ' ').toUpperCase() : 'OFF DUTY';
            const $row = $(`tr[data-driver-id="${driverId}"]`);

            // If a duty status filter is active, re-run the filtered query so the
            // driver moves in/out of the filtered view automatically.
            if (currentDutyStatus) {
                loadDrivers(currentPage, currentPerPage, currentSearchTerm, false, currentSortColumn, currentSortOrder);
                return;
            }

            if ($row.length > 0) {
                $row.find('.duty-status-badge')
                    .attr('class', `badge ${colorClass} duty-status-badge mb-1`)
                    .text(label);
            }
        }

        // Listen via DOM event (fired by app.js regardless of page load order)
        window.addEventListener('driverStatusUpdated', (e) => handleDriverStatusUpdated(e.detail));
        // Also keep callback so app.js can call it directly (single dispatch path)
        window.onDriverStatusUpdated = null; // DOM event is the sole path to avoid double-call

    </script>
@endsection