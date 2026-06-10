<script>
    $(document).ready(function() {
        // REMOVED: Don't set default dates automatically
        // const today = new Date().toISOString().split('T')[0];
        // const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        // $('#start_date').val(thirtyDaysAgo);
        // $('#end_date').val(today);
        
        // Initialize with empty values
        $('#start_date').val('');
        $('#end_date').val('');
        
        updateDateRangeDisplay();

        $('#start_date, #end_date').change(function() {
            updateDateRangeDisplay();
        });

        function updateDateRangeDisplay() {
            const start = $('#start_date').val();
            const end = $('#end_date').val();
            if (start && end) {
                const startDate = new Date(start).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const endDate = new Date(end).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                $('#dateRangeDisplay').text(`${startDate} - ${endDate}`);
            } else {
                $('#dateRangeDisplay').text('Select Date Range');
            }
        }

        // Initialize driver select styling
        function initDriverSelect() {
            const driverSelect = $('#driver_id');
            const driverIcon = $('.driver-select-icon');
            
            // Change icon color on focus
            driverSelect.on('focus', function() {
                driverIcon.css('color', 'var(--hover-color)');
                $(this).addClass('active');
            }).on('blur', function() {
                $(this).removeClass('active');
            });
            
            // Change icon color when option is selected
            driverSelect.on('change', function() {
                if ($(this).val() !== '') {
                    driverIcon.css('color', 'var(--hover-color)');
                } else {
                    driverIcon.css('color', 'var(--search-placeholder)');
                }
            });
            
            // Initial icon color
            if (driverSelect.val() !== '') {
                driverIcon.css('color', 'var(--hover-color)');
            } else {
                driverIcon.css('color', 'var(--search-placeholder)');
            }
        }

        // Call initialization
        initDriverSelect();

        // Preview button click
        $('#previewBtn').click(function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const driverId = $('#driver_id').val();

            console.log('Form Data:', { 
                start_date: startDate, 
                end_date: endDate, 
                driver_id: driverId 
            });

            // Validate inputs
            let errors = [];
            
            // Reset all invalid classes first
            $('#start_date, #end_date, #driver_id').removeClass('is-invalid');
            
            // Validate Start Date
            if (!startDate || startDate.trim() === '') {
                errors.push('Start date is required');
                $('#start_date').addClass('is-invalid').focus();
            }
            
            // Validate End Date
            if (!endDate || endDate.trim() === '') {
                errors.push('End date is required');
                $('#end_date').addClass('is-invalid').focus();
            }
            
            // Validate Driver
            if (!driverId || driverId === '') {
                errors.push('Driver selection is required');
                $('#driver_id').addClass('is-invalid').focus();
            }
            
            // Validate date range (only if both dates exist)
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                    errors.push('Invalid date format');
                    $('#start_date').addClass('is-invalid');
                    $('#end_date').addClass('is-invalid');
                } else if (start > end) {
                    errors.push('Start date cannot be greater than end date');
                    $('#start_date').addClass('is-invalid');
                    $('#end_date').addClass('is-invalid');
                }
            }
            
            if (errors.length > 0) {
                showNotification(errors.join('<br>'), 'warning');
                return false; // Prevent form submission
            }

            // Prepare form data
            const formData = {
                start_date: startDate,
                end_date: endDate,
                driver_id: driverId,
                _token: '{{ csrf_token() }}'
            };

            // Show loading state
            showLoading(true);
            const $btn = $(this);
            $btn.prop('disabled', true);
            $btn.find('.button-loader').removeClass('d-none');

            // Fetch data via AJAX
            $.ajax({
                url: '{{ route("payroll-data.index") }}',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    showLoading(false);
                    $btn.prop('disabled', false);
                    $btn.find('.button-loader').addClass('d-none');

                    console.log('Response:', response);

                    if (response.success) {
                        showNotification('Data loaded successfully', 'success');
                        displayPreviewData(response.data);
                    } else {
                        showNotification(response.message || 'Failed to load data', 'error');
                        $('#previewSection').fadeOut(300);
                    }
                },
                error: function(xhr, status, error) {
                    showLoading(false);
                    $btn.prop('disabled', false);
                    $btn.find('.button-loader').addClass('d-none');
                    
                    let errorMessage = 'Error loading data';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join(', ');
                    } else if (xhr.status === 422) {
                        errorMessage = 'Validation failed. Please check your inputs.';
                    }
                    
                    showNotification(errorMessage, 'error');
                    console.error('AJAX Error:', xhr.status, error);
                    
                    // Hide preview section on error
                    $('#previewSection').fadeOut(300);
                }
            });
        });

        function showLoading(show) {
            if (show) {
                $('body').append(`
                    <div class="loading-overlay">
                        <div class="loading-content">
                            <div class="spinner-border" style="color: var(--primary-color); width: 3rem; height: 3rem;" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Loading payroll data...</p>
                        </div>
                    </div>
                `);
            } else {
                $('.loading-overlay').remove();
            }
        }

        function showNotification(message, type) {
            $('.alert-notification').remove();

            const alertClass = type === 'success' ? 'alert-success' :
                type === 'warning' ? 'alert-warning' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle';

            const notification = $(`
                <div class="alert ${alertClass} alert-notification alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow-sm" style="z-index: 1060; min-width: 300px;">
                    <div class="d-flex align-items-center">
                        <i class="fas ${icon} me-2"></i>
                        <div class="flex-grow-1">${message}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.alert('close');
            }, 5000);
        }

        function displayPreviewData(data) {
            console.log('Data received:', data);
            const tbody = $('#previewTableBody');
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                            No payroll records found for the selected date range and driver
                        </td>
                    </tr>
                `);
                $('#recordCount').text('0');
                $('#previewSection').fadeIn(300);
                return;
            }

            const item = data[0];
            const driver = item.driver;
            const period = item.period;
            const totals = item.totals;

            const row = `
                <tr class="fade-in payroll-table-row">
                    <!-- Driver Name -->
                    <td class="ps-3">
                        <div class="d-flex align-items-center">
                            <div class="driver-avatar rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 32px; height: 32px; font-size: 0.8em;">
                                <small class="text-white fw-bold">
                                    ${(driver?.firstname?.charAt(0) || '') + (driver?.lastname?.charAt(0) || '') || 'D'}
                                </small>
                            </div>
                            <div>
                                <div class="small fw-semibold">
                                    ${driver?.firstname || ''} ${driver?.lastname || ''}
                                </div>
                                <div class="x-small text-muted">
                                    ${driver?.email || 'No email'}
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Contact Number -->
                    <td class="fw-semibold">${driver?.phoneno || 'N/A'}</td>
                    
                    <!-- Start Date -->
                    <td class="small ">${formatDate(period.start_date)}</td>
                    
                    <!-- End Date -->
                    <td class="small ">${formatDate(period.end_date)}</td>
                    
                    <!-- Total Shipments -->
                    <td class="text-center">
                        <span class=" fw-semibold">${totals.total_shipments}</span>
                    </td>
                    
                    <!-- Total Miles -->
                    <td class="text-center fw-semibold miles-cell">${totals.total_miles.toFixed(2)} mi</td>
                    
                    <!-- Per Mile Rate -->
                    <td class="text-center fw-semibold rate-cell">$${totals.per_mile_rate.toFixed(2)}</td>
                    
                    <!-- Driver Earnings -->
                    <td class="text-end fw-semibold earnings-cell">$${totals.driver_earnings.toFixed(2)}</td>
                    
                    <!-- Actions -->
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn view-btn border" onclick="viewDriverDetails('${driver.id}', '${period.start_date}', '${period.end_date}')" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn pdf-btn border" onclick="downloadDriverPayrollPDF('${driver.id}', '${period.start_date}', '${period.end_date}')" title="Download PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);

            $('#recordCount').text('1');
            $('#previewSection').fadeIn(300);
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return dateString;
                }
                
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            } catch (error) {
                console.error('Date formatting error:', error);
                return dateString;
            }
        }

        // Reset button
        $('#resetBtn').click(function() {
            // Clear all inputs
            $('#start_date').val('');
            $('#end_date').val('');
            $('#driver_id').val('');
            
            updateDateRangeDisplay();
            $('#previewSection').fadeOut(300);
            
            // Reset icon color
            $('.driver-select-icon').css('color', 'var(--search-placeholder)');
            
            // Remove validation classes
            $('#start_date, #end_date, #driver_id').removeClass('is-invalid');
            
            showNotification('All filters have been cleared', 'info');
        });

        // Clear validation on input
        $('#start_date, #end_date, #driver_id').on('input change', function() {
            $(this).removeClass('is-invalid');
        });

        // View driver details function
        window.viewDriverDetails = function(driverId, startDate, endDate) {
            if (!driverId || !startDate || !endDate) {
                showNotification('Missing parameters for details view', 'warning');
                return;
            }
            const url = `{{ url('admin/driver-payroll') }}/${driverId}/details?start_date=${startDate}&end_date=${endDate}`;
            window.open(url, '_blank');
        };

        // Download driver payroll PDF function
        window.downloadDriverPayrollPDF = function(driverId, startDate, endDate) {
            if (!driverId || !startDate || !endDate) {
                showNotification('Missing parameters for PDF download', 'warning');
                return;
            }
            showNotification('Generating PDF...', 'info');
            const url = `{{ url('admin/driver-payroll') }}/${driverId}/pdf?start_date=${startDate}&end_date=${endDate}`;
            window.open(url, '_blank');
        };
    });
</script>