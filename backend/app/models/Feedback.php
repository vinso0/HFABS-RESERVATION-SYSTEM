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
        $stmt   = $this->db->prepare("DELETE FROM feedback_photos WHERE feedback_id = ?");
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
        $list   = [];
        while ($row = $result->fetch_assoc()) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $list[]        = $row;
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
                f.comment      AS feedback,
                f.created_at   AS date,
                f.is_blocked,
                f.blocked_at,
                f.blocked_by,
                u.username     AS customerName,
                rs.booked_service_name AS service,
                b.branch_name,
                TIME_FORMAT(rsch.start_time, '%h:%i %p') AS time
             FROM feedback f
             JOIN users u  ON f.user_id = u.user_id
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
                    f.comment      AS feedback,
                    f.created_at   AS date,
                    f.is_blocked,
                    f.blocked_at,
                    u.username     AS customerName,
                    rs.booked_service_name AS service,
                    b.branch_name,
                    TIME_FORMAT(rsch.start_time, '%h:%i %p') AS time,
                    (SELECT COUNT(*) FROM feedback_reports fr WHERE fr.feedback_id = f.feedback_id) AS report_count
                FROM feedback f
                JOIN users u  ON f.user_id = u.user_id
                JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                LEFT JOIN reservation_schedule rsch ON rs.reservation_service_id = rsch.reservation_service_id
                LEFT JOIN branch b ON f.branch_id = b.branch_id
                WHERE 1=1";

        $params = [];
        $types  = '';

        if ($branchId !== null && $branchId > 0) {
            $sql     .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        if (!empty($search)) {
            $sql     .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $term     = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types   .= 'sss';
        }

        if ($minRating > 0) {
            $sql     .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types   .= 'i';
        }

        if ($blockedFilter === 'blocked') {
            $sql .= " AND f.is_blocked = 1";
        } elseif ($blockedFilter === 'active') {
            $sql .= " AND f.is_blocked = 0";
        }

        $sql     .= " ORDER BY report_count DESC, f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $row['photos'] = $this->getPhotosByFeedbackId($row['feedback_id']);
            $list[]        = $row;
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
        $types  = '';

        if ($branchId !== null && $branchId > 0) {
            $sql     .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        if (!empty($search)) {
            $sql     .= " AND (u.username LIKE ? OR rs.booked_service_name LIKE ? OR f.comment LIKE ?)";
            $term     = "%$search%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types   .= 'sss';
        }

        if ($minRating > 0) {
            $sql     .= " AND f.rating >= ?";
            $params[] = $minRating;
            $types   .= 'i';
        }

        if ($blockedFilter === 'blocked') {
            $sql .= " AND f.is_blocked = 1";
        } elseif ($blockedFilter === 'active') {
            $sql .= " AND f.is_blocked = 0";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getFeedbackStats($branchId = null)
    {
        $sql = "SELECT
                    COUNT(*)                                              AS totalReviews,
                    COALESCE(AVG(rating), 0)                             AS averageRating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END)         AS fiveStars,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END)         AS fourStars,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END)         AS threeStars,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END)         AS twoStars,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END)         AS oneStar,
                    SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END)     AS blockedCount,
                    (SELECT COUNT(*) FROM feedback_reports)              AS pendingReports
                FROM feedback f
                WHERE 1=1";

        $params = [];
        $types  = '';

        if ($branchId !== null && $branchId > 0) {
            $sql     .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ── Block / Unblock ────────────────────────────────────────────────
    public function blockFeedback($feedbackId, $adminId)
    {
        $stmt = $this->db->prepare(
            "UPDATE feedback
             SET is_blocked = 1, blocked_at = NOW(), blocked_by = ?
             WHERE feedback_id = ?"
        );
        $stmt->bind_param('ii', $adminId, $feedbackId);
        return $stmt->execute();
    }

    public function unblockFeedback($feedbackId)
    {
        $stmt = $this->db->prepare(
            "UPDATE feedback
             SET is_blocked = 0, blocked_at = NULL, blocked_by = NULL
             WHERE feedback_id = ?"
        );
        $stmt->bind_param('i', $feedbackId);
        return $stmt->execute();
    }

    // ── Delete ─────────────────────────────────────────────────────────
    public function deleteFeedback($feedbackId)
    {
        $photos = $this->getPhotosByFeedbackId($feedbackId);
        $stmt   = $this->db->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        $stmt->bind_param('i', $feedbackId);
        $stmt->execute();
        return $photos;
    }

    // ── Reports ────────────────────────────────────────────────────────
    public function reportFeedback($feedbackId, $reporterId, $reason)
    {
        // Duplicate check
        $chk = $this->db->prepare(
            "SELECT report_id FROM feedback_reports
             WHERE feedback_id = ? AND reporter_id = ?"
        );
        $chk->bind_param('ii', $feedbackId, $reporterId);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) return 'duplicate';

        $stmt = $this->db->prepare(
            "INSERT INTO feedback_reports (feedback_id, reporter_id, reason)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iis', $feedbackId, $reporterId, $reason);
        return $stmt->execute();
    }

    public function getReports($branchId = null)
    {
        $sql = "SELECT
                    f.feedback_id,
                    f.comment,
                    f.rating,
                    f.is_blocked,
                    f.created_at,
                    COALESCE(u.username, 'Anonymous') AS customer_name,
                    rs.booked_service_name            AS service,
                    COUNT(fr.report_id)               AS report_count,
                    GROUP_CONCAT(fr.reason ORDER BY fr.reported_at SEPARATOR ' | ') AS reasons
                FROM feedback_reports fr
                JOIN feedback f ON fr.feedback_id = f.feedback_id
                LEFT JOIN users u ON f.user_id = u.user_id
                LEFT JOIN reservation_services rs ON f.reservation_service_id = rs.reservation_service_id
                WHERE 1=1";

        $params = [];
        $types  = '';

        if ($branchId !== null && $branchId > 0) {
            $sql     .= " AND f.branch_id = ?";
            $params[] = $branchId;
            $types   .= 'i';
        }

        $sql .= " GROUP BY f.feedback_id ORDER BY report_count DESC, f.created_at DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function deleteReportsByFeedback($feedbackId)
    {
        $stmt = $this->db->prepare("DELETE FROM feedback_reports WHERE feedback_id = ?");
        $stmt->bind_param('i', $feedbackId);
        return $stmt->execute();
    }

    // ── Per-Service Aggregated Ratings (for branch-detail page) ──────────
    public function getServiceRatingsByBranch($branchId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(bso.default_service_id, bso.branch_service_override_id) AS service_id,
                ROUND(AVG(f.rating), 1)                                           AS avg_rating,
                COUNT(f.feedback_id)                                              AS review_count
            FROM feedback f
            JOIN reservation_services rs
                ON f.reservation_service_id = rs.reservation_service_id
            JOIN branch_service_overrides bso
                ON rs.branch_service_override_id = bso.branch_service_override_id
            WHERE f.branch_id = ?
            AND bso.branch_id = ?
            AND f.is_blocked = 0
            GROUP BY COALESCE(bso.default_service_id, bso.branch_service_override_id)"
        );
        $stmt->bind_param('ii', $branchId, $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[(int)$row['service_id']] = [
                'avg_rating'   => (float) $row['avg_rating'],
                'review_count' => (int)   $row['review_count'],
            ];
        }
        return $map;
    }

    // ── Per-Package Aggregated Ratings ────────────────────────────────────
    public function getPackageRatingsByBranch($branchId)
    {
        // Join packages → their included branch_service_overrides →
        // reservation_services (via the same FK used by reviews) → feedback
        $stmt = $this->db->prepare(
            "SELECT
                p.package_id,
                ROUND(AVG(f.rating), 1) AS avg_rating,
                COUNT(f.feedback_id)    AS review_count
            FROM packages p
            JOIN package_services ps
                ON ps.package_id = p.package_id
            JOIN reservation_services rs
                ON rs.branch_service_override_id = ps.branch_service_override_id
            JOIN feedback f
                ON f.reservation_service_id = rs.reservation_service_id
            WHERE p.branch_id = ?
            AND f.branch_id = ?
            AND f.is_blocked = 0
            GROUP BY p.package_id"
        );
        $stmt->bind_param('ii', $branchId, $branchId);

        // If prepare() fails (e.g. table doesn't exist), return empty map safely
        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[(int)$row['package_id']] = [
                'avg_rating'   => (float) $row['avg_rating'],
                'review_count' => (int)   $row['review_count'],
            ];
        }
        return $map;
    }
}