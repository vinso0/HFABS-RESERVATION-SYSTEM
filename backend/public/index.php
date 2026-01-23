<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../app/core/init.php';

$router = new Router();
$router->run();