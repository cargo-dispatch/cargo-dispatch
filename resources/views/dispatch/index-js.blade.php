@section('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
            forceTLS: true,
            authEndpoint: '{{ url('/broadcasting/auth') }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            }
        });

        const channel = pusher.subscribe('private-admin.notifications');

        channel.bind('pusher:subscription_succeeded', function () {
            console.log('✅ Pusher subscribed to admin.notifications');
        });

        channel.bind('pusher:subscription_error', function (err) {
            console.error('❌ Pusher subscription error:', err);
        });

        function handleShipmentEvent(data) {
            console.log('📦 Shipment event received, refreshing board...', data);
            // Always reload counts + current tab — don't rely on status in payload
            if (typeof window.refreshDispatchBoard === 'function') {
                window.refreshDispatchBoard(data);
            } else {
                // refreshDispatchBoard not ready yet — retry once after jQuery is ready
                setTimeout(function () {
                    if (typeof window.refreshDispatchBoard === 'function') {
                        window.refreshDispatchBoard(data);
                    }
                }, 500);
            }
        }

        channel.bind('shipment.created', handleShipmentEvent);
        channel.bind('shipment.realtime.updated', handleShipmentEvent);
    });
</script>

<script>
    $(document).ready(function() {
        // Load counts and pending data by default
        loadStatusCounts();
        loadShipmentData('pending');

        // Tab click handler
        $('#shipmentTabs .nav-link').on('click', function(e) {
            e.preventDefault();

            $('#shipmentTabs .nav-link').removeClass('active');
            $(this).addClass('active');

            const status = $(this).data('status');
            loadShipmentData(status);
        });

        // Delete button handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-button')) {
                const btn = e.target.closest('.delete-button');
                const shipmentId = btn.getAttribute('data-id');

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
                        fetch(`${APP_URL}/dispatch/${shipmentId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                // Update counts immediately
                                if (data.counts) {
                                    updateTabCounts(data.counts);
                                } else {
                                    // If counts not returned, fetch them separately
                                    loadStatusCounts();
                                }

                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Shipment has been deleted.',
                                    icon: 'success'
                                }).then(() => {
                                    // Reload current tab data without page refresh
                                    const currentTab = $('#shipmentTabs .nav-link.active');
                                    const currentStatus = currentTab.data('status');
                                    loadShipmentData(currentStatus);
                                });
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'There was a problem deleting the shipment.',
                                    icon: 'error'
                                });
                                console.error('Deletion error:', error);
                            });
                    }
                });
            }
        });

        // Function to load status counts
        function loadStatusCounts() {
            $.ajax({
                url: `${APP_URL}/shipments/counts`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        updateTabCounts(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading counts:', error);
                }
            });
        }

        // Auto-refresh counts and table every 30 seconds
        setInterval(function() {
            loadStatusCounts();
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentStatus = currentTab.data('status');
            loadShipmentData(currentStatus, false);
        }, 30000);

        // Function to refresh everything without page reload
        function refreshAllData() {
            loadStatusCounts();
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentStatus = currentTab.data('status');
            loadShipmentData(currentStatus);
        }

        // Expose for global real-time hook (called from Pusher listener)
        window.refreshDispatchBoard = function(data) {
            loadStatusCounts();
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentStatus = currentTab.data('status');
            // Always reload the current tab — status in payload may be empty on creation
            loadShipmentData(currentStatus, false);
        };

        // Add refresh button functionality (optional)
        $(document).on('click', '.refresh-data', function() {
            $(this).find('i').addClass('fa-spin'); // Add spinning animation
            refreshAllData();

            // Remove spin after 1 second
            setTimeout(() => {
                $(this).find('i').removeClass('fa-spin');
            }, 1000);
        });

        // Real-time update function for seamless transitions
        function updateDataAndCounts() {
            // First update counts
            loadStatusCounts();

            // Then update current tab data
            setTimeout(function() {
                const currentTab = $('#shipmentTabs .nav-link.active');
                const currentStatus = currentTab.data('status');
                loadShipmentData(currentStatus);
            }, 200);
        }

        // Function to show update toast notification
        function showUpdateToast() {
            const toastElement = document.getElementById('updateToast');
            if (toastElement) {
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
            }
        }

        // Enhanced function to update tab counts with animation
        function updateTabCountsAnimated(counts) {
            // Animate count changes
            $('.pending-count').fadeOut(200, function() {
                $(this).text(counts.pending || 0).fadeIn(200);
            });
            $('.active-count').fadeOut(200, function() {
                $(this).text(counts.active || 0).fadeIn(200);
            });
            $('.complete-count').fadeOut(200, function() {
                $(this).text(counts.complete || 0).fadeIn(200);
            });
            $('.cancel-count').fadeOut(200, function() {
                $(this).text(counts.cancel || 0).fadeIn(200);
            });
        }

        // Function to update tab counts
        function updateTabCounts(counts) {
            $('.pending-count').text(counts.pending || 0);
            $('.active-count').text(counts.active || 0);
            $('.complete-count').text(counts.complete || 0);
            $('.cancel-count').text(counts.cancel || 0);
        }

        let vehiclesData = [];

        // Function to load shipment data
        function loadShipmentData(status, showLoader = true) {
            if (showLoader) {
                $('#loaderBody').show();
            }

            // Destroy existing Tom Select instances before loading new data
            destroyAllVehicleDropdowns();

            $.ajax({
                url: `${APP_URL}/admin/shipments`,
                method: 'GET',
                cache: false,
                data: {
                    status: status,
                    date: getCurrentDate()
                },
                success: function(response) {
                    $('#loaderBody').hide();
                    $('#userTableBody').empty();

                    if (response.vehicle) {
                        vehiclesData = response.vehicle;
                    }

                    if (response.data && response.data.length > 0) {
                        let rows = '';
                        response.data.forEach(function(shipment, index) {
                            rows += buildTableRow(shipment, index + 1);
                        });
                        $('#userTableBody').html(rows);

                        // Initialize dropdowns after DOM is ready
                        setTimeout(() => {
                            initializeAllVehicleDropdowns();
                        }, 500);
                    } else {
                        $('#userTableBody').html('<tr><td colspan="11" class="text-center">No shipments found</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loaderBody').hide();
                    console.error('Error loading shipments:', error);
                    $('#userTableBody').html('<tr><td colspan="11" class="text-center text-danger">Error loading shipments</td></tr>');
                }
            });
        }

        function buildTableRow(shipment, serial) {
            // Create a unique ID for this dropdown
            const dropdownId = `vehicle-dropdown-${shipment.id || Math.random()}`;

            let vehicleDropdown = `
            <select class="form-control p-1 form-control-sm vehicle-dropdown-overview" 
                    id="${dropdownId}"
                    data-id="${shipment.id || ''}"
                    data-driver-id="${shipment.driver_id || ''}"
                    data-driver="${shipment.username || ''}">
                <option value="">Select a vehicle...</option>`;

            vehiclesData.forEach(vehicle => {
                const selected = shipment.vehicle_id === vehicle.id ? 'selected' : '';

                // Fix image path handling
                let imgPath = `${APP_URL}/storage/default.png`; // Default fallback

                if (vehicle.vehicle_type && vehicle.vehicle_type.image) {
                    // Handle different image path formats
                    const imagePath = vehicle.vehicle_type.image;
                    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
                        imgPath = imagePath; // Full URL
                    } else if (imagePath.startsWith('/storage/')) {
                        imgPath = `${APP_URL}${imagePath}`; // Relative path starting with /storage/
                    } else if (imagePath.startsWith('storage/')) {
                        imgPath = `${APP_URL}/${imagePath}`; // Relative path starting with storage/
                    } else {
                        imgPath = `${APP_URL}/storage/${imagePath}`; // Just filename
                    }
                }


                vehicleDropdown += `
                <option value="${vehicle.id}" ${selected}
                        data-image="${imgPath}"
                        data-vehicle-id="${vehicle.vehicle_id}">
                    ${vehicle.vehicle_id}
                </option>`;
            });

            vehicleDropdown += `</select>`;

            const tableRow = `
            <tr>
                <td>${serial}</td>
                <td>${shipment.customer?.first_name || ''} ${shipment.customer?.last_name || ''}</td>
                <td>${shipment.vehicle_type?.vehicle_type || ''}</td>
                <td>${vehicleDropdown}</td>
                <td>${shipment.pickup_address || ''}</td>
                <td>${shipment.drop_address || ''}</td>
                <td>${formatDateTime(shipment.pickup_time)}</td>
                <td>${formatDateTime(shipment.delivery_time)}</td>
              <td>
  <select 
    class="form-select status-dropdown" 
    data-shipment-id="${shipment.id}" 
    data-current-status="${shipment.status}" 
    class="form-input-dropdown">
    
    <option value="pending" ${shipment.status === 'pending' ? 'selected' : ''}>Pending</option>
    <option value="active" ${shipment.status === 'active' ? 'selected' : ''}>Active</option>
    <option value="complete" ${shipment.status === 'complete' ? 'selected' : ''}>Complete</option>
    <option value="cancel" ${shipment.status === 'cancel' ? 'selected' : ''}>Cancel</option>
  </select>
</td>

                <td>$${shipment.estimated_cost || '0.00'}</td>
                <td>
                    <a href="javascript:void(0)" class="text-primary me-1 view-customer-details" data-customer-id="${shipment.id}" title="View Customer Details">
                        <i class="bi bi-eye"></i>
                    </a>
                   <a href="javascript:void(0)" class="text-danger open-map-btn" id=openMapBtn data-pickup="${shipment.pickup_address}" data-drop="${shipment.drop_address}" title="View Map">
        <i class="fas fa-map-marker-alt"></i>
    </a>
                 
                </td>
            </tr>`;

            return tableRow;
        }

        // Enhanced function to initialize all vehicle dropdowns at once
        function initializeAllVehicleDropdowns() {

            document.querySelectorAll('.vehicle-dropdown-overview').forEach((select, index) => {
                // Skip if already initialized
                if (select.tomselect) {
                    return;
                }


                try {
                    const tomSelectInstance = new TomSelect(select, {
                        render: {
                            option: function(item, escape) {
                                // Skip empty option
                                if (!item.value) {
                                    return `<div class="py-2 px-3">${escape(item.text)}</div>`;
                                }

                                // Get the original option element to access data attributes
                                const originalOption = select.querySelector(`option[value="${item.value}"]`);
                                const imageUrl = originalOption ?
                                    originalOption.getAttribute('data-image') :
                                    `${APP_URL}/storage/default.png`;


                                return `
                                <div class="d-flex align-items-center py-2 px-3" style="min-height: 44px;">
                                    <img src="${escape(imageUrl)}" 
                                         alt="${escape(item.text)}" 
                                         style="width: 32px; height: 32px; object-fit: cover; margin-right: 12px; border-radius: 6px; flex-shrink: 0; border: 1px solid #ddd;"
                                         onload="console.log('Image loaded: ${escape(imageUrl)}')"
                                         onerror="console.log('Image failed to load: ${escape(imageUrl)}'); this.src='${APP_URL}/storage/default.png';">
                                    <span style="font-weight: 500;">${escape(item.text)}</span>
                                </div>`;
                            },
                            item: function(item, escape) {
                                // Skip empty option
                                if (!item.value) {
                                    return `<span class="text-muted">${escape(item.text)}</span>`;
                                }

                                // Get the original option element to access data attributes
                                const originalOption = select.querySelector(`option[value="${item.value}"]`);
                                const imageUrl = originalOption ?
                                    originalOption.getAttribute('data-image') :
                                    `${APP_URL}/storage/default.png`;

                                return `
                                <div class="d-flex align-items-center">
                                    <img src="${escape(imageUrl)}" 
                                         alt="${escape(item.text)}" 
                                         style="width: 24px; height: 24px; object-fit: cover; margin-right: 8px; border-radius: 4px; flex-shrink: 0; border: 1px solid #ddd;"
                                         onerror="this.src='${APP_URL}/storage/default.png';">
                                    <span>${escape(item.text)}</span>
                                </div>`;
                            }
                        },
                        placeholder: 'Select a vehicle...',
                        allowEmptyOption: true,
                        searchField: ['text'],
                        maxOptions: null,
                        closeAfterSelect: true,
                        create: false,
                        dropdownParent: 'body', // This helps with z-index issues
                        onInitialize: function() {},
                        onDropdownOpen: function() {}
                    });

                    // Handle vehicle selection change
                    tomSelectInstance.on('change', function(value) {
                        const shipmentId = select.getAttribute('data-id');
                        const driverId = select.getAttribute('data-driver-id');
                        const driver = select.getAttribute('data-driver');



                        if (value && shipmentId) {
                            updateShipmentVehicle(shipmentId, value, driverId, driver);
                        }
                    });

                } catch (error) {
                    console.error(`Error initializing TomSelect for dropdown ${select.id}:`, error);
                }
            });
        }

        // Function to update shipment vehicle assignment
        // Replace your existing updateShipmentVehicle function with this fixed version:

        // Replace your updateShipmentVehicle function with this fixed version:
        // Modified updateShipmentVehicle function
        function updateShipmentVehicle(shipmentId, vehicleId, driverId, driver, forceAssign) {
            const select = document.querySelector(`.vehicle-dropdown-overview[data-id="${shipmentId}"]`);
            const tomSelect = select?.tomselect;
            if (tomSelect) tomSelect.destroy();

            $.ajax({
                url: `${APP_URL}/admin/shipments/${shipmentId}/assign-vehicle`,
                method: 'PUT',
                data: {
                    vehicle_id: vehicleId,
                    force_assign: forceAssign ? 1 : 0,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({ title: 'Success!', text: response.message || 'Vehicle assigned successfully!', icon: 'success', timer: 3000, showConfirmButton: false });
                    if (select) select.value = vehicleId;
                    const currentTab = $('#shipmentTabs .nav-link.active');
                    loadShipmentData(currentTab.data('status'), false);
                },
                error: function(xhr) {
                    const resp = xhr.responseJSON;
                    if (xhr.status === 422 && resp?.driver_unavailable) {
                        Swal.fire({
                            title: 'Driver Unavailable',
                            html: `<b>${resp.driver_name}</b> is currently <span class="badge bg-secondary">${resp.driver_status}</span>.<br><br>Do you still want to assign?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#aaa',
                            confirmButtonText: 'Force Assign',
                        }).then(result => {
                            if (result.isConfirmed) updateShipmentVehicle(shipmentId, vehicleId, driverId, driver, true);
                        });
                        return;
                    }
                    Swal.fire({ title: 'Error!', text: resp?.message || 'Error assigning vehicle. Please try again.', icon: 'error' });
                    if (select && !select.tomselect) setTimeout(() => new TomSelect(select, {}), 500);
                }
            });
        }
        // OR - If you prefer to fix the showMessage function instead, use this version:


        // If you want to use the showMessage function, update your updateShipmentVehicle like this:

        function updateShipmentVehicleWithCustomAlert(shipmentId, vehicleId, driverId, driver) {
            $.ajax({
                url: `${APP_URL}/admin/shipments/${shipmentId}/assign-vehicle`,
                method: 'PUT',
                data: {
                    vehicle_id: vehicleId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {

                    // This will now work properly
                    showMessage(response.message || 'Vehicle assigned successfully!', 'success');

                    // Update UI with the new vehicle/driver info
                    if (response.shipment && response.shipment.vehicle) {
                        updateVehicleInfo(response.shipment.vehicle, response.shipment.driver);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error assigning vehicle:', error);
                    let errorMessage = xhr.responseJSON?.message ||
                        'Error assigning vehicle. Please try again.';

                    showMessage(errorMessage, 'error');
                }
            });
        }
        // Helper function to update UI
        function updateVehicleInfo(vehicle, driver) {
            // Example: Update your UI elements
            $('#vehicle-info').text(`${vehicle.make} ${vehicle.model} (${vehicle.license_plate})`);
            $('#driver-info').text(`${driver.name} (${driver.phone})`);
        }

        // Function to destroy all Tom Select instances (useful for cleanup)
        function destroyAllVehicleDropdowns() {
            document.querySelectorAll('.vehicle-dropdown-overview').forEach(select => {
                if (select.tomselect) {
                    try {
                        select.tomselect.destroy();
                    } catch (error) {}
                }
            });
        }

        // Status dropdown change handler
        $(document).on('change', '.status-dropdown', function() {
            const shipmentId = $(this).data('shipment-id');
            const currentStatus = $(this).data('current-status');
            const newStatus = $(this).val();
            const dropdown = $(this);

            if (newStatus === currentStatus) return;

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to change status to "${newStatus}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateShipmentStatus(shipmentId, newStatus, dropdown);
                } else {
                    dropdown.val(currentStatus);
                }
            });
        });
        $(document).on('change', '.status-dropdown', function() {
            const shipmentId = $(this).data('shipment-id');
            const currentStatus = $(this).data('current-status');
            const newStatus = $(this).val();
            const dropdown = $(this);

            console.log('📋 Status Change Request:', {
                shipmentId: shipmentId,
                currentStatus: currentStatus,
                newStatus: newStatus
            });

            // Don't do anything if status hasn't changed
            if (newStatus === currentStatus) {
                console.log('⚠️ Status unchanged, skipping');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to change status to "${newStatus}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateShipmentStatus(shipmentId, newStatus, dropdown);
                } else {
                    console.log('❌ User cancelled status change');
                    dropdown.val(currentStatus); // Revert to original
                }
            });
        });

   function updateShipmentStatus(shipmentId, newStatus, dropdown) {
    console.log('🚀 Sending status update request...', {
        url: `${APP_URL}/shipments/${shipmentId}/status`,
        shipmentId: shipmentId,
        newStatus: newStatus
    });
    
    $.ajax({
        url: `${APP_URL}/shipments/${shipmentId}/status`,
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            status: newStatus
        }),
        success: function(response) {
            console.log('✅ Success response:', response);
            
            // Update dropdown's data attribute
            dropdown.data('current-status', newStatus);
            
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: response.message || 'Status updated successfully!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Update counts if provided
            if (response.counts) {
                updateTabCounts(response.counts);
            } else {
                loadStatusCounts();
            }

            // Get current active tab status
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentTabStatus = currentTab.data('status');
            
            // Check if the updated shipment should still be in the current tab
            if (currentTabStatus === newStatus) {
                // Shipment stays in current tab - just refresh the current view
                loadShipmentData(currentTabStatus, false);
            } else {
                // Shipment has moved to another tab - refresh current view (it will disappear)
                loadShipmentData(currentTabStatus, false);
                
                // Optional: Show a notification that shipment has moved
               
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error response:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseJSON: xhr.responseJSON,
                responseText: xhr.responseText
            });
            
            // Revert dropdown to previous value
            dropdown.val(dropdown.data('current-status'));
            
            // Extract error message
            let errorMessage = 'Error updating status. Please try again.';
            
            if (xhr.responseJSON) {
                errorMessage = xhr.responseJSON.message || errorMessage;
            } else if (xhr.responseText) {
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    errorMessage = parsed.message || errorMessage;
                } catch (e) {
                    console.error('Could not parse error response');
                }
            }
            
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
        }
    });
}

// Add this function to handle automatic tab switching
function handleStatusUpdateWithTabSwitch(shipmentId, newStatus, dropdown) {
    $.ajax({
        url: `${APP_URL}/shipments/${shipmentId}/status`,
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            status: newStatus
        }),
        success: function(response) {
            // Update dropdown's data attribute
            dropdown.data('current-status', newStatus);
            
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: response.message || 'Status updated successfully!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Update counts
            if (response.counts) {
                updateTabCounts(response.counts);
            } else {
                loadStatusCounts();
            }

            // Get current active tab
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentTabStatus = currentTab.data('status');
            
            // If shipment moved to a different status tab
            if (currentTabStatus !== newStatus) {
                // Option 1: Automatically switch to the new status tab
                setTimeout(() => {
                    // Remove active class from all tabs
                    $('#shipmentTabs .nav-link').removeClass('active');
                    
                    // Add active class to the new status tab
                    $(`#shipmentTabs .nav-link[data-status="${newStatus}"]`).addClass('active');
                    
                    // Load data for the new tab
                    loadShipmentData(newStatus, true);
                    
                    // Show notification
                    Swal.fire({
                        title: 'Tab Switched',
                        text: `Switched to "${newStatus}" tab`,
                        icon: 'info',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }, 500);
            } else {
                // Just refresh current tab if status didn't change
                loadShipmentData(currentTabStatus, false);
            }
        },
        error: function(xhr) {
            // Revert dropdown to previous value
            dropdown.val(dropdown.data('current-status'));
            
            // Show error message
            let errorMessage = xhr.responseJSON?.message || 
                              'Error updating status. Please try again.';
            
            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
        }
    });
}

// Then update your change handler to use this function:
$(document).on('change', '.status-dropdown', function() {
    const shipmentId = $(this).data('shipment-id');
    const currentStatus = $(this).data('current-status');
    const newStatus = $(this).val();
    const dropdown = $(this);

    if (newStatus === currentStatus) return;

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to change status to "${newStatus}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Use the new function with tab switching
            handleStatusUpdateWithTabSwitch(shipmentId, newStatus, dropdown);
        } else {
            dropdown.val(currentStatus);
        }
    });
});
        // Bulk operations handlers
        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.shipment-checkbox').prop('checked', isChecked);
            toggleBulkActions();
        });

        $(document).on('change', '.shipment-checkbox', function() {
            const total = $('.shipment-checkbox').length;
            const checked = $('.shipment-checkbox:checked').length;

            $('#selectAllCheckbox').prop('checked', total === checked);
            toggleBulkActions();
        });

        function toggleBulkActions() {
            const count = $('.shipment-checkbox:checked').length;
            if (count > 0) {
                $('#bulkUpdateBtn, #bulkStatusSelect, #applyBulkUpdate').show();
            } else {
                $('#bulkUpdateBtn, #bulkStatusSelect, #applyBulkUpdate').hide();
            }
        }

        $('#applyBulkUpdate').on('click', function() {
            const selectedIds = $('.shipment-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            const newStatus = $('#bulkStatusSelect').val();

            if (selectedIds.length === 0) {
                alert('Please select shipments to update');
                return;
            }

            Swal.fire({
                title: 'Confirm Bulk Update',
                text: `Update ${selectedIds.length} shipment(s) to "${newStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkUpdateStatus(selectedIds, newStatus);
                }
            });
        });

        function bulkUpdateStatus(shipmentIds, newStatus) {
            $.ajax({
                url: `${APP_URL}/shipments/bulk-update-status`,
                method: 'POST',
                data: {
                    shipment_ids: shipmentIds,
                    status: newStatus,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showMessage(response.message, 'success');

                    // Update counts immediately
                    if (response.counts) {
                        updateTabCounts(response.counts);
                    } else {
                        // If counts not returned, fetch them separately
                        loadStatusCounts();
                    }

                    // Refresh current tab data without delay
                    const currentTab = $('#shipmentTabs .nav-link.active');
                    const currentStatus = currentTab.data('status');
                    loadShipmentData(currentStatus);
                },
                error: function(xhr, status, error) {
                    console.error('Error bulk updating:', error);
                    showMessage('Error updating shipments. Please try again.', 'error');
                }
            });
        }

        // Utility functions
        function getCurrentDate() {
            const today = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return today.toLocaleDateString(undefined, options);
        }




        function showMessage(message, type) {
            $('.alert-message').remove();

            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show alert-message" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

            $('.ms-4').first().before(alertHtml);

            setTimeout(function() {
                $('.alert-message').fadeOut();
            }, 3000);
        }
    });



    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            // Show customer detail modal
            const button = e.target.closest('.view-customer-details');
            if (button) {
                const customerId = button.getAttribute('data-customer-id');

                showDetailModal({
                    route: `${APP_URL}/admin/current-dispatch/details/${customerId}`,
                    modalId: 'customerDetailModal',
                    detailContainerId: 'detail-container',
                    auditContainerId: 'audit-log-container',
                    fields: [{
                            label: 'Cost',
                            key: 'estimated_cost',
                            format: value => `$${parseFloat(value || 0).toFixed(2)}`
                        },
                        {
                            label: 'Customer Name',
                            key: 'customer_name'
                        },
                        {
                            label: 'Vehicle Type',
                            key: 'vehicle_type'
                        },
                        {
                            label: 'Pickup Address',
                            key: 'pickup_address'
                        },
                        {
                            label: 'Pallets',
                            key: 'pallets'
                        },
                        {
                            label: 'Volume',
                            key: 'volume'
                        },
                        {
                            label: 'Weight',
                            key: 'weight'
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
                            label: 'Delievery Time',
                            key: 'delivery_time'
                        },
                        {
                            label: 'Equipments',
                            key: 'equipment_required'
                        },
                        {
                            label: 'Special Instructions',
                            key: 'special_instructions'
                        },
                    ],
                    renderExtras: (data) => {
                        window.currentShipmentData = data;
                        if (!data.pickup_address || !data.drop_address) return '';
                        return `
                     
                    `;
                    }
                });
            }

            // Handle map icon/button click
            // Handle map icon/button click
            // Handle map icon/button click
            if (e.target && e.target.closest('.open-map-btn')) {
                const mapBtn = e.target.closest('.open-map-btn');
                const pickup = encodeURIComponent(mapBtn.getAttribute('data-pickup') || '');
                const drop = encodeURIComponent(mapBtn.getAttribute('data-drop') || '');

                if (!pickup || !drop) {
                    alert("Pickup or drop address is missing.");
                    return;
                }

                const mapUrl = `https://www.google.com/maps/embed/v1/directions?key={{ config('services.google.maps_api_key') }}&origin=${pickup}&destination=${drop}&mode=driving`;
                document.getElementById('mapIframe').src = mapUrl;

                const mapModal = new bootstrap.Modal(document.getElementById('mapModal'));
                mapModal.show();
            }
            // Close icon (force close fallback)
            if (e.target && e.target.matches('#mapModal .btn-close')) {
                forceCloseMapModal();
            }
        });

        window.forceCloseMapModal = function() {
            const modal = document.getElementById('mapModal');
            if (!modal) return;

            modal.style.display = 'none';
            document.body.classList.remove('modal-open');

            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.getElementById('mapIframe').src = '';
        };
    });
</script>





@endsection