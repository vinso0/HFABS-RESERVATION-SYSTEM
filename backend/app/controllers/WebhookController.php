<?php
class WebhookController extends Controller {
    public function handle() {
        // 1. Capture the raw input from PayMongo
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);

        // 2. Validate the payload exists and has the required event type
        if (!$payload || !isset($payload['data']['attributes']['type'])) {
            error_log("Webhook Error: Invalid payload.");
            http_response_code(400);
            return;
        }

        $event = $payload['data']['attributes']['type'];

        // 3. Process the 'paid' event
        if ($event === 'checkout_session.payment.paid') {
            // PayMongo structure: data -> attributes -> data (the resource)
            $resource = $payload['data']['attributes']['data'];
            
            // Extract the payment ID (e.g., pay_sample_123)
            $paymongo_payment_id = $resource['id']; 
            
            // Extract the inner attributes
            $paymentData = $resource['attributes'];
            
            // Extracting metadata - Ensure you passed 'order_id' when creating the session
            $order_id    = (int)($paymentData['metadata']['order_id'] ?? 0);
            $amount_paid = $paymentData['amount'] / 100; // Convert centavos to PHP
            
            // Mapping the source type to your DB ENUM
            $raw_method = $paymentData['source']['type'] ?? 'unknown';
            
            // Clean mapping
            $method_map = [
                'paymaya' => 'maya',
                'gcash'   => 'gcash',
                'qrph'    => 'qrph'
            ];
            $method = $method_map[$raw_method] ?? 'maya'; // Default to maya if unknown

            try {
                $db = (new Database())->getConnection();
                
                // Idempotency Check: Prevent duplicate processing of the same payment
                // Using 'payment_id' as that matches your earlier error logs
                $check = $db->prepare("SELECT payment_id FROM payments WHERE payment_id = ?");
                $check->bind_param("s", $paymongo_payment_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    error_log("Webhook: Payment $paymongo_payment_id already processed.");
                    http_response_code(200);
                    echo json_encode(["status" => "already_processed"]);
                    return;
                }

                $db->begin_transaction();

                // 4. Insert Payment 
                // Using column name 'payment_id' based on your initial SQL error trace
                $sql = "INSERT INTO payments (payment_id, order_id, amount_paid, payment_method, status) VALUES (?, ?, ?, ?, 'paid')";
                $stmt = $db->prepare($sql);
                
                // sids = string (pay_id), int (order_id), double (amount), string (method)
                $stmt->bind_param("sids", $paymongo_payment_id, $order_id, $amount_paid, $method);
                $stmt->execute();

                // 5. Update Order Status
                $updateOrder = $db->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?");
                $updateOrder->bind_param("i", $order_id);
                $updateOrder->execute();

                $db->commit();
                error_log("Webhook Success: Order $order_id set to paid via $method");

            } catch (mysqli_sql_exception $e) {
                if (isset($db)) $db->rollback();
                error_log("Webhook DB Error: " . $e->getMessage());
                
                // Even if DB fails, we usually send 200/400 to stop PayMongo from retrying a broken record
                http_response_code(400); 
                echo json_encode(["error" => "Database failure"]);
                exit;
            }
        }

        // Always acknowledge receipt to PayMongo
        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit;
    }
}