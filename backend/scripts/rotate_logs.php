<?php
/**
 * Simple log rotation script for HFABS
 * Run this script daily via cron job to manage log files
 */

$logDir = __DIR__ . '/logs';
$errorLog = __DIR__ . '/public/error.log';
$maxLogSize = 5 * 1024 * 1024; // 5MB
$maxFilesToKeep = 7; // Keep 7 days of debug logs

// Rotate error log if it's too large
if (file_exists($errorLog) && filesize($errorLog) > $maxLogSize) {
    $backupFile = $errorLog . '.' . date('Y-m-d_H-i-s');
    rename($errorLog, $backupFile);
    
    // Create new empty error log
    file_put_contents($errorLog, '');
    
    // Clean up old error log backups (keep last 3)
    $errorBackups = glob($errorLog . '.*');
    sort($errorBackups);
    while (count($errorBackups) > 3) {
        unlink(array_shift($errorBackups));
    }
}

// Clean up old webhook debug logs
$webhookLogs = glob($logDir . '/webhook_debug_*.log');
sort($webhookLogs);
while (count($webhookLogs) > $maxFilesToKeep) {
    unlink(array_shift($webhookLogs));
}

echo "Log rotation completed: " . date('Y-m-d H:i:s') . "\n";
?>
