<?php

class Database
{
    protected $db;

    public function __construct()
    {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->db->connect_error) {
            die("Database Connection Failed: " . $this->db->connect_error);
        }
    }
    public function getConnection()
    {
        return $this->db;
    }
}