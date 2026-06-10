@php
    $currentUser = Auth::user();
    $unreadCount = auth()->user()->unreadNotifications->count();
    $isAdmin = auth()->user()->hasRole('admin');
@endphp
@php
    $unreadNotifications = Auth::user()?->unreadNotifications ?? collect();
@endphp

@push('scripts')
    <script>
        // Expose fetchNotifications globally so app.js Echo listeners can call it on realtime events
        window.fetchNotifications = function() {
            fetch('{{ route('notifications.fetch') }}', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const badge  = document.getElementById('notification-badge');
                const container = document.getElementById('notification-content');
                if (!badge || !container) return;

                const count = data.total_count ?? 0;
                badge.innerText = count;
                badge.style.display = count > 0 ? '' : 'none';

                if (data.notifications && data.notifications.length > 0) {
                    container.innerHTML = data.notifications.map(n => `
                        <a class="dropdown-item d-flex align-items-center py-2 theme-dropdown-item"
                           href="${window.APP_URL}/admin/shipments/${n.shipment_id}/notification/${n.id}">
                            <div class="mr-3"><div class="icon-circle theme-icon-circle"><i class="fas fa-truck"></i></div></div>
                            <div class="flex-grow-1">
                                <div class="small" style="color:var(--search-placeholder)">${n.created_at}</div>
                                <span class="font-weight-bold d-block" style="color:var(--text-color)">${n.message ?? 'New Shipment Created'}</span>
                                <div class="small" style="color:var(--search-placeholder)">
                                    Pickup: ${n.pickup ?? '-'}<br>Drop: ${n.drop ?? '-'}
                                </div>
                            </div>
                        </a>`).join('');
                }
            })
            .catch(() => {});
        };
    </script>
@endpush



<nav class="navbar mt-4 side navbar-expand navbar-light wrapper-color topbar static-top" style="margin-bottom: 20px;">
    <div class="mx-3" style="white-space:nowrap;">
        <p class="dashboard-font mb-0">@yield('title', 'Dashboard')</p>
    </div>

    <!-- Quick Shipment Button — dashboard only -->
    @if(request()->routeIs('dashboard'))
    @can('shipments list.view')
    <a href="{{ route('shipments.create') }}" class="nav-btn-quick d-none d-md-inline-flex">
        <i class="bi bi-plus-lg"></i> Quick Shipment
    </a>
    @endcan
    @endif

    <!-- Sidebar Toggle (Topbar) -->
    <!-- Mobile Hamburger Menu (visible only on small screens) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav w-100 w-lg-50 ml-auto dashboard-right">

        <div class="d-flex align-items-center custom-nav-responsive">
            <!-- Nav Item - Search Dropdown (Visible Only XS) -->
            <li class="nav-item dropdown d-md-none">

                <!-- Dropdown - Messages -->
                <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in theme-dropdown"
                    aria-labelledby="searchDropdown" style="width: 90vw; max-width: 512px; border-radius: 12px;">
                    <form class="form-inline mr-auto w-100 navbar-search">
                        <div class="input-group" style="height: 45px;">
                            <input type="text" class="form-control theme-dropdown-item" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2"
                                style="border-radius: 8px; height: 100%; padding-left: 45px; border: 1px solid var(--btn-border); color: var(--text-color);">
                            <div class="input-group-append position-absolute"
                                style="left: 15px; top: 50%; transform: translateY(-50%); z-index: 5;">
                                <span class="input-group-text theme-dropdown-item border-0">
                                    <i class="fas fa-search fa-fw" style="color: var(--text-color);"></i>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </li>

            <!-- Nav Item - Alerts -->
            <li class="nav-item dropdown no-arrow mx-1">
                <a class="nav-link dropdown-toggle theme-nav-link" href="#" id="alertsDropdown" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell fa-fw"></i>
                    <span class="badge badge-danger badge-counter theme-notification-badge" id="notification-badge"
                        style="{{ $unreadNotifications->count() > 0 ? '' : 'display: none;' }}">
                        {{ $unreadNotifications->count() }}
                    </span>
                </a>


                <style>
                    @media (max-width: 767.98px) {

                        /* mobile screens */
                        #messages-dropdown,
                        #notifications-dropdown {
                            max-width: 100% !important;
                            top: 105px !important;
                        }
                    }
                </style>
                <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                    aria-labelledby="alertsDropdown" id="notifications-dropdown"
                    style="width: 90vw; max-width: 360px; top: 50px;">

                    <h6 class="dropdown-header">Shipments Notification</h6>
                    <style>
                        @media screen and (max-width: 767px) {
                            .notification-height {
                                max-height: 163px !important;
                            }
                        }
                    </style>
                    <div id="notification-content" class="notification-height"
                        style="max-height: 280px; overflow-y: auto;">
                        @forelse ($unreadNotifications as $notification)
                            <a class="dropdown-item d-flex align-items-center py-2 theme-dropdown-item"
                                href="{{ route('shipmentsNotification.detail', ['id' => $notification->data['shipment_id'], 'notificationId' => $notification->id]) }}">
                                <div class="mr-3">
                                    <div class="icon-circle theme-icon-circle">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small" style="color: var(--search-placeholder);">
                                        {{ $notification->created_at->format('M j, g:i a') }}
                                    </div>
                                    <span class="font-weight-bold d-block" style="color: var(--text-color);">
                                        {{ $notification->data['message'] ?? 'New Shipment Created' }}
                                    </span>
                                    <div class="small" style="color: var(--search-placeholder);">
                                        Pickup: {{ $notification->data['pickup'] ?? '-' }} <br>
                                        Drop: {{ $notification->data['drop'] ?? '-' }}
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="dropdown-item text-center small theme-empty-state py-2">
                                No new notifications
                            </div>
                        @endforelse
                    </div>

                    @if ($unreadNotifications->count() > 4)
                        <a class="dropdown-item text-center small theme-dropdown-footer"
                            href="{{ route('notifications.index') }}">
                            View All Notifications
                        </a>
                    @endif
                </div>
            </li>

            <!-- Nav Item - Messages -->
            <li class="nav-item dropdown no-arrow mx-1">
                <a class="nav-link dropdown-toggle theme-nav-link" href="#" id="messagesDropdown" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-envelope fa-fw"></i>
                    <span class="badge badge-danger badge-counter theme-message-counter"
                        style="font-size:8px; margin-bottom:8px !important" id="messageCounter">0</span>
                </a>

                <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in theme-dropdown"
                    aria-labelledby="messagesDropdown" style="width: 90vw; max-width: 360px; top: 50px;">
                    <h6 class="dropdown-header theme-dropdown-header">
                        Message Center
                    </h6>
                    <div id="messageNotifications">
                        <div class="text-center p-3">
                            <p class="text-muted theme-empty-state mb-0">No new messages</p>
                        </div>
                    </div>
                    <div class="dropdown-divider theme-dropdown-divider"></div>
                    <a class="dropdown-item text-center small theme-dropdown-footer" href="#" id="clearMessagesBtn">
                        Clear All Messages
                    </a>
                </div>
            </li>

            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle theme-nav-link" href="#" id="userDropdown" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="img-profile rounded-circle"
                        src="{{ $currentUser?->profile_image ? asset('storage/' . $currentUser->profile_image) : asset('default-profile.png') }}"
                        alt="Profile Logo">
                </a>

                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in theme-dropdown"
                    aria-labelledby="userDropdown" style="width: 90vw; max-width: 250px; top: 105px !important;">
                    <a class="dropdown-item theme-dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="fas fa-user fa-sm fa-fw mr-2" style="color: var(--text-color);"></i>
                        Profile
                    </a>

                    <a class="dropdown-item theme-dropdown-item" href="{{route('general.settings')}}">
                        <i class="fas fa-cog fa-sm fa-fw mr-2" style="color: var(--text-color);"></i>
                        General Settings
                    </a>



                    <a class="dropdown-item theme-dropdown-item" href="#" data-toggle="modal"
                        data-target="#logoutModal">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2" style="color: var(--text-color);"></i>
                        Logout
                    </a>
                </div>
            </li>
        </div>
    </ul>
</nav>


<script>
    $(document).ready(function () {
        let isUpdating = false;

        function fetchNotifications() {
            if (isUpdating) return;
            isUpdating = true;

            $.ajax({
                url: window.APP_URL + "/admin/notifications/fetch?_=" + Date.now(),
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    // Badge update
                    const badge = $('#notification-badge');
                    badge.text(response.total_count);
                    response.total_count > 0 ? badge.show() : badge.hide();

                    // Build notifications HTML with theme classes
                    let html = '';
                    if (response.notifications.length > 0) {
                        response.notifications.forEach(notif => {
                            html += `
                        <a class="dropdown-item d-flex align-items-center py-2 theme-dropdown-item"
                           href="${window.APP_URL}/admin/shipments/${notif.shipment_id}/notification/${notif.id}">
                            <div class="mr-3">
                                <div class="icon-circle theme-icon-circle">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small" style="color: var(--search-placeholder);">${notif.created_at}</div>
                                <span class="font-weight-bold" style="color: var(--text-color);">${notif.message}</span>
                                <div class="small" style="color: var(--search-placeholder);">
                                    Pickup: ${notif.pickup}<br>
                                    Drop: ${notif.drop}
                                </div>
                            </div>
                        </a>`;
                        });
                    } else {
                        html = `<div class="dropdown-item text-center small theme-empty-state py-2">
                                No new notifications
                            </div>`;
                    }

                    $('#notification-content').html(html);
                },
                error: function (xhr) {
                    console.error('Notification error:', xhr.responseText);
                },
                complete: function () {
                    isUpdating = false;
                    setTimeout(fetchNotifications, 60000); // 5 seconds
                }
            });
        }

        // Initial load
        fetchNotifications();

        window.addEventListener('shipmentRealtimeUpdated', function () {
            fetchNotifications();
            window.dispatchEvent(new CustomEvent('refreshShipmentData'));
        });
    });
</script>

<script>
    // Store messages
    let navbarMessages = [];
    let navbarMessageCount = 0;
    let unreadMessages = new Set(); // Track unread message IDs

    // Function to update the message counter
    function updateNavbarMessageCounter() {
        const counter = document.getElementById('messageCounter');
        if (counter) {
            counter.textContent = navbarMessageCount;

            // Show/hide counter based on count
            if (navbarMessageCount > 0) {
                counter.style.display = 'inline';
                counter.classList.add('notification-pulse');
            } else {
                counter.style.display = 'none';
                counter.classList.remove('notification-pulse');
            }
        }
    }

    // Function to add a new message notification
    function addNavbarMessageNotification(user, messageText) {
        // Create a unique ID for this message
        const messageId = 'msg-' + Date.now();
        const userName = user.firstname || user.first_name || user.name || 'User';

        // Add to messages array as unread
        navbarMessages.unshift({
            id: messageId,
            user: user,
            userName: userName,
            message: messageText,
            time: 'Just now',
            isOnline: user.is_online || false,
            read: false // Mark as unread initially
        });

        // Add to unread messages
        unreadMessages.add(messageId);

        // Update count
        navbarMessageCount = unreadMessages.size;
        updateNavbarMessageCounter();

        // Keep only the 20 most recent messages
        if (navbarMessages.length > 20) {
            // Remove the oldest message
            const removedMessage = navbarMessages.pop();
            // If it was unread, remove from count
            if (unreadMessages.has(removedMessage.id)) {
                unreadMessages.delete(removedMessage.id);
                navbarMessageCount = unreadMessages.size;
                updateNavbarMessageCounter();
            }
        }

        // Update the notifications dropdown
        updateNavbarMessageDropdown();

        // Save to localStorage
        saveMessagesToStorage();
    }

    // Function to mark a message as read and remove it from display
    function markMessageAsRead(messageId) {
        if (unreadMessages.has(messageId)) {
            unreadMessages.delete(messageId);
            navbarMessageCount = unreadMessages.size;
            updateNavbarMessageCounter();

            // Update the message in the array to mark as read
            const messageIndex = navbarMessages.findIndex(msg => msg.id === messageId);
            if (messageIndex !== -1) {
                navbarMessages[messageIndex].read = true;
            }

            // Remove the message element from the UI
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageElement) {
                messageElement.remove();
            }

            // If no unread messages left, show the "no messages" placeholder
            if (navbarMessageCount === 0) {
                const container = document.getElementById('messageNotifications');
                if (container && container.children.length === 0) {
                    container.innerHTML = '<div class="text-center p-3"><p class="theme-empty-state">No new messages</p></div>';
                }
            }

            // Save to localStorage
            saveMessagesToStorage();
        }
    }

    // Function to mark all messages as read and remove them from display
    function markAllMessagesAsRead() {
        // Create a copy of unread messages to avoid modification during iteration
        const messagesToRemove = Array.from(unreadMessages);

        // Mark all as read and remove from display
        messagesToRemove.forEach(messageId => {
            markMessageAsRead(messageId);
        });
    }

    // Function to update the message dropdown (only shows unread messages)
    function updateNavbarMessageDropdown() {
        const container = document.getElementById('messageNotifications');
        if (!container) return;

        container.innerHTML = '';

        // Filter to show only unread messages
        const unreadMessagesToShow = navbarMessages.filter(msg => !msg.read);

        if (unreadMessagesToShow.length === 0) {
            container.innerHTML = '<div class="text-center p-3"><p class="theme-empty-state">No new messages</p></div>';
            return;
        }

        // Add all unread messages (no limit since we have scrolling)
        unreadMessagesToShow.forEach(msg => {
            const statusClass = msg.isOnline ? 'bg-success' : '';

            const messageElement = document.createElement('a');
            messageElement.className = 'dropdown-item d-flex align-items-center theme-message-item unread-message';
            messageElement.href = '#';
            messageElement.dataset.messageId = msg.id;

            messageElement.innerHTML = `
            <div class="dropdown-list-image mr-3">
                <img class="rounded-circle" src="https://ui-avatars.com/api/?name=${encodeURIComponent(msg.userName)}&background=random" alt="${msg.userName}">
                <div class="status-indicator ${statusClass}"></div>
            </div>
            <div class="message-content-wrapper w-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="font-weight-bold text-truncate mr-2 theme-message-content">
                        ${msg.userName}
                    </div>
                    <div class="small message-time theme-message-time">${msg.time}</div>
                </div>
                <div class="text-truncate small font-weight-bold mt-1 theme-message-content">
                    ${msg.message}
                </div>
            </div>
        `;

            // Add click handler to mark as read and open chat
            messageElement.addEventListener('click', function (e) {
                e.preventDefault();
                // Mark this message as read (which will remove it from display)
                markMessageAsRead(msg.id);

                // Dispatch event to open chat with this user
                const event = new CustomEvent('openChatWithUser', {
                    detail: {
                        userId: msg.user.id
                    }
                });
                document.dispatchEvent(event);
            });

            container.appendChild(messageElement);
        });
    }

    // Save messages to localStorage
    function saveMessagesToStorage() {
        try {
            localStorage.setItem('navbarMessages', JSON.stringify(navbarMessages));
            localStorage.setItem('navbarMessageCount', navbarMessageCount.toString());
            localStorage.setItem('unreadMessages', JSON.stringify(Array.from(unreadMessages)));
        } catch (e) {
            console.error('Error saving messages to localStorage:', e);
        }
    }

    // Load messages from localStorage
    function loadMessagesFromStorage() {
        try {
            const savedMessages = localStorage.getItem('navbarMessages');
            const savedCount = localStorage.getItem('navbarMessageCount');
            const savedUnread = localStorage.getItem('unreadMessages');

            if (savedMessages) {
                navbarMessages = JSON.parse(savedMessages);
            }

            if (savedCount) {
                navbarMessageCount = parseInt(savedCount);
            }

            if (savedUnread) {
                unreadMessages = new Set(JSON.parse(savedUnread));
            }
        } catch (e) {
            console.error('Error loading messages from localStorage:', e);
        }
    }

    // Clear all messages (both read and unread)
    function clearNavbarMessages() {
        navbarMessages = [];
        unreadMessages.clear();
        navbarMessageCount = 0;
        updateNavbarMessageCounter();

        // Update the UI
        const container = document.getElementById('messageNotifications');
        if (container) {
            container.innerHTML = '<div class="text-center p-3"><p class="theme-empty-state">No new messages</p></div>';
        }

        saveMessagesToStorage();
    }

    // Listen for new message events from the chat system
    document.addEventListener('newNavbarMessage', function (e) {
        addNavbarMessageNotification(e.detail.user, e.detail.message);
    });

    // Listen for open chat requests
    document.addEventListener('openChatWithUser', function (e) {
        // This will be handled by the dashboard's chat manager
        if (window.chatManager && window.chatManager._openChatWindow) {
            // Find the user in allUsers array
            const user = window.chatManager.allUsers.find(u => u.id === e.detail.userId);
            if (user) {
                window.chatManager._openChatWindow(user);
            }
        }
    });

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        loadMessagesFromStorage();
        updateNavbarMessageCounter();
        updateNavbarMessageDropdown();

        // Add event listener for clear button
        const clearBtn = document.getElementById('clearMessagesBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearNavbarMessages();
            });
        }

        // Mark all as read when dropdown is closed
        const dropdown = document.getElementById('messagesDropdown');
        if (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', function () {
                markAllMessagesAsRead();
            });
        }
    });
</script>
<script>
    // Hamburger Menu Toggle Script
    document.addEventListener('DOMContentLoaded', function () {
        // Create hamburger structure
        const sidebarToggle = document.getElementById('sidebarToggleTop');

        if (sidebarToggle) {
            // Clear existing content
            sidebarToggle.innerHTML = '';

            // Create hamburger element
            const hamburger = document.createElement('div');
            hamburger.className = 'hamburger';
            hamburger.innerHTML = `
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        `;

            sidebarToggle.appendChild(hamburger);

            // Function to update hamburger state based on sidebar
            function updateHamburgerState() {
                const sidebar = document.querySelector('.sidebar');
                if (!sidebar) return;

                const isMobile = window.innerWidth < 768;
                const isToggled = sidebar.classList.contains('toggled');

                if (isMobile) {
                    // On mobile: hamburger icon = sidebar closed, cross = sidebar open
                    if (isToggled) {
                        // Sidebar is closed (toggled class present on mobile)
                        hamburger.classList.remove('active');
                    } else {
                        // Sidebar is open
                        hamburger.classList.add('active');
                    }
                } else {
                    // On desktop: follow original logic
                    if (isToggled) {
                        hamburger.classList.remove('active');
                    } else {
                        hamburger.classList.add('active');
                    }
                }
            }

            // Initial state
            updateHamburgerState();

            // Toggle functionality
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                const sidebar = document.querySelector('.sidebar');

                if (sidebar) {
                    sidebar.classList.toggle('toggled');
                }

                // Toggle body class for responsive
                document.body.classList.toggle('sidebar-toggled');

                // Update hamburger state after toggle
                updateHamburgerState();
            });

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    const sidebar = document.querySelector('.sidebar');

                    // Auto-close sidebar on mobile if it's open
                    if (window.innerWidth < 768 && sidebar && !sidebar.classList.contains('toggled')) {
                        sidebar.classList.add('toggled');
                        document.body.classList.add('sidebar-toggled');
                    }

                    // Update hamburger state
                    updateHamburgerState();
                }, 250);
            });

            // Desktop sidebar toggle (if exists)
            const desktopToggle = document.getElementById('sidebarToggle');
            if (desktopToggle) {
                desktopToggle.addEventListener('click', function () {
                    setTimeout(updateHamburgerState, 100);
                });
            }
        }
    });
</script>
<script>
    // ========== DROPDOWN VISIBILITY FIX ==========
    document.addEventListener('DOMContentLoaded', function () {
        // Create overlay for mobile
        const overlay = document.createElement('div');
        overlay.className = 'dropdown-overlay';
        document.body.appendChild(overlay);

        // Function to handle dropdown show
        function handleDropdownShow(event) {
            if (window.innerWidth < 768) {
                // Get the dropdown menu
                const dropdown = event.target.closest('.dropdown');
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');

                if (dropdownMenu) {
                    // Set maximum z-index
                    dropdownMenu.style.zIndex = '99999';

                    // Show overlay
                    overlay.style.display = 'block';

                    // Add class to body
                    document.body.classList.add('dropdown-open');

                    // Position dropdown correctly
                    const navbarHeight = document.querySelector('.navbar').offsetHeight;
                    dropdownMenu.style.top = navbarHeight + 10 + 'px';
                    dropdownMenu.style.position = 'fixed';
                    dropdownMenu.style.right = '10px';

                    // Close dropdown when clicking overlay
                    overlay.onclick = function () {
                        $(dropdownMenu).dropdown('hide');
                    };
                }
            }
        }

        // Function to handle dropdown hide
        function handleDropdownHide() {
            if (window.innerWidth < 768) {
                overlay.style.display = 'none';
                document.body.classList.remove('dropdown-open');
            }
        }

        // Attach event listeners to notification and message dropdowns
        const alertsDropdown = document.getElementById('alertsDropdown');
        const messagesDropdown = document.getElementById('messagesDropdown');

        if (alertsDropdown) {
            alertsDropdown.addEventListener('show.bs.dropdown', handleDropdownShow);
            alertsDropdown.addEventListener('hide.bs.dropdown', handleDropdownHide);
        }

        if (messagesDropdown) {
            messagesDropdown.addEventListener('show.bs.dropdown', handleDropdownShow);
            messagesDropdown.addEventListener('hide.bs.dropdown', handleDropdownHide);
        }

        // Also handle user dropdown
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            userDropdown.addEventListener('show.bs.dropdown', handleDropdownShow);
            userDropdown.addEventListener('hide.bs.dropdown', handleDropdownHide);
        }

        // Fallback: Use mutation observer for Bootstrap events
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('show')) {
                        handleDropdownShow({ target: target });
                    } else {
                        handleDropdownHide();
                    }
                }
            });
        });

        // Observe dropdown menus for class changes
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            observer.observe(menu, { attributes: true });
        });
    });

    // ========== ALTERNATIVE SIMPLE FIX ==========
    // If the above doesn't work, try this nuclear option:
    document.addEventListener('DOMContentLoaded', function () {
        // Force dropdowns to front on mobile
        function forceDropdownsToFront() {
            if (window.innerWidth < 768) {
                // Set maximum possible z-index
                const maxZIndex = 2147483647; // Maximum integer value

                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.zIndex = maxZIndex;
                    menu.style.position = 'fixed';
                    menu.style.top = '100px';
                    menu.style.right = '10px';
                });

                // Hide sidebar when dropdown is open
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.addEventListener('show.bs.dropdown', function () {
                        document.querySelector('.sidebar').style.opacity = '0.3';
                    });
                    dropdown.addEventListener('hide.bs.dropdown', function () {
                        document.querySelector('.sidebar').style.opacity = '1';
                    });
                });
            }
        }

        // Run on load and resize
        forceDropdownsToFront();
        window.addEventListener('resize', forceDropdownsToFront);

        // Also run when dropdowns are shown (Bootstrap event)
        $(document).on('show.bs.dropdown', function (e) {
            if (window.innerWidth < 768) {
                const dropdownMenu = $(e.target).find('.dropdown-menu');
                if (dropdownMenu.length) {
                    dropdownMenu.css({
                        'z-index': '2147483647',
                        'position': 'fixed',
                        'top': '100px',
                        'right': '10px'
                    });

                    // Dim the sidebar
                    $('.sidebar').css('opacity', '0.3');
                }
            }
        });

        $(document).on('hide.bs.dropdown', function () {
            if (window.innerWidth < 768) {
                $('.sidebar').css('opacity', '1');
            }
        });
    });
</script>