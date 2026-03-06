<?php

class PaymentWebhookController extends Controller
{
    private $paymentModel;
    private $reservationModel;

    public function __construct()
    {
        $this->paymentModel = $this->model('Payment');
        $this->reservationModel = $this->model('Reservation');
    }

    public function handlePaymongoWebhook()
    {
        // MUST VERIFY FIRST
        if (!$this->verifyWebhookSignature()) {
            error_log("Invalid PayMongo Signature attempt.");
            http_response_code(401); // Unauthorized
            exit;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$payload || !isset($payload['data']['attributes']['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid webhook payload']);
            exit;
        }

        $event = $payload['data']['attributes']['type'];

        switch ($event) {
            case 'checkout_session.payment.paid':
                $this->handlePaymentSuccess($payload);
                break;
            
            case 'checkout_session.payment.failed':
                $this->handlePaymentFailed($payload);
                break;
            
            case 'checkout_session.expired':
                $this->handlePaymentExpired($payload);
                break;
            
            default:
                error_log("Unhandled webhook event: " . $event);
                http_response_code(200);
                break;
        }
    }

private function handlePaymentSuccess($payload)
{
    try {
        // Correctly traverse the PayMongo payload structure
        $paymentAttributes = $payload['data']['attributes']['data']['attributes'];
        $paymongoPaymentId = $payload['data']['attributes']['data']['id'];
        $reservationId = $paymentAttributes['metadata']['reservation_id'];
        $amountPaid = $paymentAttributes['amount'] / 100;
        
        // Ensure payment_method matches SQL ENUM ('gcash', 'maya')
        $rawMethod = $paymentAttributes['source']['type'];
        $paymentMethod = (strpos($rawMethod, 'gcash') !== false) ? 'gcash' : 'maya';

        $existingPayment = $this->paymentModel->getPaymentByReservationId($reservationId);

        if ($existingPayment) {
            $result = $this->paymentModel->updatePaymentByReservationId(
                $reservationId, $paymongoPaymentId, $amountPaid, $paymentMethod, 'paid'
            );
        } else {
            // Check if reservation exists first to satisfy Foreign Key: fk_payments_reservations
            $result = $this->paymentModel->createPayment($reservationId, $amountPaid, $paymentMethod, 'paid');
        }

        if ($result) {
            $this->reservationModel->updateReservationStatus($reservationId, 'confirmed');
            http_response_code(200);
            echo json_encode(['message' => 'Success']);
        }
    } catch (\Exception $e) {
        error_log("Webhook Error: " . $e->getMessage());
        http_response_code(500);
    }
}

private function handlePaymentFailed($payload)
{
    $paymentData = $payload['data']['attributes']['data']['attributes'];
    $reservationId = $paymentData['metadata']['reservation_id'];
    
    // Changing 'payment_failed' to 'cancelled' to match SQL ENUM
    $this->reservationModel->updateReservationStatus($reservationId, 'cancelled');
    http_response_code(200);
}
    private function handlePaymentExpired($payload)
    {
        try {
            $paymentData = $payload['data']['attributes']['data']['attributes'];
            $reservationId = $paymentData['metadata']['reservation_id'];

            $this->reservationModel->updateReservationStatus($reservationId, 'expired');

            http_response_code(200);
            echo json_encode(['message' => 'Payment expiration processed']);
        } catch (\Exception $e) {
            error_log("Payment expired webhook error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }


    public function verifyWebhookSignature()
{
    $header = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
    $payload = file_get_contents('php://input');

    if (empty($header)) {
        return false;
    }

    // 1. Split the header to get the timestamp (t) and the signature (li)
    // Paymongo signature format: t=<timestamp>,te=<test_mode>,li=<signature>
    parse_str(str_replace(',', '&', $header), $res);
    
    $timestamp = $res['t'] ?? '';
    $signatureFromHeader = $res['li'] ?? '';

    if (!$timestamp || !$signatureFromHeader) {
        return false;
    }

    // 2. Concatenate timestamp + payload (This is Paymongo's specific requirement)
    $signedPayload = $timestamp . "." . $payload;

    // 3. Hash it using your secret
    $expectedSignature = hash_hmac('sha256', $signedPayload, PAYMONGO_WEBHOOK_SECRET);
    
    // 4. Secure comparison
    return hash_equals($expectedSignature, $signatureFromHeader);
}
}
