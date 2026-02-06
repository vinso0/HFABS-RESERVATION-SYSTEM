<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found at: " . $path);
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$envPath = dirname(__DIR__, 2) . '/../.env'; 
loadEnv($envPath);

define('BASE_URL', 'http://localhost/HFABS/backend/public');

define('DB_HOST', 'localhost');
define('DB_NAME', 'hfabs');
define('DB_USER', 'root');
define('DB_PASS', '');
define('PAYMONGO_SECRET', $_ENV['PAYMONGO_SECRET_KEY'] ?? '');