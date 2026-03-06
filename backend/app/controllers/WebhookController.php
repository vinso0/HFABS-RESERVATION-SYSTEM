<?php
class WebhookController extends Controller {
    private $paymentModel;
    private $webhookEventModel;

    public function __construct() {
        $this->paymentModel = $this->model('Payment');
        $this->webhookEventModel = $this->model('WebhookEvent');
    }

    public function handle() {
        $rawPayload = file_get_contents('php://input');
        $payload = json_decode(file_get_contents('php://input'), true);
        
        // DEBUG: Log to dedicated webhook file
        $logFile = 'C:\xampp\htdocs\HFABS\backend\logs\webhook_debug.log';
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'raw_payload' => $rawPayload,
            'decoded_payload' => $payload,
            'headers' => getallheaders(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ];
        
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        // Also log to error log for immediate visibility
        error_log("=== WEBHOOK DEBUG ===");
        error_log("Raw Payload: " . $rawPayload);
        error_log("Decoded Payload: " . print_r($payload, true));
        error_log("Headers: " . print_r(getallheaders(), true));
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("==================");
        
        if (!$payload) {
            error_log("Webhook Error: Invalid JSON payload");
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        }

        $event = $payload['data']['attributes']['type'] ?? null;
        
        if (!$event) {
            error_log("Webhook Error: No event type found in payload");
            http_response_code(400);
            echo json_encode(['error' => 'No event type found']);
            return;
        }

        error_log("Webhook Event Type: " . $event);

        // Log the webhook event
        $this->webhookEventModel->logEvent($event, $payload);

        switch ($event) {
            case 'checkout_session.payment.paid':
                $this->handlePaymentPaid($payload);
                break;
            case 'payment.failed':
                $this->handlePaymentFailed($payload);
                break;
            default:
                error_log("Unhandled webhook event: " . $event);
                break;
        }

        http_response_code(200);
        echo json_encode(['status' => 'success']);
    }

private function handlePaymentPaid($payload) {
    // Navigate the correct JSON structure for PayMongo checkout session
    $checkoutSession = $payload['data']['attributes']['data']['attributes'];
    $payment = $checkoutSession['payments'][0]['attributes'];
    $paymongoPaymentId = $checkoutSession['payments'][0]['id'] ?? null;
    
    // Get metadata from payment level (falls back to checkout session level)
    $metadata = $payment['metadata'] ?? $checkoutSession['metadata'] ?? [];
    
    $reservationId = $metadata['reservation_id'] ?? null;
    $userId = $metadata['user_id'] ?? null;
    $branchId = $metadata['branch_id'] ?? null;
    $totalPrice = $metadata['total_price'] ?? null;
    
    // ADJUSTMENT: PayMongo sends centavos, divide by 100
    $amountPaid = $payment['amount'] / 100;
    $paymentMethod = $checkoutSession['payment_method_used'] ?? 'paymongo';

    error_log("Webhook Debug - Extracted Data:");
    error_log("Reservation ID: " . $reservationId);
    error_log("User ID: " . $userId);
    error_log("Branch ID: " . $branchId);
    error_log("Total Price: " . $totalPrice);
    error_log("Amount Paid: " . $amountPaid);
    error_log("Payment Method: " . $paymentMethod);
    error_log("PayMongo Payment ID: " . $paymongoPaymentId);

    if (!$reservationId) {
        error_log("Webhook Error: reservation_id missing in metadata.");
        return;
    }

    // Initialize reservation model
    $reservationModel = $this->model('Reservation');
    
    // Check if reservation exists, if not create it
    $existingReservation = $reservationModel->getReservationById($reservationId);
    if (!$existingReservation && $userId && $branchId && $totalPrice) {
        // Create the reservation first
        $newReservationId = $reservationModel->insertReservation(
            $userId, 
            $branchId, 
            $totalPrice, 
            'pending'
        );
        
        if ($newReservationId) {
            $reservationId = $newReservationId;
            error_log("Created new reservation ID: $reservationId from webhook");
        } else {
            error_log("Failed to create reservation from webhook for user_id: $userId");
            return;
        }
    } elseif (!$existingReservation) {
        error_log("Reservation ID $reservationId not found and insufficient metadata to create new reservation");
        error_log("Missing - User ID: " . ($userId ? 'YES' : 'NO') . ", Branch ID: " . ($branchId ? 'YES' : 'NO') . ", Total Price: " . ($totalPrice ? 'YES' : 'NO'));
        return;
    }

    // Create payment record using the existing createPayment function
    $paymentId = $this->paymentModel->createPayment(
        $reservationId, 
        $amountPaid, 
        $paymentMethod, 
        'paid',
        $paymongoPaymentId
    );

    if ($paymentId) {
        // Confirm the reservation
        $reservationModel->confirmReservation($reservationId);
        
        error_log("Payment $paymentId created and Reservation $reservationId confirmed.");
    } else {
        error_log("Failed to create payment record for Reservation ID: " . $reservationId);
    }
}

    private function handlePaymentFailed($payload) {
        $paymentData = $payload['data']['attributes']['data']['attributes'];
        
        $paymongoPaymentId = $payload['data']['attributes']['data']['id'];
        $reservationId = $paymentData['metadata']['reservation_id'] ?? null;

        if (!$reservationId) {
            error_log("Webhook received but no reservation_id found in metadata");
            return;
        }

        // Update payment status to failed
        $updated = $this->paymentModel->updatePaymentByReservationId(
            $reservationId,
            $paymongoPaymentId,
            null,
            null,
            'unpaid'
        );

        if (!$updated) {
            error_log("Failed to update payment status for reservation ID: " . $reservationId);
        }
    }
}