<?php

class FeedbackController extends Controller
{
    private $feedbackModel;

    public function __construct()
    {
        $this->feedbackModel = $this->model('Feedback');
    }

    // GET ALL FEEDBACK
    // API endpoint: GET /feedback
    public function index()
    {
        // CORS headers
        header('Access-Control-Allow-Origin: http://localhost/HFABS');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Credentials: true');
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

        // Get query parameters
        $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $minRating = isset($_GET['min_rating']) ? intval($_GET['min_rating']) : 0;

        // Get feedback from model
        $feedback = $this->feedbackModel->getAllFeedback($branchId, $search, $minRating);

        // Get total count
        $totalCount = $this->feedbackModel->getTotalFeedbackCount($branchId);

        // Get statistics
        $stats = $this->feedbackModel->getFeedbackStats($branchId);

        echo json_encode([
            'success' => true,
            'data' => $feedback,
            'total' => $totalCount,
            'stats' => $stats
        ]);

        exit;
    }

    // GET FEEDBACK STATISTICS
    // API endpoint: GET /feedback/stats
    public function stats()
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

        // Get query parameters
        $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;

        // Get statistics from model
        $stats = $this->feedbackModel->getFeedbackStats($branchId);

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);

        exit;
    }

    // GET FEEDBACK BY ID
    // API endpoint: GET /feedback/{id}
    public function show($feedbackId = null)
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

        if ($feedbackId === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Feedback ID is required'
            ]);
            exit;
        }

        // Get feedback from model
        $feedback = $this->feedbackModel->getFeedbackById($feedbackId);

        if (!$feedback) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Feedback not found'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $feedback
        ]);

        exit;
    }
}