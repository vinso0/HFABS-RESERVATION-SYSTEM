<?php
class AuthController extends Controller
{
    /**
     * Enhanced email validation with allowlist approach
     * Uses dynamically updated domain lists
     */
    private function validateEmail($email)
    {
        // Basic format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        require_once __DIR__ . '/../services/DomainBlacklistService.php';
        $domainService = new DomainBlacklistService();
        
        // Auto-update domain lists if needed (runs in background)
        $domainService->autoUpdateIfNeeded();

        // Extract domain from email
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        
        // Get current allowlist and blacklist
        $allowlist = $domainService->getAllowlistFromFile();
        $blacklist = $domainService->getBlacklist();
        $wildcardDomains = $domainService->getWildcardDomains();
        
        // Check if domain is blacklisted (disposable emails)
        if (in_array($domain, $blacklist)) {
            return false;
        }
        
        // Check if domain is exactly in allowed list
        if (in_array($domain, $allowlist)) {
            return true;
        }
        
        // Check wildcard domains (.edu.ph, .gov.ph)
        foreach ($wildcardDomains as $wildcard) {
            if (substr($domain, -strlen($wildcard)) === $wildcard) {
                return true;
            }
        }

        return false;
    }

    public function register()
    {
        // Always return JSON for API endpoints
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Allow only POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            exit;
        }

        // Read JSON input
        $data = json_decode(file_get_contents("php://input"), true);

        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $contact  = trim($data['contact_number'] ?? '');
        $otp      = trim($data['otp'] ?? '');

        // Validate required fields
        if (!$username || !$email || !$password || !$contact || !$otp) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'All fields including OTP are required'
            ]);
            exit;
        }

        // Enhanced email validation with allowlist
        if (!$this->validateEmail($email)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)'
            ]);
            exit;
        }

        // Password validation
        if (strlen($password) < 8) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Password must be at least 8 characters long'
            ]);
            exit;
        }

        $userModel = $this->model('User');

        // Verify OTP first
        if (!$userModel->verifyEmailOTP($email, $otp)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please request a new one.'
            ]);
            exit;
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Attempt registration
        $created = $userModel->register(
            $username,
            $email,
            $hashedPassword,
            $contact
        );

        if ($created) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'redirect' => '/HFABS/frontend/views/customer-login.html'
            ]);
        } else {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists or registration failed'
            ]);
        }

        exit;
    }

    // SEND EMAIL VERIFICATION OTP
    public function sendEmailVerificationOTP()
    {
        header('Content-Type: application/json');
        error_log("[AuthController] Email verification OTP request received");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("[AuthController] ERROR: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');
        $username = trim($data['username'] ?? '');
        
        error_log("[AuthController] Email verification OTP requested for: " . $email);

        if (!$email) {
            error_log("[AuthController] ERROR: Email is required but not provided");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("[AuthController] ERROR: Invalid email format - " . $email);
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }

        $userModel = $this->model('User');
        
        // Check if email is already registered
        if ($userModel->isEmailRegistered($email)) {
            error_log("[AuthController] WARNING: Email already registered - " . $email);
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'This email is already registered. Please use a different email or try logging in.'
            ]);
            exit;
        }

        // Generate 6-digit OTP
        $otpCode = sprintf("%06d", random_int(0, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        error_log("[AuthController] Generated OTP: " . $otpCode);
        error_log("[AuthController] OTP expires at: " . $expiresAt);

        // Save OTP to database
        if ($userModel->createEmailOTP($email, $otpCode, $expiresAt)) {
            error_log("[AuthController] OTP saved to database successfully");
            
            // Send email
            require_once __DIR__ . '/../services/EmailService.php';
            error_log("[AuthController] Initializing EmailService...");
            $emailService = new EmailService();
            
            if ($emailService->sendEmailVerificationOTP($email, $username, $otpCode)) {
                error_log("[AuthController] Verification OTP email sent successfully to: " . $email);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Verification code has been sent to your email. Please check your inbox.'
                ]);
            } else {
                error_log("[AuthController] ERROR: Failed to send verification OTP to: " . $email);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to send verification code. Please try again.'
                ]);
            }
        } else {
            error_log("[AuthController] ERROR: Failed to save OTP to database for email: " . $email);
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to generate verification code. Please try again.'
            ]);
        }

        exit;
    }

    public function getProfile()
    {
        header('Content-Type: application/json');
        
        require_once __DIR__ . '/../config/config.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        $userModel = $this->model('User');
        $userData = $userModel->getUserById($_SESSION['user_id']);

        if ($userData) {
            // Remove sensitive data
            unset($userData['password']);
            
            echo json_encode([
                'success' => true,
                'data' => $userData
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        exit;
    }


    public function login()
    {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $data = json_decode(file_get_contents("php://input"), true);

        // Get expected role from query parameter (e.g., ?role=customer)
        $expectedRole = $_GET['role'] ?? null;

        // Accept either email or username
        $identifier = '';
        if (!empty($data['email'])) {
            $identifier = $data['email'];
        } elseif (!empty($data['username'])) {
            $identifier = $data['username'];
        }
        $password = $data['password'] ?? '';

        if (!$identifier || !$password) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email/Username and password are required'
            ]);
            exit;
        }

        $userModel = $this->model('User');
        $user = $userModel->login($identifier);

        if ($user && password_verify($password, $user['password'])) {
            // Get expected role from query parameter
            $expectedRole = $_GET['role'] ?? null;
            $actualRole = $user['role'];
            
            // Role validation - each login page only accepts its specific role
            $allowedRoles = [
                'admin' => ['admin', 'cashier'],
                'superadmin' => ['superadmin'],
                'customer' => ['customer']
            ];
            
            $allowed = $allowedRoles[$expectedRole] ?? [];
            
            if ($expectedRole && !in_array($actualRole, $allowed)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => "Unauthorized Access."
                ]);
                exit;
            }
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            
            // Get branch name if branch_id exists
            if (!empty($user['branch_id'])) {
                require_once __DIR__ . '/../models/Branch.php';
                $branchModel = new Branch();
                $branch = $branchModel->getBranchById($user['branch_id']);
                $_SESSION['branch_name'] = $branch['branch_name'] ?? '';
            }

            $redirect = match ($user['role']) {
                'admin' => '/HFABS/frontend/views/admin-home.php',
                'cashier' => '/HFABS/frontend/views/admin-home.php',
                'superadmin' => '/HFABS/frontend/views/superadmin-home.php',
                default => '/HFABS/frontend/views/customer-home.php',
            };

            // Create a simple token (in production, use JWT)
            $token = bin2hex(random_bytes(32));
            
            echo json_encode([
                'success' => true,
                'redirect' => $redirect,
                'token' => $token,
                'userData' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'branch_id' => $user['branch_id'],
                    'branch_name' => $_SESSION['branch_name'] ?? ''
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
        }
        exit;
    }


    public function logout()
    {
        require_once __DIR__ . '/../config/config.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? 'customer';

        $_SESSION = [];
        session_destroy();
        
        // Redirect to frontend login pages (not backend)
        if($role === 'admin' || $role === 'cashier'){
            header("Location: /HFABS/frontend/views/admin-login.html");
        } elseif($role === 'superadmin'){
            header("Location: /HFABS/frontend/views/superadmin-login.html");
        }else{
            header("Location: /HFABS/frontend/views/customer-login.html");
        }

        exit;
    }

    // REQUEST PASSWORD RESET
    public function requestPasswordReset()
    {
        header('Content-Type: application/json');
        error_log("[AuthController] Password reset request received");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("[AuthController] ERROR: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');
        error_log("[AuthController] Password reset requested for email: " . $email);

        if (!$email) {
            error_log("[AuthController] ERROR: Email is required but not provided");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("[AuthController] ERROR: Invalid email format - " . $email);
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }

        $userModel = $this->model('User');
        error_log("[AuthController] Looking up user with email: " . $email);
        $user = $userModel->getUserByEmail($email);

        if (!$user) {
            error_log("[AuthController] WARNING: No user found with email: " . $email . " - Returning generic success message");
            // Don't reveal that email doesn't exist
            echo json_encode([
                'success' => true, 
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ]);
            exit;
        }

        error_log("[AuthController] User found: " . $user['username'] . " (ID: " . $user['user_id'] . ")");

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        error_log("[AuthController] Generated token: " . $token);
        error_log("[AuthController] Token length: " . strlen($token));
        error_log("[AuthController] Token expires at: " . $expiresAt);

        // Save token to database
        if ($userModel->createPasswordResetToken($user['user_id'], $token, $expiresAt)) {
            error_log("[AuthController] Token saved to database successfully");
            
            // Send email
            require_once __DIR__ . '/../services/EmailService.php';
            error_log("[AuthController] Initializing EmailService...");
            $emailService = new EmailService();
            
            if ($emailService->sendPasswordResetEmail($user['email'], $user['username'], $token)) {
                error_log("[AuthController] Password reset email sent successfully to: " . $user['email']);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Password reset link has been sent to your email.'
                ]);
            } else {
                error_log("[AuthController] ERROR: Failed to send reset email to: " . $user['email']);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to send reset email. Please try again.'
                ]);
            }
        } else {
            error_log("[AuthController] ERROR: Failed to save reset token to database for user ID: " . $user['user_id']);
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to generate reset token. Please try again.'
            ]);
        }

        exit;
    }

    // VERIFY RESET TOKEN
    public function verifyResetToken()
    {
        header('Content-Type: application/json');
        error_log("[AuthController] Token verification request received");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("[AuthController] ERROR: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $token = trim($data['token'] ?? '');
        error_log("[AuthController] Verifying token: " . substr($token, 0, 8) . "...");
        error_log("[AuthController] Full token length: " . strlen($token));
        error_log("[AuthController] Raw token data: " . print_r($data, true));

        if (!$token) {
            error_log("[AuthController] ERROR: Token is required but not provided");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Token is required']);
            exit;
        }

        $userModel = $this->model('User');
        error_log("[AuthController] Looking up token in database...");
        $tokenData = $userModel->getValidResetToken($token);
        
        if ($tokenData) {
            error_log("[AuthController] Token found and valid for user: " . $tokenData['username']);
            error_log("[AuthController] Token expires at: " . $tokenData['expires_at']);
            error_log("[AuthController] Current time: " . date('Y-m-d H:i:s'));
            echo json_encode([
                'success' => true, 
                'message' => 'Token is valid',
                'user' => [
                    'email' => $tokenData['email'],
                    'username' => $tokenData['username']
                ]
            ]);
        } else {
            error_log("[AuthController] WARNING: Invalid or expired token");
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid or expired token'
            ]);
        }

        exit;
    }

    // RESET PASSWORD
    public function resetPassword()
    {
        header('Content-Type: application/json');
        error_log("[AuthController] Password reset request received");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("[AuthController] ERROR: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $token = trim($data['token'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';
        
        error_log("[AuthController] Resetting password with token: " . substr($token, 0, 8) . "...");

        if (!$token || !$password || !$confirmPassword) {
            error_log("[AuthController] ERROR: Missing required fields");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        if ($password !== $confirmPassword) {
            error_log("[AuthController] ERROR: Passwords do not match");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        if (strlen($password) < 8) {
            error_log("[AuthController] ERROR: Password too short - " . strlen($password) . " characters");
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
            exit;
        }

        $userModel = $this->model('User');
        $tokenData = $userModel->getValidResetToken($token);

        if (!$tokenData) {
            error_log("[AuthController] ERROR: Invalid or expired token");
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid or expired token'
            ]);
            exit;
        }

        error_log("[AuthController] Resetting password for user: " . $tokenData['username']);

        // Reset password
        if ($userModel->resetPassword($tokenData['user_id'], $password)) {
            error_log("[AuthController] Password reset successfully for user: " . $tokenData['username']);
            echo json_encode([
                'success' => true, 
                'message' => 'Password has been reset successfully',
                'redirect' => '/HFABS/frontend/views/customer-login.html'
            ]);
        } else {
            error_log("[AuthController] ERROR: Failed to reset password for user: " . $tokenData['username']);
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to reset password. Please try again.'
            ]);
        }

        exit;
    }

    public function updateProfile()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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

        $data = json_decode(file_get_contents('php://input'), true);

        $username      = trim($data['username']       ?? '');
        $email         = trim($data['email']          ?? '');
        $contactNumber = trim($data['contact_number'] ?? '');

        if (!$username || !$email) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }

        $userModel = $this->model('User');
        $result    = $userModel->updateProfile(
            (int) $_SESSION['user_id'],
            $username,
            $email,
            $contactNumber
        );

        if ($result['success']) {
            // Keep session in sync
            $_SESSION['user_name'] = $username;
            $_SESSION['email']     = $email;

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
        } else {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }

        exit;
    }

    public function changePassword()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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

        $data      = json_decode(file_get_contents('php://input'), true);
        $currentPw = $data['current_password'] ?? '';
        $newPw     = $data['new_password']     ?? '';

        if (!$currentPw || !$newPw) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit;
        }

        if (strlen($newPw) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        $userModel      = $this->model('User');
        $hashedPassword = $userModel->getPasswordById((int) $_SESSION['user_id']);

        if (!$hashedPassword || !password_verify($currentPw, $hashedPassword)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        $newHashed = password_hash($newPw, PASSWORD_DEFAULT);
        $changed   = $userModel->changePassword((int) $_SESSION['user_id'], $newHashed);

        if ($changed) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to change password.']);
        }

        exit;
    }
}