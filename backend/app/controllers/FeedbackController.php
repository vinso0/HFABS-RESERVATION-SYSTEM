<?php

class FeedbackController extends Controller
{
    private $feedbackModel;
    private $uploadDir;
    private $maxPhotos = 5;
    private $maxFileSize = 5242880; // 5MB in bytes
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct()
    {
        $this->feedbackModel = $this->model('Feedback');
        $this->uploadDir = __DIR__ . '/../../public/uploads/feedback/';

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    private function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: http://localhost/HFABS');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json');
    }

    // GET ALL FEEDBACK (Admin)
    // API: GET /feedback
    public function index()
    {
        $this->setCorsHeaders();
        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $branchId   = isset($_GET['branch_id'])    ? $_GET['branch_id']    : null;
        $search     = isset($_GET['search'])        ? $_GET['search']       : '';
        $minRating  = isset($_GET['min_rating'])    ? intval($_GET['min_rating']) : 0;
        $statusFilter = isset($_GET['status'])      ? $_GET['status']       : 'all';

        $feedback   = $this->feedbackModel->getAllFeedback($branchId, $search, $minRating, $statusFilter);
        $totalCount = $this->feedbackModel->getTotalFeedbackCount($branchId);
        $stats      = $this->feedbackModel->getFeedbackStats($branchId);

        echo json_encode([
            'success' => true,
            'data'    => $feedback,
            'total'   => $totalCount,
            'stats'   => $stats
        ]);
        exit;
    }

    // SUBMIT FEEDBACK WITH PHOTOS (Customer)
    // API: POST /feedback/submit
    public function submit()
    {
        $this->setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

        require_once __DIR__ . '/../config/config.php';
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $reservationServiceId = isset($_POST['reservation_service_id']) ? intval($_POST['reservation_service_id']) : null;
        $branchId             = isset($_POST['branch_id'])              ? intval($_POST['branch_id'])              : null;
        $rating               = isset($_POST['rating'])                 ? intval($_POST['rating'])                 : null;
        $comment              = isset($_POST['comment'])                ? trim($_POST['comment'])                  : '';
        $userId               = $_SESSION['user_id'];

        // Validate required fields
        if (!$reservationServiceId || !$branchId || !$rating || empty($comment)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            exit;
        }

        // Validate photos if uploaded
        $uploadedFiles = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $photoCount = count($_FILES['photos']['name']);

            if ($photoCount > $this->maxPhotos) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Maximum {$this->maxPhotos} photos allowed"]);
                exit;
            }

            foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;

                // Validate file size
                if ($_FILES['photos']['size'][$i] > $this->maxFileSize) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Each photo must be under 5MB']);
                    exit;
                }

                // Validate MIME type
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (!in_array($mimeType, $this->allowedTypes)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed']);
                    exit;
                }

                // Validate extension
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $this->allowedExtensions)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
                    exit;
                }

                $uploadedFiles[] = [
                    'tmp_name' => $tmpName,
                    'ext'      => $ext,
                    'order'    => $i
                ];
            }
        }

        // Save feedback to DB
        $feedbackId = $this->feedbackModel->submitFeedback(
            $reservationServiceId, $userId, $branchId, $rating, $comment
        );

        if (!$feedbackId) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save feedback']);
            exit;
        }

        // Delete old photos if updating feedback
        $oldPhotos = $this->feedbackModel->deletePhotosByFeedbackId($feedbackId);
        foreach ($oldPhotos as $oldPhoto) {
            $filePath = $this->uploadDir . basename($oldPhoto['photo_path']);
            if (file_exists($filePath)) unlink($filePath);
        }

        // Move and save new photos
        $savedPhotos = [];
        foreach ($uploadedFiles as $file) {
            $newFilename = 'feedback_' . $feedbackId . '_' . uniqid() . '.' . $file['ext'];
            $destPath    = $this->uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $relativePath = 'uploads/feedback/' . $newFilename;
                $this->feedbackModel->savePhotoPath($feedbackId, $relativePath, $file['order']);
                $savedPhotos[] = $relativePath;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'feedback_id' => $feedbackId,
            'photos'  => $savedPhotos
        ]);
        exit;
    }

    // MODERATE FEEDBACK (Admin)
    // API: POST /feedback/moderate
    public function moderate()
    {
        $this->setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

        require_once __DIR__ . '/../config/config.php';
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $feedbackId = isset($input['feedback_id']) ? intval($input['feedback_id']) : null;
        $action     = isset($input['action'])      ? $input['action']              : null;
        $adminNote  = isset($input['admin_note'])  ? trim($input['admin_note'])    : null;

        if (!$feedbackId || !$action) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'feedback_id and action are required']);
            exit;
        }

        switch ($action) {
            case 'approve':
                $result = $this->feedbackModel->updateFeedbackStatus($feedbackId, 'approved', $adminNote);
                $message = 'Feedback approved';
                break;

            case 'reject':
                $result = $this->feedbackModel->updateFeedbackStatus($feedbackId, 'rejected', $adminNote);
                $message = 'Feedback rejected';
                break;

            case 'flag':
                $result = $this->feedbackModel->toggleFlag($feedbackId, 1);
                $message = 'Feedback flagged';
                break;

            case 'unflag':
                $result = $this->feedbackModel->toggleFlag($feedbackId, 0);
                $message = 'Feedback unflagged';
                break;

            case 'delete':
                $photos = $this->feedbackModel->deleteFeedback($feedbackId);
                foreach ($photos as $photo) {
                    $filePath = $this->uploadDir . basename($photo['photo_path']);
                    if (file_exists($filePath)) unlink($filePath);
                }
                $result  = true;
                $message = 'Feedback deleted';
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Action failed']);
        }
        exit;
    }

    // GET STATS
    public function stats()
    {
        $this->setCorsHeaders();
        require_once __DIR__ . '/../config/config.php';
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
        $stats    = $this->feedbackModel->getFeedbackStats($branchId);

        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    }

    // GET SINGLE FEEDBACK
    public function show($feedbackId = null)
    {
        $this->setCorsHeaders();
        require_once __DIR__ . '/../config/config.php';
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($feedbackId === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Feedback ID required']);
            exit;
        }

        $feedback = $this->feedbackModel->getFeedbackById($feedbackId);

        if (!$feedback) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Feedback not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $feedback]);
        exit;
    }
}
