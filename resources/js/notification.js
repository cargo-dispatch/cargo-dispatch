// resources/js/notifications.js
class NotificationManager {
    constructor() {
        this.userId = window.userId;
        this.badge = document.querySelector('#notification-badge');
        this.dropdownContent = document.querySelector('#notification-content');
        this.totalCountSpan = document.querySelector('#total-count');
        
        this.init();
    }

    init() {
        if (!window.Echo) {
            console.error('❌ Echo is not available');
            return;
        }

        if (!this.userId) {
            console.error('❌ User ID is not available');
            return;
        }

        this.setupEventListeners();
    }

    setupEventListeners() {
        // ✅ MAIN FIX: Listen for database notifications on user channel
        window.Echo.private(`App.Models.User.${this.userId}`)
            .notification((notification) => {
                this.handleNotification(notification, 'database');
            })
            .error((error) => {
                console.error('❌ Error on user channel:', error);
            });

        // ✅ Listen for shipment created events on admin channel (if user is admin)
        window.Echo.private('admin.notifications')
            .listen('.shipment.created', (data) => {
                this.handleShipmentEvent(data);
            })
            .listen('.shipment.realtime.updated', (data) => {
                this.handleShipmentRealtimeEvent(data);
                if (typeof window.refreshDispatchBoard === 'function') {
                    window.refreshDispatchBoard(data);
                }
                if (typeof window.refreshShipmentList === 'function') {
                    window.refreshShipmentList();
                }
            })
            .listen('.driver.status.updated', (data) => {
                console.log('🚛 Driver status event received:', data);
                if (typeof window.onDriverStatusUpdated === 'function') {
                    window.onDriverStatusUpdated(data);
                } else {
                    console.warn('⚠️ window.onDriverStatusUpdated not defined — reloading driver row');
                    // Fallback: trigger full table refresh
                    if (typeof loadDrivers === 'function') loadDrivers();
                }
            })
            .error((error) => {
                console.error('❌ Error on admin channel:', error);
            });
    }

    handleNotification(notification, source = 'database') {

        // Show SweetAlert
        this.showSweetAlert(notification);

        // Update UI
        this.updateNotificationBadge();
        this.addToDropdown(notification);
        this.updateTotalCount();
    }

    handleShipmentEvent(eventData) {

        // Convert event data to notification format
        const fakeNotification = {
            id: `event_${Date.now()}`, // Temporary ID for event-based notifications
            type: 'App\\Notifications\\NewShipmentNotification',
            data: {
                message: `New shipment created by ${eventData.shipment.customer?.first_name || 'Unknown'} ${eventData.shipment.customer?.last_name || 'Customer'}`,
                pickup: eventData.shipment.pickup_address || '-',
                drop: eventData.shipment.drop_address || '-',
                shipment_id: eventData.shipment.id,
                customer_name: `${eventData.shipment.customer?.first_name || ''} ${eventData.shipment.customer?.last_name || ''}`.trim()
            },
            created_at: new Date().toISOString(),
            read_at: null
        };

        // Show SweetAlert with action buttons
        this.showShipmentEventAlert(eventData, fakeNotification);

        // Update UI (but don't add to dropdown since this is just an event preview)
        this.updateNotificationBadge();
    }

    handleShipmentRealtimeEvent(eventData) {
        const type = eventData?.event_type || 'updated';
        const shipmentId = eventData?.shipment_id || eventData?.shipment?.id;
        if (!shipmentId) return;

        const readableType = String(type).replace(/_/g, ' ');
        const fakeNotification = {
            id: `event_${Date.now()}`,
            data: {
                message: `Shipment #${shipmentId} ${readableType}`,
                pickup: eventData?.shipment?.pickup_address || '-',
                drop: eventData?.shipment?.drop_address || '-',
                shipment_id: shipmentId,
            },
            created_at: new Date().toISOString(),
        };

        this.showSweetAlert(fakeNotification);
        this.updateNotificationBadge();
    }

    showSweetAlert(notification) {
        if (typeof Swal === 'undefined') {
            console.warn('⚠️ SweetAlert2 is not available');
            return;
        }

        const message = notification.data?.message || 'New notification received';
        const pickup = notification.data?.pickup || '-';
        const drop = notification.data?.drop || '-';
        const shipmentId = notification.data?.shipment_id;

        Swal.fire({
            title: '🔔 New Notification!',
            html: `
                <div class="text-left">
                    <p class="mb-2"><strong>Message:</strong> ${message}</p>
                    <div class="small text-muted">
                        <div><strong>Pickup:</strong> ${pickup}</div>
                        <div><strong>Drop:</strong> ${drop}</div>
                        <div class="mt-2">
                            <i class="fas fa-clock"></i> ${new Date(notification.created_at).toLocaleString()}
                        </div>
                    </div>
                </div>
            `,
            icon: 'info',
            showConfirmButton: true,
            confirmButtonText: 'View Details',
            showCancelButton: true,
            cancelButtonText: 'Dismiss',
            timer: 10000,
            timerProgressBar: true,
            position: 'top-end',
            toast: true,
            width: '400px',
            customClass: {
                popup: 'swal2-toast-custom',
                title: 'swal2-title-custom',
                htmlContainer: 'swal2-html-custom'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        }).then((result) => {
            if (result.isConfirmed && shipmentId) {
                // ✅ FIX: Use the correct route for marking notification as read
                if (notification.id && notification.id.toString().length > 10) {
                    window.location.href = `/admin/shipments/${shipmentId}/notification/${notification.id}`;
                } else {
                    window.location.href = `/admin/shipments/${shipmentId}`;
                }
            }
        });
    }

    showShipmentEventAlert(eventData, notification) {
        if (typeof Swal === 'undefined') {
            console.warn('⚠️ SweetAlert2 is not available');
            return;
        }

        const customerName = eventData.shipment.customer ? 
            `${eventData.shipment.customer.first_name} ${eventData.shipment.customer.last_name}` : 
            'Unknown Customer';

        Swal.fire({
            title: '🚛 New Shipment Created!',
            html: `
                <div class="text-left">
                    <p class="mb-2"><strong>Customer:</strong> ${customerName}</p>
                    <div class="small text-muted">
                        <div><strong>Pickup:</strong> ${eventData.shipment.pickup_address || '-'}</div>
                        <div><strong>Drop:</strong> ${eventData.shipment.drop_address || '-'}</div>
                        <div class="mt-2">
                            <i class="fas fa-clock"></i> ${new Date().toLocaleString()}
                        </div>
                    </div>
                </div>
            `,
            icon: 'success',
            showConfirmButton: true,
            confirmButtonText: 'View Shipment',
            showCancelButton: true,
            cancelButtonText: 'Dismiss',
            timer: 10000,
            timerProgressBar: true,
            position: 'top-end',
            toast: true,
            width: '400px',
            customClass: {
                popup: 'swal2-toast-custom shipment-alert',
                title: 'swal2-title-custom',
                htmlContainer: 'swal2-html-custom'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/shipments/${eventData.shipment.id}`;
            }
        });
    }

    updateNotificationBadge() {
        if (this.badge) {
            // ✅ FIX: Handle badge update properly
            let currentCount = parseInt(this.badge.textContent.trim()) || 0;
            
            // Only increment if badge is visible (has notifications)
            if (this.badge.style.display !== 'none') {
                currentCount = currentCount + 1;
            } else {
                currentCount = 1;
            }
            
            this.badge.textContent = currentCount;
            this.badge.style.display = 'inline-block';
            
            // Add animation
            this.badge.classList.add('badge-pulse');
            setTimeout(() => {
                this.badge.classList.remove('badge-pulse');
            }, 1000);
            
        } else {
            console.warn('⚠️ Notification badge element not found');
            this.createBadge();
        }
    }

    createBadge() {
        const alertsDropdown = document.querySelector('#alertsDropdown');
        if (alertsDropdown) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-danger badge-counter';
            badge.id = 'notification-badge';
            badge.textContent = '1';
            badge.style.cssText = 'position: absolute; top: -5px; right: -5px; font-size: 10px;';
            
            alertsDropdown.style.position = 'relative';
            alertsDropdown.appendChild(badge);
            this.badge = badge;
            
        }
    }

    addToDropdown(notification) {
        if (!this.dropdownContent) {
            console.warn('⚠️ Dropdown content element not found');
            return;
        }

        // ✅ FIX: Check if "No new notifications" message exists and remove it
        const noNotificationsMsg = this.dropdownContent.querySelector('.text-center.small.text-gray-500');
        if (noNotificationsMsg && noNotificationsMsg.textContent.includes('No new notifications')) {
            noNotificationsMsg.remove();
        }

        // Create new notification item
        const newItem = document.createElement('a');
        newItem.className = 'dropdown-item d-flex align-items-center py-2 notification-new';
        
        // Use proper route if we have a real notification ID
        const notificationId = notification.id;
        const shipmentId = notification.data?.shipment_id;
        
        if (shipmentId) {
            if (notificationId && notificationId.toString().length > 10) {
                // Real notification from database
                newItem.href = `/admin/shipments/${shipmentId}/notification/${notificationId}`;
            } else {
                // Event-based notification
                newItem.href = `/admin/shipments/${shipmentId}`;
            }
        } else {
            newItem.href = '#';
        }

        newItem.innerHTML = `
            <div class="mr-3">
                <div class="icon-circle bg-primary">
                    <i class="fas fa-truck text-white"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="small text-gray-500">
                    ${new Date(notification.created_at).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })}
                </div>
                <span class="font-weight-bold">
                    ${notification.data?.message || 'New notification'}
                </span>
                <div class="text-muted small">
                    Pickup: ${notification.data?.pickup || '-'} <br>
                    Drop: ${notification.data?.drop || '-'}
                </div>
            </div>
        `;

        // Add to the beginning of the dropdown
        const firstChild = this.dropdownContent.firstElementChild;
        if (firstChild) {
            this.dropdownContent.insertBefore(newItem, firstChild);
        } else {
            this.dropdownContent.appendChild(newItem);
        }

        // Add fade-in animation
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 100);

    }

    updateTotalCount() {
        if (this.totalCountSpan) {
            const currentCount = parseInt(this.totalCountSpan.textContent.trim()) || 0;
            this.totalCountSpan.textContent = currentCount + 1;
        }
    }

    // Public methods for testing
    testNotification() {
        
        const testNotification = {
            id: `test_${Date.now()}`,
            type: 'App\\Notifications\\NewShipmentNotification',
            data: {
                message: 'Test notification - New shipment created by John Doe',
                pickup: 'Test Pickup Address, City',
                drop: 'Test Drop Address, City',
                shipment_id: 123,
                customer_name: 'John Doe'
            },
            created_at: new Date().toISOString(),
            read_at: null
        };

        this.handleNotification(testNotification, 'test');
    }

    testShipmentEvent() {
        
        const testEventData = {
            shipment: {
                id: 456,
                pickup_address: 'Event Test Pickup',
                drop_address: 'Event Test Drop',
                customer: {
                    first_name: 'Jane',
                    last_name: 'Smith'
                }
            }
        };

        this.handleShipmentEvent(testEventData);
    }

    getStatus() {
        return {
            echoAvailable: typeof window.Echo !== 'undefined',
            pusherState: window.Echo?.connector?.pusher?.connection?.state,
            socketId: window.Echo?.socketId(),
            userId: this.userId,
            badgeElement: !!this.badge,
            dropdownElement: !!this.dropdownContent,
            connected: window.Echo?.connector?.pusher?.connection?.state === 'connected'
        };
    }
}

// Custom CSS for notifications
const notificationStyles = `
    .swal2-toast-custom {
        min-width: 380px !important;
        max-width: 450px !important;
    }
    
    .swal2-title-custom {
        font-size: 1.1rem !important;
        margin-bottom: 10px !important;
    }
    
    .swal2-html-custom {
        font-size: 0.9rem !important;
        line-height: 1.4 !important;
    }
    
    .notification-new {
        background-color: #f8f9fc !important;
        border-left: 3px solid #4e73df !important;
        animation: slideIn 0.3s ease-out;
    }
    
    .badge-pulse {
        animation: pulse 0.6s ease-in-out;
    }
    
    .shipment-alert .swal2-icon.swal2-success {
        border-color: #28a745 !important;
        color: #28a745 !important;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
`;

// Add styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

// ✅ CRITICAL FIX: Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Echo to be available
    const waitForEcho = () => {
        if (typeof window.Echo !== 'undefined' && typeof window.userId !== 'undefined') {
            window.notificationManager = new NotificationManager();
            
            // Add global test functions
            window.testNotification = () => window.notificationManager.testNotification();
            window.testShipmentEvent = () => window.notificationManager.testShipmentEvent();
            window.getNotificationStatus = () => window.notificationManager.getStatus();
            
        } else {
           
            setTimeout(waitForEcho, 500); // Increased timeout
        }
    };
    
    waitForEcho();
});

// Export for use in other files if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}