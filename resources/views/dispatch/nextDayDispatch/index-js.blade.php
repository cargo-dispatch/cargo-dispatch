@section('scripts')

<script>
    const APP_URL = "{{ config('app.url') }}";
</script>

<script>
$(document).ready(function () {
    
    loadStatusCounts();
    loadShipmentData('pending');

    // Tab click handler
    $('#shipmentTabs .nav-link').on('click', function (e) {
        e.preventDefault();

        $('#shipmentTabs .nav-link').removeClass('active');
        $(this).addClass('active');

        const status = $(this).data('status');
        loadShipmentData(status);
    });

    // FIXED Date picker change handler for tomorrow dispatch
    $('#dispatch-date').on('change', function() {
        const selectedDate = $(this).val();
        
        // Show loading indicator for counts
        showCountsLoading(true);
        
        // Clear any existing auto-refresh to prevent conflicts
        if (window.countRefreshInterval) {
            clearInterval(window.countRefreshInterval);
        }
        
        // Load counts first, then shipment data
        loadStatusCountsImmediate(selectedDate).then(() => {
            // Get current active tab
            const currentTab = $('#shipmentTabs .nav-link.active');
            const currentStatus = currentTab.data('status');
            
            // Load shipment data for the selected date and status
            loadShipmentData(currentStatus, true, selectedDate);
            
            // Hide loading indicator
            showCountsLoading(false);
            
            // Restart auto-refresh with new date context
            startAutoRefresh();
        }).catch((error) => {
            console.error('Error loading counts:', error);
            showCountsLoading(false);
            showMessage('Error loading shipment counts', 'error');
        });
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

    // IMPROVED Function to load status counts for tomorrow's dispatch
    function loadStatusCounts() {
        const selectedDate = $('#dispatch-date').val() || getTomorrowDate();
        
        $.ajax({
            url: `${APP_URL}/shipments/tomorrow/counts`,
            method: 'GET',
            data: {
                date: selectedDate
            },
            timeout: 5000,
            success: function (response) {
                if (response.success && response.data) {
                    updateTabCounts(response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading counts:', error);
                // Fallback to default counts if API fails
                updateTabCounts({
                    pending: 0,
                    active: 0,
                    complete: 0,
                    cancel: 0
                });
            }
        });
    }

    // NEW Function to load counts immediately with Promise support
    function loadStatusCountsImmediate(selectedDate = null) {
        return new Promise((resolve, reject) => {
            const dateToUse = selectedDate || $('#dispatch-date').val() || getTomorrowDate();
            
            $.ajax({
                url: `${APP_URL}/shipments/tomorrow/counts`,
                method: 'GET',
                data: {
                    date: dateToUse
                },
                timeout: 5000,
                success: function (response) {
                    if (response.success && response.data) {
                        updateTabCounts(response.data);
                        resolve(response.data);
                    } else {
                        reject('Invalid response format');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading counts:', error);
                    // Fallback to zero counts
                    const fallbackCounts = {
                        pending: 0,
                        active: 0,
                        complete: 0,
                        cancel: 0
                    };
                    updateTabCounts(fallbackCounts);
                    reject(error);
                }
            });
        });
    }

    // NEW Function to show loading state for counts
    function showCountsLoading(show) {
        if (show) {
            $('.pending-count, .active-count, .complete-count, .cancel-count').html('<i class="fas fa-spinner fa-spin"></i>');
        }
        // When hide is called, the actual counts will be updated by updateTabCounts()
    }

    // IMPROVED Auto-refresh with date context
    function startAutoRefresh() {
        // Clear existing interval
        if (window.countRefreshInterval) {
            clearInterval(window.countRefreshInterval);
        }
        
        // Start new interval that respects current date selection
        window.countRefreshInterval = setInterval(function() {
            const currentDate = $('#dispatch-date').val() || getTomorrowDate();
            loadStatusCountsImmediate(currentDate).catch(console.error);
        }, 30000);
    }

    // Initialize auto-refresh
    startAutoRefresh();

    // Function to refresh everything without page reload
    function refreshAllData() {
        loadStatusCounts();
        const currentTab = $('#shipmentTabs .nav-link.active');
        const currentStatus = currentTab.data('status');
        loadShipmentData(currentStatus);
    }

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
        $('.pending-count').fadeOut(100, function() {
            $(this).text(counts.pending || 0).fadeIn(100);
        });
        $('.active-count').fadeOut(100, function() {
            $(this).text(counts.active || 0).fadeIn(100);
        });
        $('.complete-count').fadeOut(100, function() {
            $(this).text(counts.complete || 0).fadeIn(100);
        });
        $('.cancel-count').fadeOut(100, function() {
            $(this).text(counts.cancel || 0).fadeIn(100);
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

    // IMPROVED Modified function to load shipment data for tomorrow's dispatch
    function loadShipmentData(status, showLoader = true, selectedDate = null) {
        if (showLoader) {
            $('#loaderBody').show();
        }

        // Destroy existing Tom Select instances before loading new data
        destroyAllVehicleDropdowns();

        // Use provided date or get from picker
        const dateToUse = selectedDate || $('#dispatch-date').val() || getTomorrowDate();

        $.ajax({
            url: `${APP_URL}/shipments/tomorrow/dispatch`,
            method: 'GET',
            data: {
                status: status,
                date: dateToUse
            },
            timeout: 10000,
            success: function (response) {
                $('#loaderBody').hide();
                $('#userTableBody').empty();

                if (response.vehicle) {
                    vehiclesData = response.vehicle;
                }

                // Update counts if returned from shipment data call
                if (response.tab_counts) {
                    updateTabCounts(response.tab_counts);
                }

                if (response.data && response.data.length > 0) {
                    let rows = '';
                    response.data.forEach(function (shipment, index) {
                        rows += buildTableRow(shipment, index + 1);
                    });
                    $('#userTableBody').html(rows);
                    
                    // Initialize dropdowns after DOM is ready
                    setTimeout(() => {
                        initializeAllVehicleDropdowns();
                    }, 100);
                } else {
                    const formattedDate = formatDisplayDate(dateToUse);
                    $('#userTableBody').html(`<tr><td colspan="11" class="text-center">No record found </td></tr>`);
                }
            },
            error: function (xhr, status, error) {
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
                <td>${formatDateTime(shipment.pickup_time)}    </td>
                <td>${formatDateTime(shipment.delivery_time)}</td>
                <td>
                    <select class="form-select status-dropdown" style="min-width:110px;" data-shipment-id="${shipment.id}" data-current-status="${shipment.status}">
                        <option value="pending" ${shipment.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="active" ${shipment.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="complete" ${shipment.status === 'complete' ? 'selected' : ''}>Complete</option>
                        <option value="cancel" ${shipment.status === 'cancel' ? 'selected' : ''}>Cancel</option>
                    </select>
                </td>
                <td>$${shipment.estimated_cost || '0.00'}</td>
               
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
                    dropdownParent: 'body',
                    onInitialize: function() {
                    },
                    onDropdownOpen: function() {
                    }
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
    function updateShipmentVehicle(shipmentId, vehicleId, driverId, driver) {
        $.ajax({
            url: `${APP_URL}/admin/shipments/${shipmentId}/assign-vehicle`,
            method: 'PUT',
            data: {
                vehicle_id: vehicleId,
                force_assign: true,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({ title: 'Success!', text: response.message || 'Vehicle assigned successfully!', icon: 'success', timer: 3000, showConfirmButton: false });
            },
            error: function(xhr, status, error) {
                console.error('Error assigning vehicle:', error);
                let errorMessage = xhr.responseJSON?.message || 'Error assigning vehicle. Please try again.';
                Swal.fire({ title: 'Error!', text: errorMessage, icon: 'error' });
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
                } catch (error) {
                    console.error(`Error destroying TomSelect for ${select.id}:`, error);
                }
            }
        });
    }

    // IMPROVED Status dropdown change handler
    $(document).on('change', '.status-dropdown', function () {
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

function updateShipmentStatus(shipmentId, newStatus, dropdown) {
    console.log('🚀 Next Day Dispatch - Sending status update...', {
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
        success: function (response) {
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

            // Update counts immediately with current date
            const currentDate = $('#dispatch-date').val() || getTomorrowDate();
            loadStatusCountsImmediate(currentDate).then(() => {
                // Refresh current tab data
                const currentTab = $('#shipmentTabs .nav-link.active');
                const currentStatus = currentTab.data('status');
                loadShipmentData(currentStatus, false, currentDate);
            });
        },
        error: function (xhr, status, error) {
            console.error('❌ Error response:', {
                status: xhr.status,
                responseJSON: xhr.responseJSON
            });
            
            // Revert dropdown to previous value
            dropdown.val(dropdown.data('current-status'));
            
            // Extract error message
            let errorMessage = 'Error updating status. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
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

    // Bulk operations handlers
    $('#selectAllCheckbox').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('.shipment-checkbox').prop('checked', isChecked);
        toggleBulkActions();
    });

    $(document).on('change', '.shipment-checkbox', function () {
        const total = $('.shipment-checkbox').length;
        const checked = $('.shipment-checkbox:checked').length;

        $('#selectAllCheckbox').prop('checked', total === checked);
        toggleBulkActions();
    });

    function toggleBulkActions() {
        const checkedCount = $('.shipment-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkActions').show();
        } else {
            $('#bulkActions').hide();
        }
    }

    $('#applyBulkUpdate').on('click', function () {
        const selectedIds = $('.shipment-checkbox:checked').map(function () {
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
            url: `${APP_URL}/shipments/bulk-update`,
            method: 'PUT',
            data: {
                shipment_ids: shipmentIds,
                status: newStatus,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showMessage(`Successfully updated ${shipmentIds.length} shipments`, 'success');
                
                // Update counts and reload data
                const currentDate = $('#dispatch-date').val() || getTomorrowDate();
                loadStatusCountsImmediate(currentDate).then(() => {
                    const currentTab = $('#shipmentTabs .nav-link.active');
                    const currentStatus = currentTab.data('status');
                    loadShipmentData(currentStatus, false, currentDate);
                });
                
                // Clear selections
                $('.shipment-checkbox').prop('checked', false);
                $('#selectAllCheckbox').prop('checked', false);
                toggleBulkActions();
            },
            error: function(xhr, status, error) {
                showMessage('Error updating shipments. Please try again.', 'error');
            }
        });
    }

    // Utility functions
    function getCurrentDate() {
        const today = new Date();
        return today.toISOString().split('T')[0];
    }

    function getTomorrowDate() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    }

function formatDateTime(datetimeString) {
  const date = new Date(datetimeString);
  
  // Extract UTC components (not local time)
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  
  let hours = date.getUTCHours();
  const minutes = String(date.getUTCMinutes()).padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  
  // Convert to 12-hour format
  hours = hours % 12;
  hours = hours || 12; // Convert 0 to 12
  
  return `${month}/${day}/${year} ${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
}

// Usage
const originalTime = "2025-08-19T10:01:00.000000Z";
// Output: "08/19/2025 10:01 AM" (exact UTC time)
    function formatDisplayDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
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

        setTimeout(function () {
            $('.alert-message').fadeOut();
        }, 5000);
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (window.countRefreshInterval) {
            clearInterval(window.countRefreshInterval);
        }
        destroyAllVehicleDropdowns();
    });
});
</script>

@endsection