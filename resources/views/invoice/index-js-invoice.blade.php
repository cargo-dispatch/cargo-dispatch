<script src="{{ asset('assets/js/time-date-format.js') }}?v={{ time() }}"></script>
<script>

 function formatDateTime(dbDate) {
    if (!dbDate) return 'N/A';

    // Expected format: "2025-10-16T21:03:00.000000Z"
    const parts = dbDate.split('T');
    if (parts.length < 2) return dbDate;

    const datePart = parts[0]; // 2025-10-16
    const timePart = parts[1].replace('Z', '').split('.')[0]; // 21:03:00

    const [year, month, day] = datePart.split('-');
    let [hour, minute, second] = timePart.split(':');

    // Convert 24-hour to 12-hour manually
    let ampm = 'AM';
    hour = parseInt(hour);
    if (hour >= 12) {
        ampm = 'PM';
        if (hour > 12) hour -= 12;
    }
    if (hour === 0) hour = 12;

    // Convert month number to short name
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthName = monthNames[parseInt(month) - 1];

    return `${monthName} ${day}, ${year}, ${String(hour).padStart(2, '0')}:${minute} ${ampm}`;
}
    
    $(document).ready(function() {
        // Set default dates (last 30 days)
        const today = new Date().toISOString().split('T')[0];
        const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        $('#start_date').val(thirtyDaysAgo);
        $('#end_date').val(today);
        updateDateRangeDisplay();

        // Update date range display
        $('#start_date, #end_date').change(function() {
            updateDateRangeDisplay();
        });

        function updateDateRangeDisplay() {
            const start = $('#start_date').val();
            const end = $('#end_date').val();
            if (start && end) {
                const startDate = new Date(start).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
                const endDate = new Date(end).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
                $('#dateRangeDisplay').text(`${startDate} - ${endDate}`);
            }
        }

        // Preview button click
        $('#previewBtn').click(function() {
            const formData = {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                status: $('#status').val(),
                customer_id: $('#customer_id').val(),
                _token: '{{ csrf_token() }}'
            };

            if (!formData.start_date || !formData.end_date) {
                showNotification('Please select both start and end dates', 'warning');
                return;
            }

            // Show loading state
            showLoading(true);
            $(this).prop('disabled', true);
            $(this).find('.button-loader').removeClass('d-none');

            // Fetch data
            $.ajax({
                url: '{{ route("shipments-invoice.data") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    showLoading(false);
                    $('#previewBtn').prop('disabled', false);
                    $('#previewBtn .button-loader').addClass('d-none');

                    if (response.success) {
                        showNotification('Data loaded successfully', 'success');
                        displayPreviewData(response.data);
                        updateStats(response.data);
                    } else {
                        showNotification('Failed to load data', 'error');
                    }
                },
                error: function(xhr) {
                    showLoading(false);
                    $('#previewBtn').prop('disabled', false);
                    $('#previewBtn .button-loader').addClass('d-none');
                    showNotification('Error loading data', 'error');
                }
            });
        });

        function showLoading(show) {
            if (show) {
                $('body').append(`
            <div class="loading-overlay">
                <div class="loading-content">
                    <div class="spinner-border" style="color: rgb(0, 120, 248); width: 3rem; height: 3rem;" role="status"></div>
                    <p class="mt-2 text-muted mb-0">Loading invoice data...</p>
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
                    <span class="flex-grow-1">${message}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        `);

            $('body').append(notification);

            setTimeout(() => {
                notification.alert('close');
            }, 4000);
        }

      function updateStats(data) {
    $('#statsSection').fadeIn(300);

    const totalShipments = data.length;
    let totalRevenue = 0;
    let totalCost = 0;

    data.forEach(shipment => {
        const invoice = shipment.shipment_invoice?.[0]; // âœ… fix

        if (!invoice) return;

        totalRevenue += parseFloat(invoice.total_with_profit);
        totalCost += parseFloat(invoice.total_cost);
    });

    const profitMargin = totalRevenue > 0 ? ((totalRevenue - totalCost) / totalRevenue * 100) : 0;

    animateValue('totalShipments', 0, totalShipments, 800);
    animateValue('totalRevenue', 0, totalRevenue, 800, true);
    animateValue('totalCost', 0, totalCost, 800, true);
    animateValue('profitMargin', 0, profitMargin, 800, false, '%');
}


        function animateValue(id, start, end, duration, isCurrency = false, suffix = '') {
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);

                if (isCurrency) {
                    obj.innerHTML = '$' + value.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else if (suffix === '%') {
                    obj.innerHTML = value.toFixed(1) + suffix;
                } else {
                    obj.innerHTML = value.toLocaleString();
                }

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        function displayPreviewData(data) {
            console.log('dd', data);
            const tbody = $('#previewTableBody');
            tbody.empty();

            if (data.length === 0) {
                tbody.html(`
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                        No records found for the selected filters
                    </td>
                </tr>
            `);
                $('#recordCount').text('0');
                $('#grandTotalCost').text('$0.00');
                $('#grandTotalAmount').text('$0.00');
                $('#previewSection').fadeIn(300);
                return;
            }

            let totalCost = 0;
            let totalAmount = 0;

       data.forEach(function (shipment, index) {
    const invoice = shipment.shipment_invoice?.[0]; // âœ… fix

    if (!invoice) return;

    totalCost += parseFloat(invoice.total_cost);
    totalAmount += parseFloat(invoice.total_with_profit);

    const row = `
        <tr class="fade-in">
            <td class="ps-3 fw-semibold text-dark">${String(shipment.id).padStart(7, '0')}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2"
                        style="width: 24px; height: 24px; font-size: 0.7em;">
                        <small class="text-muted fw-bold">
                            ${(shipment.customer?.first_name?.charAt(0) || '') + 
                              (shipment.customer?.last_name?.charAt(0) || '') || 'N'}
                        </small>
                    </div>
                    <span class="small">
                        ${truncate(`${shipment.customer?.first_name || ''} ${shipment.customer?.last_name || ''}`.trim() || 'N/A', 20)}
                    </span>
                </div>
            </td>
            <td class="small text-muted">${formatDateTime(shipment.pickup_time)}</td>
            <td class="small text-muted">${formatDateTime(shipment.delivery_time)}</td>
            <td>
                <div class="small">
                    <div class="text-truncate" style="max-width: 120px;" title="${shipment.pickup_address || 'N/A'}">
                        <i class="fas fa-map-marker-alt text-danger me-1" style="font-size: 0.7em;"></i>
                        ${truncate(shipment.pickup_address, 18)}
                    </div>
                    <div class="text-truncate" style="max-width: 120px;" title="${shipment.drop_address || 'N/A'}">
                        <i class="fas fa-arrow-right text-success me-1" style="font-size: 0.7em;"></i>
                        ${truncate(shipment.drop_address, 18)}
                    </div>
                </div>
            </td>
            <td class="small text-muted">${shipment.distance_miles || '0'} mi</td>
            <td>
                <span class="invoice-status-badge ${getStatusClass(shipment.status)} style="padding:0px !important">
                    ${getStatusIcon(shipment.status)} ${shipment.status || 'N/A'}
                </span>
            </td>
            <td class="text-end small text-muted">$${parseFloat(invoice.total_cost).toFixed(2)}</td>
            <td class="text-end fw-semibold text-success">$${parseFloat(invoice.total_with_profit).toFixed(2)}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <a href="{{ url('admin/shipments/invoice') }}/${shipment.id}/preview" 
                       class="btn btn-outline-primary btn-sm border" target="_blank" title="Preview">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ url('admin/shipments/invoice') }}/${shipment.id}/download" 
                       class="btn btn-outline-danger btn-sm border" title="Download PDF">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                </div>
            </td>
        </tr>
    `;
    tbody.append(row);
});


            $('#recordCount').text(data.length.toLocaleString());
            $('#grandTotalCost').text('$' + totalCost.toFixed(2));
            $('#grandTotalAmount').text('$' + totalAmount.toFixed(2));

            $('#previewSection').fadeIn(300);
        }

        function truncate(str, len) {
            if (!str) return 'N/A';
            return str.length > len ? str.substring(0, len) + '...' : str;
        }

        function getStatusClass(status) {
            const classes = {
                'pending': 'bg-warning text-dark',
                'complete': 'bg-info text-white',
                'active': 'bg-success text-white',
                'cancel': 'bg-danger text-white'
            };
            return classes[status] || 'bg-secondary text-white';
        }

        function getStatusIcon(status) {
            const icons = {
                'pending': '<i class="fas fa-clock me-1"></i>',
                'active': '<i class="fas fa-truck me-1"></i>',
                'complete': '<i class="fas fa-check me-1"></i>',
                'cancelled': '<i class="fas fa-times me-1"></i>'
            };
            return icons[status] || '<i class="fas fa-question me-1"></i>';
        }

        // Export CSV functionality
        $('#exportCsv').click(function() {
            const table = $('#previewTable');
            const headers = [];
            const data = [];

            // Get headers (skip Actions column)
            table.find('thead th').each(function() {
                if ($(this).text() !== 'ACTIONS') {
                    headers.push($(this).text());
                }
            });

            // Get data (skip Actions column)
            table.find('tbody tr').each(function() {
                const row = [];
                $(this).find('td').each(function(index) {
                    if (index !== 9) { // Skip actions column (10th column)
                        row.push($(this).text().trim().replace(/\$/g, ''));
                    }
                });
                data.push(row);
            });

            // Create CSV content
            let csvContent = headers.join(',') + '\n';
            data.forEach(row => {
                csvContent += row.join(',') + '\n';
            });

            // Download CSV
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `invoice-report-${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('CSV file downloaded successfully', 'success');
        });

        // Reset button
        $('#resetBtn').click(function() {
            $('#start_date').val(thirtyDaysAgo);
            $('#end_date').val(today);
            $('#status').val('');
            $('#customer_id').val('');
            updateDateRangeDisplay();
            $('#previewSection').fadeOut(300);
            $('#statsSection').fadeOut(300);
            showNotification('Filters reset to default', 'info');
        });
    });
</script>