<?php

class User extends Database
{
    // REGISTER FUNCTION
    public function register($username, $email, $password, $contact_number)
    {
        // Password is already hashed in the controller, don't hash again!
        
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
}
