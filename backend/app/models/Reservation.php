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
                r.total_remaining_balance,
                b.branch_name,
                o.order_status
            FROM reservations r
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            LEFT JOIN orders o ON r.order_id = o.order_id
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
                    ds.service_name,
                    ds.price,
                    ds.duration_minutes
                FROM reservation_services rs
                LEFT JOIN default_services ds ON rs.service_id = ds.service_id
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
                'total_remaining_balance' => $row['total_remaining_balance'],
                'branch_name' => $row['branch_name'],
                'order_status' => $row['order_status'],
                'services' => $services,
                'schedule' => $schedule
            ];
        }
        
        return $reservations;
    }
}
