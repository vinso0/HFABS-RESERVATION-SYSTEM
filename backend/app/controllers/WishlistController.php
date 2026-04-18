<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/WishlistModel.php';

class WishlistController extends Controller
{
    private $wishlistModel;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->wishlistModel = new WishlistModel();
    }

    // ─────────────────────────────────────────────
    // POST /wishlist/toggle
    // Body: { branch_id, wishlist_type, branch_service_override_id?, 
    //         default_service_id?, package_id? }
    // ─────────────────────────────────────────────
    public function toggle()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request data']);
            return;
        }

        $requiredBase = ['branch_id', 'wishlist_type'];
        foreach ($requiredBase as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
                return;
            }
        }

        $type = $data['wishlist_type'];
        if ($type === 'service' && empty($data['branch_service_override_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'branch_service_override_id required for service type']);
            return;
        }
        if ($type === 'package' && empty($data['package_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'package_id required for package type']);
            return;
        }

        $data['user_id'] = (int) $_SESSION['user_id'];

        $result = $this->wishlistModel->toggle($data);

        if ($result === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Wishlist toggle failed']);
            return;
        }

        echo json_encode([
            'success' => true,
            'action'  => $result,  // 'added' or 'removed'
            'message' => $result === 'added'
                ? 'Added to wishlist!'
                : 'Removed from wishlist.'
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /wishlist/status?branch_id={id}
    // Returns which services/packages the current user wishlisted at this branch
    // ─────────────────────────────────────────────
    public function status()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            // Return empty arrays — not an error, just not logged in
            echo json_encode(['success' => true, 'service_ids' => [], 'package_ids' => []]);
            return;
        }

        $branchId = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
        if (!$branchId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'branch_id required']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $status = $this->wishlistModel->getUserWishlistForBranch($userId, $branchId);

        echo json_encode([
            'success'     => true,
            'service_ids' => $status['service_ids'],
            'package_ids' => $status['package_ids']
        ]);
    }

    // ─────────────────────────────────────────────
    // GET /wishlist/mine
    // Returns all wishlist items for the current user
    // ─────────────────────────────────────────────
    public function mine()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $userId   = (int) $_SESSION['user_id'];
        $wishlist = $this->wishlistModel->getUserAllWishlists($userId);

        echo json_encode(['success' => true, 'data' => $wishlist]);
    }

    // ─────────────────────────────────────────────
    // GET /wishlist/adminServices?branch_id={id}
    // Admin: service wishlist counts for a branch
    // ─────────────────────────────────────────────
    public function adminServices()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        // Only admin/superadmin/cashier roles
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'superadmin', 'cashier'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }

        $branchId = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
        if (!$branchId) {
            // Use the admin's own branch if not specified
            $branchId = (int) ($_SESSION['branch_id'] ?? 0);
        }
        if (!$branchId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'branch_id required']);
            return;
        }

        $services = $this->wishlistModel->getServiceWishlistCounts($branchId);
        $packages = $this->wishlistModel->getPackageWishlistCounts($branchId);
        $summary  = $this->wishlistModel->getWishlistSummary($branchId);

        echo json_encode([
            'success'  => true,
            'services' => $services,
            'packages' => $packages,
            'summary'  => $summary
        ]);
    }
}