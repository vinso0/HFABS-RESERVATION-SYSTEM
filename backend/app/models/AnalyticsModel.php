<?php

class AnalyticsModel {

    private $db;

    public function __construct($db) {
        // $db is a mysqli connection (from Database->getConnection())
        $this->db = $db;
    }

    // ── Helper: run a prepared statement with a single integer param ──
    // Avoids repeating prepare/bind_param/execute/fetch on every method
    private function query(string $sql, int $branch_id): array {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Query prepare failed: ' . $this->db->error);
        }
        $stmt->bind_param('i', $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function getReservationsPerDay(int $branch_id): array {
        $sql = "
            SELECT DATE(reservation_date) AS period,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
              AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(reservation_date)
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getReservationsPerWeek(int $branch_id): array {
        $sql = "
            SELECT YEARWEEK(reservation_date, 1) AS period,
                   MIN(DATE(reservation_date))   AS week_start,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
              AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(reservation_date, 1)
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getReservationsPerMonth(int $branch_id): array {
        $sql = "
            SELECT DATE_FORMAT(reservation_date, '%Y-%m') AS period,
                   DATE_FORMAT(reservation_date, '%b %Y') AS label,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
              AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(reservation_date, '%Y-%m')
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getReservationsPerYear(int $branch_id): array {
        $sql = "
            SELECT YEAR(reservation_date) AS period,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
            GROUP BY YEAR(reservation_date)
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getMostReservedDays(int $branch_id): array {
        $sql = "
            SELECT DAYNAME(reservation_date)   AS day_name,
                   DAYOFWEEK(reservation_date) AS day_num,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
            GROUP BY DAYOFWEEK(reservation_date), DAYNAME(reservation_date)
            ORDER BY total DESC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getMostReservedMonths(int $branch_id): array {
        $sql = "
            SELECT MONTHNAME(reservation_date) AS month_name,
                   MONTH(reservation_date)     AS month_num,
                   COUNT(*) AS total
            FROM reservations
            WHERE branch_id = ?
            GROUP BY MONTH(reservation_date), MONTHNAME(reservation_date)
            ORDER BY total DESC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getReturningCustomers(int $branch_id): array {
        $sql = "
            SELECT u.username, u.email,
                   COUNT(r.reservation_id) AS reservation_count
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.branch_id = ?
              AND r.status = 'completed'
            GROUP BY r.user_id, u.username, u.email
            HAVING reservation_count > 1
            ORDER BY reservation_count DESC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getMostReservedServices(int $branch_id): array {
        $sql = "
            SELECT rs.booked_service_name    AS service_name,
                   rs.booked_category_name   AS category,
                   COUNT(*)                  AS booking_count,
                   SUM(rs.booked_unit_price) AS total_revenue
            FROM reservation_services rs
            JOIN reservations r ON rs.reservation_id = r.reservation_id
            WHERE r.branch_id = ?
            GROUP BY rs.booked_service_name, rs.booked_category_name
            ORDER BY booking_count DESC
            LIMIT 10
        ";
        return $this->query($sql, $branch_id);
    }

    public function getRevenuePerDay(int $branch_id): array {
        $sql = "
            SELECT DATE(p.created_at)  AS period,
                   SUM(p.amount_paid)  AS total_revenue,
                   COUNT(p.payment_id) AS transaction_count
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            WHERE r.branch_id = ?
              AND p.status = 'paid'
              AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(p.created_at)
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getRevenuePerMonth(int $branch_id): array {
        $sql = "
            SELECT DATE_FORMAT(p.created_at, '%Y-%m') AS period,
                   DATE_FORMAT(p.created_at, '%b %Y')  AS label,
                   SUM(p.amount_paid)   AS total_revenue,
                   COUNT(p.payment_id)  AS transaction_count
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            WHERE r.branch_id = ?
              AND p.status = 'paid'
              AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getRevenuePerYear(int $branch_id): array {
        $sql = "
            SELECT YEAR(p.created_at)   AS period,
                   SUM(p.amount_paid)   AS total_revenue,
                   COUNT(p.payment_id)  AS transaction_count
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            WHERE r.branch_id = ?
              AND p.status = 'paid'
            GROUP BY YEAR(p.created_at)
            ORDER BY period ASC
        ";
        return $this->query($sql, $branch_id);
    }

    public function getAllAnalytics(int $branch_id): array {
        return [
            'reservations_per_day'   => $this->getReservationsPerDay($branch_id),
            'reservations_per_week'  => $this->getReservationsPerWeek($branch_id),
            'reservations_per_month' => $this->getReservationsPerMonth($branch_id),
            'reservations_per_year'  => $this->getReservationsPerYear($branch_id),
            'most_reserved_days'     => $this->getMostReservedDays($branch_id),
            'most_reserved_months'   => $this->getMostReservedMonths($branch_id),
            'returning_customers'    => $this->getReturningCustomers($branch_id),
            'most_reserved_services' => $this->getMostReservedServices($branch_id),
            'revenue_per_day'        => $this->getRevenuePerDay($branch_id),
            'revenue_per_month'      => $this->getRevenuePerMonth($branch_id),
            'revenue_per_year'       => $this->getRevenuePerYear($branch_id),
        ];
    }
}