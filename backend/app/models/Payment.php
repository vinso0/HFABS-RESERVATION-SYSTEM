<?php

class Payment extends Database
{
    public function createPayment(
        $reservationId,
        $amountPaid,
        $paymentMethod,
        $status = 'paid',
        $paymongoPaymentId = null,
        $services = [],
        $scheduleData = []
    ) {
        // ── 1. Insert into payments ──────────────────────────────────────
        $query = "INSERT INTO payments 
                    (reservation_id, amount_paid, payment_method, status, paymongo_payment_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('idsss', $reservationId, $amountPaid, $paymentMethod, $status, $paymongoPaymentId);

        if (!$stmt->execute()) {
            error_log("DB Error (payments insert): " . $stmt->error);
            return false;
        }

        $paymentId = $stmt->insert_id;
        $stmt->close();

        // ── 2. Insert reservation_schedules ─────────────────────────────
        if (!empty($scheduleData)) {
            $schSql = "INSERT INTO reservation_schedules
                           (reservation_id, schedule_date, start_time, end_time,
                            is_rescheduled, previous_schedule_id, reschedule_reason)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";

            $schStmt = $this->db->prepare($schSql);
            $isRescheduled      = (int)($scheduleData['is_rescheduled']      ?? 0);
            $previousScheduleId = $scheduleData['previous_schedule_id'] ?? null;
            $rescheduleReason   = $scheduleData['reschedule_reason']    ?? null;

            $schStmt->bind_param(
                'isssiis',
                $reservationId,
                $scheduleData['schedule_date'],
                $scheduleData['start_time'],
                $scheduleData['end_time'],
                $isRescheduled,
                $previousScheduleId,
                $rescheduleReason
            );

            if (!$schStmt->execute()) {
                error_log("DB Error (reservation_schedules insert): " . $schStmt->error);
            }
            $schStmt->close();
        }

        // ── 3. Insert reservation_services ──────────────────────────────
        if (!empty($services)) {
            $firstService = $services[0];

            if (!empty($firstService['is_package']) && !empty($firstService['booked_package_id'])) {
                // ── PACKAGE BOOKING ─────────────────────────────────────
                $packageId        = (int)$firstService['booked_package_id'];
                $remainingBalance = (float)($firstService['remaining_balance'] ?? 0);

                // Fetch all services under this package
                $pkgSql = "
                    SELECT
                        bps.branch_service_override_id,
                        COALESCE(bso.duration_minutes_override, ds.duration_minutes) AS duration_minutes
                    FROM branch_package_services bps
                    INNER JOIN branch_service_overrides bso
                        ON bps.branch_service_override_id = bso.branch_service_override_id
                    INNER JOIN default_services ds
                        ON ds.service_id = bso.default_service_id
                    WHERE bps.package_id = ?
                    ORDER BY bps.sort_order ASC
                ";

                $pkgStmt = $this->db->prepare($pkgSql);
                $pkgStmt->bind_param('i', $packageId);
                $pkgStmt->execute();
                $pkgResult = $pkgStmt->get_result();

                $pkgServices = [];
                while ($row = $pkgResult->fetch_assoc()) {
                    $pkgServices[] = $row;
                }
                $pkgStmt->close();

                if (empty($pkgServices)) {
                    error_log("WARNING: No services found for package_id $packageId");
                }

                $count             = count($pkgServices);
                $perServiceBalance = $count > 0 ? round($remainingBalance / $count, 2) : 0;

                $rsSql = "INSERT INTO reservation_services
                              (reservation_id, branch_service_override_id, duration_minutes,
                               remaining_balance, booked_package_id)
                          VALUES (?, ?, ?, ?, ?)";

                foreach ($pkgServices as $svc) {
                    $rsStmt   = $this->db->prepare($rsSql);
                    $bsoId    = (int)$svc['branch_service_override_id'];
                    $duration = (int)$svc['duration_minutes'];

                    $rsStmt->bind_param(
                        'iidii',
                        $reservationId,
                        $bsoId,
                        $duration,
                        $perServiceBalance,
                        $packageId
                    );

                    if (!$rsStmt->execute()) {
                        error_log("DB Error (reservation_services package): " . $rsStmt->error);
                    }
                    $rsStmt->close();
                }

                error_log("Inserted $count reservation_services rows for package_id $packageId, reservation $reservationId");

            } else {
                // ── SINGLE SERVICE BOOKING ───────────────────────────────
                $rsSql = "INSERT INTO reservation_services
                              (reservation_id, branch_service_override_id, duration_minutes,
                               remaining_balance, booked_package_id)
                          VALUES (?, ?, ?, ?, NULL)";

                foreach ($services as $svc) {
                    $rsStmt           = $this->db->prepare($rsSql);
                    $bsoId            = isset($svc['branch_service_override_id']) ? (int)$svc['branch_service_override_id'] : null;
                    $duration         = (int)($svc['duration_minutes'] ?? 60);
                    $remainingBalance = (float)($svc['remaining_balance'] ?? 0);

                    $rsStmt->bind_param('iidd', $reservationId, $bsoId, $duration, $remainingBalance);

                    if (!$rsStmt->execute()) {
                        error_log("DB Error (reservation_services single): " . $rsStmt->error);
                    }
                    $rsStmt->close();
                }

                error_log("Inserted " . count($services) . " reservation_services row(s) for reservation $reservationId");
            }
        }

        return $paymentId;
    }

    // ── All methods below are UNCHANGED ─────────────────────────────────

    public function updatePaymentStatus($paymentId, $status, $paymongoPaymentId = null, $amountPaid = null)
    {
        $query = "UPDATE payments SET status = ?";
        $params = [$status];
        $types = 's';

        if ($paymongoPaymentId) {
            $query .= ", paymongo_payment_id = ?";
            $params[] = $paymongoPaymentId;
            $types .= 's';
        }

        if ($amountPaid !== null) {
            $query .= ", amount_paid = ?";
            $params[] = $amountPaid;
            $types .= 'd';
        }

        $query .= " WHERE payment_id = ?";
        $params[] = $paymentId;
        $types .= 'i';

        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    public function getPaymentByReservationId($reservationId)
    {
        $query = "SELECT * FROM payments WHERE reservation_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    public function getPaymentById($paymentId)
    {
        $query = "SELECT * FROM payments WHERE payment_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    public function updatePaymentByReservationId($reservationId, $paymongoPaymentId = null, $amountPaid = null, $paymentMethod = null, $status = null)
    {
        $query = "UPDATE payments SET ";
        $params = [];
        $types = '';

        if ($paymongoPaymentId !== null) {
            $query .= "paymongo_payment_id = ?, ";
            $params[] = $paymongoPaymentId;
            $types .= 's';
        }

        if ($amountPaid !== null) {
            $query .= "amount_paid = ?, ";
            $params[] = $amountPaid;
            $types .= 'd';
        }

        if ($paymentMethod !== null) {
            $query .= "payment_method = ?, ";
            $params[] = $paymentMethod;
            $types .= 's';
        }

        if ($status !== null) {
            $query .= "status = ?, ";
            $params[] = $status;
            $types .= 's';
        }

        $query = rtrim($query, ', ');
        $query .= " WHERE reservation_id = ?";
        $params[] = $reservationId;
        $types .= 'i';

        $stmt = $this->db->prepare($query);

        if (empty($params)) return false;

        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    public function createPaymentFromWebhook($reservationId, $paymongoPaymentId, $amountPaid, $paymentMethod, $status = 'paid')
    {
        $existingPayment = $this->getPaymentByReservationId($reservationId);

        if ($existingPayment) {
            return $this->updatePaymentByReservationId(
                $reservationId,
                $paymongoPaymentId,
                $amountPaid,
                $paymentMethod,
                $status
            );
        } else {
            return $this->createPayment($reservationId, $amountPaid, $paymentMethod, $status);
        }
    }

    public function getAllPayments()
    {
        $query = "SELECT p.*, r.user_id FROM payments p 
                  LEFT JOIN reservations r ON p.reservation_id = r.reservation_id 
                  ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }

        return $payments;
    }
}
