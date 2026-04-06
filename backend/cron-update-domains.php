<?php
/**
 * Cron job to update domain lists
 * Run this script daily via cron job: php cron-update-domains.php
 */

require_once __DIR__ . '/app/services/DomainBlacklistService.php';

try {
    $service = new DomainBlacklistService();
    $result = $service->autoUpdateIfNeeded();
    
    echo date('Y-m-d H:i:s') . " - Domain list update: " . $result['message'] . "\n";
    
    if ($result['success'] && isset($result['blacklisted_count'])) {
        echo "Blacklisted domains: " . $result['blacklisted_count'] . "\n";
        echo "Allowed domains: " . $result['allowed_count'] . "\n";
    }
    
} catch (Exception $e) {
    echo date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
}
?>