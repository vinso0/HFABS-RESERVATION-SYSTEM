<?php
// // Simple webhook test endpoint
// // Save as: C:\xampp\htdocs\HFABS\backend\webhook_test.php

// $logFile = 'C:\xampp\htdocs\HFABS\backend\logs\webhook_test.log';

// $logData = [
//     'timestamp' => date('Y-m-d H:i:s'),
//     'method' => $_SERVER['REQUEST_METHOD'],
//     'headers' => getallheaders(),
//     'raw_input' => file_get_contents('php://input'),
//     'get_data' => $_GET,
//     'post_data' => $_POST
// ];

// file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// http_response_code(200);
// echo json_encode(['status' => 'received', 'timestamp' => date('Y-m-d H:i:s')]);
?>
