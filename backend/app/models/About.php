<?php

class About extends Database
{
    private function conn()
    {
        return $this->getConnection();
    }

    // ── Fetch the single About row ────────────────────────────────────────────
    public function getAbout()
    {
        $result = $this->conn()->query("SELECT * FROM about_content LIMIT 1");
        return $result ? $result->fetch_assoc() : null;
    }

    // ── Fetch all active policies, ordered ────────────────────────────────────
    public function getPolicies()
    {
        $result = $this->conn()->query(
            "SELECT * FROM policies WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    // ── Fetch ALL policies including hidden (Superadmin) ──────────────────────
    public function getAllPolicies()
    {
        $result = $this->conn()->query(
            "SELECT * FROM policies ORDER BY sort_order ASC"
        );
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    // ── Save / Update About content ───────────────────────────────────────────
    public function saveAbout($title, $description, $vision, $mission)
    {
        $conn  = $this->conn();
        $check = $conn->query("SELECT id FROM about_content LIMIT 1")->fetch_assoc();

        if ($check) {
            $stmt = $conn->prepare(
                "UPDATE about_content SET title=?, description=?, vision=?, mission=? WHERE id=?"
            );
            $stmt->bind_param('ssssi', $title, $description, $vision, $mission, $check['id']);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO about_content (title, description, vision, mission) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $title, $description, $vision, $mission);
        }

        return $stmt->execute();
    }

    // ── Add a new policy ──────────────────────────────────────────────────────
    public function addPolicy($title, $content, $sort_order)
    {
        $conn = $this->conn();
        $stmt = $conn->prepare(
            "INSERT INTO policies (title, content, sort_order) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('ssi', $title, $content, $sort_order);
        $stmt->execute();
        return $conn->insert_id;
    }

    // ── Update an existing policy ─────────────────────────────────────────────
    public function updatePolicy($policy_id, $title, $content, $sort_order, $is_active)
    {
        $stmt = $this->conn()->prepare(
            "UPDATE policies SET title=?, content=?, sort_order=?, is_active=? WHERE policy_id=?"
        );
        $stmt->bind_param('ssiii', $title, $content, $sort_order, $is_active, $policy_id);
        return $stmt->execute();
    }

    // ── Delete a policy ───────────────────────────────────────────────────────
    public function deletePolicy($policy_id)
    {
        $stmt = $this->conn()->prepare("DELETE FROM policies WHERE policy_id = ?");
        $stmt->bind_param('i', $policy_id);
        return $stmt->execute();
    }
}