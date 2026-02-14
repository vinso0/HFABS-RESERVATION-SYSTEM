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
        
        // Check if user is logged in and is an admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated or not authorized'
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
}
