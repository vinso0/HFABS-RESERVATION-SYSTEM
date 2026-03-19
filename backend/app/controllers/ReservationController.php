<?php
class ReservationController extends Controller
{
    public function getCustomerReservations()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Get reservations from model
        $reservations = $reservationModel->getReservationsByUserId($userId);
        
        echo json_encode([
            'success' => true,
            'data' => $reservations
        ]);
        
        exit;
    }

    public function submitFeedback()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get feedback data from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['reservation_service_id'], $data['branch_id'], $data['rating'], $data['comment'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            exit;
        }
        
        // Load feedback model
        $feedbackModel = $this->model('Feedback');
        
        try {
            // Submit feedback
            $result = $feedbackModel->submitFeedback(
                $data['reservation_service_id'],
                $userId,
                $data['branch_id'],
                $data['rating'],
                $data['comment']
            );
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback submitted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to submit feedback'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error submitting feedback: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    public function rescheduleReservation()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get reschedule data from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['reservation_id'], $data['new_date'], $data['new_time'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            exit;
        }
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Reschedule reservation
        $result = $reservationModel->rescheduleReservation(
            $data['reservation_id'],
            $data['new_date'],
            $data['new_time'],
            $data['reason'] ?? ''
        );
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation rescheduled successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to reschedule reservation'
            ]);
        }
        
        exit;
    }

    public function cancelReservation()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get reservation id from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['reservation_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required field: reservation_id'
            ]);
            exit;
        }
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Cancel reservation
        $result = $reservationModel->cancelReservation($data['reservation_id']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation cancelled successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to cancel reservation'
            ]);
        }
        
        exit;
    }

    public function getTodaysReservations()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Debug: Log session data (remove in production)
        error_log('Session data in getTodaysReservations: ' . print_r($_SESSION, true));
        
        // Check if user is logged in and is an admin
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authorized. Role: ' . ($_SESSION['role'] ?? 'none')
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Get today's reservations from model
        $reservations = $reservationModel->getTodaysReservations($userId);
        
        echo json_encode([
            'success' => true,
            'data' => $reservations
        ]);
        
        exit;
    }

    public function getAllReservations()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Debug: Log session data (remove in production)
        error_log('Session data in getAllReservations: ' . print_r($_SESSION, true));
        
        // Check if user is logged in and is an admin
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authorized. Role: ' . ($_SESSION['role'] ?? 'none')
            ]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get query parameters
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $itemsPerPage = isset($_GET['itemsPerPage']) ? intval($_GET['itemsPerPage']) : 10;
        
        // Validate parameters
        if ($page < 1) $page = 1;
        if ($itemsPerPage < 1) $itemsPerPage = 10;
        if ($itemsPerPage > 100) $itemsPerPage = 100;
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Get all reservations from model
        $result = $reservationModel->getAllReservations($userId, $status, $page, $itemsPerPage);
        
        echo json_encode([
            'success' => true,
            'data' => $result['reservations'],
            'total' => $result['total'],
            'branch_name' => $result['branch_name'],
            'page' => $page,
            'itemsPerPage' => $itemsPerPage
        ]);
        
        exit;
    }

    public function updateReservationStatus()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
            ]);
            exit;
        }
        
        // Get status update data from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['reservation_id'], $data['status'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: reservation_id and status'
            ]);
            exit;
        }
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        // Update reservation status
        $result = $reservationModel->updateReservationStatus(
            $data['reservation_id'],
            $data['status']
        );
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation status updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update reservation status'
            ]);
        }
        
        exit;
    }
    
    public function adminRescheduleReservation()
    {
        header('Content-Type: application/json');
        
        // Debug: Log method entry
        error_log('[' . date('Y-m-d H:i:s') . '] adminRescheduleReservation method called');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and is an admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
            ]);
            exit;
        }
        
        // Only handle POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed. Only POST requests are accepted.'
            ]);
            exit;
        }
        
        // Get reschedule data from POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Debug: Log the received data with timestamp
        error_log('[' . date('Y-m-d H:i:s') . '] Admin reschedule request - Method: ' . $_SERVER['REQUEST_METHOD'] . ', Data: ' . print_r($data, true));
        
        if (!isset($data['reservation_id'], $data['new_date'], $data['new_time'])) {
            error_log('Missing fields - reservation_id: ' . (isset($data['reservation_id']) ? 'yes' : 'no') . 
                     ', new_date: ' . (isset($data['new_date']) ? 'yes' : 'no') . 
                     ', new_time: ' . (isset($data['new_time']) ? 'yes' : 'no'));
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: reservation_id, new_date, and new_time'
            ]);
            exit;
        }
        
        // Load reservation model
        error_log('[' . date('Y-m-d H:i:s') . '] Loading reservation model...');
        $reservationModel = $this->model('Reservation');
        
        if (!$reservationModel) {
            error_log('[' . date('Y-m-d H:i:s') . '] Failed to load reservation model');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load reservation model'
            ]);
            exit;
        }
        
        error_log('[' . date('Y-m-d H:i:s') . '] Attempting to reschedule reservation ID: ' . $data['reservation_id'] . 
                 ' to date: ' . $data['new_date'] . ' time: ' . $data['new_time'] . ' reason: ' . ($data['reason'] ?? 'Updated by admin'));
        
        // Reschedule reservation
        try {
            $result = $reservationModel->adminReschedule(
                $data['reservation_id'],
                $data['new_date'],
                $data['new_time'],
                $data['reason'] ?? 'Rescheduled by admin'
            );
            
            error_log('[' . date('Y-m-d H:i:s') . '] Reschedule result: ' . ($result ? 'success' : 'failure'));
            
        } catch (Exception $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Exception during reschedule: ' . $e->getMessage());
            error_log('[' . date('Y-m-d H:i:s') . '] Exception trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Exception during reschedule: ' . $e->getMessage()
            ]);
            exit;
        }
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reservation rescheduled successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to reschedule reservation'
            ]);
        }
        
        exit;
    }

    public function checkAvailability()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Debug: Log method entry
        error_log('[' . date('Y-m-d H:i:s') . '] checkAvailability called');
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            error_log('[' . date('Y-m-d H:i:s') . '] User not authenticated in checkAvailability');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        // Get parameters
        $date = $_GET['date'] ?? '';
        $time = $_GET['time'] ?? '';
        $serviceId = $_GET['serviceId'] ?? '';
        
        error_log('[' . date('Y-m-d H:i:s') . '] checkAvailability parameters: date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId);
        
        if (!$date || !$time || !$serviceId) {
            error_log('[' . date('Y-m-d H:i:s') . '] Missing parameters in checkAvailability');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required parameters: date, time, serviceId'
            ]);
            exit;
        }
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        if (!$reservationModel) {
            error_log('[' . date('Y-m-d H:i:s') . '] Failed to load reservation model in checkAvailability');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load reservation model'
            ]);
            exit;
        }
        
        // Check if there's an existing reservation for this date and time
        error_log('[' . date('Y-m-d H:i:s') . '] Calling checkTimeSlotAvailability with: date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId);
        $isAvailable = $reservationModel->checkTimeSlotAvailability($date, $time, $serviceId);
        
        error_log('[' . date('Y-m-d H:i:s') . '] checkTimeSlotAvailability result: ' . ($isAvailable ? 'AVAILABLE' : 'NOT AVAILABLE'));
        
        echo json_encode([
            'success' => true,
            'available' => $isAvailable
        ]);
        
        exit;
    }
}