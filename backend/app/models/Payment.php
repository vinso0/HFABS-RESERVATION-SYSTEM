<?php

class Payment extends Database
{
    public function createPayment($reservationId, $amountPaid, $paymentMethod, $status = 'paid', $paymongoPaymentId = null)
    {
        // Ensure the query matches your column names exactly
        $query = "INSERT INTO payments (reservation_id, amount_paid, payment_method, status, paymongo_payment_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($query);
        
        // ADJUSTMENT: Use 'i' for integer reservationId, 'd' for decimal amount
        // If reservation_id is a string (e.g., VARCHAR), use 's' instead of 'i'
        $stmt->bind_param('idsss', $reservationId, $amountPaid, $paymentMethod, $status, $paymongoPaymentId);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        error_log("DB Error: " . $stmt->error);
        return false;
    }

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

        // Build query dynamically
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

        // Remove trailing comma and space
        $query = rtrim($query, ', ');

        $query .= " WHERE reservation_id = ?";
        $params[] = $reservationId;
        $types .= 'i';

        $stmt = $this->db->prepare($query);

        // Safety check if no fields were updated
        if (empty($params)) return false; 

        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    public function createPaymentFromWebhook($reservationId, $paymongoPaymentId, $amountPaid, $paymentMethod, $status = 'paid')
    {
        // Check if payment already exists for this reservation
        $existingPayment = $this->getPaymentByReservationId($reservationId);
        
        if ($existingPayment) {
            // Update existing payment
            return $this->updatePaymentByReservationId(
                $reservationId,
                $paymongoPaymentId,
                $amountPaid,
                $paymentMethod,
                $status
            );
        } else {
            // Create new payment
            return $this->createPayment($reservationId, $amountPaid, $paymentMethod, $status);
        }
    }

    public function getAllPayments()
    {
        $query = "SELECT p.*, r.user_id FROM payments p LEFT JOIN reservations r ON p.reservation_id = r.reservation_id ORDER BY p.created_at DESC";
        
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