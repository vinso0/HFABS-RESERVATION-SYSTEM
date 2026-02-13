<?php
class WebhookController extends Controller {
    public function handle() {
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);

        if (!$payload || !isset($payload['data']['attributes']['type'])) {
            error_log("Webhook Error: Invalid payload.");
            return;
        }

        $event = $payload['data']['attributes']['type'];

        if ($event === 'checkout_session.payment.paid') {
            $resource = $payload['data']['attributes']['data'];
            $paymongo_payment_id = $resource['id']; 
            $paymentData = $resource['attributes'];
            
            $order_id    = (int)($paymentData['metadata']['order_id'] ?? 0);
            $amount_paid = $paymentData['amount'] / 100;
            
            // --- UPDATED PAYMENT METHOD MAPPING ---
            $raw_method = $paymentData['source']['type'] ?? 'unknown';
            
            // Mapping payment method
            if ($raw_method === 'paymaya' ) {
                $method = 'maya';
            } elseif ($raw_method === 'gcash') {
                $method = 'gcash';
            } elseif ($raw_method === 'qrph') {
                $method = 'qrph';
            } else {
                $method = 'maya'; // Default fallback or handle as 'unknown'
            }

            try {
                $db = (new Database())->getConnection();
                
                // Idempotency Check
                $check = $db->prepare("SELECT payment_id FROM payments WHERE paymongo_payment_id = ?");
                $check->bind_param("s", $paymongo_payment_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    http_response_code(200);
                    return;
                }

                $db->begin_transaction();

                // Insert Payment
                $sql = "INSERT INTO payments (paymongo_payment_id, order_id, amount_paid, payment_method, status) VALUES (?, ?, ?, ?, 'paid')";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sids", $paymongo_payment_id, $order_id, $amount_paid, $method);
                $stmt->execute();

                // Update Order Status
                $updateOrder = $db->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?");
                $updateOrder->bind_param("i", $order_id);
                $updateOrder->execute();

                $db->commit();
                error_log("Webhook Success: QRPh/Payment processed for Order: " . $order_id);

            } catch (mysqli_sql_exception $e) {
                if (isset($db)) $db->rollback();
                error_log("Webhook DB Error: " . $e->getMessage());
                http_response_code(400); 
                exit;
            }
        }

        http_response_code(200);
        echo json_encode(["status" => "success"]);
        exit;
    }
}