class AdvancedNotificationPoller {
    constructor(options = {}) {
        this.baseInterval = options.interval || 15000; // 15 seconds
        this.maxInterval = options.maxInterval || 300000; // 5 minutes max
        this.currentInterval = this.baseInterval;
        this.url = options.url || '/api/notifications/unread';
        this.lastCount = 0;
        this.lastCheck = null;
        this.isPolling = false;
        this.pollTimer = null;
        this.consecutiveErrors = 0;
        this.maxRetries = options.maxRetries || 3;
        
        this.callbacks = {
            onNewNotification: options.onNewNotification || (() => {}),
            onCountChange: options.onCountChange || (() => {}),
            onError: options.onError || (() => {}),
            onReconnect: options.onReconnect || (() => {})
        };

        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        this.handleOnline = this.handleOnline.bind(this);
        this.handleOffline = this.handleOffline.bind(this);
    }

    start() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.consecutiveErrors = 0;
        this.currentInterval = this.baseInterval;
        
        // Initial poll
        this.poll();
        
        // Set up event listeners
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        window.addEventListener('online', this.handleOnline);
        window.addEventListener('offline', this.handleOffline);
        
    }

    stop() {
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        
        this.isPolling = false;
        
        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
        
    }

    async poll() {
        if (!this.isPolling) return;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            const response = await fetch(this.url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            // Reset error counter on successful request
            if (this.consecutiveErrors > 0) {
                this.consecutiveErrors = 0;
                this.currentInterval = this.baseInterval;
                this.callbacks.onReconnect();
            }

            this.handleSuccessfulPoll(data);
            
        } catch (error) {
            this.handlePollError(error);
        } finally {
            // Schedule next poll if still polling
            if (this.isPolling) {
                this.scheduleNextPoll();
            }
        }
    }

    handleSuccessfulPoll(data) {
        const currentTime = Date.now();
        
        // Check if count changed
        if (data.count !== this.lastCount) {
            // Detect new notifications (only if we had a previous check)
            if (data.count > this.lastCount && this.lastCheck !== null) {
                const newNotifications = this.getNewNotifications(data.notifications);
                if (newNotifications.length > 0) {
                    this.callbacks.onNewNotification(newNotifications);
                    this.showMultipleNotificationToasts(newNotifications);
                }
            }
            
            this.lastCount = data.count;
            this.callbacks.onCountChange(data.count);
            this.updateNotificationUI(data);
        }

        this.lastCheck = currentTime;
    }

    handlePollError(error) {
        this.consecutiveErrors++;
        console.error(`Polling error (attempt ${this.consecutiveErrors}):`, error);

        // Implement exponential backoff
        if (this.consecutiveErrors <= this.maxRetries) {
            this.currentInterval = Math.min(
                this.baseInterval * Math.pow(2, this.consecutiveErrors - 1),
                this.maxInterval
            );
        }

        this.callbacks.onError(error, this.consecutiveErrors);

        // Stop polling after max retries
        if (this.consecutiveErrors > this.maxRetries) {
            console.error('Max polling retries exceeded. Stopping notification polling.');
            this.stop();
        }
    }

    getNewNotifications(allNotifications) {
        if (!this.lastCheck) return [];
        
        return allNotifications.filter(notification => {
            const notificationTime = new Date(notification.created_at).getTime();
            return notificationTime > this.lastCheck;
        });
    }

    scheduleNextPoll() {
        this.pollTimer = setTimeout(this.poll, this.currentInterval);
    }

    handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden, increase polling interval to save battery
            this.currentInterval = Math.max(this.baseInterval * 4, 60000); // At least 1 minute
        } else {
            // Page is visible, restore normal polling
            this.currentInterval = this.baseInterval;
            // Poll immediately when page becomes visible
            if (this.isPolling) {
                clearTimeout(this.pollTimer);
                this.poll();
            }
        }
    }

    handleOnline() {
        if (this.isPolling) {
            this.consecutiveErrors = 0;
            this.currentInterval = this.baseInterval;
            clearTimeout(this.pollTimer);
            this.poll();
        }
    }

    handleOffline() {
    }

    updateNotificationUI(data) {
        // Update notification badge
        const badge = document.querySelector('.badge-counter');
        if (badge) {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline';
                badge.classList.add('notification-pulse');
                setTimeout(() => badge.classList.remove('notification-pulse'), 1000);
            } else {
                badge.style.display = 'none';
            }
        }

        // Update dropdown content
        const dropdown = document.querySelector('#notification-content');
        if (dropdown && data.notifications) {
            this.updateDropdownContent(dropdown, data.notifications);
        }

        // Update show all link
        const totalCount = document.querySelector('#total-count');
        if (totalCount) {
            totalCount.textContent = data.count;
        }
    }

    updateDropdownContent(dropdown, notifications) {
        if (notifications.length === 0) {
            dropdown.innerHTML = `
                <div class="dropdown-item text-center small text-gray-500 py-2">
                    No new notifications
                </div>
            `;
            return;
        }

        let html = '';
        notifications.slice(0, 4).forEach(notification => {
            html += this.createNotificationHTML(notification);
        });

        if (notifications.length > 4) {
            html += '<div style="max-height: 200px; overflow-y: auto; border-top: 1px solid #eee;">';
            notifications.slice(4).forEach(notification => {
                html += this.createNotificationHTML(notification, true);
            });
            html += '</div>';
        }

        dropdown.innerHTML = html;
    }

    createNotificationHTML(notification, compact = false) {
        const date = new Date(notification.created_at);
        const formattedDate = compact ? 
            date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) :
            date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });

        const iconSize = compact ? 'style="width: 30px; height: 30px;"' : '';
        const iconClass = compact ? 'style="font-size: 0.8rem;"' : '';
        const messageStyle = compact ? 'style="font-size: 0.9rem;"' : '';
        const message = compact ? this.truncate(notification.data.message || 'New Shipment', 30) : notification.data.message || 'New Shipment Created';

        return `
            <a class="dropdown-item d-flex align-items-center py-2" 
               href="/shipments-notification/${notification.data.shipment_id}?notificationId=${notification.id}"
               data-notification-id="${notification.id}">
                <div class="mr-3">
                    <div class="icon-circle bg-primary" ${iconSize}>
                        <i class="fas fa-truck text-white" ${iconClass}></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">${formattedDate}</div>
                    <span class="font-weight-bold" ${messageStyle}>${message}</span>
                    ${!compact ? `<div class="text-muted small">
                        Pickup: ${notification.data.pickup || '-'} <br>
                        Drop: ${notification.data.drop || '-'}
                    </div>` : ''}
                </div>
            </a>
        `;
    }

    showMultipleNotificationToasts(notifications) {
        notifications.forEach((notification, index) => {
            setTimeout(() => {
                this.showNotificationToast(notification);
            }, index * 500); // Stagger toasts by 500ms
        });
    }

    showNotificationToast(notification) {
        // Remove existing toasts to prevent overflow
        const existingToasts = document.querySelectorAll('.notification-toast');
        if (existingToasts.length >= 3) {
            existingToasts[0].remove();
        }

        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="toast-message">
                    <strong>New Shipment</strong>
                    <p>${this.truncate(notification.data.message, 60)}</p>
                    <small class="text-muted">From: ${notification.data.customer_name}</small>
                </div>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        // Add enhanced styles
        this.addToastStyles();

        // Position multiple toasts
        const existingToastsCount = document.querySelectorAll('.notification-toast').length;
        toast.style.top = `${20 + (existingToastsCount * 80)}px`;

        document.body.appendChild(toast);

        // Auto remove after 6 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 6000);

        // Add click handler to navigate to notification
        toast.addEventListener('click', () => {
            window.location.href = `/shipments-notification/${notification.data.shipment_id}?notificationId=${notification.id}`;
        });

        this.playNotificationSound();
    }

    addToastStyles() {
        if (document.querySelector('#advanced-notification-toast-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'advanced-notification-toast-styles';
        styles.textContent = `
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border: 1px solid #ddd;
                border-left: 4px solid #007bff;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 320px;
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            .notification-toast:hover {
                transform: translateX(-5px);
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            }
            .toast-content {
                display: flex;
                align-items: flex-start;
                padding: 12px;
            }
            .toast-icon {
                background: #007bff;
                color: white;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 12px;
                flex-shrink: 0;
            }
            .toast-message {
                flex: 1;
                min-width: 0;
            }
            .toast-message strong {
                display: block;
                margin-bottom: 4px;
                color: #333;
            }
            .toast-message p {
                margin: 0 0 4px 0;
                font-size: 0.9em;
                color: #666;
                line-height: 1.3;
            }
            .toast-message small {
                font-size: 0.8em;
            }
            .toast-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: #999;
                padding: 0;
                margin-left: 10px;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .toast-close:hover {
                color: #666;
            }
            .badge-counter.notification-pulse {
                animation: pulse 0.6s ease-in-out;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
        `;
        document.head.appendChild(styles);
    }

    truncate(str, length) {
        return str && str.length > length ? str.substring(0, length) + '...' : str;
    }

    playNotificationSound() {
        try {
            // Create a simple beep sound using Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Fallback to HTML5 audio if available
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYCDAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
                audio.volume = 0.1;
                audio.play().catch(() => {});
            } catch (e2) {
                // Silent fallback
            }
        }
    }

    // Public methods for manual control
    forceCheck() {
        if (this.isPolling) {
            clearTimeout(this.pollTimer);
            this.poll();
        }
    }

    getStatus() {
        return {
            isPolling: this.isPolling,
            currentInterval: this.currentInterval,
            consecutiveErrors: this.consecutiveErrors,
            lastCheck: this.lastCheck,
            lastCount: this.lastCount
        };
    }
}