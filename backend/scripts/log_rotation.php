<?php
/**
 * Simple Log Rotation Script
 * Run this script periodically (e.g., daily via cron) to rotate error.log
 */

$logFile = 'C:\xampp\htdocs\HFABS\backend\public\error.log';
$maxSize = 5 * 1024 * 1024; // 5MB
$backupCount = 3; // Keep 3 backup files

if (file_exists($logFile) && filesize($logFile) > $maxSize) {
    // Rotate existing backups
    for ($i = $backupCount; $i > 1; $i--) {
        $oldBackup = $logFile . '.' . ($i - 1);
        $newBackup = $logFile . '.' . $i;
        if (file_exists($oldBackup)) {
            rename($oldBackup, $newBackup);
        }
    }
    
    // Move current log to backup
    rename($logFile, $logFile . '.1');
    
    // Create new empty log file
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Log rotated - New log file created\n");
    
    // Delete oldest backup if it exists
    $oldestBackup = $logFile . '.' . ($backupCount + 1);
    if (file_exists($oldestBackup)) {
        unlink($oldestBackup);
    }
    
    echo "Log rotated successfully. Current size: " . number_format($maxSize / 1024 / 1024, 2) . "MB";
} else {
    echo "Log rotation not needed. Current size: " . number_format(filesize($logFile) / 1024 / 1024, 2) . "MB";
}
?>
