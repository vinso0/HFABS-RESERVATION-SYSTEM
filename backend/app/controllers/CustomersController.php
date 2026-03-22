<?php

class CustomersController extends Controller
{
    private $userModel;

    public function __construct()
    {
        // Load config first to ensure DB constants are available
        require_once __DIR__ . '/../config/config.php';
        $this->userModel = $this->model('User');
    }

    // GET ALL CUSTOMERS
    // API endpoint: GET /customers
    public function index()
    {
        // CORS headers
        header('Access-Control-Allow-Origin: http://localhost/HFABS');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in and is admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        // Get query parameters
        $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $filterReservations = isset($_GET['filter_reservations']) ? $_GET['filter_reservations'] : '';

        // Get customers from model
        $customers = $this->userModel->getAllCustomers($branchId, $search, $filterReservations);

        // Get total count
        $totalCount = $this->userModel->getTotalCustomerCount($branchId);

        echo json_encode([
            'success' => true,
            'data' => $customers,
            'total' => $totalCount
        ]);

        exit;
    }

    // GET CUSTOMER RESERVATIONS
    // API endpoint: GET /customers/{userId}/reservations
    public function reservations($userId = null)
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in and is admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        if ($userId === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
            exit;
        }

        // Get query parameters
        $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;

        // Get customer reservations from model
        $reservations = $this->userModel->getCustomerReservationsByUserId($userId, $branchId);

        echo json_encode([
            'success' => true,
            'data' => $reservations
        ]);

        exit;
    }

    // GET CUSTOMER DETAILS
    // API endpoint: GET /customers/{userId}
    public function show($userId = null)
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in and is admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        if ($userId === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
            exit;
        }

        // Get customer from model
        $customer = $this->userModel->getUserById($userId);

        if (!$customer) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Customer not found'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $customer
        ]);

        exit;
    }
}