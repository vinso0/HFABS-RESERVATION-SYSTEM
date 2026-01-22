<?php
class AuthController extends Controller
{

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /HFABS/frontend/views/customer-register.html");
            exit;
        }

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $contact = $_POST['contact_number'] ?? '';

        if (!$username || !$contact || !$email || !$password) {
            $_SESSION['register_error'] = "All fields are required.";
            header("Location: /HFABS/frontend/views/customer-register.html");
            exit;
        }

        $user = $this->model('User');

        if ($user->register($username, $email, $password, $contact)) {
            $_SESSION['register_success'] = "Account created successfully!";
            header("Location: /HFABS/frontend/views/customer-login.html");
        } else {
            $_SESSION['register_error'] = "Registration failed.";
            header("Location: /HFABS/frontend/views/customer-register.html");
        }
        exit;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /HFABS/frontend/views/customer-login.html");
            exit;
        }

        $identifier = $_POST['email'] ?? $_POST['username'] ??  '';
        $password = $_POST['password'] ?? '';

        $userModel = $this->model('User');
        $user = $userModel->login($identifier);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin'){
                header("Location: /HFABS/frontend/views/admin-home.php");
            } elseif ($user['role'] === 'superadmin'){
                header("Location: /HFABS/frontend/views/superadmin-home.php");
            } else {
                header("Location: /HFABS/frontend/views/customer-home.php");
            }
        } else {
            $_SESSION['login_error'] = "Invalid credentials.";
            if (isset($_SERVER['HTTP_REFERER'])) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
            } else {
                header("Location: /HFABS/frontend/views/customer-login.html");
            }
                }
                exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? 'customer';

        $_SESSION = [];
        session_destroy();
        if($role === 'admin'){
            header("Location: /HFABS/frontend/views/admin-login.html");
        }else{
            header("Location: /HFABS/frontend/views/customer-login.html");
        }

        exit;
    }

}
