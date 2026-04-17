<?php
/**
 * Admin Notification Bell Component
 * Include this component in admin pages where you want notifications
 */

// Check if user is admin/cashier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'cashier']);

if ($isAdmin):
?>

<!-- Admin Notification Bell -->
<div class="notification-wrapper">
    <a href="#" class="notification-bell" id="notificationBell" role="button">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" style="display: none;">0</span>
    </a>
    
    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6>Notifications</h6>
            <button class="mark-all-read-btn" type="button">Mark all as read</button>
        </div>
        
        <div class="notification-content">
            <!-- Notifications will be loaded here via JavaScript -->
            <div class="notification-loading">
                <i class="fas fa-spinner"></i> Loading notifications...
            </div>
        </div>
        
        <div class="notification-footer">
            <a href="/HFABS/frontend/views/admin-notifications.php">View all notifications</a>
        </div>
    </div>
</div>

<?php endif; ?>
