<?php
/**
 * Web-based domain list updater
 * Access via: yourdomain.com/HFABS/backend/update-domains-web.php
 */
require_once __DIR__ . '/app/services/DomainBlacklistService.php';

try {
    $service = new DomainBlacklistService();
    $result = $service->autoUpdateIfNeeded();
    
    echo "<h2>Domain List Update Status</h2>";
    echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Status:</strong> " . $result['message'] . "</p>";
    
    if ($result['success'] && isset($result['blacklisted_count'])) {
        echo "<p><strong>Blacklisted domains:</strong> " . $result['blacklisted_count'] . "</p>";
        echo "<p><strong>Allowed domains:</strong> " . $result['allowed_count'] . "</p>";
    }
    
    echo "<br><a href='javascript:history.back()'>Go Back</a>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<br><a href='javascript:history.back()'>Go Back</a>";
}
?>