<?php

class Branch
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get all active branches
    // Returns an array of all branches that are active or have empty status (for backward compatibility)
    public function getAllBranches()
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM branch WHERE status = "active" OR status = ""';
        $result = $conn->query($sql);
        
        $branches = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
        }
        
        return $branches;
    }

    // Get branch by ID
    // Returns a single branch record based on the provided branch ID
    public function getBranchById($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM branch WHERE branch_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Get services for a specific branch
    // Returns all services available at the specified branch, including category information
    // Joins with default_services_categories to get category names
    // Falls back to default_services if branch-specific services have empty values
    public function getBranchServices($branchId)
    {
        $conn = $this->db->getConnection();
        
        // First, get all branch services
        $sql = 'SELECT
                    bs.branch_service_id as serviceid,
                    bs.service_name as servicename,
                    bs.description,
                    bs.price,
                    bs.duration_minutes as duration,
                    bs.category_id,
                    dsc.category_name as category,
                    1 as isavailable
                FROM branch_services bs
                LEFT JOIN default_services_categories dsc ON bs.category_id = dsc.service_category_id
                WHERE bs.branch_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $services = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // If branch service has empty values, get from default services
                if (empty($row['servicename']) || empty($row['description']) || $row['price'] == 0 || $row['duration'] == 0) {
                    $defaultService = $this->getDefaultServiceByCategory($row['category_id']);
                    if ($defaultService) {
                        if (empty($row['servicename'])) $row['servicename'] = $defaultService['service_name'];
                        if (empty($row['description'])) $row['description'] = $defaultService['description'];
                        if ($row['price'] == 0) $row['price'] = $defaultService['price'];
                        if ($row['duration'] == 0) $row['duration'] = $defaultService['duration_minutes'];
                    }
                }
                
                // Format duration to be more readable (e.g., "60 min" instead of 60)
                if (!empty($row['duration'])) {
                    $row['duration'] = $row['duration'] . ' min';
                } else {
                    $row['duration'] = 'N/A';
                }
                
                // Map category names to more user-friendly format (e.g., "hair" to "Hair Services")
                if (!empty($row['category'])) {
                    $row['category'] = ucfirst($row['category']) . ' Services';
                } else {
                    $row['category'] = 'Other Services';
                }
                
                $services[] = $row;
            }
        }
        
        return $services;
    }
    
    // Helper method to get a default service by category ID
    private function getDefaultServiceByCategory($categoryId)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM default_services WHERE category_id = ? LIMIT 1';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}

?>
