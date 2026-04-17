/**
 * Admin Notifications System
 * Handles real-time notifications for admin users
 */

class AdminNotifications {
    constructor() {
        this.notificationUrl = '/HFABS/backend/index.php';
        this.unreadCount = 0;
        this.notifications = [];
        this.pollingInterval = null;
        this.isPolling = false;
        
        this.init();
    }
    
    init() {
        // Start polling for notifications
        this.startPolling();
        
        // Setup notification bell click handler
        this.setupNotificationBell();
        
        // Setup mark as read handlers
        this.setupMarkAsReadHandlers();
        
        // Initial load
        this.loadNotifications();
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.pollingInterval = setInterval(() => {
            this.loadNotifications(true); // silent = true for polling
        }, 10000); // Poll every 10 seconds
        
        console.log('[AdminNotifications] Started polling for notifications (10-second interval)');
    }
    
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            this.isPolling = false;
            console.log('[AdminNotifications] Stopped polling for notifications');
        }
    }
    
    async loadNotifications(silent = false) {
        try {
            const response = await fetch(`${this.notificationUrl}?url=notification/getAdminNotifications&limit=10`, {
                credentials: 'include'
            });
            const result = await response.json();
            
            // Debug logging
            console.log('[AdminNotifications] API Response:', result);
            
            if (result.success) {
                this.notifications = result.data || [];
                this.unreadCount = result.unread_count || 0;
                
                console.log('[AdminNotifications] Notifications loaded:', this.notifications.length, 'Unread:', this.unreadCount);
                
                if (!silent) {
                    this.updateNotificationUI();
                } else {
                    this.updateUnreadCount();
                }
                
                // Show toast for new notifications
                if (!silent && this.unreadCount > 0) {
                    this.showNewNotificationToast();
                }
            } else {
                console.error('[AdminNotifications] API Error:', result);
                if (!silent) {
                    this.showError(result.message || 'Failed to load notifications');
                }
            }
        } catch (error) {
            console.error('[AdminNotifications] Error loading notifications:', error);
            if (!silent) {
                this.showError('Failed to load notifications');
            }
        }
    }
    
    updateNotificationUI() {
        // Update notification bell
        this.updateNotificationBell();
        
        // Update notification dropdown
        this.updateNotificationDropdown();
    }
    
    updateNotificationBell() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'block' : 'none';
        }
    }
    
    updateUnreadCount() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            if (this.unreadCount > currentCount) {
                // New notifications arrived
                badge.textContent = this.unreadCount;
                badge.style.display = 'block';
                this.showNewNotificationToast();
            }
        }
    }
    
    updateNotificationDropdown() {
        const contentArea = document.querySelector('.notification-dropdown .notification-content');
        if (!contentArea) return;

        if (this.notifications.length === 0) {
            contentArea.innerHTML = `
                <div class="notification-item empty">
                    <p class="text-muted text-center mb-0">No notifications</p>
                </div>
            `;
            return;
        }

        let html = '';
        this.notifications.forEach(notification => {
            const isRead = notification.is_read == 1;
            const readClass = isRead ? 'read' : 'unread';
            const timeAgo = this.getTimeAgo(notification.created_at);

            html += `
                <div class="notification-item ${readClass}" data-notification-id="${notification.notification_id}">
                    <div class="notification-content-wrapper">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                    ${!isRead ? '<div class="notification-indicator"></div>' : ''}
                </div>
            `;
        });

        contentArea.innerHTML = html;

        // Add click handlers to notification items
        contentArea.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', (e) => {
                const notificationId = item.dataset.notificationId;
                this.markAsRead(notificationId);
            });
        });
    }
    
    setupNotificationBell() {
        const bell = document.querySelector('.notification-bell');
        const dropdown = document.querySelector('.notification-dropdown');
        
        if (bell && dropdown) {
            bell.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
                
                // Load notifications when opening dropdown
                if (dropdown.classList.contains('show')) {
                    this.loadNotifications();
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }
    }
    
    setupMarkAsReadHandlers() {
        // Mark all as read button
        const markAllBtn = document.querySelector('.mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`${this.notificationUrl}?url=notification/markAsRead`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update local data
                const notification = this.notifications.find(n => n.notification_id == notificationId);
                if (notification) {
                    notification.is_read = 1;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                
                this.updateNotificationUI();
            } else {
                this.showError(result.message || 'Failed to mark notification as read');
            }
        } catch (error) {
            console.error('[AdminNotifications] Error marking notification as read:', error);
            this.showError('Failed to update notification');
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch(`${this.notificationUrl}?url=notification/markAllAsRead`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update local data
                this.notifications.forEach(n => n.is_read = 1);
                this.unreadCount = 0;
                
                this.updateNotificationUI();
                this.showSuccess('All notifications marked as read');
            } else {
                this.showError(result.message || 'Failed to mark all notifications as read');
            }
        } catch (error) {
            console.error('[AdminNotifications] Error marking all as read:', error);
            this.showError('Failed to update notifications');
        }
    }
    
    showNewNotificationToast() {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast notification-toast';
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-bell"></i>
                <strong>New Reservation</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                You have ${this.unreadCount} new reservation${this.unreadCount > 1 ? 's' : ''} to review.
            </div>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 5000);
        
        // Setup close button
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        });
    }
    
    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
        
        return date.toLocaleDateString();
    }
    
    showError(message) {
        // Simple error display - you can customize this
        console.error('[AdminNotifications] Error:', message);
        const existing = document.querySelector('.notification-error');
        if (existing) existing.remove();
        
        const error = document.createElement('div');
        error.className = 'alert alert-danger notification-error';
        error.textContent = message;
        error.style.position = 'fixed';
        error.style.top = '20px';
        error.style.right = '20px';
        error.style.zIndex = '9999';
        
        document.body.appendChild(error);
        
        setTimeout(() => {
            error.remove();
        }, 5000);
    }
    
    showSuccess(message) {
        const existing = document.querySelector('.notification-success');
        if (existing) existing.remove();
        
        const success = document.createElement('div');
        success.className = 'alert alert-success notification-success';
        success.textContent = message;
        success.style.position = 'fixed';
        success.style.top = '20px';
        success.style.right = '20px';
        success.style.zIndex = '9999';
        
        document.body.appendChild(success);
        
        setTimeout(() => {
            success.remove();
        }, 3000);
    }
}

// Initialize notifications when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on admin pages
    if (window.location.pathname.includes('admin')) {
        window.adminNotifications = new AdminNotifications();
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminNotifications;
}