<script>


    $(document).ready(function() {
    let currentPage = 1;
    let currentPerPage = 10;
    let currentFilters = {};
    let totalPages = 1; // Initialize totalPages

    // Initialize form submission
    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });

    // Per page change
    $('#perPageSelect').on('change', function() {
        currentPerPage = $(this).val();
        currentPage = 1; // Reset to first page when changing per page
        if (Object.keys(currentFilters).length > 0) {
            loadReportData(currentFilters, currentPage, currentPerPage);
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val();
        currentPage = 1; // Reset to first page when searching
        if (Object.keys(currentFilters).length > 0) {
            loadReportData({...currentFilters, search: searchTerm}, currentPage, currentPerPage);
        }
    });

    // Generate report
    function generateReport() {
        const formData = $('#reportFilterForm').serialize();
        const filters = Object.fromEntries(new URLSearchParams(formData));
        
        // Remove empty values
        currentFilters = Object.fromEntries(
            Object.entries(filters).filter(([_, v]) => v !== '')
        );

        currentPage = 1;
        loadReportData(currentFilters, currentPage, currentPerPage);
    }

    // Load report data
    function loadReportData(filters, page = 1, perPage = 10) {
        showLoader();
        
        $.ajax({
            url: "{{ route('shipments.report.data') }}",
            method: 'GET',
            data: {
                ...filters,
                page: page,
                per_page: perPage
            },
            success: function(response) {
                hideLoader();
                totalPages = response.last_page; // Update totalPages
                populateTable(response.data);
                updatePagination(response);
                updateReportTitle(filters);
                $('#reportResults').show();
                
                // Update current page and per page values
                currentPage = response.current_page;
                currentPerPage = response.per_page;
                
                // Update the per page selector to reflect current value
                $('#perPageSelect').val(currentPerPage);
            },
            error: function(xhr, status, error) {
                hideLoader();
                console.error('Error loading report data:', error);
                alert('Error loading report data. Please try again.');
            }
        });
    }

    // Populate table with data
    function populateTable(shipments) {
        const tbody = $('#reportTableBody');
        tbody.empty();

        if (!shipments || shipments.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No shipments found matching your criteria.</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        shipments.forEach(shipment => {
            const row = createTableRow(shipment);
            tbody.append(row);
        });

        initializeDeleteButtons(); // Important to reattach delete event
    }
    
    // Create table row
    function createTableRow(shipment) {
     
        const statusBadge = getStatusBadge(shipment.status);
       const pickupTime = shipment.pickup_time ? formatDateTime(shipment.pickup_time) : 'N/A';
console.log('timeeeeee', pickupTime);
const deliveryTime = shipment.delivery_time ? formatDateTime(shipment.delivery_time) : 'N/A';
        const cost = shipment.estimated_cost ? '$' + parseFloat(shipment.estimated_cost).toFixed(2) : '$0.00';

        return `
            <tr>
                <td><input type="checkbox" class="shipment-checkbox" value="${shipment.id}"></td>
                <td>${shipment.customer?.customer_title || 'N/A'}</td>
                <td>${shipment.vehicle_type?.vehicle_type || 'N/A'}</td>
                <td>${shipment.pickup_address || 'N/A'}</td>
                <td>${shipment.drop_address || 'N/A'}</td>
                <td>${pickupTime} </td>
                <td>${deliveryTime}</td>
                <td>${statusBadge}</td>
                <td>${cost}</td>
            </tr>
        `;
    }

    // Get status badge
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">Pending</span>',
            'active': '<span class="badge bg-primary">Active</span>',
            'complete': '<span class="badge bg-success">Complete</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    // Update pagination
    function updatePagination(response) {
        const paginationContainer = $('#pagination');
        paginationContainer.empty();

        if (response.last_page > 1) {
            let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
            
            // Previous button - disabled if on first page
            paginationHtml += `
                <li class="page-item ${response.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${response.current_page - 1}" ${response.current_page === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>
                        &laquo; Previous
                    </a>
                </li>`;
            
            // Always show first page
            if (response.current_page > 3) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>`;
                if (response.current_page > 4) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            // Page numbers around current page
            const startPage = Math.max(1, response.current_page - 2);
            const endPage = Math.min(response.last_page, response.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === response.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
            }
            
            // Always show last page
            if (response.current_page < response.last_page - 2) {
                if (response.current_page < response.last_page - 3) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${response.last_page}">${response.last_page}</a>
                    </li>`;
            }
            
            // Next button - disabled if on last page
            paginationHtml += `
                <li class="page-item ${response.current_page === response.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${response.current_page + 1}" ${response.current_page === response.last_page ? 'tabindex="-1" aria-disabled="true"' : ''}>
                        Next &raquo;
                    </a>
                </li>`;
            
            paginationHtml += '</ul></nav>';
            paginationContainer.html(paginationHtml);
            
            // Attach click events to pagination links
            attachPaginationEvents();
        }
    }

    // Attach pagination events
    function attachPaginationEvents() {
        $('#pagination .page-link').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                changePage(page);
            }
            return false;
        });
    }

    // Change page function
    function changePage(page) {
        if (!page || page < 1 || page > totalPages) {
            return false;
        }
        
        currentPage = page;
        loadReportData(currentFilters, currentPage, currentPerPage);
        return false;
    }

    // Make changePage available globally (for backward compatibility)
    window.changePage = changePage;

    // Update report title
 function updateReportTitle(filters) {
    let title = 'Shipment Report';

    // Helper to format date: YYYY-MM-DD → Mon DD, YYYY
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    if (filters.start_date && filters.end_date) {
        const start = formatDate(filters.start_date);
        const end = formatDate(filters.end_date);
        title += ` (${start} to ${end})`;
    }

    $('#reportTitle').text(title);
}


    // Show/hide loader
    function showLoader() {
        $('#reportTableBody').hide();
        $('#loaderBody').show();
    }

    function hideLoader() {
        $('#loaderBody').hide();
        $('#reportTableBody').show();
    }

    // Reset filters
    window.resetFilters = function() {
        $('#reportFilterForm')[0].reset();
        $('#start_date').val(new Date().toISOString().slice(0, 7) + '-01');
        $('#end_date').val(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().slice(0, 10));
        $('#reportResults').hide();
        currentFilters = {};
        currentPage = 1;
        currentPerPage = 10;
        $('#perPageSelect').val(10);
    };

    // Download report
    window.downloadReport = function(format) {
        if (Object.keys(currentFilters).length === 0) {
            alert('Please generate a report first.');
            return;
        }

        // Convert filters to URLSearchParams
        const params = new URLSearchParams();
        
        // Add all filters to params
        for (const [key, value] of Object.entries(currentFilters)) {
            params.append(key, value);
        }
        
        params.append('format', format);
        
        // Use the Laravel route helper for the download URL
        window.open(`{{ route('shipments.report.download') }}?${params.toString()}`, '_blank');
    };

    // Initialize delete buttons
    function initializeDeleteButtons() {
        $('.delete-button').off('click').on('click', function() {
            const shipmentId = $(this).data('id');
            if (confirm('Are you sure you want to delete this shipment?')) {
                deleteShipment(shipmentId);
            }
        });
    }

    // Delete shipment
    function deleteShipment(shipmentId) {
        $.ajax({
            url: `/shipments/${shipmentId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('Shipment deleted successfully.');
                loadReportData(currentFilters, currentPage, currentPerPage);
            },
            error: function(xhr, status, error) {
                console.error('Error deleting shipment:', error);
                alert('Error deleting shipment. Please try again.');
            }
        });
    }

    // Select all checkboxes
    $('#selectAllCheckbox').on('change', function() {
        $('.shipment-checkbox').prop('checked', this.checked);
    });

    // Bulk delete
    $('#bulkDeleteBtn').on('click', function() {
        const selectedIds = $('.shipment-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Please select at least one shipment to delete.');
            return;
        }

        if (confirm(`Are you sure you want to delete ${selectedIds.length} shipment(s)?`)) {
            bulkDeleteShipments(selectedIds);
        }
    });

    function formatDateTime(dbDate) {
    if (!dbDate) return 'N/A';
    
    // Extract date and time parts directly from string
    // Expected format: "2025-10-16T21:03:00.000000Z" or "2025-10-16 21:03:00"
    
    // Handle both formats
    const dateTimeStr = dbDate.replace('T', ' ').split('.')[0].replace('Z', '');
    const [datePart, timePart] = dateTimeStr.split(' ');
    
    if (!datePart || !timePart) return 'N/A';
    
    const [year, month, day] = datePart.split('-');
    const [hour24, minute] = timePart.split(':');
    
    // Convert to 12-hour format
    let hour = parseInt(hour24);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12; // Convert 0 to 12, and 13-23 to 1-11
    
    // Month names
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthName = monthNames[parseInt(month) - 1];
    
    return `${monthName} ${day}, ${year}, ${String(hour).padStart(2, '0')}:${minute} ${ampm}`;
}


    // Bulk delete shipments
    function bulkDeleteShipments(ids) {
        $.ajax({
            url: '/shipments/bulk-destroy',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                ids: ids
            },
            success: function(response) {
                alert(response.message);
                loadReportData(currentFilters, currentPage, currentPerPage);
                $('#selectAllCheckbox').prop('checked', false);
            },
            error: function(xhr, status, error) {
                console.error('Error bulk deleting shipments:', error);
                alert('Error deleting shipments. Please try again.');
            }
        });
    }
});
</script>