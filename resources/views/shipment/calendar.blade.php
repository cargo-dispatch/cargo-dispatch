@extends('layouts.app')

@section('title', 'Shipment Calendar')

@section('content')
    <div class="container-fluid container-sm-custom">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header sidebar-wrapper bg-gradient-primary text-white">
                        <h4 class="mb-0 fs-11">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Shipment Calendar
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Calendar Filters -->
                        <div class="calendar-filters mb-4">
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6 col-12">
                                    <label class="form-label">Status Filter:</label>
                                    <select class="form-control sidebar-wrapper" id="statusFilter">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="active">Active</option>
                                        <option value="complete">Complete</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 col-12">
                                    <label class="form-label">Customer:</label>
                                    <select class="form-select" id="customerFilter">
                                        <option value="">All Customers</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 col-12">
                                    <label class="form-label">Shipment Type:</label>
                                    <select class="form-control sidebar-wrapper" id="eventTypeFilter">
                                        <option value="">All Shipment</option>
                                        <option value="pickup">Pickup Only</option>
                                        <option value="delivery">Delivery Only</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 col-12 d-flex align-items-end calendar-buttons">
                                    <div class="d-flex flex-wrap gap-2 w-100">
                                        <button class="btn mbl-btn btn-secondary flex-grow-1" id="clearFilters">
                                            <i class="fas fa-refresh me-1"></i>Clear
                                        </button>
                                        <button class="btn mbl-btn theme-btn flex-grow-1" id="refreshCalendar">
                                            <i class="fas fa-sync me-1"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Legend -->
                        <div class="calendar-legend mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-md-start">
                                        <!-- Add legend items here if needed -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Wrapper with scroll -->
                        <div class="calendar-scroll-wrapper" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                            <div id="calendar" style="min-width: 800px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipment Details Modal -->
    <div class="modal fade" id="shipmentModal" tabindex="-1" aria-labelledby="shipmentModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shipmentModalLabel">Shipment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="shipmentDetailsContent">
                        <div class="text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeModalBtn">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css" rel="stylesheet">
    <style>
        /* Fix calendar scroll when sidebar is open */
        .calendar-wrapper {
            overflow-x: auto !important;
            overflow-y: visible;
            width: 100%;
            max-width: 100%;
            position: relative;
            -webkit-overflow-scrolling: touch;
            display: block;
            background: #fff;
            border-radius: 4px;
        }

        #calendar {
            width: 100% !important;
            min-width: 100% !important;
        }

        /* Force the calendar to be wide enough to show all days */
        .fc {
            width: 100% !important;
        }

        .fc-view-harness {
            width: 100% !important;
        }

        /* Prevent body scroll issues */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }

        /* Force calendar table to be scrollable */
        .fc-scrollgrid {
            width: 100% !important;
            min-width: 100% !important;
        }

        .fc-scrollgrid-sync-table {
            width: 100% !important;
            min-width: 100% !important;
        }

        /* Make sure all day columns show */
        .fc-col-header,
        .fc-daygrid-body {
            width: 100% !important;
        }

        .fc-col-header table,
        .fc-scrollgrid-sync-table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        /* Individual day cells */
        .fc-col-header-cell,
        .fc-daygrid-day {
            width: 14.28571% !important; /* 100% / 7 days */
            min-width: 80px !important;
        }

        .fc-col-header-cell-cushion {
            display: block !important;
            width: 100%;
            text-align: center;
        }

        /* Make calendar wider on smaller screens to force scroll */
        @media (max-width: 1400px) {
            .calendar-wrapper {
                overflow-x: auto !important;
                border: 1px solid #dee2e6;
            }

            #calendar {
                min-width: 900px !important;
            }

            .fc-scrollgrid,
            .fc-scrollgrid-sync-table,
            .fc-col-header table,
            .fc-daygrid-body table {
                min-width: 900px !important;
            }

            .fc-col-header-cell,
            .fc-daygrid-day {
                min-width: 128px !important;
            }
        }

        @media (max-width: 1200px) {
            #calendar {
                min-width: 850px !important;
            }

            .fc-scrollgrid,
            .fc-scrollgrid-sync-table,
            .fc-col-header table,
            .fc-daygrid-body table {
                min-width: 850px !important;
            }

            .fc-col-header-cell,
            .fc-daygrid-day {
                min-width: 121px !important;
            }
        }

        @media (max-width: 991px) {
            #calendar {
                min-width: 800px !important;
            }

            .fc-scrollgrid,
            .fc-scrollgrid-sync-table,
            .fc-col-header table,
            .fc-daygrid-body table {
                min-width: 800px !important;
            }

            .fc-col-header-cell,
            .fc-daygrid-day {
                min-width: 114px !important;
            }
        }

        @media (max-width: 768px) {
            #calendar {
                min-width: 700px !important;
            }

            .fc-scrollgrid,
            .fc-scrollgrid-sync-table,
            .fc-col-header table,
            .fc-daygrid-body table {
                min-width: 700px !important;
            }

            .fc-col-header-cell,
            .fc-daygrid-day {
                min-width: 100px !important;
            }

            .calendar-wrapper {
                border: 2px solid #007bff;
                box-shadow: inset 0 0 0 1px rgba(0,123,255,0.1);
            }
        }

        @media (max-width: 576px) {
            #calendar {
                min-width: 650px !important;
            }

            .fc-scrollgrid,
            .fc-scrollgrid-sync-table,
            .fc-col-header table,
            .fc-daygrid-body table {
                min-width: 650px !important;
            }

            .fc-col-header-cell,
            .fc-daygrid-day {
                min-width: 92px !important;
            }
        }

        /* Dark theme for calendar wrapper */
        [data-theme="dark"] .calendar-wrapper,
        .dark-mode .calendar-wrapper {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .calendar-wrapper,
        .dark-mode .calendar-wrapper {
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Base Calendar Styles */
        .fc-event {
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            padding: 2px 6px;
            margin-bottom: 2px;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }

        .fc-event-pickup {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }

        .fc-event-delivery {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: white !important;
        }

        .fc-event-pending {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #212529 !important;
        }

        .fc-event-active {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        .fc-event-complete {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }

        /* Better event title visibility */
        .fc-event-title,
        .fc-event-time {
            color: inherit !important;
            font-weight: 500;
        }

        .fc-daygrid-event {
            white-space: normal !important;
            align-items: flex-start;
        }

        .fc-daygrid-event-dot {
            display: none;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .shipment-details {
            max-height: 400px;
            overflow-y: auto;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-active {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-complete {
            background-color: #e2e6ea;
            color: #495057;
        }

        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        /* Modal close button fix */
        .btn-close {
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: 0.25rem;
            opacity: 0.5;
            padding: 0.5rem;
            width: 1em;
            height: 1em;
        }

        .btn-close:hover {
            opacity: 0.75;
        }

        .btn-close:focus {
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            opacity: 1;
        }

        /* Modal improvements */
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
        }

        /* Customer Filter Styles */
        #customerFilter option {
            background-color: #f0f0f0;
            color: #333;
        }

        #customerFilter option:hover,
        #customerFilter option:focus,
        #customerFilter option:checked {
            background-color: #e0e0e0;
            color: #000;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            /* Calendar Toolbar */
            .fc-toolbar {
                flex-direction: column !important;
                gap: 10px;
                padding: 10px 5px !important;
            }

            .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                width: 100%;
            }

            .fc-toolbar-title {
                font-size: 1.1rem !important;
                text-align: center;
            }

            /* Calendar buttons */
            .fc-button {
                padding: 6px 10px !important;
                font-size: 0.85rem !important;
            }

            .fc-button-group {
                flex-wrap: wrap;
            }

            /* Calendar day grid */
            .fc-col-header-cell {
                font-size: 0.75rem !important;
                padding: 4px 2px !important;
                min-width: 85px !important;
            }

            .fc-daygrid-day-number {
                font-size: 0.85rem !important;
                padding: 4px !important;
            }

            .fc-daygrid-day-frame {
                min-height: 80px !important;
            }

            .fc-daygrid-day {
                min-width: 85px !important;
            }

            /* Show all day abbreviations */
            .fc-col-header-cell-cushion {
                display: block !important;
                padding: 4px 2px !important;
            }

            /* Calendar events - better visibility */
            .fc-event {
                font-size: 0.75rem !important;
                padding: 3px 5px !important;
                margin-bottom: 2px !important;
                line-height: 1.3;
                min-height: 22px;
            }

            .fc-event-title {
                font-size: 0.75rem !important;
                display: block;
            }

            .fc-event-time {
                font-size: 0.7rem !important;
                display: block;
            }

            .fc-daygrid-event-harness {
                margin-top: 2px !important;
            }

            /* More events link */
            .fc-daygrid-more-link {
                font-size: 0.7rem !important;
                padding: 2px 4px;
            }

            /* Filter buttons */
            .calendar-buttons .btn {
                font-size: 0.85rem;
                padding: 8px 12px;
            }

            /* Modal adjustments */
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-body {
                padding: 1rem;
                font-size: 0.9rem;
                max-height: 70vh;
                overflow-y: auto;
            }

            .modal-body h6 {
                font-size: 1rem;
                margin-top: 1rem;
                margin-bottom: 0.75rem;
            }

            .modal-body p {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }

            .modal-header {
                padding: 0.75rem 1rem;
            }

            .modal-footer {
                padding: 0.75rem 1rem;
            }

            .modal-title {
                font-size: 1.1rem;
            }

            /* Card body padding */
            .card-body {
                padding: 1rem;
            }

            /* Filter labels */
            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
            }

            /* Select dropdowns */
            .form-control,
            .form-select {
                font-size: 0.9rem;
            }

            /* Status badge in modal */
            .status-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }

            /* Calendar wrapper scroll */
            .calendar-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        @media (max-width: 576px) {
            /* Extra small devices */
            .fc-toolbar-title {
                font-size: 0.95rem !important;
            }

            .fc-button {
                padding: 5px 8px !important;
                font-size: 0.75rem !important;
            }

            .fc-col-header-cell {
                font-size: 0.65rem !important;
                padding: 2px 1px !important;
            }

            .fc-daygrid-day-number {
                font-size: 0.75rem !important;
            }

            .fc-daygrid-day-frame {
                min-height: 70px !important;
            }

            .fc-event {
                font-size: 0.7rem !important;
                padding: 2px 4px !important;
            }

            /* Stack filter buttons */
            .calendar-buttons {
                flex-direction: column;
            }

            .calendar-buttons .d-flex {
                flex-direction: column;
            }

            .calendar-buttons .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            /* Card header */
            .card-header h4 {
                font-size: 1rem;
            }

            .card-header i {
                font-size: 0.9rem;
            }

            /* Modal on very small screens */
            .modal-dialog {
                margin: 0.25rem;
                max-width: calc(100% - 0.5rem);
            }

            .modal-body {
                padding: 0.75rem;
            }

            .btn-close {
                padding: 0.25rem;
            }
        }

        /* Dark theme compatibility */
        [data-theme="dark"] .fc,
        .dark-mode .fc {
            --fc-border-color: rgba(255, 255, 255, 0.1);
            --fc-button-bg-color: #374151;
            --fc-button-border-color: #4b5563;
            --fc-button-hover-bg-color: #4b5563;
            --fc-button-hover-border-color: #6b7280;
            --fc-button-active-bg-color: #6b7280;
            --fc-button-active-border-color: #9ca3af;
            --fc-page-bg-color: transparent;
            --fc-neutral-bg-color: rgba(255, 255, 255, 0.05);
            --fc-neutral-text-color: #e5e7eb;
            --fc-today-bg-color: rgba(59, 130, 246, 0.1);
        }

        [data-theme="dark"] .fc-theme-standard td,
        [data-theme="dark"] .fc-theme-standard th,
        .dark-mode .fc-theme-standard td,
        .dark-mode .fc-theme-standard th {
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .fc-day-today,
        .dark-mode .fc-day-today {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }

        [data-theme="dark"] .fc-daygrid-day-number,
        [data-theme="dark"] .fc-col-header-cell-cushion,
        .dark-mode .fc-daygrid-day-number,
        .dark-mode .fc-col-header-cell-cushion {
            color: #e5e7eb;
        }

        [data-theme="dark"] .modal-content,
        .dark-mode .modal-content {
            background-color: #1f2937;
            color: #e5e7eb;
        }

        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer,
        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .btn-close,
        .dark-mode .btn-close {
            filter: invert(1);
        }

        [data-theme="dark"] .status-pending,
        .dark-mode .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        [data-theme="dark"] .status-active,
        .dark-mode .status-active {
            background-color: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }

        [data-theme="dark"] .status-complete,
        .dark-mode .status-complete {
            background-color: rgba(108, 117, 125, 0.2);
            color: #adb5bd;
        }

        /* Landscape orientation optimizations */
        @media (max-width: 768px) and (orientation: landscape) {
            .fc-daygrid-day-frame {
                min-height: 60px !important;
            }

            .fc-toolbar {
                flex-direction: row !important;
                flex-wrap: wrap;
            }

            .fc-toolbar-chunk {
                width: auto;
            }

            .modal-body {
                max-height: 60vh;
            }
        }

        /* Touch optimization */
        @media (hover: none) and (pointer: coarse) {
            .fc-event {
                min-height: 32px;
                touch-action: manipulation;
            }

            .fc-button {
                min-height: 38px;
                min-width: 38px;
            }

            .fc-daygrid-day-frame {
                cursor: pointer;
            }

            .btn-close {
                min-width: 44px;
                min-height: 44px;
            }

            .modal-footer .btn {
                min-height: 44px;
                padding: 0.5rem 1rem;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
    <script>
        let calendar;
        let allEvents = [];
        let currentShipmentId = null;
        let shipmentModal = null;

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');

            // Initialize modal
            const modalElement = document.getElementById('shipmentModal');
            shipmentModal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });

            // Determine initial view based on screen size
            const isMobile = window.innerWidth < 768;
            const initialView = isMobile ? 'timeGridDay' : 'dayGridMonth';

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: initialView,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: isMobile ? 'dayGridMonth,timeGridDay' : 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [],
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    showShipmentDetails(info.event.extendedProps.shipmentId);
                },
                height: 'auto',
                contentHeight: isMobile ? 400 : 'auto',
                aspectRatio: isMobile ? 1.2 : 1.35,
                handleWindowResize: true,
                windowResizeDelay: 100,
                stickyHeaderDates: false,
                eventDisplay: 'block',
                dayMaxEvents: isMobile ? 2 : 3,
                dayHeaderFormat: {
                    weekday: 'short'
                },
                eventDidMount: function (info) {
                    info.el.setAttribute('title', info.event.title);
                    // Ensure event colors are applied
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    // Add classes for styling
                    if (props.eventType) {
                        info.el.classList.add(`fc-event-${props.eventType}`);
                    }
                    if (props.status) {
                        info.el.classList.add(`fc-event-${props.status}`);
                    }
                },
                viewDidMount: function(view) {
                    // Force proper table layout after view loads
                    setTimeout(() => {
                        const calendarElement = document.getElementById('calendar');
                        const tables = calendarElement.querySelectorAll('table');
                        tables.forEach(table => {
                            table.style.width = '100%';
                            table.style.minWidth = '100%';
                            table.style.tableLayout = 'fixed';
                        });

                        // Log to verify all columns
                        const headerCells = calendarElement.querySelectorAll('.fc-col-header-cell');
                        console.log(`View mounted with ${headerCells.length} header cells`);
                    }, 50);
                },
                datesSet: function (info) {
                    const start = formatDateWithoutTimezone(info.start);
                    const end = formatDateWithoutTimezone(info.end);
                    loadCalendarData(start, end);
                    
                    // Force calendar to recalculate size after data loads
                    setTimeout(() => {
                        calendar.updateSize();
                        
                        // Ensure all tables and columns are properly sized
                        const calendarElement = document.getElementById('calendar');
                        const tables = calendarElement.querySelectorAll('table');
                        tables.forEach(table => {
                            table.style.width = '100%';
                            table.style.minWidth = '100%';
                            table.style.tableLayout = 'fixed';
                        });

                        // Force column widths
                        const headerCells = calendarElement.querySelectorAll('.fc-col-header-cell');
                        const dayCells = calendarElement.querySelectorAll('.fc-daygrid-day');
                        
                        headerCells.forEach(cell => {
                            cell.style.width = '14.28571%';
                        });
                        
                        dayCells.forEach(cell => {
                            cell.style.width = '14.28571%';
                        });

                        console.log(`DatesSet: ${headerCells.length} headers, ${dayCells.length} day cells`);
                    }, 100);
                },
                // Mobile-specific settings
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                slotLabelFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                // Improve mobile touch handling
                longPressDelay: 500,
                eventLongPressDelay: 500,
                selectLongPressDelay: 500,
                // Ensure all days are visible
                fixedWeekCount: false,
                showNonCurrentDates: true,
                // Force day headers to show
                dayHeaders: true
            });

            calendar.render();

            // Force calendar to render properly after initial load
            setTimeout(() => {
                calendar.updateSize();
                
                // Ensure tables have proper structure
                const calendarElement = document.getElementById('calendar');
                const tables = calendarElement.querySelectorAll('table');
                tables.forEach(table => {
                    table.style.width = '100%';
                    table.style.minWidth = '100%';
                    table.style.tableLayout = 'fixed';
                });

                // Ensure all 7 columns are visible
                const headerCells = calendarElement.querySelectorAll('.fc-col-header-cell');
                const dayCells = calendarElement.querySelectorAll('.fc-daygrid-day');
                
                console.log(`Header cells found: ${headerCells.length}`);
                console.log(`Day cells found: ${dayCells.length}`);
                
                if (headerCells.length < 7) {
                    console.warn('Not all day headers are rendering!');
                }
            }, 500);

            // Handle window resize
            window.addEventListener('resize', function() {
                const newIsMobile = window.innerWidth < 768;
                if (newIsMobile && calendar.view.type === 'timeGridWeek') {
                    calendar.changeView('timeGridDay');
                }
                // Force calendar to update size on resize
                setTimeout(() => {
                    calendar.updateSize();
                }, 200);
            });

            // Listen for sidebar toggle events if available
            document.addEventListener('click', function(e) {
                // Check if sidebar toggle button was clicked
                if (e.target.closest('.sidebar-toggle') || 
                    e.target.closest('[data-toggle="sidebar"]') ||
                    e.target.closest('.menu-toggle')) {
                    // Wait for sidebar animation to complete
                    setTimeout(() => {
                        calendar.updateSize();
                    }, 350);
                }
            });

            loadCustomers();
            setupEventListeners();

            // Setup modal close handlers
            setupModalCloseHandlers();
        });

        function setupModalCloseHandlers() {
            const modalElement = document.getElementById('shipmentModal');
            const closeBtn = document.getElementById('closeModalBtn');
            const closeBtnX = modalElement.querySelector('.btn-close');

            // Close button click
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (shipmentModal) {
                        shipmentModal.hide();
                    }
                });
            }

            // X button click
            if (closeBtnX) {
                closeBtnX.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (shipmentModal) {
                        shipmentModal.hide();
                    }
                });
            }

            // Click outside modal
            modalElement.addEventListener('click', function(e) {
                if (e.target === modalElement) {
                    if (shipmentModal) {
                        shipmentModal.hide();
                    }
                }
            });

            // ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modalElement.classList.contains('show')) {
                    if (shipmentModal) {
                        shipmentModal.hide();
                    }
                }
            });

            // Clean up when modal is hidden
            modalElement.addEventListener('hidden.bs.modal', function () {
                currentShipmentId = null;
                document.getElementById('shipmentDetailsContent').innerHTML = '';
            });
        }

        function formatDateWithoutTimezone(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Helper function to enforce table structure
        function enforceTableStructure() {
            // Not needed - wrapper handles scroll
            return;
        }

        function loadCalendarData(start, end) {
            const statusFilter = document.getElementById('statusFilter').value;
            const customerFilter = document.getElementById('customerFilter').value;

            const params = new URLSearchParams({
                start: start,
                end: end
            });

            if (statusFilter) params.append('status', statusFilter);
            if (customerFilter) params.append('customer_id', customerFilter);

            fetch(`{{ route('shipments.calendar.data') }}?${params}`)
                .then(response => response.json())
                .then(data => {
                    allEvents = data;
                    calendar.removeAllEvents();
                    calendar.addEventSource(allEvents);
                    applyFilters();
                    
                    setTimeout(() => {
                        calendar.updateSize();
                    }, 150);
                })
                .catch(error => {
                    console.error('Error loading calendar data:', error);
                    showAlert('Error loading calendar data', 'danger');
                });
        }

        function loadCustomers() {
            fetch(`{{ route('shipments.calendar.customers') }}`)
                .then(response => response.json())
                .then(customers => {
                    const customerFilter = document.getElementById('customerFilter');
                    
                    const classesToAdd = ['sidebar-wrapper', 'form-control'];
                    classesToAdd.forEach(className => {
                        if (!customerFilter.classList.contains(className)) {
                            customerFilter.classList.add(className);
                        }
                    });
                    
                    customerFilter.innerHTML = '<option value="">All Customers</option>';
                    customers.forEach(customer => {
                        customerFilter.innerHTML += `<option value="${customer.id}">${customer.customer_title}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading customers:', error);
                });
        }

        function setupEventListeners() {
            const statusFilter = document.getElementById('statusFilter');
            const customerFilter = document.getElementById('customerFilter');
            const eventTypeFilter = document.getElementById('eventTypeFilter');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const refreshBtn = document.getElementById('refreshCalendar');

            [statusFilter, customerFilter, eventTypeFilter].forEach(filter => {
                filter.addEventListener('change', applyFilters);
            });

            clearFiltersBtn.addEventListener('click', function () {
                statusFilter.value = '';
                customerFilter.value = '';
                eventTypeFilter.value = '';
                applyFilters();
            });

            refreshBtn.addEventListener('click', function () {
                const view = calendar.view;
                const start = formatDateWithoutTimezone(view.activeStart);
                const end = formatDateWithoutTimezone(view.activeEnd);
                loadCalendarData(start, end);
            });
        }

        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const customerFilter = document.getElementById('customerFilter').value;
            const eventTypeFilter = document.getElementById('eventTypeFilter').value;

            let filteredEvents = allEvents.filter(event => {
                const props = event.extendedProps;

                if (statusFilter && props.status !== statusFilter) {
                    return false;
                }

                if (customerFilter && props.customerId !== parseInt(customerFilter)) {
                    return false;
                }

                if (eventTypeFilter && props.eventType !== eventTypeFilter) {
                    return false;
                }

                return true;
            });

            calendar.removeAllEvents();
            calendar.addEventSource(filteredEvents);
        }

        function showShipmentDetails(shipmentId) {
            currentShipmentId = shipmentId;

            document.getElementById('shipmentDetailsContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading shipment details...</p>
                </div>
            `;

            if (shipmentModal) {
                shipmentModal.show();
            }

            fetch(`{{ route('shipments.calendar.shipment', ':id') }}`.replace(':id', shipmentId))
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(shipment => {
                    const statusClass = `status-${shipment.status}`;
                    const statusText = shipment.status.charAt(0).toUpperCase() + shipment.status.slice(1);

                    const equipmentRequired = [];
                    if (shipment.requires_liftgate) equipmentRequired.push('Liftgate');
                    if (shipment.is_hazmat) equipmentRequired.push('Hazmat');
                    if (shipment.temperature_controlled) equipmentRequired.push('Temperature Control');

                    const detailsHTML = `
                        <div class="container-fluid">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-user me-2"></i>Customer Information
                                    </h6>
                                    <p class="mb-2"><strong>Weight:</strong> ${shipment.weight} lbs</p>
                                    <p class="mb-2"><strong>Volume:</strong> ${shipment.volume} cubic ft</p>
                                    <p class="mb-2"><strong>Pallets:</strong> ${shipment.pallets}</p>
                                    <p class="mb-2"><strong>Cost:</strong> ${shipment.estimated_cost}</p>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-arrow-up me-2"></i>Pickup Details
                                    </h6>
                                    <p class="mb-2"><strong>Address:</strong> ${shipment.pickup_address}</p>
                                    <p class="mb-2"><strong>Time:</strong> ${shipment.pickup_time}</p>
                                </div>
                                <div class="col-md-6 col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-arrow-down me-2"></i>Delivery Details
                                    </h6>
                                    <p class="mb-2"><strong>Address:</strong> ${shipment.drop_address}</p>
                                    <p class="mb-2"><strong>Time:</strong> ${shipment.delivery_time}</p>
                                </div>
                            </div>

                            ${equipmentRequired.length > 0 ? `
                            <hr class="my-3">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-tools me-2"></i>Equipment Required
                                    </h6>
                                    <p class="mb-2">${equipmentRequired.join(', ')}</p>
                                </div>
                            </div>
                            ` : ''}

                            ${shipment.special_instructions ? `
                            <hr class="my-3">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-clipboard me-2"></i>Special Instructions
                                    </h6>
                                    <p class="mb-2">${shipment.special_instructions}</p>
                                </div>
                            </div>
                            ` : ''}

                            <hr class="my-3">

                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <p class="mb-2 text-muted small">
                                        <strong>Created:</strong> ${shipment.created_at}
                                    </p>
                                </div>
                                <div class="col-md-6 col-12">
                                    <p class="mb-2 text-muted small">
                                        <strong>Updated:</strong> ${shipment.updated_at}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;

                    document.getElementById('shipmentDetailsContent').innerHTML = detailsHTML;
                })
                .catch(error => {
                    console.error('Error loading shipment details:', error);
                    document.getElementById('shipmentDetailsContent').innerHTML = `
                        <div class="alert alert-danger m-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error!</strong> Unable to load shipment details. Please try again.
                        </div>
                    `;
                });
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const container = document.querySelector('.container-fluid');
            const firstRow = container.querySelector('.row');
            container.insertBefore(alertDiv, firstRow);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        window.refreshShipmentCalendar = function () {
            const view = calendar.view;
            const start = formatDateWithoutTimezone(view.activeStart);
            const end = formatDateWithoutTimezone(view.activeEnd);
            loadCalendarData(start, end);
        };
    </script>
