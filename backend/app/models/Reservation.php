<?php

class Reservation extends Database
{
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
        // First, get the branch associated with the admin user
        $branchQuery = "
            SELECT b.branch_id, b.branch_name
            FROM users u
            JOIN admin_branch ab ON u.user_id = ab.user_id
            JOIN branch b ON ab.branch_id = b.branch_id
            WHERE u.user_id = ?
        ";
        
        $branchStmt = $this->db->prepare($branchQuery);
        $branchStmt->bind_param('i', $userId);
        $branchStmt->execute();
        $branchResult = $branchStmt->get_result();
        
        if (!$branchResult || $branchResult->num_rows === 0) {
            return [];
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
            WHERE r.branch_id = ?
                AND DATE(r.reservation_date) = ?
                AND r.status = 'confirmed'
            ORDER BY r.reservation_date ASC
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
}
