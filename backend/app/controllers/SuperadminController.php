<?php

class SuperadminController extends Controller
{
    private $superadminModel;

    public function __construct()
    {
        $this->superadminModel = $this->model('SuperadminModel');
    }

    // ── Auth guard ──
    private function requireSuperadmin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }

    private function json($data)
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function db()
    {
        $database = new Database();
        return $database->getConnection(); // returns mysqli instance
    }

    // =========================================================
    //  DASHBOARD STATS
    //  URL: ?url=superadmin/dashboardStats
    // =========================================================
    public function dashboardStats()
    {
        $this->requireSuperadmin();
        
        $stats = $this->superadminModel->getDashboardStats();

        $this->json([
            'success' => true,
            'data'    => $stats
        ]);
    }

    // ── URL: ?url=superadmin/recentAdmins ──
    public function recentAdmins()
    {
        $this->requireSuperadmin();
        
        $recentAdmins = $this->superadminModel->getRecentAdmins();

        $this->json(['success' => true, 'data' => $recentAdmins]);
    }

    // =========================================================
    //  DOMAIN MANAGEMENT
    //  POST ?url=superadmin/updateDomains
    // =========================================================
    public function updateDomains()
    {
        $this->requireSuperadmin();
        
        require_once __DIR__ . '/../services/DomainBlacklistService.php';
        $domainService = new DomainBlacklistService();
        
        $result = $domainService->updateDomainLists();
        
        $this->json($result);
    }
    
    // =========================================================
    //  DOMAIN STATUS
    //  GET ?url=superadmin/domainStatus
    // =========================================================
    public function domainStatus()
    {
        $this->requireSuperadmin();
        
        require_once __DIR__ . '/../services/DomainBlacklistService.php';
        $domainService = new DomainBlacklistService();
        
        $blacklist = $domainService->getBlacklist();
        $allowlist = $domainService->getAllowlistFromFile();
        $wildcardDomains = $domainService->getWildcardDomains();
        $needsUpdate = $domainService->needsUpdate();
        
        $timestampFile = __DIR__ . '/../../storage/domains_last_updated.txt';
        $lastUpdated = file_exists($timestampFile) ? file_get_contents($timestampFile) : 'Never';
        
        $this->json([
            'success' => true,
            'data' => [
                'blacklisted_count' => count($blacklist),
                'allowed_count' => count($allowlist),
                'wildcard_domains' => $wildcardDomains,
                'last_updated' => $lastUpdated,
                'needs_update' => $needsUpdate,
                'source' => 'https://github.com/disposable-email-domains/disposable-email-domains'
            ]
        ]);
    }

    // =========================================================
    //  ADMIN PASSWORD RESET
    //  POST ?url=superadmin/resetAdminPassword/{id}
    // =========================================================
    public function resetAdminPassword($id)
    {
        $this->requireSuperadmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Method not allowed']);
        }
        
        $id = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);
        
        $superadminPassword = $input['superadmin_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';
        
        $result = $this->superadminModel->resetAdminPassword(
            $id, 
            $superadminPassword, 
            $newPassword, 
            $confirmPassword, 
            $_SESSION['user_id']
        );
        
        // Set appropriate HTTP status code
        if (!$result['success']) {
            if (strpos($result['message'], 'not found') !== false) {
                http_response_code(404);
            } elseif (strpos($result['message'], 'Incorrect') !== false) {
                http_response_code(401);
            } elseif (strpos($result['message'], 'required') !== false || strpos($result['message'], 'password must be') !== false || strpos($result['message'], 'do not match') !== false) {
                http_response_code(400);
            } else {
                http_response_code(500);
            }
        }
        
        $this->json($result);
    }

    // =========================================================
    //  ADMIN MANAGEMENT
    //  GET/POST  ?url=superadmin/admins
    //  PUT/DELETE ?url=superadmin/admins/{id}
    // =========================================================
    public function admins($id = null)
    {
        $this->requireSuperadmin();
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET'    && $id === null) { $this->getAdmins();        return; }
        if ($method === 'POST'   && $id === null) { $this->addAdmin();         return; }
        if ($method === 'PUT'    && $id !== null) { $this->updateAdmin($id);   return; }
        if ($method === 'DELETE' && $id !== null) { $this->deleteAdmin($id);   return; }

        http_response_code(405);
        $this->json(['success' => false, 'message' => 'Method not allowed']);
    }

    private function getAdmins()
    {
        $admins = $this->superadminModel->getAdmins();
        $this->json(['success' => true, 'data' => $admins]);
    }

    private function addAdmin()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $username       = trim($input['username']       ?? '');
        $email          = trim($input['email']          ?? '');
        $contact_number = trim($input['contact_number'] ?? '');
        $role           = $input['role']                ?? 'admin';
        $branch_id      = !empty($input['branch_id'])   ? (int)$input['branch_id'] : null;
        $is_active      = isset($input['is_active'])    ? (int)$input['is_active'] : 1;
        $password       = $input['password']            ?? '';

        $result = $this->superadminModel->addAdmin($username, $email, $contact_number, $role, $branch_id, $is_active, $password);

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    private function updateAdmin($id)
    {
        $id    = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $username       = trim($input['username']       ?? '');
        $email          = trim($input['email']          ?? '');
        $contact_number = trim($input['contact_number'] ?? '');
        $role           = $input['role']                ?? 'admin';
        $branch_id      = !empty($input['branch_id'])   ? (int)$input['branch_id'] : null;
        $is_active      = isset($input['is_active'])    ? (int)$input['is_active'] : 1;

        $result = $this->superadminModel->updateAdmin($id, $username, $email, $contact_number, $role, $branch_id, $is_active);

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    private function deleteAdmin($id)
    {
        $id = (int)$id;
        $result = $this->superadminModel->deleteAdmin($id);

        if (!$result['success']) {
            http_response_code(404);
        }

        $this->json($result);
    }

    // =========================================================
    //  SERVICE MANAGEMENT
    //  GET/POST  ?url=superadmin/services
    //  PUT/DELETE ?url=superadmin/services/{id}
    // =========================================================
    public function services($id = null)
    {
        $this->requireSuperadmin();
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET'    && $id === null)           { $this->getServices();        return; }
        if ($method === 'GET'    && $id === 'deactivated')  { $this->getDeactivatedServices(); return; }
        if ($method === 'POST'   && $id === null)           { $this->addService();         return; }
        if ($method === 'PUT'    && $id !== null)           { $this->updateService($id);   return; }
        if ($method === 'DELETE' && $id !== null)           { $this->deleteService($id);   return; }

        http_response_code(405);
        $this->json(['success' => false, 'message' => 'Method not allowed']);
    }
    
    private function getServices()
    {
        ob_start();

        $services = $this->superadminModel->getServices();
        $baseUrl  = $this->getBaseUrl();

        foreach ($services as &$s) {
            $s['image_url'] = !empty($s['image_path'])
                ? $baseUrl . '/HFABS/backend/public/' . ltrim($s['image_path'], '/')
                : null;
        }
        unset($s);

        ob_end_clean();
        $this->json(['success' => true, 'data' => $services]);
    }

    private function getDeactivatedServices()
    {
        $services = $this->superadminModel->getDeactivatedServices();
        $this->json(['success' => true, 'data' => $services]);
    }

    private function addService()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $service_name     = trim($input['service_name']      ?? '');
        $description      = trim($input['description']       ?? '');
        $base_price       = isset($input['base_price'])       ? (float)$input['base_price']       : 0;
        $duration_minutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes']   : 30;
        $category_id      = isset($input['category_id'])      ? (int)$input['category_id']        : 1;
        $is_active        = isset($input['is_active'])        ? (int)$input['is_active']          : 1;
        $branch_ids       = $input['branch_ids']  ?? [];
        $reactivate_id    = isset($input['reactivate_id'])    ? (int)$input['reactivate_id']      : null;
        $image_base64     = $input['image_base64'] ?? null;   // NEW: base64 image data

        // Handle image upload
        $imagePath = null;
        if ($image_base64) {
            require_once __DIR__ . '/../services/ImageUploadService.php';
            $imgService = new ImageUploadService();
            $imgResult  = $imgService->saveBase64Image($image_base64);
            if (!$imgResult['success']) {
                http_response_code(400);
                $this->json(['success' => false, 'message' => $imgResult['error']]);
                return;
            }
            $imagePath = $imgResult['path'];
        }

        $result = $this->superadminModel->addService(
            $service_name, $description, $base_price, $duration_minutes,
            $category_id, $is_active, $branch_ids, $reactivate_id, $imagePath
        );

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    private function updateService($id)
    {
        $id    = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $service_name     = trim($input['service_name']      ?? '');
        $description      = trim($input['description']       ?? '');
        $base_price       = isset($input['base_price'])       ? (float)$input['base_price']       : 0;
        $duration_minutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes']   : 30;
        $category_id      = isset($input['category_id'])      ? (int)$input['category_id']        : 1;
        $is_active        = isset($input['is_active'])        ? (int)$input['is_active']          : 1;
        $branch_ids       = $input['branch_ids']  ?? [];
        $image_base64     = $input['image_base64']  ?? null;  // NEW: base64 image data
        $remove_image     = isset($input['remove_image']) && $input['remove_image'] == true;

        // Handle image
        $imagePath    = null;
        $updateImage  = false;

        if ($remove_image) {
            // Explicitly clearing the image
            $updateImage = true;
            $imagePath   = null;
            // Delete the old file
            $existing = $this->superadminModel->getServiceById($id);
            if ($existing && !empty($existing['image_path'])) {
                require_once __DIR__ . '/../services/ImageUploadService.php';
                (new ImageUploadService())->deleteImage($existing['image_path']);
            }
        } elseif ($image_base64) {
            $existing = $this->superadminModel->getServiceById($id);
            $oldPath  = $existing['image_path'] ?? null;

            require_once __DIR__ . '/../services/ImageUploadService.php';
            $imgService = new ImageUploadService();
            $imgResult  = $imgService->saveBase64Image($image_base64, $oldPath);
            if (!$imgResult['success']) {
                http_response_code(400);
                $this->json(['success' => false, 'message' => $imgResult['error']]);
                return;
            }
            $imagePath   = $imgResult['path'];
            $updateImage = true;
        }

        $result = $this->superadminModel->updateService(
            $id, $service_name, $description, $base_price, $duration_minutes,
            $category_id, $is_active, $branch_ids, $updateImage ? $imagePath : false
        );

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    // Add this helper at the bottom of private methods:
    private function getBaseUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'];
    }

    private function deleteService($id)
    {
        $id = (int)$id;
        $result = $this->superadminModel->deleteService($id);

        if (!$result['success']) {
            if (strpos($result['message'], 'Cannot delete service') !== false) {
                http_response_code(409);
            } else {
                http_response_code(404);
            }
        }

        $this->json($result);
    }

    // =========================================================
    //  BRANCH MANAGEMENT
    //  GET/POST  ?url=superadmin/branches
    //  PUT/DELETE ?url=superadmin/branches/{id}
    // =========================================================
    public function branches($id = null)
    {
        $this->requireSuperadmin();
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET'    && $id === null) { $this->getBranches();        return; }
        if ($method === 'POST'   && $id === null) { $this->addBranch();          return; }
        if ($method === 'PUT'    && $id !== null) { $this->updateBranch($id);    return; }
        if ($method === 'DELETE' && $id !== null) { $this->deleteBranch($id);    return; }

        http_response_code(405);
        $this->json(['success' => false, 'message' => 'Method not allowed']);
    }

    private function getBranches()
    {
        $branches = $this->superadminModel->getBranches();
        $this->json(['success' => true, 'data' => $branches]);
    }

    private function addBranch()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $branch_name       = trim($input['branch_name']      ?? '');
        $branch_location   = trim($input['branch_location']  ?? '');
        $contact_number    = trim($input['contact_number']   ?? '');
        $email             = trim($input['email']            ?? '');
        $opening_time      = $input['opening_time']          ?? '';
        $closing_time      = $input['closing_time']          ?? '';
        $down_payment_rate = isset($input['down_payment_rate']) ? (float)$input['down_payment_rate'] : 0.5;
        $status            = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'active';

        $result = $this->superadminModel->addBranch($branch_name, $branch_location, $contact_number, $email, $opening_time, $closing_time, $down_payment_rate, $status);

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    private function updateBranch($id)
    {
        $id = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $branch_name       = trim($input['branch_name']      ?? '');
        $branch_location   = trim($input['branch_location']  ?? '');
        $contact_number    = trim($input['contact_number']   ?? '');
        $email             = trim($input['email']            ?? '');
        $opening_time      = $input['opening_time']          ?? '';
        $closing_time      = $input['closing_time']          ?? '';
        $down_payment_rate = isset($input['down_payment_rate']) ? (float)$input['down_payment_rate'] : 0.5;
        $status            = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'active';

        $result = $this->superadminModel->updateBranch($id, $branch_name, $branch_location, $contact_number, $email, $opening_time, $closing_time, $down_payment_rate, $status);

        if (!$result['success']) {
            http_response_code(400);
        }

        $this->json($result);
    }

    private function deleteBranch($id)
    {
        $id = (int)$id;
        $result = $this->superadminModel->deleteBranch($id);

        if (!$result['success']) {
            if (strpos($result['message'], 'Cannot delete branch') !== false) {
                http_response_code(409);
            } else {
                http_response_code(404);
            }
        }

        $this->json($result);
    }

    // =========================================================
    //  CATEGORIES MANAGEMENT
    //  GET/POST  ?url=superadmin/categories
    //  PUT/DELETE ?url=superadmin/categories/{id}
    // =========================================================
    public function categories($id = null)
    {
        $this->requireSuperadmin();
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET'    && $id === null) { $this->getCategories();        return; }
        if ($method === 'POST'   && $id === null) { $this->addCategory();         return; }
        if ($method === 'PUT'    && $id !== null) { $this->updateCategory($id);   return; }
        if ($method === 'DELETE' && $id !== null) { $this->deleteCategory($id);   return; }

            $this->json(['success' => true, 'message' => 'Branch deleted successfully.']);
        }

    private function getCategories()
    {
        $result = $this->superadminModel->getCategories();
        $this->json($result);
    }

    private function addCategory()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $category_name    = trim($input['category_name']    ?? '');
        $description      = trim($input['description']      ?? '');
        $def_capacity     = isset($input['def_capacity']) ? (int)$input['def_capacity'] : 5;
        $is_active        = isset($input['is_active'])        ? (int)$input['is_active'] : 1;
        $branch_ids      = $input['branch_ids'] ?? [];

        $result = $this->superadminModel->addCategory($category_name, $description, $def_capacity, $is_active, $branch_ids);
        
        // Set HTTP status code if provided in result
        if (isset($result['status'])) {
            http_response_code($result['status']);
        }
        
        $this->json($result);
    }

    private function updateCategory($id)
    {
        $id    = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $category_name    = trim($input['category_name']    ?? '');
        $description      = trim($input['description']      ?? '');
        $def_capacity     = isset($input['def_capacity']) ? (int)$input['def_capacity'] : 5;
        $is_active        = isset($input['is_active'])        ? (int)$input['is_active'] : 1;
        $branch_ids      = $input['branch_ids'] ?? [];

        $result = $this->superadminModel->updateCategory($id, $category_name, $description, $def_capacity, $is_active, $branch_ids);
        
        // Set HTTP status code if provided in result
        if (isset($result['status'])) {
            http_response_code($result['status']);
        }
        
        $this->json($result);
    }

    private function deleteCategory($id)
    {
        $id = (int)$id;
        $result = $this->superadminModel->deleteCategory($id);
        
        // Set HTTP status code if provided in result
        if (isset($result['status'])) {
            http_response_code($result['status']);
        }
        
        $this->json($result);
    }
}