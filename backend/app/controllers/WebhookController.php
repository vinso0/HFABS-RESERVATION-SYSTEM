<?php
class WebhookController extends Controller {
    public function handle() {
        $payload = json_decode(file_get_contents('php://input'), true);
        $event = $payload['data']['attributes']['type'];

        if ($event === 'checkout_session.payment.paid') {
            $paymentData = $payload['data']['attributes']['data']['attributes'];
            
            // extract info for db; not yet working
            $payment_id = $payload['data']['attributes']['data']['id']; 
            $order_id = $paymentData['metadata']['order_id'];
            $amount_paid = $paymentData['amount'] / 100;
            $method = $paymentData['source']['type']; // gcash, maya, etc.

            // save to db; not yet working
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE payments SET payment_id = ?, amount_paid = ?, payment_method = ?, status = 'paid' WHERE order_id = ?");
            $stmt->bind_param("sdds", $payment_id, $amount_paid, $method, $order_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                http_response_code(200);
            } else {
                // log error
                error_log("Webhook received but no order found for ID: " . $order_id);
            }
            http_response_code(200); 
        }
    }
}