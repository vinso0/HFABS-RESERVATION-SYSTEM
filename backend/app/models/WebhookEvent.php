<?php

class WebhookEvent extends Database
{
    public function logEvent($eventType, $payload)
    {
        $query = "INSERT INTO webhook_events (event_type, payload, processed, created_at) VALUES (?, ?, ?, NOW())";
        
        $payloadJson = json_encode($payload);
        $processed = 0;
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $eventType, $payloadJson, $processed);
        
        return $stmt->execute();
    }

    public function markAsProcessed($eventId)
    {
        $query = "UPDATE webhook_events SET processed = 1, processed_at = NOW() WHERE webhook_event_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $eventId);
        
        return $stmt->execute();
    }

    public function getUnprocessedEvents()
    {
        $query = "SELECT * FROM webhook_events WHERE processed = 0 ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }

    public function getEventById($eventId)
    {
        $query = "SELECT * FROM webhook_events WHERE webhook_event_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function getAllEvents($limit = 100, $offset = 0)
    {
        $query = "SELECT * FROM webhook_events ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }

    public function getEventsByType($eventType, $limit = 50)
    {
        $query = "SELECT * FROM webhook_events WHERE event_type = ? ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('si', $eventType, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }

    public function deleteOldEvents($daysOld = 30)
    {
        $query = "DELETE FROM webhook_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $daysOld);
        
        return $stmt->execute();
    }
}
