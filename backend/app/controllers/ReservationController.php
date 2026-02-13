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
}
