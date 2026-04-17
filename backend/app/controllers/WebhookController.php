<?php
/**
 * Webhook Controller for PayMongo Integration
 * 
 * LOGGING STRATEGY:
 * - Error log: Only important events (webhook events, payments, errors)
 * - Debug logs: Full payload saved to daily rotated files (webhook_debug_YYYY-MM-DD.log)
 * - Database: All webhook events logged to webhook_events table
 * 
 * This keeps the main error.log clean while preserving detailed debug info when needed
 */
require_once __DIR__ . '/../services/NotificationService.php';

class WebhookController extends Controller {
    private $paymentModel;
    private $webhookEventModel;
    private $notificationService;
    private $db;

    public function __construct() {
        $this->paymentModel = $this->model('Payment');
        $this->webhookEventModel = $this->model('WebhookEvent');
        $this->notificationService = new NotificationService();
        $this->db = (new Database())->getConnection();
    }

    public function handle() {
        $rawPayload = file_get_contents('php://input');
        $payload = json_decode(file_get_contents('php://input'), true);
        
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

        // Only log important webhook events to error log
        error_log("Webhook Event: " . $event);
        
        // Log webhook event to database
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

    // Enhanced logging for debugging
    error_log("WEBHOOK PAYMENT DEBUG - Reservation: $reservationId, User: $userId, Amount: $amountPaid, Method: $paymentMethod, PayMongo ID: $paymongoPaymentId");
    error_log("WEBHOOK METADATA DEBUG: " . json_encode($metadata));

    if (!$reservationId) {
        error_log("Webhook Error: reservation_id missing in metadata for payment: $paymongoPaymentId");
        return;
    }

    // Initialize reservation model
    $reservationModel = $this->model('Reservation');
    
    // Check if reservation exists, if not create it
    $existingReservation = $reservationModel->getReservationById($reservationId);
    if (!$existingReservation && $userId && $branchId && $totalPrice) {
        error_log("WEBHOOK: Creating missing reservation $reservationId for user $userId");
        
        // Create the reservation first
        $scheduleDate = $metadata['schedule_date'] ?? null;
        $newReservationId = $reservationModel->insertReservation(
            $userId, 
            $branchId, 
            $totalPrice, 
            'pending',
            $scheduleDate
        );
        
        if ($newReservationId) {
            $reservationId = $newReservationId;
            error_log("WEBHOOK: Successfully created reservation ID: $reservationId from webhook payment");
        } else {
            error_log("WEBHOOK ERROR: Failed to create reservation from webhook for user_id: $userId");
            return;
        }
    } elseif (!$existingReservation) {
        error_log("WEBHOOK ERROR: Reservation $reservationId not found and insufficient metadata to create. Available metadata: " . json_encode($metadata));
        return;
    }

    // Create payment record
    $services = $metadata['services'] ?? [];
    
    // Handle services data - PayMongo might send it as a string instead of array
    if (is_string($services)) {
        $services = json_decode($services, true) ?? [];
    }
    
    $scheduleData = [];
    
    // Extract schedule data from metadata if available
    if (isset($metadata['schedule_date']) && isset($metadata['start_time']) && isset($metadata['end_time'])) {
        $scheduleData = [
            'schedule_date' => $metadata['schedule_date'],
            'start_time' => $metadata['start_time'],
            'end_time' => $metadata['end_time'],
            'is_rescheduled' => 0,
            'previous_schedule_id' => null,
            'reschedule_reason' => null
        ];
        error_log("WEBHOOK: Schedule data prepared: " . json_encode($scheduleData));
    }
    
    error_log("WEBHOOK: Attempting to create payment record...");
    
    $paymentId = $this->paymentModel->createPayment(
        $reservationId, 
        $amountPaid, 
        $paymentMethod, 
        'paid',
        $paymongoPaymentId,
        $services,
        $scheduleData
    );

    if ($paymentId) {
        // Confirm the reservation
        $reservationModel->confirmReservation($reservationId);
        error_log("WEBHOOK SUCCESS: Payment processed successfully - Reservation ID: $reservationId, Payment ID: $paymentId, Amount: $amountPaid");
        
        // Get reservation details for admin notification
        $reservationDetails = $reservationModel->getReservationById($reservationId);
        if ($reservationDetails) {
            // Count services for this reservation
            $servicesCountQuery = "SELECT COUNT(*) as services_count FROM reservation_services WHERE reservation_id = ?";
            $stmt = $this->db->prepare($servicesCountQuery);
            $stmt->bind_param('i', $reservationId);
            $stmt->execute();
            $servicesResult = $stmt->get_result();
            $servicesRow = $servicesResult->fetch_assoc();
            
            $reservationData = [
                'reservation_id' => $reservationId,
                'customer_name' => $reservationDetails['customer_name'],
                'customer_email' => $reservationDetails['customer_email'],
                'branch_name' => $reservationDetails['branch_name'],
                'branch_id' => $reservationDetails['branch_id'],
                'reservation_date' => $reservationDetails['reservation_date'],
                'total_price' => $reservationDetails['total_price'],
                'services_count' => $servicesRow['services_count'] ?? 0
            ];
            
            // Send admin notifications
            $this->notificationService->notifyAdminsNewReservation($reservationData);
        }
    } else {
        error_log("WEBHOOK ERROR: Failed to create payment for Reservation ID: $reservationId. Check database logs for details.");
    }
}

    private function handlePaymentFailed($payload) {
        $paymentData = $payload['data']['attributes']['data']['attributes'];
        
        $paymongoPaymentId = $payload['data']['attributes']['data']['id'];
        $reservationId = $paymentData['metadata']['reservation_id'] ?? null;

        if (!$reservationId) {
            error_log("Webhook Error: Payment failed but no reservation_id found in metadata");
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
            error_log("Webhook Error: Failed to update payment status for reservation ID: $reservationId");
        } else {
            error_log("Payment failed - Reservation ID: $reservationId, Payment ID: $paymongoPaymentId");
        }
    }
}