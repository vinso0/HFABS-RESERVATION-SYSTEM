<?php

class SuperadminModel
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Enhanced email validation with allowlist approach
     * Uses dynamically updated domain lists
     */
    public function validateEmail($email)
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
        
        // Check if domain is in allowlist or matches wildcard
        if (in_array($domain, $allowlist)) {
            return true;
        }
        
        // Check wildcard domains (e.g., *.edu.ph, *.gov.ph)
        foreach ($wildcardDomains as $wildcard) {
            $pattern = str_replace('*', '.*', preg_quote($wildcard, '/'));
            if (preg_match('/^' . $pattern . '$/', $domain)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get dashboard statistics for superadmin
     */
    public function getDashboardStats()
    {
        // Total admins + cashiers
        $result      = $this->db->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin','cashier') AND deleted_at IS NULL");
        $totalAdmins = (int) $result->fetch_assoc()['total'];

        // Active admins + cashiers
        $result       = $this->db->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin','cashier') AND is_active = 1 AND deleted_at IS NULL");
        $activeAdmins = (int) $result->fetch_assoc()['total'];

        // Total branches
        $result        = $this->db->query("SELECT COUNT(*) AS total FROM branch");
        $totalBranches = (int) $result->fetch_assoc()['total'];

        // Active branches
        $result         = $this->db->query("SELECT COUNT(*) AS total FROM branch WHERE status = 'active'");
        $activeBranches = (int) $result->fetch_assoc()['total'];

        // Total system users (not deleted)
        $result     = $this->db->query("SELECT COUNT(*) AS total FROM users WHERE deleted_at IS NULL");
        $totalUsers = (int) $result->fetch_assoc()['total'];

        return [
            'total_admins'      => $totalAdmins,
            'active_admins'     => $activeAdmins,
            'total_branches'    => $totalBranches,
            'active_branches'   => $activeBranches,
            'inactive_branches' => $totalBranches - $activeBranches,
            'total_users'       => $totalUsers,
        ];
    }

    /**
     * Get recent admins (limit 5)
     */
    public function getRecentAdmins()
    {
        $stmt = $this->db->prepare(
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

        return $rows;
    }

    /**
     * Reset admin password
     */
    public function resetAdminPassword($id, $superadminPassword, $newPassword, $confirmPassword, $superadminUserId)
    {
        // Validate inputs
        if (!$superadminPassword || !$newPassword || !$confirmPassword) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Verify new password strength
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters long'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New passwords do not match'];
        }

        // Verify superadmin password
        $stmt = $this->db->prepare("SELECT password FROM users WHERE user_id = ? AND role = 'superadmin' AND deleted_at IS NULL");
        $stmt->bind_param('i', $superadminUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Superadmin account not found'];
        }
        
        $superadmin = $result->fetch_assoc();
        if (!password_verify($superadminPassword, $superadmin['password'])) {
            return ['success' => false, 'message' => 'Incorrect superadmin password'];
        }
        
        // Check if admin exists
        $stmt = $this->db->prepare("SELECT username, email FROM users WHERE user_id = ? AND role IN ('admin','cashier') AND deleted_at IS NULL");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Admin account not found'];
        }
        
        $admin = $result->fetch_assoc();
        
        // Update admin password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashedPassword, $id);
        $stmt->execute();
        
        if ($this->db->affected_rows === 0) {
            return ['success' => false, 'message' => 'Failed to update password'];
        }
        
        // Log the password reset
        $adminUsername = $admin['username'] ?? 'Unknown';
        $superadminUsername = $_SESSION['username'] ?? 'Unknown';
        error_log("[SUPERADMIN] Password reset for admin: {$adminUsername} (ID: {$id}) by superadmin: {$superadminUsername} (ID: {$superadminUserId})");
        
        return [
            'success' => true, 
            'message' => "Password for {$admin['username']} has been reset successfully"
        ];
    }

    /**
     * Get all admins
     */
    public function getAdmins()
    {
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.username, u.email, u.contact_number,
                    u.role, u.branch_id, u.is_active, u.created_at,
                    b.branch_name
             FROM users u
             LEFT JOIN branch b ON u.branch_id = b.branch_id
             WHERE u.role IN ('admin','cashier')
             ORDER BY u.created_at DESC"
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Add new admin
     */
    public function addAdmin($username, $email, $contact_number, $role, $branch_id, $is_active, $password)
    {
        if (!$username || !$email || !$password) {
            return ['success' => false, 'message' => 'Username, email, and password are required.'];
        }

        if (!$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)'];
        }

        if (!in_array($role, ['admin', 'cashier'])) {
            return ['success' => false, 'message' => 'Invalid role. Must be admin or cashier.'];
        }

        // Check duplicate email
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email is already in use.'];
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, contact_number, password, role, branch_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssii', $username, $email, $contact_number, $hashed, $role, $branch_id, $is_active);
        $stmt->execute();
        $newId = $this->db->insert_id;

        return ['success' => true, 'message' => 'Admin account added successfully.', 'user_id' => $newId];
    }

    /**
     * Update admin
     */
    public function updateAdmin($id, $username, $email, $contact_number, $role, $branch_id, $is_active)
    {
        if (!$username || !$email) {
            return ['success' => false, 'message' => 'Username and email are required.'];
        }

        if (!$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)'];
        }

        if (!in_array($role, ['admin', 'cashier'])) {
            return ['success' => false, 'message' => 'Invalid role.'];
        }

        // Check duplicate email (exclude self)
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email is already used by another account.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET username=?, email=?, contact_number=?, role=?, branch_id=?, is_active=?
             WHERE user_id=?"
        );
        $stmt->bind_param('ssssiis', $username, $email, $contact_number, $role, $branch_id, $is_active, $id);
        $stmt->execute();

        return ['success' => true, 'message' => 'Admin account updated successfully.'];
    }

    /**
     * Delete admin (soft delete)
     */
    public function deleteAdmin($id)
    {
        // Soft delete
        $stmt = $this->db->prepare(
            "UPDATE users SET is_active = 0
             WHERE user_id = ? AND role IN ('admin','cashier')"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($this->db->affected_rows === 0) {
            return ['success' => false, 'message' => 'Admin not found.'];
        }

        return ['success' => true, 'message' => 'Admin account deleted successfully.'];
    }

    /**
     * Get all active services
     */
    public function getServices()
    {
        $stmt = $this->db->prepare(
            "SELECT s.service_id, s.service_name, s.description, s.price AS base_price,
                    s.duration_minutes, s.category_id, s.is_available AS is_active,
                    s.image_path,
                    sc.category_name,
                    GROUP_CONCAT(DISTINCT b.branch_name ORDER BY b.branch_name SEPARATOR ', ') AS branch_names,
                    s.created_at
            FROM default_services s
            LEFT JOIN default_services_categories sc ON s.category_id = sc.service_category_id
            LEFT JOIN branch_service_overrides bso ON s.service_id = bso.default_service_id
            LEFT JOIN branch b ON bso.branch_id = b.branch_id
            WHERE s.is_available IN (0, 1)
            GROUP BY s.service_id
            ORDER BY s.service_id DESC"
        );
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get deactivated services
     */
    public function getDeactivatedServices()
    {
        $stmt = $this->db->prepare(
            "SELECT s.service_id, s.service_name, s.description, s.price as base_price, s.duration_minutes, 
                    s.category_id, c.category_name
             FROM default_services s
             LEFT JOIN default_services_categories c ON s.category_id = c.service_category_id
             WHERE s.is_available = 0
             ORDER BY s.service_name ASC"
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getServiceById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT service_id, service_name, description, price, duration_minutes,
                    category_id, is_available, image_path
            FROM default_services
            WHERE service_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Add new service or reactivate existing
     */
    public function addService($service_name, $description, $base_price, $duration_minutes, $category_id, $is_active, $branch_ids, $reactivate_id, $imagePath = null)
    {
        if (!$service_name) {
            return ['success' => false, 'message' => 'Service name is required.'];
        }
        if ($base_price < 0) {
            return ['success' => false, 'message' => 'Base price must be a positive number.'];
        }
        if ($duration_minutes <= 0) {
            return ['success' => false, 'message' => 'Duration must be a positive number.'];
        }

        // ── Reactivation path ──
        if ($reactivate_id) {
            $stmt = $this->db->prepare("SELECT service_id FROM default_services WHERE service_id = ? AND is_available = 0");
            $stmt->bind_param('i', $reactivate_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Deactivated service not found.'];
            }

            $this->db->begin_transaction();
            try {
                $stmt = $this->db->prepare(
                    "UPDATE default_services
                    SET service_name=?, description=?, price=?, duration_minutes=?, category_id=?, is_available=1
                    WHERE service_id=?"
                );
                $stmt->bind_param('ssdiii', $service_name, $description, $base_price, $duration_minutes, $category_id, $reactivate_id);
                $stmt->execute();

                // Update image if provided
                if ($imagePath !== null) {
                    $imgStmt = $this->db->prepare("UPDATE default_services SET image_path=? WHERE service_id=?");
                    $imgStmt->bind_param('si', $imagePath, $reactivate_id);
                    $imgStmt->execute();
                }

                $stmt = $this->db->prepare("DELETE FROM branch_service_overrides WHERE default_service_id = ?");
                $stmt->bind_param('i', $reactivate_id);
                $stmt->execute();

                if (!empty($branch_ids) && is_array($branch_ids)) {
                    foreach ($branch_ids as $branch_id) {
                        $stmt = $this->db->prepare("INSERT INTO branch_service_overrides (branch_id, default_service_id) VALUES (?, ?)");
                        $stmt->bind_param('ii', $branch_id, $reactivate_id);
                        $stmt->execute();
                    }
                }

                $this->db->commit();
                return ['success' => true, 'message' => 'Service reactivated successfully.', 'service_id' => $reactivate_id];
            } catch (Exception $e) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to reactivate service: ' . $e->getMessage()];
            }
        }

        // ── New service path ──
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO default_services (service_name, description, price, duration_minutes, category_id, is_available, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssdiiss', $service_name, $description, $base_price, $duration_minutes, $category_id, $is_active, $imagePath);
            $stmt->execute();
            $serviceId = $this->db->insert_id;

            if (!empty($branch_ids) && is_array($branch_ids)) {
                foreach ($branch_ids as $branch_id) {
                    $stmt = $this->db->prepare("INSERT INTO branch_service_overrides (branch_id, default_service_id) VALUES (?, ?)");
                    $stmt->bind_param('ii', $branch_id, $serviceId);
                    $stmt->execute();
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Service created successfully.', 'service_id' => $serviceId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create service: ' . $e->getMessage()];
        }
    }

    /**
     * Update service
     */
    public function updateService($id, $service_name, $description, $base_price, $duration_minutes, $category_id, $is_active, $branch_ids, $imagePath = false)
    {
        if (!$service_name) {
            return ['success' => false, 'message' => 'Service name is required.'];
        }
        if ($base_price < 0) {
            return ['success' => false, 'message' => 'Base price must be a positive number.'];
        }
        if ($duration_minutes <= 0) {
            return ['success' => false, 'message' => 'Duration must be a positive number.'];
        }

        $this->db->begin_transaction();
        try {
            // Update core fields
            $stmt = $this->db->prepare(
                "UPDATE default_services
                SET service_name=?, description=?, price=?, duration_minutes=?, category_id=?, is_available=?
                WHERE service_id=?"
            );
            $stmt->bind_param('ssdiiii', $service_name, $description, $base_price, $duration_minutes, $category_id, $is_active, $id);
            $stmt->execute();

            // Update image only when explicitly passed (false = don't touch, null = clear it, string = new path)
            if ($imagePath !== false) {
                $imgStmt = $this->db->prepare("UPDATE default_services SET image_path=? WHERE service_id=?");
                $imgStmt->bind_param('si', $imagePath, $id);
                $imgStmt->execute();
            }

            // ── Branch assignments (same logic as before) ──
            if (!empty($branch_ids) && is_array($branch_ids)) {
                $stmt = $this->db->prepare("SELECT branch_service_override_id, branch_id FROM branch_service_overrides WHERE default_service_id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();

                $existingOverrides = [];
                while ($row = $result->fetch_assoc()) {
                    $existingOverrides[] = $row;
                }

                $usedInPackages = [];
                if (!empty($existingOverrides)) {
                    $overrideIds  = array_column($existingOverrides, 'branch_service_override_id');
                    $placeholders = str_repeat('?,', count($overrideIds) - 1) . '?';
                    $stmt = $this->db->prepare(
                        "SELECT DISTINCT branch_service_override_id FROM branch_package_services
                        WHERE branch_service_override_id IN ($placeholders)"
                    );
                    $stmt->bind_param(str_repeat('i', count($overrideIds)), ...$overrideIds);
                    $stmt->execute();
                    $pkgResult = $stmt->get_result();
                    while ($row = $pkgResult->fetch_assoc()) {
                        $usedInPackages[] = $row['branch_service_override_id'];
                    }
                }

                foreach ($existingOverrides as $override) {
                    if (!in_array($override['branch_service_override_id'], $usedInPackages) &&
                        !in_array($override['branch_id'], $branch_ids)) {
                        $stmt = $this->db->prepare("DELETE FROM branch_service_overrides WHERE branch_service_override_id = ?");
                        $stmt->bind_param('i', $override['branch_service_override_id']);
                        $stmt->execute();
                    }
                }

                foreach ($branch_ids as $branch_id) {
                    $exists = false;
                    foreach ($existingOverrides as $override) {
                        if ($override['branch_id'] == $branch_id) { $exists = true; break; }
                    }
                    if (!$exists) {
                        $stmt = $this->db->prepare("INSERT INTO branch_service_overrides (branch_id, default_service_id) VALUES (?, ?)");
                        $stmt->bind_param('ii', $branch_id, $id);
                        $stmt->execute();
                    }
                }
            } else {
                $stmt = $this->db->prepare(
                    "SELECT bso.branch_service_override_id
                    FROM branch_service_overrides bso
                    LEFT JOIN branch_package_services bps ON bso.branch_service_override_id = bps.branch_service_override_id
                    WHERE bso.default_service_id = ? AND bps.branch_service_override_id IS NULL"
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $deleteStmt = $this->db->prepare("DELETE FROM branch_service_overrides WHERE branch_service_override_id = ?");
                    $deleteStmt->bind_param('i', $row['branch_service_override_id']);
                    $deleteStmt->execute();
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Service updated successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to update service: ' . $e->getMessage()];
        }
    }

    /**
     * Delete service (soft delete)
     */
    public function deleteService($id)
    {
        // Soft delete by setting is_available to 0
        $stmt = $this->db->prepare("UPDATE default_services SET is_available = 0 WHERE service_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($this->db->affected_rows === 0) {
            return ['success' => false, 'message' => 'Service not found.'];
        }

        return ['success' => true, 'message' => 'Service deleted successfully.'];
    }

    /**
     * Get all branches
     */
    public function getBranches()
    {
        $result = $this->db->query("SELECT * FROM branch ORDER BY branch_id ASC");

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Add new branch
     */
    public function addBranch($branch_name, $branch_location, $contact_number, $email, $opening_time, $closing_time, $down_payment_rate, $status)
    {
        if (!$branch_name || !$branch_location || !$opening_time || !$closing_time) {
            return ['success' => false, 'message' => 'Branch name, location, and hours are required.'];
        }

        // Enhanced email validation with allowlist (if email provided)
        if ($email && !$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO branch (branch_name, branch_location, contact_number, opening_time, closing_time, down_payment_rate, email, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssdss', $branch_name, $branch_location, $contact_number, $opening_time, $closing_time, $down_payment_rate, $email, $status);
        $stmt->execute();
        $newId = $this->db->insert_id;

        return ['success' => true, 'message' => 'Branch added successfully.', 'branch_id' => $newId];
    }

    /**
     * Update branch
     */
    public function updateBranch($id, $branch_name, $branch_location, $contact_number, $email, $opening_time, $closing_time, $down_payment_rate, $status)
    {
        if (!$branch_name || !$branch_location || !$opening_time || !$closing_time) {
            return ['success' => false, 'message' => 'Branch name, location, and hours are required.'];
        }

        // Enhanced email validation with allowlist (if email provided)
        if ($email && !$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Only trusted email domains are allowed (Gmail, Outlook, Yahoo, .edu.ph, .gov.ph, etc.)'];
        }

        $stmt = $this->db->prepare(
            "UPDATE branch
             SET branch_name=?, branch_location=?, contact_number=?,
                 opening_time=?, closing_time=?, down_payment_rate=?, email=?, status=?
             WHERE branch_id=?"
        );
        $stmt->bind_param('sssssdssi', $branch_name, $branch_location, $contact_number, $opening_time, $closing_time, $down_payment_rate, $email, $status, $id);
        $stmt->execute();

        return ['success' => true, 'message' => 'Branch updated successfully.'];
    }

    /**
     * Delete branch
     */
    public function deleteBranch($id)
    {
        // Safety: prevent deleting branch with assigned users
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt FROM users WHERE branch_id = ? AND deleted_at IS NULL"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $cnt = (int) $stmt->get_result()->fetch_assoc()['cnt'];

        if ($cnt > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete branch: ' . $cnt . ' admin account(s) are still assigned to it. Reassign or delete them first.'
            ];
        }

        $stmt = $this->db->prepare("DELETE FROM branch WHERE branch_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($this->db->affected_rows === 0) {
            return ['success' => false, 'message' => 'Branch not found.'];
        }

        return ['success' => true, 'message' => 'Branch deleted successfully.'];
    }

    // =========================================================
    //  CATEGORIES MANAGEMENT
    // =========================================================

    public function getCategories()
    {
        $stmt = $this->db->prepare(
            "SELECT c.service_category_id, c.category_name, c.description, c.def_capacity, c.is_active,
                    GROUP_CONCAT(DISTINCT CASE WHEN bco.is_active_override = 1 OR bco.is_active_override IS NULL THEN b.branch_name END ORDER BY b.branch_name SEPARATOR ', ') as branch_names,
                    GROUP_CONCAT(DISTINCT CASE WHEN bco.is_active_override = 1 OR bco.is_active_override IS NULL THEN b.branch_id END ORDER BY b.branch_id) as branch_ids
             FROM default_services_categories c
             LEFT JOIN branch_category_overrides bco ON c.service_category_id = bco.default_category_id
             LEFT JOIN branch b ON bco.branch_id = b.branch_id
             GROUP BY c.service_category_id
             ORDER BY c.service_category_id ASC"
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return ['success' => true, 'data' => $rows];
    }

    public function addCategory($category_name, $description, $def_capacity, $is_active, $branch_ids = [])
    {
        // Validate inputs
        if (!$category_name) {
            return ['success' => false, 'message' => 'Category name is required.', 'status' => 400];
        }

        if (!$description) {
            return ['success' => false, 'message' => 'Description is required.', 'status' => 400];
        }

        if ($def_capacity <= 0) {
            return ['success' => false, 'message' => 'Default capacity must be a positive number.', 'status' => 400];
        }

        // Check duplicate category name
        $stmt = $this->db->prepare("SELECT service_category_id FROM default_services_categories WHERE category_name = ?");
        $stmt->bind_param('s', $category_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Category name already exists.', 'status' => 409];
        }

        $this->db->begin_transaction();

        try {
            // Insert category
            $stmt = $this->db->prepare(
                "INSERT INTO default_services_categories (category_name, description, def_capacity, is_active)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssii', $category_name, $description, $def_capacity, $is_active);
            $stmt->execute();
            $newId = $this->db->insert_id;

            // Toggle logic: if specific branches are provided, category is only active for those branches
            // If no branches provided, category is active for all branches (no overrides needed)
            if (!empty($branch_ids) && is_array($branch_ids)) {
                // Get all branches to create overrides for inactive ones
                $allBranchesStmt = $this->db->prepare("SELECT branch_id FROM branch");
                $allBranchesStmt->execute();
                $allBranchesResult = $allBranchesStmt->get_result();
                
                $allBranchIds = [];
                while ($branch = $allBranchesResult->fetch_assoc()) {
                    $allBranchIds[] = $branch['branch_id'];
                }

                // Create overrides for all branches - selected ones will be active, others inactive
                $overrideStmt = $this->db->prepare(
                    "INSERT INTO branch_category_overrides (default_category_id, branch_id, is_active_override) VALUES (?, ?, ?)"
                );
                
                foreach ($allBranchIds as $branch_id) {
                    $isActive = in_array($branch_id, $branch_ids) ? 1 : 0;
                    $overrideStmt->bind_param('iii', $newId, $branch_id, $isActive);
                    $overrideStmt->execute();
                }
            }
            // If no branch_ids provided, category is available to all branches by default (no overrides needed)

            $this->db->commit();
            return ['success' => true, 'message' => 'Category added successfully.', 'category_id' => $newId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to add category: ' . $e->getMessage(), 'status' => 500];
        }
    }

    public function updateCategory($id, $category_name, $description, $def_capacity, $is_active, $branch_ids = [])
    {
        // Validate inputs
        if (!$category_name) {
            return ['success' => false, 'message' => 'Category name is required.', 'status' => 400];
        }

        if (!$description) {
            return ['success' => false, 'message' => 'Description is required.', 'status' => 400];
        }

        if ($def_capacity <= 0) {
            return ['success' => false, 'message' => 'Default capacity must be a positive number.', 'status' => 400];
        }

        // Check duplicate category name (exclude self)
        $stmt = $this->db->prepare("SELECT service_category_id, is_active FROM default_services_categories WHERE category_name = ? AND service_category_id != ?");
        $stmt->bind_param('si', $category_name, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $duplicate = $result->fetch_assoc();
            // Only show error if the duplicate is active (not if it's deactivated)
            if ($duplicate['is_active'] == 1) {
                return ['success' => false, 'message' => 'Category name already exists.', 'status' => 409];
            }
        }

        $this->db->begin_transaction();

        try {
            // Update category
            $stmt = $this->db->prepare(
                "UPDATE default_services_categories 
                 SET category_name=?, description=?, def_capacity=?, is_active=?
                 WHERE service_category_id=?"
            );
            $stmt->bind_param('ssiii', $category_name, $description, $def_capacity, $is_active, $id);
            $stmt->execute();

            // Check if category exists (not just if it was updated)
            $checkStmt = $this->db->prepare("SELECT service_category_id FROM default_services_categories WHERE service_category_id = ?");
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Category not found.', 'status' => 404];
            }

            // Toggle logic for branch assignments
            // First, remove existing assignments
            $deleteStmt = $this->db->prepare("DELETE FROM branch_category_overrides WHERE default_category_id = ?");
            $deleteStmt->bind_param('i', $id);
            $deleteStmt->execute();

            // If specific branches are provided, create overrides for all branches with toggle logic
            if (!empty($branch_ids) && is_array($branch_ids)) {
                // Get all branches to create overrides
                $allBranchesStmt = $this->db->prepare("SELECT branch_id FROM branch");
                $allBranchesStmt->execute();
                $allBranchesResult = $allBranchesStmt->get_result();
                
                $allBranchIds = [];
                while ($branch = $allBranchesResult->fetch_assoc()) {
                    $allBranchIds[] = $branch['branch_id'];
                }

                // Create overrides for all branches - selected ones will be active, others inactive
                $overrideStmt = $this->db->prepare(
                    "INSERT INTO branch_category_overrides (default_category_id, branch_id, is_active_override) VALUES (?, ?, ?)"
                );
                
                foreach ($allBranchIds as $branch_id) {
                    $isActive = in_array($branch_id, $branch_ids) ? 1 : 0;
                    $overrideStmt->bind_param('iii', $id, $branch_id, $isActive);
                    $overrideStmt->execute();
                }
            }
            // If no branch_ids provided, category is available to all branches by default (no overrides needed)

            $this->db->commit();
            return ['success' => true, 'message' => 'Category updated successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to update category: ' . $e->getMessage(), 'status' => 500];
        }
    }

    public function deleteCategory($id)
    {
        // Check if category has services
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM default_services WHERE category_id = ? AND is_available = 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $serviceCount = (int) $result->fetch_assoc()['count'];

        if ($serviceCount > 0) {
            return ['success' => false, 'message' => 'Cannot delete category: ' . $serviceCount . ' service(s) are linked to this category.', 'status' => 409];
        }

        // Soft delete by setting is_active to 0
        $stmt = $this->db->prepare("UPDATE default_services_categories SET is_active = 0 WHERE service_category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($this->db->affected_rows === 0) {
            return ['success' => false, 'message' => 'Category not found.', 'status' => 404];
        }

        return ['success' => true, 'message' => 'Category deactivated successfully.'];
    }
}