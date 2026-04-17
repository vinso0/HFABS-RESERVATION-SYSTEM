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
        $jsonInput = file_get_contents('php://input');
        error_log('Raw JSON input: ' . $jsonInput);
        $data = json_decode($jsonInput, true);
        error_log('Decoded data: ' . print_r($data, true));
        
        error_log('Checking required fields:');
        error_log('reservation_id isset: ' . (isset($data['reservation_id']) ? 'YES (' . $data['reservation_id'] . ')' : 'NO'));
        error_log('new_date isset: ' . (isset($data['new_date']) ? 'YES (' . $data['new_date'] . ')' : 'NO'));
        error_log('new_time isset: ' . (isset($data['new_time']) ? 'YES (' . $data['new_time'] . ')' : 'NO'));
        
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
        
        // Convert data types for validation
        $reservationId = (int)$data['reservation_id'];
        $newTime = $data['new_time'] . ':00'; // Convert H:i to H:i:s format
        
        // Reschedule reservation
        $result = $reservationModel->rescheduleReservation(
            $reservationId,
            $data['new_date'],
            $newTime,
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
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
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
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }
        
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
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
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
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
        
        // Check if user is logged in and is an admin or cashier
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
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

    public function checkPackageAvailability()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        // Get parameters from query string
        $date = $_GET['date'] ?? '';
        $time = $_GET['time'] ?? '';
        $packageId = $_GET['packageId'] ?? '';
        
        error_log('[' . date('Y-m-d H:i:s') . '] checkPackageAvailability called');
        error_log('[' . date('Y-m-d H:i:s') . '] checkPackageAvailability parameters: date=' . $date . ', time=' . $time . ', packageId=' . $packageId);
        
        // Validate parameters
        if (empty($date) || empty($time) || empty($packageId)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Date, time, and packageId are required'
            ]);
            exit;
        }
        
        // Load reservation model
        $reservationModel = $this->model('Reservation');
        
        if (!$reservationModel) {
            error_log('[' . date('Y-m-d H:i:s') . '] Failed to load reservation model in checkPackageAvailability');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load reservation model'
            ]);
            exit;
        }
        
        // Check package availability
        error_log('[' . date('Y-m-d H:i:s') . '] Calling checkPackageServiceTimeAvailability with: date=' . $date . ', time=' . $time . ', packageId=' . $packageId);
        $isAvailable = $reservationModel->checkPackageServiceTimeAvailability($date, $time, $packageId);
        
        error_log('[' . date('Y-m-d H:i:s') . '] checkPackageServiceTimeAvailability result: ' . ($isAvailable ? 'AVAILABLE' : 'NOT AVAILABLE'));
        
        echo json_encode([
            'success' => true,
            'available' => $isAvailable,
            'date' => $date,
            'time' => $time,
            'packageId' => $packageId
        ]);
        
        exit;
    }

    public function checkAvailability()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
        $date      = $_GET['date']      ?? '';
        $time      = $_GET['time']      ?? '';
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

        // ── BUSINESS RULE: Reservations must be made at least 8 hours in advance ──
        $now = new DateTime('now');
        $minAllowedDateTime = clone $now;
        $minAllowedDateTime->modify('+8 hours');

        // Combine the submitted date + time into a DateTime object
        $requestedDateTimeStr = $date . ' ' . $time;
        try {
            $requestedDateTime = new DateTime($requestedDateTimeStr);
        } catch (Exception $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Invalid date/time format: ' . $requestedDateTimeStr);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date or time format provided.'
            ]);
            exit;
        }

        if ($requestedDateTime < $minAllowedDateTime) {
            error_log('[' . date('Y-m-d H:i:s') . '] Advance notice check FAILED: ' .
                'Requested=' . $requestedDateTimeStr .
                ', MinAllowed=' . $minAllowedDateTime->format('Y-m-d H:i:s'));
            echo json_encode([
                'success'   => true,
                'available' => false,
                'reason'    => 'Reservations must be made at least 8 hours in advance.'
            ]);
            exit;
        }

        error_log('[' . date('Y-m-d H:i:s') . '] Advance notice check PASSED: ' .
            'Requested=' . $requestedDateTimeStr .
            ', MinAllowed=' . $minAllowedDateTime->format('Y-m-d H:i:s'));
        // ── END ADVANCE NOTICE CHECK ──

        // ── NEW: Get branch_id from session ──
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        // ── NEW CHECK 1: Blocked date or recurring blocked day ──
        if ($branchId) {
            $branchModel = $this->model('Branch');
            if ($branchModel->isDateBlocked($branchId, $date)) {
                error_log('[' . date('Y-m-d H:i:s') . '] Date is blocked: ' . $date);
                echo json_encode([
                    'success'   => true,
                    'available' => false,
                    'reason'    => 'This date is not available for booking.'
                ]);
                exit;
            }
        }
 
        // ── NEW: Get reservation model now for time-based capacity checks
        $reservationModel = $this->model('Reservation');

        // ── NEW CHECK 2: Date-specific category capacity ──
        if ($branchId) {
            $servicesModel = $this->model('Services');
            $conn = (new Database())->getConnection();

            // Find the branch_category_override_id for this service
            $stmt = $conn->prepare("
                SELECT bco.branch_category_override_id
                FROM branch_service_overrides bso
                JOIN default_services ds ON ds.service_id = bso.default_service_id
                JOIN branch_category_overrides bco
                    ON bco.default_category_id = ds.category_id
                    AND bco.branch_id = bso.branch_id
                WHERE bso.branch_service_override_id = ?
                AND bso.branch_id = ?
            ");
            $stmt->bind_param('ii', $serviceId, $branchId);
            $stmt->execute();
            $catRow = $stmt->get_result()->fetch_assoc();

            if ($catRow) {
                $branchCategoryOverrideId = (int) $catRow['branch_category_override_id'];
                $effectiveCapacity = $servicesModel->getEffectiveCapacity($branchId, $branchCategoryOverrideId, $date);

                if ($effectiveCapacity !== null) {
                    $stmt2 = $conn->prepare("
                        SELECT COUNT(*) AS booking_count
                        FROM reservations r
                        JOIN branch_service_overrides bso ON bso.branch_service_override_id = r.service_id
                        JOIN default_services ds ON ds.service_id = bso.default_service_id
                        JOIN branch_category_overrides bco
                            ON bco.default_category_id = ds.category_id
                            AND bco.branch_id = r.branch_id
                        WHERE r.reservation_date = ?
                        AND bco.branch_category_override_id = ?
                        AND r.branch_id = ?
                        AND r.status NOT IN ('cancelled', 'rejected')
                    ");
                    $stmt2->bind_param('sii', $date, $branchCategoryOverrideId, $branchId);
                    $stmt2->execute();
                    $countRow     = $stmt2->get_result()->fetch_assoc();
                    $bookingCount = (int) ($countRow['booking_count'] ?? 0);

                    if ($bookingCount >= $effectiveCapacity) {
                        error_log('[' . date('Y-m-d H:i:s') . '] Category capacity full for date: ' . $date);
                        echo json_encode([
                            'success'   => true,
                            'available' => false,
                            'reason'    => 'This category is fully booked for the selected date.'
                        ]);
                        exit;
                    }

                    // New time-snapped category capacity validation (slot overlap within time range)
                    $serviceDuration = $reservationModel->getServiceDurationMinutes($serviceId);
                    if (!$serviceDuration || $serviceDuration <= 0) {
                        $serviceDuration = 60; // fallback
                    }

                    $startTime = date('H:i:s', strtotime($time));
                    $endTime = date('H:i:s', strtotime($time . ' +' . $serviceDuration . ' minutes'));

                    $concurrent = $reservationModel->countConcurrentCategoryBookings(
                        $branchCategoryOverrideId,
                        $branchId,
                        $date,
                        $startTime,
                        $endTime
                    );

                    if ($concurrent >= $effectiveCapacity) {
                        error_log('[' . date('Y-m-d H:i:s') . '] Category capacity out for time range: ' . $date . ' ' . $startTime . '-' . $endTime . ' (concurrent=' . $concurrent . ', cap=' . $effectiveCapacity . ')');
                        echo json_encode([
                            'success'   => true,
                            'available' => false,
                            'reason'    => 'This category is fully booked for selected time range.'
                        ]);
                        exit;
                    }
                }
            }
        }

        // ── ORIGINAL: Time slot availability check (unchanged) ──
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

        error_log('[' . date('Y-m-d H:i:s') . '] Calling checkServiceTimeAvailability with: date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId);
        $isAvailable = $reservationModel->checkServiceTimeAvailability($date, $time, $serviceId);

        error_log('[' . date('Y-m-d H:i:s') . '] checkServiceTimeAvailability result: ' . ($isAvailable ? 'AVAILABLE' : 'NOT AVAILABLE'));

        echo json_encode([
            'success'   => true,
            'available' => $isAvailable
        ]);

        exit;
    }

    public function validateReschedule()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        error_log('[' . date('Y-m-d H:i:s') . '] validateReschedule called');

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            error_log('[' . date('Y-m-d H:i:s') . '] User not authenticated in validateReschedule');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            exit;
        }

        // Get parameters
        $reservationId = $_GET['reservationId'] ?? '';
        $date          = $_GET['date']          ?? '';
        $time          = $_GET['time']          ?? '';
        $serviceId     = $_GET['serviceId']     ?? '';

        error_log('[' . date('Y-m-d H:i:s') . '] validateReschedule parameters: reservationId=' . $reservationId . ', date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId);

        if (!$reservationId || !$date || !$time || !$serviceId) {
            error_log('[' . date('Y-m-d H:i:s') . '] Missing parameters in validateReschedule');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required parameters: reservationId, date, time, serviceId'
            ]);
            exit;
        }

        // ── BUSINESS RULE 1: Reservations must be made at least 8 hours in advance ──
        $now = new DateTime('now');
        $minAllowedDateTime = clone $now;
        $minAllowedDateTime->modify('+8 hours');

        $requestedDateTimeStr = $date . ' ' . $time;
        try {
            $requestedDateTime = new DateTime($requestedDateTimeStr);
        } catch (Exception $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Invalid date/time format in validateReschedule: ' . $requestedDateTimeStr);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date or time format provided.'
            ]);
            exit;
        }

        if ($requestedDateTime < $minAllowedDateTime) {
            error_log('[' . date('Y-m-d H:i:s') . '] Advance notice check FAILED in validateReschedule');
            echo json_encode([
                'success'   => true,
                'available' => false,
                'reason'    => 'Reservations must be made at least 8 hours in advance.'
            ]);
            exit;
        }

        // ── BUSINESS RULE 2: Check if date is blocked ──
        $branchId = (int) ($_SESSION['branch_id'] ?? 0);

        if ($branchId) {
            $branchModel = $this->model('Branch');
            if ($branchModel->isDateBlocked($branchId, $date)) {
                error_log('[' . date('Y-m-d H:i:s') . '] Date is blocked in validateReschedule: ' . $date);
                echo json_encode([
                    'success'   => true,
                    'available' => false,
                    'reason'    => 'This date is not available for booking.'
                ]);
                exit;
            }
        }

        // ── BUSINESS RULE 3 & 4: Check category capacity (excluding current reservation) ──
        if ($branchId) {
            $servicesModel = $this->model('Services');
            $conn = (new Database())->getConnection();
            $reservationModel = $this->model('Reservation');

            // Find the branch_category_override_id for this service
            $stmt = $conn->prepare("
                SELECT bco.branch_category_override_id
                FROM branch_service_overrides bso
                JOIN default_services ds ON ds.service_id = bso.default_service_id
                JOIN branch_category_overrides bco
                    ON bco.default_category_id = ds.category_id
                    AND bco.branch_id = bso.branch_id
                WHERE bso.branch_service_override_id = ?
                AND bso.branch_id = ?
            ");
            $stmt->bind_param('ii', $serviceId, $branchId);
            $stmt->execute();
            $catRow = $stmt->get_result()->fetch_assoc();

            if ($catRow) {
                $branchCategoryOverrideId = (int) $catRow['branch_category_override_id'];
                $effectiveCapacity = $servicesModel->getEffectiveCapacity($branchId, $branchCategoryOverrideId, $date);

                if ($effectiveCapacity !== null) {
                    // RULE 3: Check daily capacity (exclude current reservation)
                    $stmt2 = $conn->prepare("
                        SELECT COUNT(*) AS booking_count
                        FROM reservations r
                        JOIN branch_service_overrides bso ON bso.branch_service_override_id = r.service_id
                        JOIN default_services ds ON ds.service_id = bso.default_service_id
                        JOIN branch_category_overrides bco
                            ON bco.default_category_id = ds.category_id
                            AND bco.branch_id = r.branch_id
                        WHERE r.reservation_date = ?
                        AND bco.branch_category_override_id = ?
                        AND r.branch_id = ?
                        AND r.status NOT IN ('cancelled', 'rejected')
                        AND r.reservation_id != ?
                    ");
                    $stmt2->bind_param('siii', $date, $branchCategoryOverrideId, $branchId, $reservationId);
                    $stmt2->execute();
                    $countRow     = $stmt2->get_result()->fetch_assoc();
                    $bookingCount = (int) ($countRow['booking_count'] ?? 0);

                    if ($bookingCount >= $effectiveCapacity) {
                        error_log('[' . date('Y-m-d H:i:s') . '] Category capacity full for date in validateReschedule: ' . $date);
                        echo json_encode([
                            'success'   => true,
                            'available' => false,
                            'reason'    => 'This category is fully booked for the selected date.'
                        ]);
                        exit;
                    }

                    // RULE 4: Check time-specific category capacity (exclude current reservation)
                    $serviceDuration = $reservationModel->getServiceDurationMinutes($serviceId);
                    if (!$serviceDuration || $serviceDuration <= 0) {
                        $serviceDuration = 60;
                    }

                    $startTime = date('H:i:s', strtotime($time));
                    $endTime = date('H:i:s', strtotime($time . ' +' . $serviceDuration . ' minutes'));

                    $concurrent = $reservationModel->countConcurrentCategoryBookings(
                        $branchCategoryOverrideId,
                        $branchId,
                        $date,
                        $startTime,
                        $endTime,
                        $reservationId  // ← Exclude current reservation
                    );

                    if ($concurrent >= $effectiveCapacity) {
                        error_log('[' . date('Y-m-d H:i:s') . '] Category capacity out for time range in validateReschedule: ' . $date . ' ' . $startTime . '-' . $endTime);
                        echo json_encode([
                            'success'   => true,
                            'available' => false,
                            'reason'    => 'This category is fully booked for selected time range.'
                        ]);
                        exit;
                    }
                }
            }
        }

        // ── BUSINESS RULE 5: Check service time overlap (exclude current reservation) ──
        $reservationModel = $this->model('Reservation');

        if (!$reservationModel) {
            error_log('[' . date('Y-m-d H:i:s') . '] Failed to load reservation model in validateReschedule');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load reservation model'
            ]);
            exit;
        }

        error_log('[' . date('Y-m-d H:i:s') . '] Calling checkServiceTimeAvailability for reschedule with: date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId . ', excludeReservationId=' . $reservationId);
        $isAvailable = $reservationModel->checkServiceTimeAvailability($date, $time, $serviceId, $reservationId);

        error_log('[' . date('Y-m-d H:i:s') . '] checkServiceTimeAvailability result for reschedule: ' . ($isAvailable ? 'AVAILABLE' : 'NOT AVAILABLE'));

        echo json_encode([
            'success'   => true,
            'available' => $isAvailable
        ]);

        exit;
    }
}   