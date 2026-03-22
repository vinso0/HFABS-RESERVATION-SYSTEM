<?php

class BranchController extends Controller
{
    // Get all branches
    // API endpoint: GET /api/branches
    public function index()
    {
        $branchModel = $this->model('Branch');
        $branches = $branchModel->getAllBranches();
        
        // Format branches to match frontend expected structure
        $formattedBranches = array_map(function($branch) {
            return array(
                'branchid' => $branch['branch_id'],
                'branchname' => $branch['branch_name'],
                'location' => $branch['branch_location'],
                'contact_number' => $branch['contact_number'],
                'opening_time' => $branch['opening_time'],
                'closing_time' => $branch['closing_time']
            );
        }, $branches);
        
        header('Content-Type: application/json');
        echo json_encode($formattedBranches);
    }

    // Get branch by ID
    // API endpoint: GET /api/branches/{id}
    public function show($id)
    {
        $branchModel = $this->model('Branch');
        $branch = $branchModel->getBranchById($id);
        
        if (!$branch) {
            http_response_code(404);
            echo json_encode(array('error' => 'Branch not found'));
            return;
        }
        
        // Format branch to match frontend expected structure
        $formattedBranch = array(
            'branchid' => $branch['branch_id'],
            'branchname' => $branch['branch_name'],
            'location' => $branch['branch_location'],
            'contact_number' => $branch['contact_number'],
            'opening_time' => $branch['opening_time'],
            'closing_time' => $branch['closing_time']
        );
        
        header('Content-Type: application/json');
        echo json_encode($formattedBranch);
    }

    // Get services for a specific branch
    // API endpoint: GET /api/branches/{id}/services
    public function services($branchId)
    {
        $branchModel = $this->model('Branch');
        $services = $branchModel->getBranchServices($branchId);
        
        header('Content-Type: application/json');
        echo json_encode($services);
    }

    // Get categories for a specific branch
    // API endpoint: GET /api/branches/{id}/categories
    public function categories($branchId)
    {
        $branchModel = $this->model('Branch');
        $categories = $branchModel->getBranchCategories($branchId);
        
        header('Content-Type: application/json');
        echo json_encode($categories);
    }

    // Get reviews for a specific branch
    // API endpoint: GET /api/branches/{id}/reviews
    public function reviews($branchId)
    {
        $branchModel = $this->model('Branch');

        $reviews = $branchModel->getBranchReviews($branchId);
        $summary = $branchModel->getBranchRatingSummary($branchId);

        header('Content-Type: application/json');
        echo json_encode([
            'summary' => $summary,
            'reviews' => $reviews
        ]);
    }

        // GET  ?url=branch/settings
    public function settings()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $branchId    = (int) ($_SESSION['branch_id'] ?? 0);
        $branchModel = $this->model('Branch');
        $data        = $branchModel->getBranchSettings($branchId);

        if (!$data) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Branch not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // POST ?url=branch/updateSettings
    public function updateSettings()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        $data = [
            'branch_location'  => trim($input['branch_location']  ?? ''),
            'contact_number'   => trim($input['contact_number']   ?? ''),
            'email'            => trim($input['email']            ?? ''),
            'opening_time'     => trim($input['opening_time']     ?? ''),
            'closing_time'     => trim($input['closing_time']     ?? ''),
            'down_payment_rate' => (float) ($input['down_payment_rate'] ?? 30),
        ];

        if (empty($data['opening_time']) || empty($data['closing_time'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Opening and closing time are required.']);
            exit;
        }

        if ($data['down_payment_rate'] < 0 || $data['down_payment_rate'] > 100) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Downpayment rate must be between 0 and 100.']);
            exit;
        }

        $branchModel = $this->model('Branch');
        $result      = $branchModel->updateBranchSettings($branchId, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Branch settings updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update settings.']);
        }
        exit;
    }

    // GET  ?url=branch/getClosedDates
    public function getClosedDates()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $branchId    = (int) ($_SESSION['branch_id'] ?? 0);
        $branchModel = $this->model('Branch');
        $dates       = $branchModel->getClosedDates($branchId);

        echo json_encode(['success' => true, 'data' => $dates]);
        exit;
    }

    // POST ?url=branch/addClosedDate
    public function addClosedDate()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $date   = trim($input['closed_date'] ?? '');
        $reason = trim($input['reason']      ?? '');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
            exit;
        }

        $branchId    = (int) ($_SESSION['branch_id'] ?? 0);
        $branchModel = $this->model('Branch');
        $result      = $branchModel->addClosedDate($branchId, $date, $reason);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Closed date added.']);
        } else {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Date may already exist or could not be added.']);
        }
        exit;
    }

    // POST ?url=branch/removeClosedDate
    public function removeClosedDate()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true);
        $id       = (int) ($input['id'] ?? 0);
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        if (!$id) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $branchModel = $this->model('Branch');
        $result      = $branchModel->removeClosedDate($id, $branchId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Closed date removed.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove date.']);
        }
        exit;
    }

    // GET ?url=branch/closedDatesPublic  (used by customer booking calendar)
    public function closedDatesPublic()
    {
        header('Content-Type: application/json');

        $branchId = (int) ($_GET['branch_id'] ?? 0);

        if (!$branchId) {
            echo json_encode(['success' => false, 'message' => 'branch_id required']);
            exit;
        }

        $branchModel = $this->model('Branch');
        $dates       = $branchModel->getClosedDatesByBranch($branchId);

        echo json_encode(['success' => true, 'data' => $dates]);
        exit;
    }

}

?>
