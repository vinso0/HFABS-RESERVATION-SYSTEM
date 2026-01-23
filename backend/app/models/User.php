<?php

class User extends Database
{
    // REGISTER FUNCTION
    public function register($username, $email, $password, $contact_number)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password, contact_number, role) VALUES (?, ?, ?, ?, 'customer')"
        );
        $stmt->bind_param("sssi", $username, $email, $password, $contact_number);

        return $stmt->execute();
    }

    // LOGIN FUNCTION
    public function login($identifier)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? or username = ?"
        );
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}