<?php

class Reservation extends Database
{
    public function confirmReservation($reservationId)
    {
        // Assuming you have a database connection property like $this->db
        $query = "UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $reservationId);
        return $stmt->execute();
    }

    public function getReservationsByUserId($userId)
    {
        $query = "
            SELECT 
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                b.branch_name,
                b.branch_id
            FROM reservations r
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name,
                    rs.booked_description as description,
                    rs.default_service_id,
                    rs.branch_service_override_id,
                    rs.remaining_balance
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $row['reservation_id']);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            $services = [];
            $totalRemainingBalance = 0;
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                $services[] = $serviceRow;
                $totalRemainingBalance += $serviceRow['remaining_balance'];
            }
            
            // Get schedule information
            $scheduleQuery = "
                SELECT 
                    rs.schedule_date,
                    rs.start_time,
                    rs.end_time
                FROM reservation_schedule rs
                LEFT JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                WHERE rsv.reservation_id = ?
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }
            
            // Get feedback for this reservation
            $feedbackQuery = "
                SELECT f.*
                FROM feedback f
                JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                WHERE rs.reservation_id = ?
            ";
            
            $feedbackStmt = $this->db->prepare($feedbackQuery);
            $feedbackStmt->bind_param('i', $row['reservation_id']);
            $feedbackStmt->execute();
            $feedbackResult = $feedbackStmt->get_result();
            
            $feedback = [];
            while ($feedbackRow = $feedbackResult->fetch_assoc()) {
                $photosQuery = "SELECT * FROM feedback_photos WHERE feedback_id = ? ORDER BY photo_order ASC";
                $photosStmt = $this->db->prepare($photosQuery);
                $photosStmt->bind_param('i', $feedbackRow['feedback_id']);
                $photosStmt->execute();
                $photosResult = $photosStmt->get_result();
                $photos = [];
                while ($photoRow = $photosResult->fetch_assoc()) {
                    $photos[] = $photoRow;
                }
                $feedbackRow['photos'] = $photos;
                $feedback[] = $feedbackRow;
            }
            
            $reservations[] = [
                'reservation_id' => $row['reservation_id'],
                'reservation_date' => $row['reservation_date'],
                'status' => $row['status'],
                'total_price' => $row['total_price'],
                'total_remaining_balance' => $totalRemainingBalance,
                'branch_name' => $row['branch_name'],
                'branch_id' => $row['branch_id'],
                'order_status' => null,
                'services' => $services,
                'schedule' => $schedule,
                'feedback' => $feedback
            ];
        }
        
        return $reservations;
    }

    public function rescheduleReservation($reservationId, $newDate, $newTime, $reason = '')
    {
        // Validate input parameters first
        if (!$this->isValidReservationId($reservationId) ||
            !$this->isValidDate($newDate) ||
            !$this->isValidTime($newTime)) {
            return false;
        }

        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Get all reservation services with duration in single query for efficiency
            $servicesQuery = "
                SELECT rs.reservation_service_id, rs.booked_duration_minutes
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $reservationId);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            // Validate if reservation has any services
            if ($servicesResult->num_rows === 0) {
                $this->db->rollback();
                return false;
            }

            // Reuse DateTime objects outside loop for performance
            $startTime = new DateTime($newTime);
            $timeFormat = 'H:i:s';
            
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                // Get current schedule for the service
                $scheduleQuery = "
                    SELECT reservation_schedule_id, schedule_date, start_time, end_time 
                    FROM reservation_schedule 
                    WHERE reservation_service_id = ?
                    ORDER BY reservation_schedule_id DESC 
                    LIMIT 1
                ";
                
                $scheduleStmt = $this->db->prepare($scheduleQuery);
                $scheduleStmt->bind_param('i', $serviceRow['reservation_service_id']);
                $scheduleStmt->execute();
                $scheduleResult = $scheduleStmt->get_result();
                
                if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                    // Calculate new end time based on duration (reuse start time clone)
                    $endTime = clone $startTime;
                    $endTime->add(new DateInterval('PT' . $serviceRow['booked_duration_minutes'] . 'M'));
                    
                    // Format dates as strings before binding to avoid "Only variables can be passed by reference" warning
                    $formattedStartTime = $startTime->format($timeFormat);
                    $formattedEndTime = $endTime->format($timeFormat);
                    
                    // Create new schedule entry
                    $newScheduleQuery = "
                        INSERT INTO reservation_schedule 
                        (reservation_service_id, schedule_date, start_time, end_time, is_rescheduled, previous_schedule_id, reschedule_reason) 
                        VALUES (?, ?, ?, ?, 1, ?, ?)
                    ";
                    
                    $newScheduleStmt = $this->db->prepare($newScheduleQuery);
                    $newScheduleStmt->bind_param(
                        'isssis',
                        $serviceRow['reservation_service_id'],
                        $newDate,
                        $formattedStartTime,
                        $formattedEndTime,
                        $scheduleRow['reservation_schedule_id'],
                        $reason
                    );
                    $newScheduleStmt->execute();
                    
                    // Check for query execution errors
                    if ($newScheduleStmt->affected_rows <= 0) {
                        throw new Exception('Failed to create new schedule entry');
                    }
                }
            }
            
            // Update reservation status to rescheduled
            $updateQuery = "
                UPDATE reservations 
                SET status = 'rescheduled' 
                WHERE reservation_id = ?
            ";
            
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bind_param('i', $reservationId);
            $updateStmt->execute();
            
            // Check for update errors
            if ($updateStmt->affected_rows <= 0) {
                throw new Exception('Failed to update reservation status');
            }
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            $this->db->rollback();
            // Log error for debugging
            error_log('Reservation reschedule failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate reservation ID
     * @param int $reservationId
     * @return bool
     */
    private function isValidReservationId($reservationId): bool
    {
        return is_int($reservationId) && $reservationId > 0;
    }

    /**
     * Validate date format (YYYY-MM-DD expected)
     * @param string $date
     * @return bool
     */
    private function isValidDate($date): bool
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime !== false && $dateTime->format('Y-m-d') === $date;
    }

    /**
     * Validate time format (HH:MM:SS expected)
     * @param string $time
     * @return bool
     */
    private function isValidTime($time): bool
    {
        $dateTime = DateTime::createFromFormat('H:i:s', $time);
        return $dateTime !== false && $dateTime->format('H:i:s') === $time;
    }

    public function cancelReservation($reservationId)
    {
        $query = "
            UPDATE reservations
            SET status = 'cancelled'
            WHERE reservation_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $reservationId);
        
        return $stmt->execute();
    }

    public function getTodaysReservations($userId)
    {
        // First, get the branch associated with the admin user (if admin_branch table exists)
        $branchQuery = "
            SELECT b.branch_id, b.branch_name
            FROM users u
            JOIN admin_branch ab ON u.user_id = ab.user_id
            JOIN branch b ON ab.branch_id = b.branch_id
            WHERE u.user_id = ?
        ";
        
        try {
            $branchStmt = $this->db->prepare($branchQuery);
            
            if (!$branchStmt) {
                // Table doesn't exist - treat as superadmin, show all today's reservations
                error_log('admin_branch table does not exist in getTodaysReservations');
                return $this->getTodaysReservationsSuperadmin();
            }
        } catch (Exception $e) {
            // Table doesn't exist or other error - treat as superadmin
            error_log('Exception in getTodaysReservations: ' . $e->getMessage());
            return $this->getTodaysReservationsSuperadmin();
        }
        
        $branchStmt->bind_param('i', $userId);
        $branchStmt->execute();
        $branchResult = $branchStmt->get_result();
        
        if (!$branchResult || $branchResult->num_rows === 0) {
            error_log('No branch found for user_id in getTodaysReservations: ' . $userId);
            // No branch assigned - treat as superadmin
            return $this->getTodaysReservationsSuperadmin();
        }
        
        $branch = $branchResult->fetch_assoc();
        $branchId = $branch['branch_id'];
        
        // Get today's date
        $today = date('Y-m-d');
        
        // Get reservations for today at this branch with confirmed status
        $query = "
            SELECT
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                u.username as customer_name,
                u.email as customer_email,
                u.contact_number as customer_contact
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
            JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
            WHERE r.branch_id = ?
                AND DATE(rsch.schedule_date) = ?
                AND r.status IN ('confirmed', 'rescheduled')
            GROUP BY r.reservation_id
            ORDER BY rsch.schedule_date ASC, rsch.start_time ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('is', $branchId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name,
                    rs.booked_description as description,
                    rs.remaining_balance
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $row['reservation_id']);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            $services = [];
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                $services[] = $serviceRow;
            }
            
            // Get schedule information
            $scheduleQuery = "
                SELECT
                    rs.schedule_date,
                    rs.start_time,
                    rs.end_time
                FROM reservation_schedule rs
                LEFT JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                WHERE rsv.reservation_id = ?
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }

            $totalRemainingBalance = array_sum(array_column($services, 'remaining_balance'));
            
            $reservations[] = [
                'reservation_id' => $row['reservation_id'],
                'reservation_date' => $row['reservation_date'],
                'status' => $row['status'],
                'total_price' => $row['total_price'],
                'total_remaining_balance' => $totalRemainingBalance,
                'customer_name' => $row['customer_name'],
                'customer_email' => $row['customer_email'],
                'customer_contact' => $row['customer_contact'],
                'branch_name' => $branch['branch_name'],
                'branch_id' => $branchId,
                'services' => $services,
                'schedule' => $schedule
            ];
        }
        
        return $reservations;
    }

    // Superadmin method to get today's reservations across all branches
    private function getTodaysReservationsSuperadmin()
    {
        // Get today's date
        $today = date('Y-m-d');
        
        // Get all confirmed reservations for today across all branches
        $query = "
            SELECT
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                r.branch_id,
                u.username as customer_name,
                u.email as customer_email,
                u.contact_number as customer_contact,
                b.branch_name
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
            JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            WHERE DATE(rsch.schedule_date) = ?
            AND r.status IN ('confirmed', 'rescheduled')
            GROUP BY r.reservation_id
            ORDER BY rsch.schedule_date ASC, rsch.start_time ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name,
                    rs.booked_description as description
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $row['reservation_id']);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            $services = [];
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                $services[] = $serviceRow;
            }
            
            // Get schedule information
            $scheduleQuery = "
                SELECT
                    rs.schedule_date,
                    rs.start_time,
                    rs.end_time
                FROM reservation_schedule rs
                LEFT JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                WHERE rsv.reservation_id = ?
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }
            
            $reservations[] = [
                'reservation_id' => $row['reservation_id'],
                'reservation_date' => $row['reservation_date'],
                'status' => $row['status'],
                'total_price' => $row['total_price'],
                'customer_name' => $row['customer_name'],
                'customer_email' => $row['customer_email'],
                'customer_contact' => $row['customer_contact'],
                'branch_name' => $row['branch_name'],
                'branch_id' => $row['branch_id'],
                'services' => $services,
                'schedule' => $schedule
            ];
        }
        
        return $reservations;
    }

    public function getAllReservations($userId, $status = 'all', $page = 1, $itemsPerPage = 10)
    {
        // First, get the branch associated with the admin user (if admin_branch table exists)
        $branchQuery = "
            SELECT b.branch_id, b.branch_name
            FROM users u
            JOIN admin_branch ab ON u.user_id = ab.user_id
            JOIN branch b ON ab.branch_id = b.branch_id
            WHERE u.user_id = ?
        ";
        
        try {
            $branchStmt = $this->db->prepare($branchQuery);
            
            if (!$branchStmt) {
                // Table doesn't exist - treat as superadmin, show all reservations
                error_log('admin_branch table does not exist, showing all reservations');
                return $this->getAllReservationsSuperadmin($status, $page, $itemsPerPage);
            }
        } catch (Exception $e) {
            // Table doesn't exist or other error - treat as superadmin
            error_log('Exception in getAllReservations: ' . $e->getMessage());
            return $this->getAllReservationsSuperadmin($status, $page, $itemsPerPage);
        }
        
        $branchStmt->bind_param('i', $userId);
        $branchStmt->execute();
        $branchResult = $branchStmt->get_result();
        
        if (!$branchResult || $branchResult->num_rows === 0) {
            error_log('No branch found for user_id: ' . $userId);
            // No branch assigned - treat as superadmin
            return $this->getAllReservationsSuperadmin($status, $page, $itemsPerPage);
        }
        
        $branch = $branchResult->fetch_assoc();
        $branchId = $branch['branch_id'];
        $branchName = $branch['branch_name'];
        
        // Build the base query
        $whereClause = "r.branch_id = ?";
        $params = [$branchId];
        $types = 'i';
        
        // Add status filter
        if ($status !== 'all' && in_array($status, ['confirmed', 'completed', 'cancelled', 'rescheduled', 'no-show'])) {
            $whereClause .= " AND r.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        // Get total count
        $countQuery = "
            SELECT COUNT(*) as total
            FROM reservations r
            WHERE $whereClause
        ";
        
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = $countRow['total'];
        
        // Calculate offset
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get reservations with pagination
        $query = "
            SELECT
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                r.created_at,
                u.username as customer_name,
                u.email as customer_email,
                u.contact_number as customer_contact
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            WHERE $whereClause
            ORDER BY r.reservation_date DESC, r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $itemsPerPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name,
                    rs.booked_description as description
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $row['reservation_id']);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            $services = [];
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                $services[] = $serviceRow;
            }
            
            // Get schedule information
            $scheduleQuery = "
                SELECT 
                    rs.schedule_date,
                    rs.start_time,
                    rs.end_time
                FROM reservation_schedule rs
                LEFT JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                WHERE rsv.reservation_id = ?
                ORDER BY rs.schedule_date ASC, rs.start_time ASC
                LIMIT 1
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }
            
            $reservations[] = [
                'reservation_id' => $row['reservation_id'],
                'reservation_date' => $row['reservation_date'],
                'start_time' => $schedule['start_time'] ?? null,
                'end_time' => $schedule['end_time'] ?? null,
                'status' => $row['status'],
                'total_price' => $row['total_price'],
                'created_at' => $row['created_at'],
                'customer_name' => $row['customer_name'],
                'customer_email' => $row['customer_email'],
                'customer_contact' => $row['customer_contact'],
                'branch_name' => $branch['branch_name'],
                'services' => $services,
                'schedule' => $schedule
            ];
        }
        
        return [
            'reservations' => $reservations,
            'total' => $total,
            'branch_name' => $branchName ?? null
        ];
    }

    // Superadmin method to get all reservations across all branches
    private function getAllReservationsSuperadmin($status = 'all', $page = 1, $itemsPerPage = 10)
    {
        // Build the base query - no branch filter
        $whereClause = "1=1";
        $params = [];
        $types = '';
        
        // Add status filter
        if ($status !== 'all' && in_array($status, ['confirmed', 'completed', 'cancelled', 'rescheduled', 'no-show'])) {
            $whereClause .= " AND r.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        // Get total count
        $countQuery = "
            SELECT COUNT(*) as total
            FROM reservations r
            WHERE $whereClause
        ";
        
        $countStmt = $this->db->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = $countRow['total'];
        
        // Calculate offset
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get reservations with pagination
        $query = "
            SELECT
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                r.created_at,
                r.branch_id,
                u.username as customer_name,
                u.email as customer_email,
                u.contact_number as customer_contact,
                b.branch_name
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            WHERE $whereClause
            ORDER BY r.reservation_date DESC, r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $itemsPerPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reservations = [];
        
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name,
                    rs.booked_description as description
                FROM reservation_services rs
                WHERE rs.reservation_id = ?
            ";
            
            $servicesStmt = $this->db->prepare($servicesQuery);
            $servicesStmt->bind_param('i', $row['reservation_id']);
            $servicesStmt->execute();
            $servicesResult = $servicesStmt->get_result();
            
            $services = [];
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                $services[] = $serviceRow;
            }
            
            // Get schedule information
            $scheduleQuery = "
                SELECT 
                    rs.schedule_date,
                    rs.start_time,
                    rs.end_time
                FROM reservation_schedule rs
                LEFT JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                WHERE rsv.reservation_id = ?
                ORDER BY rs.schedule_date ASC, rs.start_time ASC
                LIMIT 1
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }
            
            $reservations[] = [
                'reservation_id' => $row['reservation_id'],
                'reservation_date' => $row['reservation_date'],
                'start_time' => $schedule['start_time'] ?? null,
                'end_time' => $schedule['end_time'] ?? null,
                'status' => $row['status'],
                'total_price' => $row['total_price'],
                'created_at' => $row['created_at'],
                'customer_name' => $row['customer_name'],
                'customer_email' => $row['customer_email'],
                'customer_contact' => $row['customer_contact'],
                'branch_name' => $row['branch_name'],
                'services' => $services,
                'schedule' => $schedule
            ];
        }
        
        return [
            'reservations' => $reservations,
            'total' => $total,
            'branch_name' => 'All Branches'
        ];
    }

    public function updateReservationStatus($reservationId, $status)
    {
        // Validate status
        $validStatuses = ['completed', 'cancelled', 'no-show'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        // Start a transaction so both updates succeed or fail together
        $this->db->begin_transaction();

        try {
            // 1. Update the reservation status
            $statusQuery = "
                UPDATE reservations
                SET status = ?
                WHERE reservation_id = ?
            ";
            $statusStmt = $this->db->prepare($statusQuery);
            $statusStmt->bind_param('si', $status, $reservationId);
            $statusStmt->execute();

            if ($statusStmt->affected_rows === 0) {
                // Reservation not found or status already the same
                error_log('[' . date('Y-m-d H:i:s') . '] updateReservationStatus: no rows affected for reservation_id=' . $reservationId);
            }

            // 2. If marking as completed, zero out remaining_balance
            //    for all services tied to this reservation
            if ($status === 'completed') {
                $balanceQuery = "
                    UPDATE reservation_services
                    SET remaining_balance = 0
                    WHERE reservation_id = ?
                ";
                $balanceStmt = $this->db->prepare($balanceQuery);
                $balanceStmt->bind_param('i', $reservationId);
                $balanceStmt->execute();

                error_log('[' . date('Y-m-d H:i:s') . '] updateReservationStatus: zeroed remaining_balance for reservation_id=' . $reservationId . ', affected rows=' . $balanceStmt->affected_rows);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('[' . date('Y-m-d H:i:s') . '] updateReservationStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    public function insertReservation($userId, $branchId, $totalPrice, $status = 'pending')
    {
        $query = "
            INSERT INTO reservations (user_id, branch_id, total_price, status, reservation_date, created_at) 
            VALUES (?, ?, ?, ?, CURDATE(), NOW())
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('iids', $userId, $branchId, $totalPrice, $status);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        error_log("DB Error in insertReservation: " . $stmt->error);
        return false;
    }

    public function getReservationById($reservationId)
    {
        $query = "
            SELECT r.*, u.username as customer_name, u.email as customer_email, 
                   b.branch_name, b.branch_location
            FROM reservations r
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            WHERE r.reservation_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}