<?php

class Branch extends Database
{

    // Get all active branches
    // Returns an array of all branches that are active or have empty status (for backward compatibility)
    public function getAllBranches()
    {
        $conn = $this->getConnection();
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
        $conn = $this->getConnection();
        $sql = 'SELECT * FROM branch WHERE branch_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Get services for a specific branch
    // Returns all services available at the specified branch, including category information
    // Joins with default_services and default_services_categories to get base service and category data
    // Uses branch_service_overrides for branch-specific modifications
    public function getBranchServices($branchId)
    {
        $conn = $this->getConnection();
        
        // Get all branch service overrides with corresponding default services and categories
        $sql = 'SELECT
                    COALESCE(bso.default_service_id, bso.branch_service_override_id) as serviceid,
                    COALESCE(bso.display_name, ds.service_name) as servicename,
                    COALESCE(bso.description_override, ds.description) as description,
                    COALESCE(bso.price_override, ds.price) as price,
                    COALESCE(bso.duration_minutes_override, ds.duration_minutes) as duration,
                    ds.category_id,
                    COALESCE(bso.default_service_id, bso.branch_service_override_id) as default_service_id,
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
                // Format duration to be more readable (e.g., "60 min" instead of 60)
                if (!empty($row['duration'])) {
                    $row['duration'] = $row['duration'] . ' min';
                } else {
                    $row['duration'] = 'N/A';
                }
                
                // Map category names to more user-friendly format (e.g., "hair" to "Hair Services")
                if (!empty($row['category'])) {
                    $row['category'] = ucfirst(strtolower($row['category'])) . ' Services';
                } else {
                    $row['category'] = 'Other Services';
                }
                
                $services[] = $row;
            }
        }
        
        return $services;
    }

    // Get categories for a specific branch
    // Returns all categories available at the specified branch, including override information
    // Joins with default_services_categories to get base category data
    // Uses branch_category_overrides for branch-specific modifications
    public function getBranchCategories($branchId)
    {
        $conn = $this->getConnection();
        
        $sql = 'SELECT
                    bco.branch_category_override_id as categoryid,
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
                // Map category names to more user-friendly format (e.g., "hair" to "Hair Services")
                if (!empty($row['categoryname'])) {
                    $row['categoryname'] = ucfirst(strtolower($row['categoryname'])) . ' Services';
                } else {
                    $row['categoryname'] = 'Other Services';
                }
                
                $categories[] = $row;
            }
        }
        
        return $categories;
    }

    // Get reviews for a specific branch
    // Returns all feedback/reviews for the specified branch with customer information
    public function getBranchReviews($branchId)
    {
        $conn = $this->getConnection();
        
        $sql = 'SELECT 
                    f.rating,
                    f.comment,
                    f.created_at,
                    COALESCE(u.username, "Anonymous") as customer_name
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.user_id
                WHERE f.branch_id = ?
                ORDER BY f.created_at DESC';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $reviews = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
        }
        
        return $reviews;
    }

    // Get rating summary for a specific branch
    // Returns summary statistics including average rating and total review count
    public function getBranchRatingSummary($branchId)
    {
        $conn = $this->getConnection();
        
        $sql = 'SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating
                FROM feedback
                WHERE branch_id = ?';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return array(
                'total_reviews' => (int)$row['total_reviews'],
                'average_rating' => $row['average_rating'] ? round($row['average_rating'], 1) : 0
            );
        }
        
        return array(
            'total_reviews' => 0,
            'average_rating' => 0
        );
    }

        // ── Get full branch settings by branch_id ──
    public function getBranchSettings($branchId)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT branch_id, branch_name, branch_location, contact_number, email,
                    opening_time, closing_time, down_payment_rate, status
             FROM branch WHERE branch_id = ?"
        );
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ── Update branch settings ──
    public function updateBranchSettings($branchId, $data)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "UPDATE branch
             SET branch_location   = ?,
                 contact_number    = ?,
                 email             = ?,
                 opening_time      = ?,
                 closing_time      = ?,
                 down_payment_rate  = ?
             WHERE branch_id = ?"
        );
        $stmt->bind_param(
            'sssssdi',
            $data['branch_location'],
            $data['contact_number'],
            $data['email'],
            $data['opening_time'],
            $data['closing_time'],
            $data['down_payment_rate'],
            $branchId
        );
        return $stmt->execute();
    }

    // ── Get all closed dates for a branch ──
    public function getClosedDates($branchId)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT id, closed_date, reason
             FROM branch_closed_dates
             WHERE branch_id = ?
             ORDER BY closed_date ASC"
        );
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row;
        }
        return $dates;
    }

    // ── Add a closed date ──
    public function addClosedDate($branchId, $date, $reason = '')
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO branch_closed_dates (branch_id, closed_date, reason)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iss', $branchId, $date, $reason);
        return $stmt->execute();
    }

    // ── Remove a closed date by ID ──
    public function removeClosedDate($id, $branchId)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "DELETE FROM branch_closed_dates WHERE id = ? AND branch_id = ?"
        );
        $stmt->bind_param('ii', $id, $branchId);
        return $stmt->execute();
    }

    // ── Get all closed dates as plain array (used by booking) ──
    public function getClosedDatesByBranch($branchId)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT closed_date FROM branch_closed_dates WHERE branch_id = ?"
        );
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['closed_date'];
        }
        return $dates;
    }

}

?>