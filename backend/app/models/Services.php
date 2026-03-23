<?php

class Services
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // =========================================
    // DEFAULT SERVICES (Base services)
    // =========================================

    // Get all default services
    public function getAllDefaultServices()
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT ds.*, dsc.category_name 
                FROM default_services ds 
                LEFT JOIN default_services_categories dsc ON ds.category_id = dsc.service_category_id 
                ORDER BY ds.category_id, ds.service_name';
        $result = $conn->query($sql);

        $services = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }

        return $services;
    }

    // Get default service by ID
    public function getDefaultServiceById($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT ds.*, dsc.category_name 
                FROM default_services ds 
                LEFT JOIN default_services_categories dsc ON ds.category_id = dsc.service_category_id 
                WHERE ds.service_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Get default services by category
    public function getDefaultServicesByCategory($categoryId)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM default_services WHERE category_id = ? AND is_available = 1 ORDER BY service_name';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();

        $result = $stmt->get_result();

        $services = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }

        return $services;
    }

    // Create a new default service
    public function createDefaultService($data)
    {
        $conn = $this->db->getConnection();
        $sql = 'INSERT INTO default_services (category_id, service_name, description, duration_minutes, price, is_available) 
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issidi', 
            $data['category_id'],
            $data['service_name'],
            $data['description'],
            $data['duration_minutes'],
            $data['price'],
            $data['is_available']
        );

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    // Update a default service
    public function updateDefaultService($id, $data)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE default_services 
                SET category_id = ?, service_name = ?, description = ?, duration_minutes = ?, price = ?, is_available = ? 
                WHERE service_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issidii', 
            $data['category_id'],
            $data['service_name'],
            $data['description'],
            $data['duration_minutes'],
            $data['price'],
            $data['is_available'],
            $id
        );

        return $stmt->execute();
    }

    // Delete a default service
    public function deleteDefaultService($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'DELETE FROM default_services WHERE service_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }

    // =========================================
    // BRANCH SERVICE OVERRIDES
    // =========================================

    // Get all branch service overrides for a branch with service details
    public function getBranchServices($branchId)
    {
        $conn = $this->db->getConnection();
        
        $sql = 'SELECT
                    bso.branch_service_override_id as serviceid,
                    bso.branch_id,
                    bso.default_service_id,
                    COALESCE(bso.display_name, ds.service_name) as servicename,
                    COALESCE(bso.description_override, ds.description) as description,
                    COALESCE(bso.price_override, ds.price) as price,
                    COALESCE(bso.duration_minutes_override, ds.duration_minutes) as duration,
                    ds.category_id,
                    CONCAT(
                        UCASE(LEFT(dsc.category_name, 1)),
                        SUBSTRING(dsc.category_name, 2)
                    ) as category,
                    COALESCE(bso.is_available_override, ds.is_available) as isavailable
                FROM branch_service_overrides bso
                LEFT JOIN default_services ds ON bso.default_service_id = ds.service_id
                LEFT JOIN default_services_categories dsc ON ds.category_id = dsc.service_category_id
                WHERE bso.branch_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $services = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }
        
        return $services;
    }

    // Get branch service override by ID
    public function getBranchServiceById($id)
    {
        $conn = $this->db->getConnection();
        
        $sql = 'SELECT
                    bso.*,
                    ds.service_name,
                    ds.description,
                    ds.price,
                    ds.duration_minutes,
                    ds.category_id,
                    dsc.category_name
                FROM branch_service_overrides bso
                LEFT JOIN default_services ds ON bso.default_service_id = ds.service_id
                LEFT JOIN default_services_categories dsc ON ds.category_id = dsc.service_category_id
                WHERE bso.branch_service_override_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Create branch service override
    public function createBranchServiceOverride($data)
    {
        $conn = $this->db->getConnection();
        
        // Check if this service already exists for this branch
        $checkSql = 'SELECT branch_service_override_id FROM branch_service_overrides 
                     WHERE branch_id = ? AND default_service_id = ?';
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('ii', $data['branch_id'], $data['default_service_id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            return 'duplicate';
        }
        
        // Convert null values to appropriate defaults
        $displayName = $data['display_name'] ?? '';
        $descriptionOverride = $data['description_override'] ?? '';
        $durationOverride = !empty($data['duration_minutes_override']) ? $data['duration_minutes_override'] : 0;
        $priceOverride = !empty($data['price_override']) ? $data['price_override'] : 0.0;
        $isAvailableOverride = isset($data['is_available_override']) ? $data['is_available_override'] : 1;
        
        $sql = 'INSERT INTO branch_service_overrides 
                (branch_id, default_service_id, display_name, description_override, duration_minutes_override, price_override, is_available_override) 
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissidd', 
            $data['branch_id'],
            $data['default_service_id'],
            $displayName,
            $descriptionOverride,
            $durationOverride,
            $priceOverride,
            $isAvailableOverride
        );

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    // Update branch service override
    public function updateBranchServiceOverride($id, $data)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_service_overrides 
                SET display_name = ?, description_override = ?, duration_minutes_override = ?, 
                    price_override = ?, is_available_override = ? 
                WHERE branch_service_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiddi', 
            $data['display_name'],
            $data['description_override'],
            $data['duration_minutes_override'],
            $data['price_override'],
            $data['is_available_override'],
            $id
        );

        return $stmt->execute();
    }

    // Update service availability
    public function updateServiceAvailability($id, $isAvailable)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_service_overrides SET is_available_override = ? WHERE branch_service_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $isAvailable, $id);

        return $stmt->execute();
    }

    // Update service price
    public function updateServicePrice($id, $price)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_service_overrides SET price_override = ? WHERE branch_service_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('di', $price, $id);

        return $stmt->execute();
    }

    // Update service duration
    public function updateServiceDuration($id, $duration)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_service_overrides SET duration_minutes_override = ? WHERE branch_service_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $duration, $id);

        return $stmt->execute();
    }

    // Delete branch service override
    public function deleteBranchServiceOverride($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'DELETE FROM branch_service_overrides WHERE branch_service_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }

    // =========================================
    // CATEGORIES
    // =========================================

    // Get all categories
    public function getAllCategories()
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM default_services_categories ORDER BY category_name';
        $result = $conn->query($sql);

        $categories = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }

        return $categories;
    }

    // Get category by ID
    public function getCategoryById($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM default_services_categories WHERE service_category_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Create a new category
    public function createCategory($data)
    {
        $conn = $this->db->getConnection();
        $sql = 'INSERT INTO default_services_categories (category_name, description, def_capacity) VALUES (?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $data['category_name'], $data['description'], $data['def_capacity']);

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    // Update a category
    public function updateCategory($id, $data)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE default_services_categories SET category_name = ?, description = ?, def_capacity = ? WHERE service_category_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $data['category_name'], $data['description'], $data['def_capacity'], $id);

        return $stmt->execute();
    }

    // Delete a category
    public function deleteCategory($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'DELETE FROM default_services_categories WHERE service_category_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }

    // =========================================
    // BRANCH CATEGORY OVERRIDES
    // =========================================

    // Get all branch category overrides for a branch
    public function getBranchCategories($branchId)
    {
        $conn = $this->db->getConnection();
        
        $sql = 'SELECT
                    bco.branch_category_override_id as categoryid,
                    bco.branch_id,
                    bco.default_category_id,
                    COALESCE(bco.display_name, dsc.category_name) as categoryname,
                    COALESCE(bco.description_override, dsc.description) as description,
                    COALESCE(bco.capacity_override, dsc.def_capacity) as capacity,
                    COALESCE(bco.is_active_override, 1) as isactive,
                    dsc.service_category_id as default_category_id
                FROM branch_category_overrides bco
                LEFT JOIN default_services_categories dsc ON bco.default_category_id = dsc.service_category_id
                WHERE bco.branch_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $categories = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }

    // Get branch category override by ID
    public function getBranchCategoryById($id)
    {
        $conn = $this->db->getConnection();
        
        $sql = 'SELECT
                    bco.*,
                    dsc.category_name,
                    dsc.description,
                    dsc.def_capacity
                FROM branch_category_overrides bco
                LEFT JOIN default_services_categories dsc ON bco.default_category_id = dsc.service_category_id
                WHERE bco.branch_category_override_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Create branch category override
    public function createBranchCategoryOverride($data)
    {
        $conn = $this->db->getConnection();
        $sql = 'INSERT INTO branch_category_overrides 
                (branch_id, default_category_id, display_name, description_override, capacity_override, is_active_override) 
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissii', 
            $data['branch_id'],
            $data['default_category_id'],
            $data['display_name'],
            $data['description_override'],
            $data['capacity_override'],
            $data['is_active_override']
        );

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }

    // Update branch category override
    public function updateBranchCategoryOverride($id, $data)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_category_overrides 
                SET display_name = ?, description_override = ?, capacity_override = ?, is_active_override = ? 
                WHERE branch_category_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiii', 
            $data['display_name'],
            $data['description_override'],
            $data['capacity_override'],
            $data['is_active_override'],
            $id
        );

        return $stmt->execute();
    }

    // Update category capacity for a branch
    public function updateCategoryCapacity($id, $capacity)
    {
        $conn = $this->db->getConnection();
        $sql = 'UPDATE branch_category_overrides SET capacity_override = ? WHERE branch_category_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $capacity, $id);

        return $stmt->execute();
    }

    // Delete branch category override
    public function deleteBranchCategoryOverride($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'DELETE FROM branch_category_overrides WHERE branch_category_override_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }

    // ── Get all date-specific capacity overrides for a branch ──
    public function getDateCapacities($branchId)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT cdc.id, cdc.branch_category_override_id, cdc.override_date,
                cdc.capacity_override, cdc.reason,
                COALESCE(bco.display_name, dsc.category_name) AS category_name
            FROM category_date_capacity cdc
            JOIN branch_category_overrides bco
                ON bco.branch_category_override_id = cdc.branch_category_override_id
            JOIN default_services_categories dsc
                ON dsc.service_category_id = bco.default_category_id
            WHERE cdc.branch_id = ?
            AND cdc.override_date >= CURDATE()
            ORDER BY cdc.override_date ASC, dsc.category_name ASC
        ");
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    // ── Add or update a date-specific capacity override ──
    public function saveDateCapacity($branchId, $branchCategoryOverrideId, $date, $capacity, $reason)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO category_date_capacity
                (branch_id, branch_category_override_id, override_date, capacity_override, reason)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                capacity_override = VALUES(capacity_override),
                reason            = VALUES(reason)
        ");
        $stmt->bind_param('iisis', $branchId, $branchCategoryOverrideId, $date, $capacity, $reason);
        return $stmt->execute();
    }

    // ── Remove a date-specific capacity override by ID ──
    public function removeDateCapacity($id, $branchId)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "DELETE FROM category_date_capacity WHERE id = ? AND branch_id = ?"
        );
        $stmt->bind_param('ii', $id, $branchId);
        return $stmt->execute() && $conn->affected_rows > 0;
    }

    // ── Get effective capacity for a category on a given date ──
    public function getEffectiveCapacity($branchId, $branchCategoryOverrideId, $date)
    {
        $conn = $this->db->getConnection();

        // 1. Check date-specific override first
        $stmt = $conn->prepare("
            SELECT capacity_override FROM category_date_capacity
            WHERE branch_id = ? AND branch_category_override_id = ? AND override_date = ?
        ");
        $stmt->bind_param('iis', $branchId, $branchCategoryOverrideId, $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) return (int) $row['capacity_override'];

        // 2. Fallback to branch category default
        $stmt = $conn->prepare("
            SELECT COALESCE(bco.capacity_override, dsc.def_capacity) AS capacity
            FROM branch_category_overrides bco
            JOIN default_services_categories dsc ON dsc.service_category_id = bco.default_category_id
            WHERE bco.branch_category_override_id = ? AND bco.branch_id = ?
        ");
        $stmt->bind_param('ii', $branchCategoryOverrideId, $branchId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) return (int) $row['capacity'];

        return null;
    }
}