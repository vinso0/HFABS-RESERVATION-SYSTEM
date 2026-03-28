<?php

class Reservation extends Database
{
    public function confirmReservation($reservationId) {
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
                ORDER BY rs.schedule_id DESC
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
        error_log('[' . date('Y-m-d H:i:s') . '] rescheduleReservation called with: reservationId=' . $reservationId . 
                 ', newDate=' . $newDate . ', newTime=' . $newTime . ', reason=' . $reason);
        // Validate input parameters first
        if (!$this->isValidReservationId($reservationId) || 
            !$this->isValidDate($newDate) || 
            !$this->isValidTime($newTime)) {
            error_log('[' . date('Y-m-d H:i:s') . '] Validation failed for reschedule parameters');
            return false;
        }
        error_log('[' . date('Y-m-d H:i:s') . '] Parameters validated, starting transaction');
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
            
            error_log('[' . date('Y-m-d H:i:s') . '] Found ' . $servicesResult->num_rows . ' services for reservation ' . $reservationId);
            // Validate if reservation has any services
            if ($servicesResult->num_rows === 0) {
                error_log('[' . date('Y-m-d H:i:s') . '] No services found for reservation ' . $reservationId);
                $this->db->rollback();
                return false;
            }
            // Reuse DateTime objects outside loop for performance
            $startTime = new DateTime($newTime);
            $timeFormat = 'H:i:s';
            
            error_log('[' . date('Y-m-d H:i:s') . '] Processing services for reschedule');
            while ($serviceRow = $servicesResult->fetch_assoc()) {
                error_log('[' . date('Y-m-d H:i:s') . '] Processing service ID: ' . $serviceRow['reservation_service_id'] . 
                         ', duration: ' . $serviceRow['booked_duration_minutes'] . ' minutes');
                // Get current schedule for the service
                $scheduleQuery = "
                    SELECT schedule_id, schedule_date, start_time, end_time 
                    FROM reservation_schedule 
                    WHERE reservation_service_id = ?
                    ORDER BY schedule_id DESC 
                    LIMIT 1
                ";
                
                $scheduleStmt = $this->db->prepare($scheduleQuery);
                $scheduleStmt->bind_param('i', $serviceRow['reservation_service_id']);
                $scheduleStmt->execute();
                $scheduleResult = $scheduleStmt->get_result();
                
                if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                    error_log('[' . date('Y-m-d H:i:s') . '] Found existing schedule, creating new one');
                    // Calculate new end time based on duration (reuse start time clone)
                    $endTime = clone $startTime;
                    $endTime->add(new DateInterval('PT' . $serviceRow['booked_duration_minutes'] . 'M'));
                    
                    // Format dates as strings before binding to avoid "Only variables can be passed by reference" warning
                    $formattedStartTime = $startTime->format($timeFormat);
                    $formattedEndTime = $endTime->format($timeFormat);
                    
                    error_log('[' . date('Y-m-d H:i:s') . '] New times: start=' . $formattedStartTime . ', end=' . $formattedEndTime);
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
                        $scheduleRow['schedule_id'], 
                        $reason
                    );
                    $newScheduleStmt->execute();
                    
                    // Check for query execution errors
                    if ($newScheduleStmt->affected_rows <= 0) {
                        error_log('[' . date('Y-m-d H:i:s') . '] Failed to create new schedule entry for service ' . $serviceRow['reservation_service_id']);
                        throw new Exception('Failed to create new schedule entry');
                    }
                    error_log('[' . date('Y-m-d H:i:s') . '] Successfully created new schedule entry');
                } else {
                    error_log('[' . date('Y-m-d H:i:s') . '] No existing schedule found for service ' . $serviceRow['reservation_service_id']);
                }
            }
            
            error_log('[' . date('Y-m-d H:i:s') . '] Updating reservation status to rescheduled');
            // Update reservation status to rescheduled
            $updateQuery = "
                UPDATE reservations 
                SET status = 'rescheduled' 
                WHERE reservation_id = ?
            ";
            
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bind_param('i', $reservationId);
            $updateStmt->execute();
            
            // Check for update errors - don't fail if status is already 'rescheduled'
            if ($updateStmt->affected_rows <= 0) {
                error_log('[' . date('Y-m-d H:i:s') . '] Reservation status update affected 0 rows (may already be rescheduled)');
                // Don't throw exception - the schedule was still created successfully
            }
            
            error_log('[' . date('Y-m-d H:i:s') . '] Committing transaction');
            // Commit transaction
            $this->db->commit();
            error_log('[' . date('Y-m-d H:i:s') . '] Reschedule completed successfully for reservation ' . $reservationId);
            return true;
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            error_log('[' . date('Y-m-d H:i:s') . '] Exception during reschedule: ' . $e->getMessage());
            error_log('[' . date('Y-m-d H:i:s') . '] Rolling back transaction');
            $this->db->rollback();
            // Log error for debugging
            error_log('Reservation reschedule failed: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
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
        // Accept both integer and numeric string
        return (is_int($reservationId) && $reservationId > 0) || 
               (is_string($reservationId) && ctype_digit($reservationId) && (int)$reservationId > 0);
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
        // Try HH:MM:SS format first
        $dateTime = DateTime::createFromFormat('H:i:s', $time);
        if ($dateTime !== false && $dateTime->format('H:i:s') === $time) {
            return true;
        }
        
        // Try HH:MM format
        $dateTime = DateTime::createFromFormat('H:i', $time);
        if ($dateTime !== false && $dateTime->format('H:i') === $time) {
            return true;
        }
        
        // Try parsing with strtotime for more flexibility
        $timestamp = strtotime($time);
        if ($timestamp !== false) {
            // Check if it's a valid time (0-23 hours)
            $hour = (int)date('H', $timestamp);
            $minute = (int)date('i', $timestamp);
            return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59;
        }
        
        return false;
    }

    public function getTodaysReservations($userId)
    {
        // First, get the branch associated with the admin user (if admin_branch table exists)
        $branchQuery = "
            SELECT b.branch_id, b.branch_name
            FROM users u
            JOIN branch_id ab ON u.user_id = ab.user_id
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
                AND r.status = 'confirmed'
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
                ORDER BY rs.schedule_id DESC
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
                'status' => $row['status'],
                'total_price' => $row['total_price'],
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
                AND r.status = 'confirmed'
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
                ORDER BY rs.schedule_id DESC
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
        // Get branch information for admin user from users table
        $branchQuery = "
            SELECT b.branch_id, b.branch_name
            FROM users u
            LEFT JOIN branch b ON u.branch_id = b.branch_id
            WHERE u.user_id = ? AND u.role = 'admin'
        ";
        
        $branchStmt = $this->db->prepare($branchQuery);
        $branchStmt->bind_param('i', $userId);
        $branchStmt->execute();
        $branchResult = $branchStmt->get_result();
        
        if (!$branchResult || $branchResult->num_rows === 0) {
            error_log('No branch found for admin user_id: ' . $userId . ', showing all reservations');
            // No branch assigned or user is not admin - treat as superadmin
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
                ORDER BY rs.schedule_id DESC
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
                ORDER BY rs.schedule_id DESC
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
        
        $query = "
            UPDATE reservations
            SET status = ?
            WHERE reservation_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('si', $status, $reservationId);
        
        return $stmt->execute();
    }

    public function insertReservation($userId, $branchId, $totalPrice, $status = 'pending', $reservationDate = null)
    {
        // Use provided reservation date or default to current date
        $dateToUse = $reservationDate ? "'$reservationDate'" : 'CURDATE()';
        
        $query = "
            INSERT INTO reservations (user_id, branch_id, total_price, status, reservation_date, created_at) 
            VALUES (?, ?, ?, ?, $dateToUse, NOW())
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

    public function addReservationService($reservationId, $serviceData)
    {
        $query = "
            INSERT INTO reservation_services (
                reservation_id, 
                booked_service_name, 
                booked_unit_price, 
                booked_duration_minutes, 
                booked_category_name, 
                booked_description, 
                default_service_id, 
                branch_service_override_id, 
                remaining_balance
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            'isdissidd', 
            $reservationId,
            $serviceData['service_name'],
            $serviceData['price'],
            $serviceData['duration_minutes'],
            $serviceData['category_name'],
            $serviceData['description'],
            $serviceData['default_service_id'],
            $serviceData['branch_service_override_id'],
            $serviceData['remaining_balance']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        error_log("DB Error in addReservationService: " . $stmt->error);
        return false;
    }

    public function addReservationSchedule($reservationServiceId, $scheduleData)
    {
        // Get service duration from reservation service
        $durationQuery = "
            SELECT rs.booked_duration_minutes, ds.duration_minutes as default_duration
            FROM reservation_services rs
            LEFT JOIN default_services ds ON rs.default_service_id = ds.service_id
            WHERE rs.reservation_service_id = ?
        ";
        
        $stmt = $this->db->prepare($durationQuery);
        $stmt->bind_param('i', $reservationServiceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $serviceData = $result->fetch_assoc();
        
        if (!$serviceData) {
            error_log("DB Error in addReservationSchedule: Service not found for reservation_service_id: $reservationServiceId");
            return false;
        }
        
        // Use booked duration first, then default duration
        $durationMinutes = $serviceData['booked_duration_minutes'] ?? $serviceData['default_duration'] ?? 60;
        
        // Calculate end time based on start time and duration
        $startTime = $scheduleData['start_time'];
        $endTime = $this->calculateEndTime($startTime, $durationMinutes);
        
        $query = "
            INSERT INTO reservation_schedule (
                reservation_service_id, 
                schedule_date, 
                start_time, 
                end_time, 
                is_rescheduled, 
                previous_schedule_id, 
                reschedule_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $isRescheduled = $scheduleData['is_rescheduled'] ?? 0;
        $previousScheduleId = $scheduleData['previous_schedule_id'] ?? null;
        $rescheduleReason = $scheduleData['reschedule_reason'] ?? null;
        
        $stmt = $this->db->prepare($query);
        
        // Handle NULL for previous_schedule_id properly
        if ($previousScheduleId === null) {
            $stmt->bind_param(
                'isssiss', 
                $reservationServiceId,
                $scheduleData['schedule_date'],
                $startTime,
                $endTime,
                $isRescheduled,
                $previousScheduleId,
                $rescheduleReason
            );
        } else {
            $stmt->bind_param(
                'isssiis', 
                $reservationServiceId,
                $scheduleData['schedule_date'],
                $startTime,
                $endTime,
                $isRescheduled,
                $previousScheduleId,
                $rescheduleReason
            );
        }
        
        if ($stmt->execute()) {
            error_log("Schedule created: Start=$startTime, End=$endTime, Duration=$durationMinutes minutes");
            return $stmt->insert_id;
        }
        
        error_log("DB Error in addReservationSchedule: " . $stmt->error);
        return false;
    }
    
    private function calculateEndTime($startTime, $durationMinutes)
    {
        // Convert start time to 24-hour format for calculation
        $time24 = date('H:i', strtotime($startTime));
        
        // Add duration minutes
        $endTime24 = date('H:i', strtotime($time24 . ' + ' . $durationMinutes . ' minutes'));
        
        // Convert back to 24-hour format for database storage
        return date('H:i:s', strtotime($endTime24));
    }

    public function checkPackageServiceTimeAvailability($date, $time, $packageId)
    {
        error_log('[' . date('Y-m-d H:i:s') . '] checkPackageServiceTimeAvailability called with: date=' . $date . ', time=' . $time . ', packageId=' . $packageId);
        
        // Convert time to 24-hour format for database comparison
        $time24 = date('H:i:s', strtotime($time));
        error_log('[' . date('Y-m-d H:i:s') . '] Converted time to 24h format: ' . $time24);
        
        // Get all services in this package
        $packageQuery = "
            SELECT COALESCE(bso.duration_minutes_override, ds.duration_minutes) as duration_minutes, 
                   bso.default_service_id, ds.service_name
            FROM branch_package_services bps
            JOIN branch_service_overrides bso ON bps.branch_service_override_id = bso.branch_service_override_id
            JOIN default_services ds ON bso.default_service_id = ds.service_id
            WHERE bps.package_id = ?
        ";
        $packageStmt = $this->db->prepare($packageQuery);
        $packageStmt->bind_param('i', $packageId);
        $packageStmt->execute();
        $packageResult = $packageStmt->get_result();
        
        if ($packageResult->num_rows === 0) {
            error_log('[' . date('Y-m-d H:i:s') . '] Package not found with ID: ' . $packageId);
            return false; // Package not found
        }
        
        $services = [];
        while ($service = $packageResult->fetch_assoc()) {
            $services[] = $service;
        }
        
        error_log('[' . date('Y-m-d H:i:s') . '] Package contains ' . count($services) . ' services');
        
        // Check each service individually - if ANY service is unavailable, the package is unavailable
        foreach ($services as $service) {
            $serviceId = $service['default_service_id'];
            $serviceName = $service['service_name'];
            $duration = $service['duration_minutes'];
            
            error_log('[' . date('Y-m-d H:i:s') . '] Checking availability for service: ' . $serviceName . ' (ID: ' . $serviceId . ')');
            
            // Calculate end time for this specific service
            $startTime = new DateTime($date . ' ' . $time24);
            $endTime = clone $startTime;
            $endTime->add(new DateInterval('PT' . $duration . 'M'));
            $endTime24 = $endTime->format('H:i:s');
            
            // Check for existing reservations of this specific service that overlap with the requested time slot
            $checkQuery = "
                SELECT COUNT(*) as conflicting_reservations
                FROM reservation_schedule rs
                INNER JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
                INNER JOIN reservations r ON rsv.reservation_id = r.reservation_id
                WHERE rs.schedule_date = ?
                AND r.status NOT IN ('cancelled', 'completed')
                AND rsv.default_service_id = ?
                AND (
                    (rs.start_time <= ? AND rs.end_time > ?) OR
                    (rs.start_time < ? AND rs.end_time >= ?) OR
                    (rs.start_time >= ? AND rs.end_time <= ?)
                )
            ";
            
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bind_param(
                'sissssss',
                $date,
                $serviceId,
                $time24,
                $time24,
                $endTime24,
                $endTime24,
                $time24,
                $endTime24
            );
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $conflicts = $checkResult->fetch_assoc();
            
            $isServiceAvailable = $conflicts['conflicting_reservations'] == 0;
            
            error_log('[' . date('Y-m-d H:i:s') . '] Service ' . $serviceName . ' (ID: ' . $serviceId . ') availability: ' . ($isServiceAvailable ? 'AVAILABLE' : 'NOT AVAILABLE') . ' - Conflicts: ' . $conflicts['conflicting_reservations']);
            
            // If this service is not available, the entire package is not available
            if (!$isServiceAvailable) {
                error_log('[' . date('Y-m-d H:i:s') . '] Package unavailable because service ' . $serviceName . ' is not available at ' . $time24);
                return false;
            }
        }
        
        // All services are available
        error_log('[' . date('Y-m-d H:i:s') . '] Package availability result: AVAILABLE (all services available)');
        return true;
    }

    public function checkServiceTimeAvailability($date, $time, $serviceId)
    {
        error_log('[' . date('Y-m-d H:i:s') . '] checkServiceTimeAvailability called with: date=' . $date . ', time=' . $time . ', serviceId=' . $serviceId);
        
        // Convert time to 24-hour format for database comparison
        $time24 = date('H:i:s', strtotime($time));
        error_log('[' . date('Y-m-d H:i:s') . '] Converted time to 24h format: ' . $time24);
        
        // Get service duration to calculate end time
        $serviceQuery = "SELECT duration_minutes FROM default_services WHERE service_id = ?";
        $serviceStmt = $this->db->prepare($serviceQuery);
        $serviceStmt->bind_param('i', $serviceId);
        $serviceStmt->execute();
        $serviceResult = $serviceStmt->get_result();
        
        if ($serviceResult->num_rows === 0) {
            error_log('[' . date('Y-m-d H:i:s') . '] Service not found with ID: ' . $serviceId);
            return false; // Service not found
        }
        
        $service = $serviceResult->fetch_assoc();
        $duration = $service['duration_minutes'];
        error_log('[' . date('Y-m-d H:i:s') . '] Service duration: ' . $duration . ' minutes');
        
        // Calculate end time
        $startTime = new DateTime($date . ' ' . $time24);
        $endTime = clone $startTime;
        $endTime->add(new DateInterval('PT' . $duration . 'M'));
        $endTime24 = $endTime->format('H:i:s');
        error_log('[' . date('Y-m-d H:i:s') . '] Time slot: ' . $time24 . ' to ' . $endTime24);
        
        // Check for existing reservations of this specific service that overlap with the requested time slot
        $checkQuery = "
            SELECT COUNT(*) as conflicting_reservations
            FROM reservation_schedule rs
            INNER JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
            INNER JOIN reservations r ON rsv.reservation_id = r.reservation_id
            WHERE rs.schedule_date = ?
            AND r.status NOT IN ('cancelled', 'completed')
            AND rsv.default_service_id = ?
            AND (
                (rs.start_time <= ? AND rs.end_time > ?) OR
                (rs.start_time < ? AND rs.end_time >= ?) OR
                (rs.start_time >= ? AND rs.end_time <= ?)
            )
        ";
        
        error_log('[' . date('Y-m-d H:i:s') . '] Executing service availability check query');
        error_log('[' . date('Y-m-d H:i:s') . '] Query: ' . str_replace("\n", " ", $checkQuery));
        error_log('[' . date('Y-m-d H:i:s') . '] Parameters: date=' . $date . ', serviceId=' . $serviceId . ', time24=' . $time24 . ', endTime24=' . $endTime24);
        
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bind_param(
            'sissssss',
            $date,
            $serviceId,
            $time24,
            $time24,
            $endTime24,
            $endTime24,
            $time24,
            $endTime24
        );
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $conflicts = $checkResult->fetch_assoc();
        
        error_log('[' . date('Y-m-d H:i:s') . '] Found ' . $conflicts['conflicting_reservations'] . ' conflicting reservations for service ' . $serviceId);
        
        // Let's also check what's actually in the tables for debugging
        $debugQuery = "
            SELECT r.reservation_id, rs.schedule_date, rs.start_time, rs.end_time, r.status, rsv.default_service_id
            FROM reservation_schedule rs
            INNER JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
            INNER JOIN reservations r ON rsv.reservation_id = r.reservation_id
            WHERE rs.schedule_date = ?
            AND rsv.default_service_id = ?
            ORDER BY rs.schedule_date, rs.start_time
        ";
        
        $debugStmt = $this->db->prepare($debugQuery);
        $debugStmt->bind_param('si', $date, $serviceId);
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        
        error_log('[' . date('Y-m-d H:i:s') . '] Debug: Existing reservations for service ' . $serviceId . ' on date ' . $date . ':');
        while ($row = $debugResult->fetch_assoc()) {
            error_log('[' . date('Y-m-d H:i:s') . '] Debug: Reservation ID ' . $row['reservation_id'] . 
                     ' from ' . $row['start_time'] . ' to ' . $row['end_time'] . 
                     ' status: ' . $row['status']);
        }
        
        // Return true if no conflicts found, false if conflicts exist
        $isAvailable = $conflicts['conflicting_reservations'] == 0;
        error_log('[' . date('Y-m-d H:i:s') . '] Service availability result: ' . ($isAvailable ? 'AVAILABLE' : 'NOT AVAILABLE'));

        return $isAvailable;
    }

    public function getServiceDurationMinutes($serviceId)
    {
        $query = "SELECT duration_minutes FROM default_services WHERE service_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $serviceId);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return (int) ($row['duration_minutes'] ?? 0);
    }

    public function countConcurrentCategoryBookings($branchCategoryOverrideId, $branchId, $date, $startTime, $endTime, $excludeReservationId = null)
    {
        $query = "
            SELECT COUNT(*) AS concurrent_count
            FROM reservation_schedule rs
            INNER JOIN reservation_services rsv ON rs.reservation_service_id = rsv.reservation_service_id
            INNER JOIN reservations r ON rsv.reservation_id = r.reservation_id
            INNER JOIN branch_service_overrides bso ON rsv.branch_service_override_id = bso.branch_service_override_id
            INNER JOIN default_services ds ON ds.service_id = bso.default_service_id
            INNER JOIN branch_category_overrides bco ON bco.default_category_id = ds.category_id AND bco.branch_id = r.branch_id
            WHERE rs.schedule_date = ?
              AND r.status NOT IN ('cancelled','rejected')
              AND r.branch_id = ?
              AND bco.branch_category_override_id = ?
              AND (
                (rs.start_time < ? AND rs.end_time > ?) OR
                (rs.start_time < ? AND rs.end_time > ?) OR
                (rs.start_time >= ? AND rs.end_time <= ?)
              )
        ";

        if ($excludeReservationId !== null) {
            $query .= ' AND r.reservation_id != ?';
        }

        $stmt = $this->db->prepare($query);

        if ($excludeReservationId !== null) {
            $stmt->bind_param('sisisssssi', $date, $branchId, $branchCategoryOverrideId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime, $excludeReservationId);
        } else {
            $stmt->bind_param('sisisssss', $date, $branchId, $branchCategoryOverrideId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) ($row['concurrent_count'] ?? 0);
    }

    public function adminReschedule($reservationId, $newDate, $newTime, $reason = 'Updated by admin')
    {
        return $this->rescheduleReservation($reservationId, $newDate, $newTime, $reason);
    }
}