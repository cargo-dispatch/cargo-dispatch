<ul class="m-1 navbar-nav sidebar-wrapper border-radius-sidebar sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-start mt-4" href="{{route('dashboard')}}">
        <div class="sidebar-brand-icon rotate-n-150">
            <img src="{{asset('assets/img/logo.png')}}" style="width: 107px;height:66px;margin-top:20px" alt="">
        </div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0 mt-4">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link sidebar-link" href="{{route('dashboard')}}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item">
        @can('user management.view')
            <a class="nav-link collapsed sidebar-link" data-toggle="collapse" data-target="#collapseTwo"
                aria-expanded="false" aria-controls="collapseTwo">
                <i class="fa-solid fa-users me-2"></i>
                <span>User Management</span>
            </a>
        @endcan

        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 sidebar-wrapper collapse-inner rounded">
                <a class="collapse-item sidebar-link" href="{{ route('users.index') }}">
                    <i class="fa-solid fa-user me-2"></i> Users List
                </a>
                <a class="collapse-item sidebar-link" href="{{ route('roles.index') }}">
                    <i class="fa-solid fa-user-shield me-2"></i> Roles
                </a>
                <a class="collapse-item sidebar-link" href="{{ route('modules.index') }}">
                    <i class="fa-solid fa-puzzle-piece me-2"></i> Modules
                </a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Driver Management -->
    <li class="nav-item">
        @can('driver management.view')
            <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true"
                aria-controls="collapseUtilities">
                <i class="fa-solid fa-user-tie"></i>
                <span>Driver Management</span>
            </a>
        @endcan
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
            <div class="bg-white py-2 sidebar-wrapper collapse-inner rounded">
                @can('driver type.view')
                    <a class="collapse-item" href="{{route('driver.index')}}">Driver Types</a>
                @endcan
                @can('drivers.view')
                    <a class="collapse-item" href="{{route('managedriver.index')}}">Drivers</a>
                @endcan
                @can('drivers.view')
                @php $__pendingSidebarCount = \App\Models\Drivers\Driver::whereIn('onboarding_status',['docs_submitted','under_review'])->count(); @endphp
                @if($__pendingSidebarCount > 0)
                    <a class="collapse-item d-flex align-items-center justify-content-between"
                       href="{{ route('drivers.onboarding.pending') }}">
                        <span>Pending Approvals</span>
                        <span class="badge bg-danger ms-2">{{ $__pendingSidebarCount }}</span>
                    </a>
                @endif
                @endcan
            </div>
        </div>
    </li>

    <!-- Nav Item - Vehicle Management -->
    @can('vehicel management.view')
        <li class="nav-item">
            <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true"
                aria-controls="collapsePages">
                <i class="fa fa-truck"></i>
                <span>Vehicle Management</span>
            </a>
            <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 sidebar-wrapper collapse-inner rounded">
                    @can('vehicle type.view')
                        <a class="collapse-item" href="{{route('vehiclestype.index')}}">Vehicle Types</a>
                    @endcan
                    @can('vehicles.view')
                        <a class="collapse-item" href="{{route('vehicles.index')}}">Vehicle</a>
                    @endcan
                    @can('maintenance type.view')
                        <a class="collapse-item" href="{{route('maintenance_type.index')}}">Maintenance Type</a>
                    @endcan
                    @can('maintenance.view')
                        <a class="collapse-item" href="{{route('maintenance.index')}}">Vehicle Maintenance</a>
                    @endcan
                </div>
            </div>
        </li>
    @endcan

    <div class="sidebar-heading"></div>

    <!-- Nav Item - Customers -->
    @can('customers.view')
        <li class="nav-item">
            <a class="nav-link" href="{{route('customers.index')}}">
                <i class="fas fa-fw fa-user"></i>
                <span>Customers</span>
            </a>
        </li>
    @endcan

    <!-- Nav Item - Shipment Management -->
    @can('shipment management.view')
        <li class="nav-item">
            <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseShipment" aria-expanded="false"
                aria-controls="collapseShipment">
                <i class="fa-solid fa-box"></i>
                <span>Shipment Management</span>
            </a>
            <div id="collapseShipment" class="collapse" aria-labelledby="headingShipment" data-parent="#accordionSidebar">
                <div class="bg-white sidebar-wrapper py-2 collapse-inner rounded">
                    @can('shipments list.view')
                        <a class="collapse-item" href="{{route('shipments.index')}}">
                            <i class="fa-solid fa-list me-2"></i> Shipments List
                        </a>
                    @endcan
                    @can('shipments list.view')
                        <a class="collapse-item" href="{{route('shipments.completed')}}">
                            <i class="fa-solid fa-check-circle me-2"></i> Completed Shipments
                        </a>
                    @endcan
                    @can('shipment calendar.view')
                        <a class="collapse-item" href="{{route('shipments.calendar')}}">
                            <i class="fa-solid fa-calendar-days me-2"></i> Shipment Calendar
                        </a>
                    @endcan
                </div>
            </div>
        </li>
    @endcan

    <!-- Nav Item - Dispatching -->
    @can('dispatching.view')
        <li class="nav-item">
            <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseDispatch" aria-expanded="false"
                aria-controls="collapseDispatch">
                <i class="fa-solid fa-truck-fast"></i>
                <span>Dispatching</span>
            </a>
            <div id="collapseDispatch" class="collapse" aria-labelledby="headingDispatch" data-parent="#accordionSidebar">
                <div class="bg-white sidebar-wrapper py-2 collapse-inner rounded">
                    @can('today dispatches.view')
                        <a class="collapse-item" href="{{ route('dispatch.index') }}">Today Dispatches</a>
                    @endcan
                    @can('next day dispatches.view')
                        <a class="collapse-item" href="{{route('dispatch.tomorrow')}}">Next Day Schedule</a>
                    @endcan
                    <a class="collapse-item" href="{{ route('dispatch.ai-board') }}">AI Load Board</a>
                </div>
            </div>
        </li>
    @endcan

    <!-- Nav Item - Vehicle Assignment -->
    @can('vehicle assignment.view')
        <li class="nav-item">
            <a class="nav-link" href="{{route('vehicleassignment.index')}}">
                <i class="fa-solid fa-caravan"></i>
                <span>Vehicle Assignment</span>
            </a>
        </li>
    @endcan
    
    @can('track drivers.view')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('drivers.map') }}">
                <i class="fas fa-map-marked-alt"></i>
                <span>Track Drivers Location</span>
            </a>
        </li>
    @endcan

    @can('reports.view')
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports"
                aria-expanded="true" aria-controls="collapseReports">
                <i class="fa fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <div id="collapseReports" class="collapse" aria-labelledby="headingReports" data-parent="#accordionSidebar">
                <div class="bg-white sidebar-wrapper py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{route('shipments.report')}}">
                        <i class="fa-solid fa-file-excel me-2"></i> Shipment Report
                    </a>
                    <a class="collapse-item" href="{{route('shipments-invoice.index')}}">
                        <i class="fa-solid fa-file-excel me-2"></i> Invoice Report
                    </a>
                    <a class="collapse-item" href="{{route('payroll.index')}}">
                        <i class="fa-solid fa-file-excel me-2"></i> Pay Roll Report
                    </a>
                </div>
            </div>
        </li>
    @endcan

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button style="background: #b8b8bd; color: #fff;" class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
    
    <!-- THEME TOGGLE -->
    <div class="theme-mode mb-3">
        <div class="d-flex align-items-center w-100 px-3 py-2 toggle-theme-btn" style="cursor: pointer;">
            <div class="theme-toggle-container position-relative" style="width: 44px; height: 22px;">
                <div class="theme-toggle-track position-absolute w-100 h-100 rounded-pill"></div>
                
                <!-- Sun icon -->
                <img src="{{ asset('assets/img/Sun-Brightness-light.png') }}" 
                     alt="Light" class="sun-icon" style="left: 0;">
                
                <!-- Moon icon -->
                <img src="{{ asset('assets/img/Half Moon-light.png') }}" 
                     alt="Dark" class="moon-icon" style="left: 0;">
                
                <div class="theme-toggle-knob position-absolute rounded-circle"></div>
            </div>
            <p class="mb-0 ms-3 theme-mode-text" style="font-size: 10px;">Theme</p>
        </div>
    </div>

</ul>

<!-- Complete Sidebar JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUrl = window.location.href;
        const sidebarLinks = document.querySelectorAll('#accordionSidebar .nav-link, #accordionSidebar .collapse-item');

        // ========================================
        // 1. ACTIVE MENU HIGHLIGHTING
        // ========================================
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
            link.style.color = '';

            if (link.href === currentUrl) {
                link.classList.add('active');
                link.style.color = '#FFFFFF';
                link.style.backgroundColor = '#272A39';
                link.style.fontWeight = '600';

                // Expand parent collapse ONLY on desktop (width >= 768px)
                let parentCollapse = link.closest('.collapse');
                if (parentCollapse && window.innerWidth >= 768) {
                    parentCollapse.classList.add('show');
                    let toggleLink = parentCollapse.previousElementSibling;
                    if (toggleLink && toggleLink.classList.contains('collapsed')) {
                        toggleLink.classList.remove('collapsed');
                        toggleLink.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        });

        // ========================================
        // 2. AUTO-CLOSE COLLAPSE ON MOBILE
        // ========================================
        const collapseItems = document.querySelectorAll('#accordionSidebar .collapse-item');
        
        collapseItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Only auto-close on mobile screens (width < 768px)
                if (window.innerWidth < 768) {
                    // Small delay to allow the link to be followed
                    setTimeout(function() {
                        // Find the parent collapse div
                        const parentCollapse = item.closest('.collapse');
                        
                        if (parentCollapse) {
                            // Close the collapse using Bootstrap's collapse method
                            $(parentCollapse).collapse('hide');
                            
                            // Update the parent nav-link state
                            const toggleLink = parentCollapse.previousElementSibling;
                            if (toggleLink && toggleLink.classList.contains('nav-link')) {
                                toggleLink.classList.add('collapsed');
                                toggleLink.setAttribute('aria-expanded', 'false');
                            }
                        }
                        
                        // Optional: Also close/toggle the main sidebar if using sidebar toggle
                        const body = document.querySelector('body');
                        if (body && body.classList.contains('sidebar-toggled')) {
                            // If you have a sidebar toggle functionality, trigger it
                            const sidebarToggle = document.getElementById('sidebarToggle');
                            if (sidebarToggle) {
                                sidebarToggle.click();
                            }
                        }
                    }, 100);
                }
            });
        });

        // ========================================
        // 3. CLOSE COLLAPSE ON WINDOW RESIZE
        // ========================================
        // Close all collapses when resizing from mobile to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // If we've resized to desktop, close all mobile collapses
                if (window.innerWidth >= 768) {
                    const allCollapses = document.querySelectorAll('#accordionSidebar .collapse.show');
                    allCollapses.forEach(collapse => {
                        // Only close if it's not the active page's collapse
                        const activeLink = collapse.querySelector('.collapse-item.active');
                        if (!activeLink) {
                            $(collapse).collapse('hide');
                        }
                    });
                }
            }, 250);
        });

        // ========================================
        // 4. STORE COLLAPSE STATE (OPTIONAL)
        // ========================================
        // Store which collapse was opened for better UX
        const navLinks = document.querySelectorAll('#accordionSidebar .nav-link[data-toggle="collapse"]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                if (window.innerWidth < 768) {
                    // Store the currently opened collapse in sessionStorage
                    sessionStorage.setItem('openedCollapse', target);
                }
            });
        });

        // ========================================
        // 5. ENSURE ALL COLLAPSES ARE CLOSED ON MOBILE
        // ========================================
        // On mobile, keep all collapses closed by default
        if (window.innerWidth < 768) {
            const allCollapses = document.querySelectorAll('#accordionSidebar .collapse');
            allCollapses.forEach(collapse => {
                // Force close all collapses on mobile
                collapse.classList.remove('show');
                const toggleLink = collapse.previousElementSibling;
                if (toggleLink && toggleLink.classList.contains('nav-link')) {
                    toggleLink.classList.add('collapsed');
                    toggleLink.setAttribute('aria-expanded', 'false');
                }
            });
            
            // Clear any stored collapse state
            sessionStorage.removeItem('openedCollapse');
        }
    });
</script>