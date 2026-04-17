<?php

class User extends Database
{
    // REGISTER FUNCTION
    public function register($username, $email, $password, $contact_number)
    {
        // Password is already hashed in the controller, don't hash again!
        
        // Check if email already exists
        if ($this->getUserByEmail($email)) {
            error_log("Registration error: Email already exists - " . $email);
            return false;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password, contact_number, role) VALUES (?, ?, ?, ?, 'customer')"
        );
        // Fixed: contact_number should be 's' (string), not 'i' (integer)
        $stmt->bind_param("ssss", $username, $email, $password, $contact_number);

        $result = $stmt->execute();
        
        // Check for errors
        if (!$result) {
            error_log("Registration error: " . $stmt->error);
            return false;
        }
        
        return $result;
    }

    // LOGIN FUNCTION
    public function login($identifier)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? OR username = ?"
        );
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // GET USER BY ID
    public function getUserById($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE user_id = ?"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // UPDATE PROFILE
    public function updateProfile($userId, $username, $email, $contactNumber)
    {
        // Check if email is taken by another user
        $check = $this->db->prepare(
            "SELECT user_id FROM users WHERE email = ? AND user_id != ?"
        );
        $check->bind_param("si", $email, $userId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            return ['success' => false, 'message' => 'Email is already in use.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET username = ?, email = ?, contact_number = ? WHERE user_id = ?"
        );
        $stmt->bind_param("sssi", $username, $email, $contactNumber, $userId);
        $result = $stmt->execute();

        if (!$result) {
            error_log("Update profile error: " . $stmt->error);
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }

        return ['success' => true];
    }

    public function getPasswordById($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT password FROM users WHERE user_id = ?"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['password'] ?? null;
    }

    // GET USER BY EMAIL
    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function changePassword($userId, $newHashedPassword)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password = ? WHERE user_id = ?"
        );
        $stmt->bind_param("si", $newHashedPassword, $userId);
        $result = $stmt->execute();

        if (!$result) {
            error_log("Change password error: " . $stmt->error);
            return false;
        }

        return true;
    }

    // CREATE PASSWORD RESET TOKEN
    public function createPasswordResetToken($userId, $token, $expiresAt)
    {
        // First, invalidate any existing tokens for this user
        $this->invalidatePasswordResetTokens($userId);
        
        error_log("[User] Creating password reset token for user ID: " . $userId);
        error_log("[User] Token being stored: " . $token);
        error_log("[User] Token length: " . strlen($token));
        error_log("[User] Expires at: " . $expiresAt);
        
        $stmt = $this->db->prepare(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $userId, $token, $expiresAt);
        
        $result = $stmt->execute();
        
        error_log("[User] Token insertion result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        return $result;
    }

    // INVALIDATE EXISTING TOKENS
    public function invalidatePasswordResetTokens($userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0"
        );
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // GET VALID RESET TOKEN
    public function getValidResetToken($token)
    {
        error_log("[User] Looking for token: " . $token);
        error_log("[User] Token length: " . strlen($token));
        
        $stmt = $this->db->prepare(
            "SELECT pr.*, u.email, u.username 
             FROM password_resets pr 
             JOIN users u ON pr.user_id = u.user_id 
             WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > ?"
        );
        $currentTime = date('Y-m-d H:i:s');
        $stmt->bind_param("ss", $token, $currentTime);
        $result = $stmt->execute();
        
        error_log("[User] Query executed: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("[User] Current time: " . $currentTime);
        error_log("[User] Affected rows: " . $stmt->affected_rows);
        
        $data = $stmt->get_result()->fetch_assoc();
        
        if ($data) {
            error_log("[User] Token found - ID: " . $data['id'] . ", User: " . $data['username']);
            error_log("[User] Token expires at: " . $data['expires_at']);
        } else {
            error_log("[User] Token NOT FOUND in database");
        }
        
        return $data;
    }

    // RESET PASSWORD
    public function resetPassword($userId, $newPassword)
    {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $this->db->prepare(
            "UPDATE users SET password = ? WHERE user_id = ?"
        );
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Mark all tokens as used
            $this->invalidatePasswordResetTokens($userId);
        }
        
        return $result;
    }

    // MARK TOKEN AS USED
    public function markTokenAsUsed($token)
    {
        $stmt = $this->db->prepare(
            "UPDATE password_resets SET used = 1 WHERE token = ?"
        );
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }

    // GET ALL CUSTOMERS WITH RESERVATION COUNT
    public function getAllCustomers($branchId = null, $search = '', $filterReservations = '')
    {
        $query = "
            SELECT 
                u.user_id as id,
                u.username as name,
                u.email,
                u.contact_number as contact,
                COUNT(DISTINCT r.reservation_id) as reservation_count
            FROM users u
            LEFT JOIN reservations r ON u.user_id = r.user_id
            WHERE u.role = 'customer'
        ";

        $params = [];
        $types = '';

        // Add branch filter if provided
        if ($branchId !== null) {
            $query .= " AND r.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.contact_number LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        $query .= " GROUP BY u.user_id, u.username, u.email, u.contact_number";

        // Add reservation count filter
        if (!empty($filterReservations)) {
            if ($filterReservations === '1-5') {
                $query .= " HAVING reservation_count BETWEEN 1 AND 5";
            } elseif ($filterReservations === '6-10') {
                $query .= " HAVING reservation_count BETWEEN 6 AND 10";
            } elseif ($filterReservations === '11-20') {
                $query .= " HAVING reservation_count BETWEEN 11 AND 20";
            } elseif ($filterReservations === '20+') {
                $query .= " HAVING reservation_count > 20";
            }
        }

        $query .= " ORDER BY reservation_count DESC, u.username ASC";

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        return $customers;
    }

    // GET CUSTOMER RESERVATIONS BY USER ID
    public function getCustomerReservationsByUserId($userId, $branchId = null)
    {
        $query = "
            SELECT 
                r.reservation_id,
                r.reservation_date,
                r.status,
                r.total_price,
                b.branch_name,
                b.branch_id
            FROM reservations r
            LEFT JOIN branch b ON r.branch_id = b.branch_id
            WHERE r.user_id = ?
        ";

        $params = [$userId];
        $types = 'i';

        if ($branchId !== null) {
            $query .= " AND r.branch_id = ?";
            $params[] = $branchId;
            $types .= 'i';
        }

        $query .= " ORDER BY r.reservation_date DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            // Get services for each reservation
            $servicesQuery = "
                SELECT
                    rs.reservation_service_id,
                    rs.booked_service_name as service_name,
                    rs.booked_unit_price as price,
                    rs.booked_duration_minutes as duration_minutes,
                    rs.booked_category_name as category_name
                FROM reservation_services rs
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
            
            // Get schedule information (from first service)
            $scheduleQuery = "
                SELECT 
                    rsch.schedule_date,
                    rsch.start_time,
                    rsch.end_time
                FROM reservation_schedule rsch
                WHERE rsch.reservation_service_id = (
                    SELECT rs2.reservation_service_id 
                    FROM reservation_services rs2 
                    WHERE rs2.reservation_id = ? 
                    LIMIT 1
                )
            ";
            
            $scheduleStmt = $this->db->prepare($scheduleQuery);
            $scheduleStmt->bind_param('i', $row['reservation_id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            $schedule = null;
            if ($scheduleRow = $scheduleResult->fetch_assoc()) {
                $schedule = $scheduleRow;
            }
            
            // Combine services into service names string
            $serviceNames = implode(', ', array_map(function($s) {
                return $s['service_name'];
            }, $services));
            
            // Calculate total duration
            $totalDuration = array_sum(array_map(function($s) {
                return intval($s['duration_minutes']);
            }, $services));
            
            // Add services and schedule to the reservation
            $row['services'] = $services;
            $row['service_name'] = $serviceNames ?: 'N/A';
            $row['duration_minutes'] = $totalDuration ?: 0;
            $row['schedule_date'] = $schedule ? $schedule['schedule_date'] : null;
            $row['start_time'] = $schedule ? $schedule['start_time'] : null;
            $row['end_time'] = $schedule ? $schedule['end_time'] : null;
            
            $reservations[] = $row;
        }

        return $reservations;
    }

    // GET TOTAL CUSTOMER COUNT
    public function getTotalCustomerCount($branchId = null)
    {
        $query = "
            SELECT COUNT(DISTINCT u.user_id) as total
            FROM users u
            LEFT JOIN reservations r ON u.user_id = r.user_id
            WHERE u.role = 'customer'
        ";

        $params = [];
        $types = '';

        if ($branchId !== null) {
            $query .= " AND r.branch_id = ?";
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

    // CREATE EMAIL VERIFICATION OTP
    public function createEmailOTP($email, $otpCode, $expiresAt)
    {
        // First, invalidate any existing OTPs for this email
        $this->invalidateEmailOTPs($email);
        
        error_log("[User] Creating email OTP for: " . $email);
        error_log("[User] OTP: " . $otpCode);
        error_log("[User] Expires at: " . $expiresAt);
        
        $stmt = $this->db->prepare(
            "INSERT INTO email_verification_otp (email, otp_code, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $email, $otpCode, $expiresAt);
        
        $result = $stmt->execute();
        
        error_log("[User] OTP creation result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        return $result;
    }

    // INVALIDATE EXISTING EMAIL OTPS
    public function invalidateEmailOTPs($email)
    {
        $stmt = $this->db->prepare(
            "UPDATE email_verification_otp SET is_used = 1 WHERE email = ? AND is_used = 0"
        );
        $stmt->bind_param("s", $email);
        $result = $stmt->execute();
        
        error_log("[User] Invalidated existing OTPs for: " . $email . " - Result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        return $result;
    }

    // VERIFY EMAIL OTP
    public function verifyEmailOTP($email, $otpCode)
    {
        error_log("[User] Verifying OTP for email: " . $email);
        error_log("[User] OTP provided: " . $otpCode);
        
        $stmt = $this->db->prepare(
            "SELECT * FROM email_verification_otp 
             WHERE email = ? AND otp_code = ? AND is_used = 0 AND expires_at > ?"
        );
        $currentTime = date('Y-m-d H:i:s');
        $stmt->bind_param("sss", $email, $otpCode, $currentTime);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            error_log("[User] OTP found and valid");
            // Mark OTP as used
            $this->markOTPAsUsed($result['id']);
            return true;
        } else {
            error_log("[User] Invalid or expired OTP");
            return false;
        }
    }

    // MARK OTP AS USED
    public function markOTPAsUsed($otpId)
    {
        $stmt = $this->db->prepare(
            "UPDATE email_verification_otp SET is_used = 1 WHERE id = ?"
        );
        $stmt->bind_param("i", $otpId);
        return $stmt->execute();
    }

    // CHECK IF EMAIL IS ALREADY REGISTERED
    public function isEmailRegistered($email)
    {
        $user = $this->getUserByEmail($email);
        return !empty($user);
    }
}
