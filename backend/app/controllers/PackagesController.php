<?php
class PackagesController extends Controller
{
    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function authorizeAdmin()
    {
        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'User not authorized'
            ], 403);
        }

        $branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;

        if ($branchId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Branch not found for this admin'
            ], 400);
        }

        return $branchId;
    }

    public function getAll()
    {
        $branchId = $this->authorizeAdmin();

        $packageModel = $this->model('Package');
        $packages = $packageModel->getAllPackages($branchId);

        $this->jsonResponse([
            'success' => true,
            'data' => $packages
        ]);
    }

    public function getBranchServices()
    {
        $branchId = $this->authorizeAdmin();

        $packageModel = $this->model('Package');
        $services = $packageModel->getBranchServices($branchId);

        $this->jsonResponse([
            'success' => true,
            'data' => $services
        ]);
    }

    public function getOne($packageId = null)
    {
        $branchId = $this->authorizeAdmin();

        if (!$packageId || !is_numeric($packageId)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid package ID'
            ], 400);
        }

        $packageModel = $this->model('Package');
        $package = $packageModel->getPackageById((int)$packageId, $branchId);

        if (!$package) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Package not found'
            ], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $package
        ]);
    }

    public function create()
    {
        $branchId = $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        $packageModel = $this->model('Package');
        $result = $packageModel->createPackage($branchId, $payload);

        if (!$result['success']) {
            $this->jsonResponse($result, 400);
        }

        $this->jsonResponse($result);
    }

    public function update()
    {
        $branchId = $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
        }

        $payload = json_decode(file_get_contents('php://input'), true);

        $packageModel = $this->model('Package');
        $result = $packageModel->updatePackage($branchId, $payload);

        if (!$result['success']) {
            $this->jsonResponse($result, 400);
        }

        $this->jsonResponse($result);
    }

    public function delete()
    {
        $branchId = $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $packageId = isset($payload['package_id']) ? (int)$payload['package_id'] : 0;

        if ($packageId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid package ID'
            ], 400);
        }

        $packageModel = $this->model('Package');
        $result = $packageModel->deletePackage($branchId, $packageId);

        if (!$result['success']) {
            $this->jsonResponse($result, 400);
        }

        $this->jsonResponse($result);
    }
}
