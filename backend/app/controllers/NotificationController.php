<?php

require_once __DIR__ . '/../services/NotificationService.php';

class NotificationController extends Controller
{
    private $notificationService;
    
    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }
    
    /**
     * Get notifications for the logged-in admin
     */
    public function getAdminNotifications()
    {
        // Set CORS headers for AJAX requests
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized',
                'debug' => [
                    'session_id' => session_id(),
                    'user_id_set' => isset($_SESSION['user_id']),
                    'role_set' => isset($_SESSION['role']),
                    'role_value' => $_SESSION['role'] ?? 'none',
                    'session_data_keys' => array_keys($_SESSION)
                ]
            ]);
            exit;
        }
        
        $adminId = $_SESSION['user_id'];
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        // Validate limit
        if ($limit < 1) $limit = 10;
        if ($limit > 50) $limit = 50;
        
        try {
            $notifications = $this->notificationService->getAdminNotifications($adminId, $limit);
            $unreadCount = $this->notificationService->getUnreadNotificationCount($adminId);
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unreadCount
            ]);
            
        } catch (Exception $e) {
            error_log('[NotificationController] Error getting notifications: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error retrieving notifications'
            ]);
        }
        
        exit;
    }
    
    /**
     * Mark a notification as read
     */
    public function markAsRead()
    {
        // Set CORS headers for AJAX requests
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
            ]);
            exit;
        }
        
        $adminId = $_SESSION['user_id'];
        
        // Get notification ID from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['notification_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required field: notification_id'
            ]);
            exit;
        }
        
        try {
            $result = $this->notificationService->markNotificationAsRead($data['notification_id'], $adminId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to mark notification as read'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('[NotificationController] Error marking notification as read: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating notification'
            ]);
        }
        
        exit;
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        // Set CORS headers for AJAX requests
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
            ]);
            exit;
        }
        
        $adminId = $_SESSION['user_id'];
        
        try {
            $unreadCount = $this->notificationService->getUnreadNotificationCount($adminId);
            
            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
            
        } catch (Exception $e) {
            error_log('[NotificationController] Error getting unread count: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error retrieving notification count'
            ]);
        }
        
        exit;
    }
    
    /**
     * Mark all notifications as read for the admin
     */
    public function markAllAsRead()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
            ]);
            exit;
        }
        
        $adminId = $_SESSION['user_id'];
        
        try {
            $db = (new Database())->getConnection();
            $query = "UPDATE admin_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $adminId);
            $result = $stmt->execute();
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'All notifications marked as read',
                    'updated_count' => $stmt->affected_rows
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to mark all notifications as read'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('[NotificationController] Error marking all as read: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating notifications'
            ]);
        }
        
        exit;
    }
}
