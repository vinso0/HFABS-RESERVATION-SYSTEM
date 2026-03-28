<?php
class Feedback extends Database
{
    // ── Submit / Update ────────────────────────────────────────────────
    public function submitFeedback($reservationServiceId, $userId, $branchId, $rating, $comment)
    {
        $existing = $this->getFeedbackByReservationServiceId($reservationServiceId);
        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE feedback
                 SET rating = ?, comment = ?, updated_at = NOW()
                 WHERE reservation_service_id = ?"
            );
            $stmt->bind_param('isi', $rating, $comment, $reservationServiceId);
            $stmt->execute();
            return $existing['feedback_id'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO feedback
             (reservation_service_id, user_id, branch_id, rating, comment, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->bind_param('iiiss', $reservationServiceId, $userId, $branchId, $rating, $comment);
        $stmt->execute();
        return $this->db->insert_id;
    }

    // ── Photos ─────────────────────────────────────────────────────────
    public function savePhotoPath($feedbackId, $photoPath, $order = 0)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO feedback_photos (feedback_id, photo_path, photo_order, uploaded_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param('isi', $feedbackId, $photoPath, $order);
        return $stmt->execute();
    }

    public function getPhotosByFeedbackId($feedbackId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM feedback_photos
             WHERE feedback_id = ?
             ORDER BY photo_order ASC, uploaded_at ASC"
        );
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();

        $result = $stmt->get_result();
        $photos = [];
        while ($row = $result->fetch_assoc()) $photos[] = $row;

        return $photos;
    }

    public function deletePhotosByFeedbackId($feedbackId)
    {
        $photos = $this->getPhotosByFeedbackId($feedbackId);

        $stmt = $this->db->prepare("DELETE FROM feedback_photos WHERE feedback_id = ?");
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();

        return $photos;
    }

    public function deletePhotoByPath($photoPath)
    {
        $stmt = $this->db->prepare("DELETE FROM feedback_photos WHERE photo_path = ?");
        $stmt->bind_param('s', $photoPath);
        return $stmt->execute();
    }

    // ── Single fetchers ────────────────────────────────────────────────
    public function getFeedbackByReservationServiceId($reservationServiceId)
    {
        $stmt = $this->db->prepare("SELECT * FROM feedback WHERE reservation_service_id = ?");
        $stmt->bind_param('i', $reservationServiceId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getFeedbackByReservationId($reservationId)
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, rs.reservation_id
             FROM feedback f
             JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
             WHERE rs.reservation_id = ?"
        );
        $stmt->bind_param('i', $reservationId);
        $stmt->execute();

        $result = $stmt->get_result();
        $list = [];

        while ($row = $result->fetch_assoc()) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $list[] = $row;
        }

        return $list;
    }

    public function getFeedbackById($feedbackId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                f.feedback_id AS id,
                f.feedback_id,
                f.user_id,
                f.rating,
                f.comment AS feedback,
                f.created_at AS date,
                f.is_blocked,
                f.blocked_at,
                f.blocked_by,
                u.username AS customerName,
                rs.booked_service_name AS service,
                b.branch_name,
                TIME_FORMAT(rsch.start_time, '%h:%i %p') AS time
             FROM feedback f
             JOIN users u ON f.user_id = u.user_id
             JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
             LEFT JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
             LEFT JOIN branch b ON f.branch_id = b.branch_id
             WHERE f.feedback_id = ?"
        );
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        if ($row) $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);

        return $row;
    }

    // ── Admin: list all feedback ───────────────────────────────────────
    public function getAllFeedback($branchId = null, $search = '', $minRating = 0, $blockedFilter = 'all', $limit = 10, $offset = 0)
    {
        $sql = "SELECT
                    f.feedback_id AS id,
                    f.feedback_id,
                    f.rating,
                    f.comment AS feedback,
                    f.created_at AS date,
                    f.is_blocked,
                    f.blocked_at,
                    u.username AS customerName,
                    rs.booked_service_name AS service,
                    b.branch_name,
                    TIME_FORMAT(rsch.start_time, '%h:%i %p') AS time,
                    (SELECT COUNT(*) FROM feedback_reports fr WHERE fr.feedback_id = f.feedback_id) AS report_count
                FROM feedback f
                JOIN users u ON f.user_id = u.user_id
                JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                LEFT JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
                LEFT JOIN branch b ON f.branch_id = b.branch_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if ($branchId !== null && $branchId > 0) {
            $sql .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $term = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= 'sss';
        }

        if ($minRating > 0) {
            $sql .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types .= 'i';
        }

        if ($blockedFilter === 'blocked') {
            $sql .= " AND f.is_blocked = 1";
        } elseif ($blockedFilter === 'active') {
            $sql .= " AND f.is_blocked = 0";
        }

        $sql .= " ORDER BY report_count DESC, f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        $list = [];

        while ($row = $result->fetch_assoc()) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $list[] = $row;
        }

        return $list;
    }

    public function getTotalFeedbackCount($branchId = null, $search = '', $minRating = 0, $blockedFilter = 'all')
    {
        $sql = "SELECT COUNT(*) AS total
                FROM feedback f
                JOIN users u ON f.user_id = u.user_id
                JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if ($branchId !== null && $branchId > 0) {
            $sql .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $term = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= 'sss';
        }

        if ($minRating > 0) {
            $sql .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types .= 'i';
        }

        if ($blockedFilter === 'blocked') {
            $sql .= " AND f.is_blocked = 1";
        } elseif ($blockedFilter === 'active') {
            $sql .= " AND f.is_blocked = 0";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return (int)$stmt->get_result()->fetch_assoc()['total'];
    }
}