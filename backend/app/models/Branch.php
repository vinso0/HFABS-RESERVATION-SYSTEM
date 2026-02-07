<?php

class Branch
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get all active branches
    public function getAllBranches()
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM branch WHERE status = "active" OR status = ""';
        $result = $conn->query($sql);
        
        $branches = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
        }
        
        return $branches;
    }

    // Get branch by ID
    public function getBranchById($id)
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT * FROM branch WHERE branch_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}

?>
