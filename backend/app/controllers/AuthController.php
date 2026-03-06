<?php
class AuthController extends Controller
{

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

        // Validate required fields
        if (!$username || !$email || !$password || !$contact) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            exit;
        }

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit;
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userModel = $this->model('User');

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
                'superadmin' => '/HFABS/frontend/views/superadmin-home.php',
                'cashier' => '/HFABS/frontend/views/admin-home.php',
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
                    'role' => $user['role']
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
        
        if($role === 'admin' || $role === 'cashier'){
            header("Location: " . BASE_URL . "/../../frontend/views/admin-login.html");
        } elseif($role === 'superadmin'){
            header("Location: " . BASE_URL . "/../../frontend/views/superadmin-login.html");
        }else{
            header("Location: " . BASE_URL . "/../../frontend/views/customer-login.html");
        }

        exit;
    }

}