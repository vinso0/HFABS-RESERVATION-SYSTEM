<?php

class Feedback extends Database
{
    public function submitFeedback($reservationServiceId, $userId, $branchId, $rating, $comment)
    {
        // Check if feedback already exists for this reservation service
        $existingFeedback = $this->getFeedbackByReservationServiceId($reservationServiceId);
        
        if ($existingFeedback) {
            // Update existing feedback
            $query = "
                UPDATE feedback
                SET rating = ?, comment = ?, updated_at = NOW()
                WHERE reservation_service_id = ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('isi', $rating, $comment, $reservationServiceId);
        } else {
            // Insert new feedback
            $query = "
                INSERT INTO feedback
                (reservation_service_id, user_id, branch_id, rating, comment, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('iiiss', $reservationServiceId, $userId, $branchId, $rating, $comment);
        }
        
        return $stmt->execute();
    }

    public function getFeedbackByReservationServiceId($reservationServiceId)
    {
        $query = "
            SELECT * FROM feedback 
            WHERE reservation_service_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $reservationServiceId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getFeedbackByReservationId($reservationId)
    {
        $query = "
            SELECT f.*, rs.reservation_id 
            FROM feedback f
            JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
            WHERE rs.reservation_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $reservationId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $feedbackList = [];
        while ($row = $result->fetch_assoc()) {
            $feedbackList[] = $row;
        }
        
        return $feedbackList;
    }
}
