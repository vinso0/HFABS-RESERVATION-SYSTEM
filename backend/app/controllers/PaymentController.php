<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

class PaymentController extends Controller {
    public function create() {
        if (empty(PAYMONGO_SECRET)) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['error' => 'Server Configuration Error: API Key missing.']);
        return;
    }
    // get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log to main error log
    error_log("Payment Request: " . json_encode($input));
    
    // Validate input
    if (!$input || !isset($input['amount']) || !isset($input['reservation_id'])) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['error' => 'Invalid request: amount and reservation_id are required']);
        return;
    }
    
    // convert amount to int and validate
    $amount = floatval($input['amount']);
    if ($amount < 1.00 || $amount > 999999.99) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['error' => 'Amount must be between 1.00 and 999,999.99 PHP']);
        return;
    }
    
    $amount_in_cents = (int)(round($amount * 100)); 
    $reservationId = (string)$input['reservation_id'];
    
    // Get metadata from input or use defaults
    $metadata = $input['metadata'] ?? [
        'reservation_id' => $reservationId
    ];

    // payload
    $payload = json_encode([
        'data' => [
            'attributes' => [
                'payment_method_types' => ['gcash', 'paymaya'],
                'line_items' => [
                    [
                        'amount'      => $amount_in_cents,
                        'currency'    => 'PHP',
                        'description' => "Order #$reservationId",
                        'name'        => "HFABS Product",
                        'quantity'    => 1
                    ]
                ],
                'description' => "HFABS Order #$reservationId",
                'success_url' => "http://undappled-bea-schemeful.ngrok-free.dev/HFABS/frontend/views/success.html",
                'metadata'    => $metadata
            ]
        ]
    ]);

    // api request
    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET . ':')
    ]);

    $raw_response = curl_exec($ch);
    $response = json_decode($raw_response, true);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // response for debugging
    if ($http_code === 200 && isset($response['data']['attributes']['checkout_url'])) {
        echo json_encode(['checkout_url' => $response['data']['attributes']['checkout_url']]);
    } else {
        // error details in console of browser
        header('Content-Type: application/json', true, 400);
        echo json_encode([
            'error' => $response['errors'][0]['detail'] ?? 'Unknown PayMongo Error',
            'raw' => $response
        ]);
    }
}
}