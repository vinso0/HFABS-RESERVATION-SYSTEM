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
        
        // ✅ FIXED — added down_payment_rate to the response
        $formattedBranch = array(
            'branchid'          => $branch['branch_id'],
            'branchname'        => $branch['branch_name'],
            'location'          => $branch['branch_location'],
            'contact_number'    => $branch['contact_number'],
            'email'             => $branch['email'],
            'opening_time'      => $branch['opening_time'],
            'closing_time'      => $branch['closing_time'],
            'down_payment_rate' => (float) $branch['down_payment_rate']  // ✅ ADD THIS LINE
        );
        
        header('Content-Type: application/json');
        echo json_encode($formattedBranch);
    }

    // Get services for a specific branch
    // API endpoint: GET /api/branches/{id}/services
    public function services($branchId)
    {
        $branchModel = $this->model('Branch');
        $services    = $branchModel->getBranchServices($branchId);

        // Build the base URL once — used to convert stored relative paths to absolute URLs
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'];

        $formatted = array_map(function($service) use ($baseUrl) {
            // Fallback: branch override image → global image → null
            $rawImage = !empty($service['image_path_override'])
                ? $service['image_path_override']
                : (!empty($service['image_path']) ? $service['image_path'] : null);

            $service['image_url'] = $rawImage
                ? $baseUrl . '/HFABS/backend/' . ltrim($rawImage, '/')
                : null;

            return $service;
        }, $services);

        header('Content-Type: application/json');
        echo json_encode($formatted);
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
        ob_start();                          // buffer any stray PHP warnings
        $branchModel = $this->model('Branch');
        $reviews     = $branchModel->getBranchReviews($branchId);
        $summary     = $branchModel->getBranchRatingSummary($branchId);
        ob_end_clean();                      // discard any warnings

        header('Content-Type: application/json');
        echo json_encode([
            'summary' => $summary,
            'reviews' => $reviews
        ]);
    }

    // GET branch/{branchId}/service-ratings
    // Returns aggregated avg + count for services and packages at this branch.
    public function serviceRatings($branchId = null)
    {
        // ── REQUIRED: ensure DB constants are loaded before instantiating any model ──
        require_once __DIR__ . '/../config/config.php';

        ob_start(); // buffer any stray PHP warnings/notices
        header('Content-Type: application/json');

        if (!$branchId || !is_numeric($branchId)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
            exit;
        }

        $branchId      = (int) $branchId;
        $feedbackModel = $this->model('Feedback');

        $serviceRatings = $feedbackModel->getServiceRatingsByBranch($branchId);
        $packageRatings = $feedbackModel->getPackageRatingsByBranch($branchId);

        ob_end_clean(); // discard any warnings before outputting JSON

        echo json_encode([
            'success'         => true,
            'service_ratings' => $serviceRatings,
            'package_ratings' => $packageRatings,
        ]);
        exit;
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

    // POST ?url=branch/sendInquiry
    public function sendInquiry()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $branchId = (int) ($input['branch_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');

        // Validate required fields
        if (!$branchId || !$name || !$email || !$subject || !$message) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // Get branch details
        $branchModel = $this->model('Branch');
        $branch = $branchModel->getBranchById($branchId);

        if (!$branch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Branch not found.']);
            exit;
        }

        $branchEmail = $branch['email'];
        $branchName = $branch['branch_name'];

        if (empty($branchEmail)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'This branch does not have an email address configured.']);
            exit;
        }

        // Load email configuration
        $emailConfig = require __DIR__ . '/../../config/email.php';

        // Send email using PHPMailer
        require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $emailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $emailConfig['username'];
            $mail->Password = $emailConfig['password'];
            $mail->SMTPSecure = $emailConfig['encryption'];
            $mail->Port = $emailConfig['port'];

            // Recipients
            $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
            $mail->addAddress($branchEmail, $branchName);
            $mail->addReplyTo($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Inquiry: $subject";
            
            $htmlBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #f8bbd9; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .header h2 { color: #c2185b; margin: 0; }
                        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                        .field { margin-bottom: 15px; }
                        .label { font-weight: bold; color: #555; }
                        .value { margin-top: 5px; padding: 10px; background-color: #fff; border: 1px solid #eee; border-radius: 3px; }
                        .message-box { white-space: pre-wrap; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>New Inquiry from $branchName</h2>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <div class='label'>From:</div>
                                <div class='value'>$name</div>
                            </div>
                            <div class='field'>
                                <div class='label'>Email:</div>
                                <div class='value'>$email</div>
                            </div>
                            <div class='field'>
                                <div class='label'>Subject:</div>
                                <div class='value'>$subject</div>
                            </div>
                            <div class='field'>
                                <div class='label'>Message:</div>
                                <div class='value message-box'>$message</div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->Body = $htmlBody;
            $mail->AltBody = "From: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

            $mail->send();
            
            echo json_encode(['success' => true, 'message' => 'Your inquiry has been sent successfully!']);
            exit;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
            exit;
        }
    }

    // GET ?url=branch/getBlockedDays
    public function getBlockedDays()
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
        $days        = $branchModel->getBlockedDays($branchId);

        echo json_encode(['success' => true, 'data' => $days]);
        exit;
    }

    // POST ?url=branch/saveBlockedDays
    public function saveBlockedDays()
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

        $input    = json_decode(file_get_contents('php://input'), true);
        $days     = $input['blocked_days'] ?? [];
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        // Validate: must be array of integers 0–6
        $days = array_filter(array_map('intval', (array) $days), fn($d) => $d >= 0 && $d <= 6);

        $branchModel = $this->model('Branch');
        $branchModel->saveBlockedDays($branchId, array_values($days));

        echo json_encode(['success' => true, 'message' => 'Blocked days saved successfully.']);
        exit;
    }

    // GET ?url=branch/blockedDaysPublic  (used by customer booking calendar)
    public function blockedDaysPublic()
    {
        header('Content-Type: application/json');

        $branchId = (int) ($_GET['branch_id'] ?? 0);
        if (!$branchId) {
            echo json_encode(['success' => false, 'message' => 'branch_id required']);
            exit;
        }

        $branchModel = $this->model('Branch');
        $days        = $branchModel->getBlockedDaysByBranch($branchId);

        echo json_encode(['success' => true, 'data' => $days]);
        exit;
    }
}

?>