// Listen for the notification broadcast (from Laravel Notification)
window.Echo.channel(`App.Models.User.${userId}`) // Replace userId with actual admin user ID
    .notification((notification) => {
        
        // ✅ SweetAlert popup
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'New Shipment!',
                text: notification.message,
                icon: 'info',
                timer: 5000,
                timerProgressBar: true
            });
        }

        // ✅ Update notification badge counter
        let badge = document.querySelector('.badge-counter');
        if (badge) {
            const count = parseInt(badge.textContent.trim()) || 0;
            badge.textContent = count + 1;
        } else {
            const bell = document.querySelector('#alertsDropdown');
            if (bell) {
                badge = document.createElement('span');
                badge.className = 'badge badge-danger badge-counter';
                badge.textContent = '1';
                bell.appendChild(badge);
            }
        }

        // ✅ Add new notification to dropdown with proper notification ID
        const dropdownArea = document.querySelector('#alertsDropdown + .dropdown-menu .dropdown-list > div');
        if (dropdownArea) {
            const newItem = document.createElement('a');
            newItem.className = 'dropdown-item d-flex align-items-center py-2';
            // 🔧 FIX: Use proper notification route with real notification ID
            newItem.href = `/admin/notifications/${notification.id}/mark-read?redirect=/admin/shipments/${notification.data.shipment_id}`;

            newItem.innerHTML = `
                <div class="mr-3">
                    <div class="icon-circle bg-primary">
                        <i class="fas fa-truck text-white"></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">${new Date(notification.created_at).toLocaleString()}</div>
                    <span class="font-weight-bold">
                        ${notification.data.message}
                    </span>
                    <div class="text-muted small">
                        Pickup: ${notification.data.pickup ?? '-'}<br>
                        Drop: ${notification.data.drop ?? '-'}
                    </div>
                </div>
            `;

            dropdownArea.prepend(newItem);
        }
    });