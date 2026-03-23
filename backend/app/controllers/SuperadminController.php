<?php

class SuperadminController extends Controller
{
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
        $conn = $this->db();

        // Total admins + cashiers
        $result      = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin','cashier') AND deleted_at IS NULL");
        $totalAdmins = (int) $result->fetch_assoc()['total'];

        // Active admins + cashiers
        $result       = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin','cashier') AND is_active = 1 AND deleted_at IS NULL");
        $activeAdmins = (int) $result->fetch_assoc()['total'];

        // Total branches
        $result        = $conn->query("SELECT COUNT(*) AS total FROM branch");
        $totalBranches = (int) $result->fetch_assoc()['total'];

        // Active branches
        $result         = $conn->query("SELECT COUNT(*) AS total FROM branch WHERE status = 'active'");
        $activeBranches = (int) $result->fetch_assoc()['total'];

        // Total system users (not deleted)
        $result     = $conn->query("SELECT COUNT(*) AS total FROM users WHERE deleted_at IS NULL");
        $totalUsers = (int) $result->fetch_assoc()['total'];

        $this->json([
            'success' => true,
            'data'    => [
                'total_admins'      => $totalAdmins,
                'active_admins'     => $activeAdmins,
                'total_branches'    => $totalBranches,
                'active_branches'   => $activeBranches,
                'inactive_branches' => $totalBranches - $activeBranches,
                'total_users'       => $totalUsers,
            ]
        ]);
    }

    // ── URL: ?url=superadmin/recentAdmins ──
    public function recentAdmins()
    {
        $this->requireSuperadmin();
        $conn = $this->db();

        $stmt = $conn->prepare(
            "SELECT u.user_id, u.username, u.email, u.contact_number,
                    u.role, u.branch_id, u.is_active, u.created_at,
                    b.branch_name
             FROM users u
             LEFT JOIN branch b ON u.branch_id = b.branch_id
             WHERE u.role IN ('admin','cashier') AND u.deleted_at IS NULL
             ORDER BY u.created_at DESC
             LIMIT 5"
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->json(['success' => true, 'data' => $rows]);
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
        $conn = $this->db();

        $stmt = $conn->prepare(
            "SELECT u.user_id, u.username, u.email, u.contact_number,
                    u.role, u.branch_id, u.is_active, u.created_at,
                    b.branch_name
             FROM users u
             LEFT JOIN branch b ON u.branch_id = b.branch_id
             WHERE u.role IN ('admin','cashier') AND u.deleted_at IS NULL
             ORDER BY u.created_at DESC"
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->json(['success' => true, 'data' => $rows]);
    }

    private function addAdmin()
    {
        $conn  = $this->db();
        $input = json_decode(file_get_contents('php://input'), true);

        $username       = trim($input['username']       ?? '');
        $email          = trim($input['email']          ?? '');
        $contact_number = trim($input['contact_number'] ?? '');
        $role           = $input['role']                ?? 'admin';
        $branch_id      = !empty($input['branch_id'])   ? (int)$input['branch_id'] : null;
        $is_active      = isset($input['is_active'])    ? (int)$input['is_active'] : 1;
        $password       = $input['password']            ?? '';

        if (!$username || !$email || !$password) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Username, email, and password are required.']);
        }

        if (!in_array($role, ['admin', 'cashier'])) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Invalid role. Must be admin or cashier.']);
        }

        // Check duplicate email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            $this->json(['success' => false, 'message' => 'Email is already in use.']);
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, contact_number, password, role, branch_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssii', $username, $email, $contact_number, $hashed, $role, $branch_id, $is_active);
        $stmt->execute();
        $newId = $conn->insert_id;

        $this->json(['success' => true, 'message' => 'Admin account added successfully.', 'user_id' => $newId]);
    }

    private function updateAdmin($id)
    {
        $conn  = $this->db();
        $id    = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $username       = trim($input['username']       ?? '');
        $email          = trim($input['email']          ?? '');
        $contact_number = trim($input['contact_number'] ?? '');
        $role           = $input['role']                ?? 'admin';
        $branch_id      = !empty($input['branch_id'])   ? (int)$input['branch_id'] : null;
        $is_active      = isset($input['is_active'])    ? (int)$input['is_active'] : 1;

        if (!$username || !$email) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Username and email are required.']);
        }

        if (!in_array($role, ['admin', 'cashier'])) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Invalid role.']);
        }

        // Check duplicate email (exclude self)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            $this->json(['success' => false, 'message' => 'Email is already used by another account.']);
        }

        $stmt = $conn->prepare(
            "UPDATE users SET username=?, email=?, contact_number=?, role=?, branch_id=?, is_active=?
             WHERE user_id=? AND deleted_at IS NULL"
        );
        $stmt->bind_param('ssssiis', $username, $email, $contact_number, $role, $branch_id, $is_active, $id);
        $stmt->execute();

        $this->json(['success' => true, 'message' => 'Admin account updated successfully.']);
    }

    private function deleteAdmin($id)
    {
        $conn = $this->db();
        $id   = (int)$id;

        // Soft delete
        $stmt = $conn->prepare(
            "UPDATE users SET deleted_at = NOW(), is_active = 0
             WHERE user_id = ? AND role IN ('admin','cashier')"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($conn->affected_rows === 0) {
            http_response_code(404);
            $this->json(['success' => false, 'message' => 'Admin not found.']);
        }

        $this->json(['success' => true, 'message' => 'Admin account deleted successfully.']);
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
        $conn   = $this->db();
        $result = $conn->query("SELECT * FROM branch ORDER BY branch_id ASC");

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->json(['success' => true, 'data' => $rows]);
    }

    private function addBranch()
    {
        $conn  = $this->db();
        $input = json_decode(file_get_contents('php://input'), true);

        $branch_name       = trim($input['branch_name']      ?? '');
        $branch_location   = trim($input['branch_location']  ?? '');
        $contact_number    = trim($input['contact_number']   ?? '');
        $email             = trim($input['email']            ?? '');
        $opening_time      = $input['opening_time']          ?? '';
        $closing_time      = $input['closing_time']          ?? '';
        $down_payment_rate = isset($input['down_payment_rate']) ? (float)$input['down_payment_rate'] : 0.5;
        $status            = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'active';

        if (!$branch_name || !$branch_location || !$opening_time || !$closing_time) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Branch name, location, and hours are required.']);
        }

        $stmt = $conn->prepare(
            "INSERT INTO branch (branch_name, branch_location, contact_number, opening_time, closing_time, down_payment_rate, email, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssds s', $branch_name, $branch_location, $contact_number, $opening_time, $closing_time, $down_payment_rate, $email, $status);
        $stmt->execute();
        $newId = $conn->insert_id;

        $this->json(['success' => true, 'message' => 'Branch added successfully.', 'branch_id' => $newId]);
    }

    private function updateBranch($id)
    {
        $conn  = $this->db();
        $id    = (int)$id;
        $input = json_decode(file_get_contents('php://input'), true);

        $branch_name       = trim($input['branch_name']      ?? '');
        $branch_location   = trim($input['branch_location']  ?? '');
        $contact_number    = trim($input['contact_number']   ?? '');
        $email             = trim($input['email']            ?? '');
        $opening_time      = $input['opening_time']          ?? '';
        $closing_time      = $input['closing_time']          ?? '';
        $down_payment_rate = isset($input['down_payment_rate']) ? (float)$input['down_payment_rate'] : 0.5;
        $status            = in_array($input['status'] ?? '', ['active','inactive']) ? $input['status'] : 'active';

        if (!$branch_name || !$branch_location || !$opening_time || !$closing_time) {
            http_response_code(400);
            $this->json(['success' => false, 'message' => 'Branch name, location, and hours are required.']);
        }

        $stmt = $conn->prepare(
            "UPDATE branch
             SET branch_name=?, branch_location=?, contact_number=?,
                 opening_time=?, closing_time=?, down_payment_rate=?, email=?, status=?
             WHERE branch_id=?"
        );
        $stmt->bind_param('sssssdssi', $branch_name, $branch_location, $contact_number, $opening_time, $closing_time, $down_payment_rate, $email, $status, $id);
        $stmt->execute();

        $this->json(['success' => true, 'message' => 'Branch updated successfully.']);
    }

    private function deleteBranch($id)
    {
        $conn = $this->db();
        $id   = (int)$id;

        // Safety: prevent deleting branch with assigned users
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM users WHERE branch_id = ? AND deleted_at IS NULL"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $cnt = (int) $stmt->get_result()->fetch_assoc()['cnt'];

        if ($cnt > 0) {
            http_response_code(409);
            $this->json([
                'success' => false,
                'message' => 'Cannot delete branch: ' . $cnt . ' admin account(s) are still assigned to it. Reassign or delete them first.'
            ]);
        }

        $stmt = $conn->prepare("DELETE FROM branch WHERE branch_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($conn->affected_rows === 0) {
            http_response_code(404);
            $this->json(['success' => false, 'message' => 'Branch not found.']);
        }

        $this->json(['success' => true, 'message' => 'Branch deleted successfully.']);
    }
}
