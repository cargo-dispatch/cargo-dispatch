import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;

document.addEventListener('DOMContentLoaded', function () {
    // Debug presence
    if (!window.Echo || !window.userId) {
        console.error('❌ Echo or userId not available');
        return;
    }


    // 🔔 Personal notifications (database notification)
    window.Echo.channel(`App.Models.User.${window.userId}`)
        .notification((notification) => {
            showSweetAlertNotification(notification);
            updateNotificationUI(notification);
        });

    const isAdmin = window.userIsAdmin === true || window.userIsAdmin === 'true';

    // Canonical shipment realtime stream
    window.Echo.private('shipments')
        .listen('.shipment.realtime.updated', (data) => {
            window.dispatchEvent(new CustomEvent('shipmentRealtimeUpdated', { detail: data }));
            if (typeof window.fetchNotifications === 'function') window.fetchNotifications();
            if (typeof window.refreshShipmentList === 'function') window.refreshShipmentList();
        });

    // Admin-specific notifications
    if (isAdmin) {
        window.Echo.private('admin.notifications')
            .listen('.shipment.created', (data) => {
                showSweetAlertEvent(data);
                if (typeof window.fetchNotifications === 'function') window.fetchNotifications();
            })
            .listen('.shipment.realtime.updated', (data) => {
                window.dispatchEvent(new CustomEvent('shipmentRealtimeUpdated', { detail: data }));
                if (typeof window.fetchNotifications === 'function') window.fetchNotifications();
                if (typeof window.refreshDispatchBoard === 'function') window.refreshDispatchBoard(data);
                if (typeof window.refreshShipmentList === 'function') window.refreshShipmentList();
            })
            .listen('.driver.status.updated', (data) => {
                // Dispatch a DOM event so any page can listen regardless of load order
                window.dispatchEvent(new CustomEvent('driverStatusUpdated', { detail: data }));
                if (typeof window.onDriverStatusUpdated === 'function') window.onDriverStatusUpdated(data);
                pushDriverStatusNotification(data);
            });
    }
});

// ✅ DOM update function
function updateNotificationUI(data) {
    const badge = document.getElementById('notification-badge');
    const container = document.getElementById('notification-content');

    if (!badge || !container) return;

    let count = parseInt(badge.innerText || '0');
    badge.innerText = count + 1;
    badge.style.display = '';

    const createdAt = new Date().toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true,
    });

    const shipment = data.shipment || {};
    const pickup = shipment.pickup_address || '-';
    const drop = shipment.drop_address || '-';
    const shipmentId = shipment.id || '';
    const notificationId = data.notification_id || ''; // ✅ Make sure this is passed

    const item = `
        <a class="dropdown-item d-flex align-items-center py-2"
           href="${window.APP_URL}/admin/shipments/${shipmentId}/notification/${notificationId}">
            <div class="mr-3">
                <div class="icon-circle bg-primary">
                    <i class="fas fa-truck text-white"></i>
                </div>
            </div>
            <div>
                <div class="small text-gray-500">${createdAt}</div>
                <span class="font-weight-bold">New Shipment Created</span>
                <div class="text-muted small">
                    Pickup: ${pickup}<br>
                    Drop: ${drop}
                </div>
            </div>
        </a>
    `;

    container.insertAdjacentHTML('afterbegin', item);
}





// ✅ Bell notification for driver duty status change
function pushDriverStatusNotification(data) {
    const badge = document.getElementById('notification-badge');
    const container = document.getElementById('notification-content');
    if (!badge || !container) return;

    const count = parseInt(badge.innerText || '0') + 1;
    badge.innerText = count;
    badge.style.display = '';

    const name = `${data.firstname ?? ''} ${data.lastname ?? ''}`.trim() || 'Driver';
    const status = (data.current_duty_status ?? '').replace(/_/g, ' ').toUpperCase();
    const time = new Date().toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'numeric', hour12:true });

    const item = `
        <a class="dropdown-item d-flex align-items-center py-2 theme-dropdown-item" href="#">
            <div class="mr-3">
                <div class="icon-circle" style="background:#17a2b8">
                    <i class="fas fa-truck text-white"></i>
                </div>
            </div>
            <div>
                <div class="small text-gray-500">${time}</div>
                <span class="font-weight-bold">${name} is now ${status}</span>
            </div>
        </a>`;
    container.insertAdjacentHTML('afterbegin', item);
}

// ✅ SweetAlert - database notification
function showSweetAlertNotification(notification) {
    Swal.fire({
        title: '📦 New Shipment!',
        text: notification.data.message,
        icon: 'info',
        timer: 5000,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
    });
}

// ✅ SweetAlert - broadcast event
function showSweetAlertEvent(data) {
    const customer = data.shipment.customer;
    const name = customer
        ? `${customer.first_name} ${customer.last_name}`
        : 'Customer';

    Swal.fire({
        title: '📦 New Shipment Created',
        html: `Shipment #${data.shipment.id} by ${name}`,
        icon: 'info',
        timer: 5000,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
    });
}
