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

    // GET ALL FEEDBACK FOR ADMIN (with customer name and service)
    public function getAllFeedback($branchId = null, $search = '', $minRating = 0)
    {
        $query = "
            SELECT 
                f.feedback_id as id,
                f.rating,
                f.comment as feedback,
                f.created_at as date,
                u.username as customerName,
                rs.booked_service_name as service,
                b.branch_name,
                TIME_FORMAT(rsch.start_time, '%h:%i %p') as time
            FROM feedback f
            JOIN users u ON f.user_id = u.user_id
            JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
            LEFT JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
            LEFT JOIN reservations r ON rs.reservation_id = r.reservation_id
            LEFT JOIN branch b ON f.branch_id = b.branch_id
            WHERE 1=1
        ";

        $params = [];
        $types = '';

        // Add branch filter if provided
        if ($branchId !== null) {
            $query .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        // Add minimum rating filter
        if ($minRating > 0) {
            $query .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types .= 'i';
        }

        $query .= " ORDER BY f.created_at DESC";

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $feedbackList = [];
        while ($row = $result->fetch_assoc()) {
            $feedbackList[] = $row;
        }

        return $feedbackList;
    }

    // GET FEEDBACK STATISTICS
    public function getFeedbackStats($branchId = null)
    {
        $query = "
            SELECT 
                COUNT(*) as totalReviews,
                COALESCE(AVG(f.rating), 0) as averageRating,
                SUM(CASE WHEN f.rating = 5 THEN 1 ELSE 0 END) as fiveStars,
                SUM(CASE WHEN f.rating = 4 THEN 1 ELSE 0 END) as fourStars,
                SUM(CASE WHEN f.rating = 3 THEN 1 ELSE 0 END) as threeStars,
                SUM(CASE WHEN f.rating = 2 THEN 1 ELSE 0 END) as twoStars,
                SUM(CASE WHEN f.rating = 1 THEN 1 ELSE 0 END) as oneStar
            FROM feedback f
            WHERE 1=1
        ";

        $params = [];
        $types = '';

        if ($branchId !== null) {
            $query .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    // GET TOTAL FEEDBACK COUNT
    public function getTotalFeedbackCount($branchId = null)
    {
        $query = "SELECT COUNT(*) as total FROM feedback f WHERE 1=1";

        $params = [];
        $types = '';

        if ($branchId !== null) {
            $query .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['total'] ?? 0;
    }

    // GET FEEDBACK BY ID
    public function getFeedbackById($feedbackId)
    {
        $query = "
            SELECT 
                f.feedback_id as id,
                f.rating,
                f.comment as feedback,
                f.created_at as date,
                u.username as customerName,
                rs.booked_service_name as service,
                b.branch_name,
                TIME_FORMAT(rsch.start_time, '%h:%i %p') as time
            FROM feedback f
            JOIN users u ON f.user_id = u.user_id
            JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
            LEFT JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
            LEFT JOIN reservations r ON rs.reservation_id = r.reservation_id
            LEFT JOIN branch b ON f.branch_id = b.branch_id
            WHERE f.feedback_id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}