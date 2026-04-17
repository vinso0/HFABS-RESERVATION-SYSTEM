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
        error_log("PAYMENT DEBUG: createPayment called with reservationId=$reservationId, amountPaid=$amountPaid, services count=" . count($services));
        error_log("PAYMENT DEBUG: Services data: " . json_encode($services));
        error_log("PAYMENT DEBUG: Schedule data: " . json_encode($scheduleData));
        
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
        
        error_log("PAYMENT DEBUG: Payment record created successfully with ID: $paymentId");

        // ── 2. Insert reservation_services ──────────────────────────────
        if (!empty($services)) {
            error_log("PAYMENT DEBUG: Processing services insertion...");
            $firstService = $services[0];
            error_log("PAYMENT DEBUG: First service data: " . json_encode($firstService));

            if (!empty($firstService['is_package']) && !empty($firstService['booked_package_id'])) {
                // ── PACKAGE BOOKING ─────────────────────────────────────
                $packageId        = (int)$firstService['booked_package_id'];
                $remainingBalance = (float)($firstService['remaining_balance'] ?? 0);

                // Fetch all services under this package
                $pkgSql = "
                    SELECT COALESCE(bso.duration_minutes_override, ds.duration_minutes) as duration_minutes,
                           COALESCE(bso.price_override, ds.price) as price, 
                           dsc.category_name,
                           bso.branch_service_override_id,
                           ds.service_id as default_service_id,
                           ds.service_name
                    FROM branch_package_services bps
                    JOIN branch_service_overrides bso ON bps.branch_service_override_id = bso.branch_service_override_id
                    JOIN default_services ds ON bso.default_service_id = ds.service_id
                    JOIN default_services_categories dsc ON ds.category_id = dsc.service_category_id
                    WHERE bps.package_id = ?
                ";
                $pkgStmt = $this->db->prepare($pkgSql);
                $pkgStmt->bind_param('i', $packageId);
                $pkgStmt->execute();
                $pkgResult = $pkgStmt->get_result();
                $pkgServices = $pkgResult->fetch_all(MYSQLI_ASSOC);
                $pkgStmt->close();

                if (empty($pkgServices)) {
                    error_log("WARNING: No services found for package_id $packageId");
                }

                $count             = count($pkgServices);
                $perServiceBalance = $count > 0 ? round($remainingBalance / $count, 2) : 0;

                $rsSql = "INSERT INTO reservation_services
                              (reservation_id, default_service_id, branch_service_override_id, 
                               remaining_balance, booked_package_id, booked_service_name, 
                               booked_description, booked_duration_minutes, booked_unit_price, 
                               booked_category_name)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($pkgServices as $svc) {
                    $rsStmt   = $this->db->prepare($rsSql);
                    $defaultServiceId = (int)$svc['default_service_id'];
                    $bsoId    = (int)$svc['branch_service_override_id'];
                    $duration = (int)$svc['duration_minutes'];
                    $serviceName = $svc['service_name'] ?? 'Unknown Service';
                    $description = $svc['description'] ?? 'Service description';
                    $unitPrice = (float)($svc['price'] ?? 0);
                    $categoryName = $svc['category_name'] ?? null;

                    $rsStmt->bind_param(
                        'iiiddssdds',
                        $reservationId,
                        $defaultServiceId,
                        $bsoId,
                        $perServiceBalance,
                        $packageId,
                        $serviceName,
                        $description,
                        $duration,
                        $unitPrice,
                        $categoryName
                    );

                    if (!$rsStmt->execute()) {
                        error_log("DB Error (reservation_services package): " . $rsStmt->error);
                    }
                    $rsStmt->close();
                }

                error_log("Inserted $count reservation_services rows for package_id $packageId, reservation $reservationId");

            } else {
                // ── SINGLE SERVICE BOOKING ───────────────────────────────
                error_log("PAYMENT DEBUG: Processing single service booking...");
                
                $rsSql = "INSERT INTO reservation_services
                              (reservation_id, default_service_id, branch_service_override_id, 
                               remaining_balance, booked_package_id, booked_service_name, 
                               booked_description, booked_duration_minutes, booked_unit_price, 
                               booked_category_name)
                          VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)";
                
                error_log("PAYMENT DEBUG: Single service SQL: " . $rsSql);

                foreach ($services as $svc) {
                    $rsStmt           = $this->db->prepare($rsSql);
                    $defaultServiceId = (int)($svc['default_service_id'] ?? 0);
                    $bsoId            = isset($svc['branch_service_override_id']) ? (int)$svc['branch_service_override_id'] : null;
                    $duration         = (int)($svc['duration_minutes'] ?? 60);
                    $remainingBalance = (float)($svc['remaining_balance'] ?? 0);
                    $serviceName      = $svc['service_name'] ?? 'Unknown Service';
                    $description      = $svc['description'] ?? 'Service description';
                    $unitPrice        = (float)($svc['price'] ?? 0);
                    $categoryName     = $svc['category_name'] ?? null;

                    error_log("PAYMENT DEBUG: Binding params - reservationId: $reservationId, defaultServiceId: $defaultServiceId, bsoId: " . ($bsoId ?? 'NULL') . ", remainingBalance: $remainingBalance, serviceName: $serviceName, description: $description, duration: $duration, unitPrice: $unitPrice, categoryName: " . ($categoryName ?? 'NULL'));

                    $rsStmt->bind_param(
                        'iiidssdds',
                        $reservationId,           // 1. reservation_id (i)
                        $defaultServiceId,       // 2. default_service_id (i)
                        $bsoId,                   // 3. branch_service_override_id (i)
                        $remainingBalance,        // 4. remaining_balance (d)
                        $serviceName,             // 6. booked_service_name (s)
                        $description,             // 7. booked_description (s)
                        $duration,                // 8. booked_duration_minutes (d)
                        $unitPrice,               // 9. booked_unit_price (d)
                        $categoryName             // 10. booked_category_name (s)
                    );

                    if (!$rsStmt->execute()) {
                        error_log("DB Error (reservation_services single): " . $rsStmt->error);
                    } else {
                        error_log("PAYMENT DEBUG: Single service inserted successfully for reservation $reservationId");
                    }
                    $rsStmt->close();
                }

                error_log("Inserted " . count($services) . " reservation_services row(s) for reservation $reservationId");
            }
        }

        // ── 3. Insert reservation_schedules (AFTER services are created) ─────────────────────────────
        if (!empty($scheduleData)) {
            $schSql = "INSERT INTO reservation_schedule
                           (reservation_service_id, schedule_date, start_time, end_time,
                            schedule_status, is_rescheduled, previous_schedule_id, reschedule_reason)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            // Get the first reservation service ID (for single service bookings)
            $getServiceIdSql = "SELECT reservation_service_id FROM reservation_services 
                                WHERE reservation_id = ? ORDER BY reservation_service_id ASC LIMIT 1";
            $getServiceStmt = $this->db->prepare($getServiceIdSql);
            $getServiceStmt->bind_param('i', $reservationId);
            $getServiceStmt->execute();
            $serviceResult = $getServiceStmt->get_result();
            $serviceRow = $serviceResult->fetch_assoc();
            $reservationServiceId = $serviceRow['reservation_service_id'] ?? null;

            if ($reservationServiceId) {
                $schStmt = $this->db->prepare($schSql);
                $isRescheduled      = (int)($scheduleData['is_rescheduled']      ?? 0);
                $previousScheduleId = $scheduleData['previous_schedule_id'] ?? null;
                $rescheduleReason   = $scheduleData['reschedule_reason']    ?? null;
                $scheduleStatus     = $scheduleData['schedule_status'] ?? 'confirmed';

                error_log("PAYMENT DEBUG: Inserting schedule with status: $scheduleStatus");

                $schStmt->bind_param(
                    'isssisss',
                    $reservationServiceId,
                    $scheduleData['schedule_date'],
                    $scheduleData['start_time'],
                    $scheduleData['end_time'],
                    $scheduleStatus,
                    $isRescheduled,
                    $previousScheduleId,
                    $rescheduleReason
                );

                if (!$schStmt->execute()) {
                    error_log("DB Error (reservation_schedule insert): " . $schStmt->error);
                }
                $schStmt->close();
            } else {
                error_log("WARNING: Could not find reservation_service_id for reservation $reservationId");
            }
        }

        error_log("PAYMENT DEBUG: createPayment completed successfully, returning paymentId: $paymentId");
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
