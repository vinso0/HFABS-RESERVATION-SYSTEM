<?php

class Feedback extends Database
{
    public function submitFeedback($reservationServiceId, $userId, $branchId, $rating, $comment)
    {
        $existingFeedback = $this->getFeedbackByReservationServiceId($reservationServiceId);

        if ($existingFeedback) {
            $query = "
                UPDATE feedback
                SET rating = ?, comment = ?, updated_at = NOW(), status = 'pending'
                WHERE reservation_service_id = ?
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('isi', $rating, $comment, $reservationServiceId);
            $stmt->execute();
            return $existingFeedback['feedback_id'];
        } else {
            $query = "
                INSERT INTO feedback
                (reservation_service_id, user_id, branch_id, rating, comment, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
            ";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('iiiss', $reservationServiceId, $userId, $branchId, $rating, $comment);
            $stmt->execute();
            return $this->db->insert_id;
        }
    }

    public function savePhotoPath($feedbackId, $photoPath, $order = 0)
    {
        $query = "INSERT INTO feedback_photos (feedback_id, photo_path, photo_order, uploaded_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('isi', $feedbackId, $photoPath, $order);
        return $stmt->execute();
    }

    public function getPhotosByFeedbackId($feedbackId)
    {
        $query = "SELECT * FROM feedback_photos WHERE feedback_id = ? ORDER BY photo_order ASC, uploaded_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();
        $result = $stmt->get_result();
        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row;
        }
        return $photos;
    }

    public function deletePhotosByFeedbackId($feedbackId)
    {
        // Get photo paths first to delete files
        $photos = $this->getPhotosByFeedbackId($feedbackId);

        $query = "DELETE FROM feedback_photos WHERE feedback_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();

        return $photos; // Return paths so controller can delete files
    }

    public function getFeedbackByReservationServiceId($reservationServiceId)
    {
        $query = "SELECT * FROM feedback WHERE reservation_service_id = ?";
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
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $feedbackList[] = $row;
        }
        return $feedbackList;
    }

    public function getAllFeedback($branchId = null, $search = '', $minRating = 0, $statusFilter = 'all', $limit = 10, $offset = 0)
    {
        $query = "
            SELECT
                f.feedback_id as id,
                f.feedback_id,
                f.rating,
                f.comment as feedback,
                f.created_at as date,
                f.status,
                f.is_flagged,
                f.admin_note,
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
        $types  = '';

        if ($branchId !== null) {
            $query   .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        if (!empty($search)) {
            $query   .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types   .= 'sss';
        }

        if ($minRating > 0) {
            $query   .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types   .= 'i';
        }

        if ($statusFilter === 'flagged') {
            $query .= " AND f.is_flagged = 1";
        } elseif ($statusFilter !== 'all') {
            $query   .= " AND f.status = ?";
            $params[] = $statusFilter;
            $types   .= 's';
        }

        $query   .= " ORDER BY f.is_flagged DESC, f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $feedbackList = [];
        while ($row = $result->fetch_assoc()) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $feedbackList[] = $row;
        }
        return $feedbackList;
    }

    public function deletePhotoByPath($photoPath)
    {
        $query = "DELETE FROM feedback_photos WHERE photo_path = ?";
        $stmt  = $this->db->prepare($query);
        $stmt->bind_param('s', $photoPath);
        return $stmt->execute();
    }

    // GET APPROVED FEEDBACK ONLY (for customer-facing display)
    public function getApprovedFeedback($branchId = null)
    {
        $query = "
            SELECT
                f.feedback_id,
                f.rating,
                f.comment,
                f.created_at,
                u.username as customerName,
                rs.booked_service_name as service
            FROM feedback f
            JOIN users u ON f.user_id = u.user_id
            JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
            WHERE f.status = 'approved'
        ";

        $params = [];
        $types = '';

        if ($branchId !== null) {
            $query .= " AND f.branch_id = ?";
            $params[] = $branchId;
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
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $feedbackList[] = $row;
        }
        return $feedbackList;
    }

    public function updateFeedbackStatus($feedbackId, $status, $adminNote = null)
    {
        $query = "UPDATE feedback SET status = ?, admin_note = ?, updated_at = NOW() WHERE feedback_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $status, $adminNote, $feedbackId);
        return $stmt->execute();
    }

    public function toggleFlag($feedbackId, $isFlagged)
    {
        $query = "UPDATE feedback SET is_flagged = ?, updated_at = NOW() WHERE feedback_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $isFlagged, $feedbackId);
        return $stmt->execute();
    }

    public function deleteFeedback($feedbackId)
    {
        // Photos are deleted via CASCADE, but we need file paths first
        $photos = $this->getPhotosByFeedbackId($feedbackId);

        $query = "DELETE FROM feedback WHERE feedback_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();

        return $photos;
    }

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
                SUM(CASE WHEN f.rating = 1 THEN 1 ELSE 0 END) as oneStar,
                SUM(CASE WHEN f.is_flagged = 1 THEN 1 ELSE 0 END) as flaggedCount,
                SUM(CASE WHEN f.status = 'pending' THEN 1 ELSE 0 END) as pendingCount
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

    public function getTotalFeedbackCount($branchId = null, $search = '', $minRating = 0, $statusFilter = 'all')
    {
        $query = "SELECT COUNT(*) as total FROM feedback f
                JOIN users u ON f.user_id = u.user_id
                JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                WHERE 1=1";

        $params = [];
        $types  = '';

        if ($branchId !== null) {
            $query   .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        if (!empty($search)) {
            $query   .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $term     = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types   .= 'sss';
        }

        if ($minRating > 0) {
            $query   .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types   .= 'i';
        }

        if ($statusFilter === 'flagged') {
            $query .= " AND f.is_flagged = 1";
        } elseif ($statusFilter !== 'all') {
            $query   .= " AND f.status = ?";
            $params[] = $statusFilter;
            $types   .= 's';
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    public function getFeedbackById($feedbackId)
    {
        $query = "
            SELECT
                f.feedback_id as id,
                f.feedback_id,
                f.rating,
                f.comment as feedback,
                f.created_at as date,
                f.status,
                f.is_flagged,
                f.admin_note,
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
        $row = $result->fetch_assoc();

        if ($row) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
        }

        return $row;
    }
}
