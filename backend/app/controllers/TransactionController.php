<?php

class TransactionController extends Controller
{
    public function getByBranch()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Auth check — admin or cashier only
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'User not authorized']);
            exit;
        }

        $branch_id = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;

        if ($branch_id === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No branch assigned to this account.']);
            exit;
        }

        // Connect using the project's Database class (mysqli)
        $database = new Database();
        $db = $database->getConnection();

        $sql = "
            SELECT
                p.payment_id,
                p.paymongo_payment_id,
                p.reservation_id,
                p.amount_paid,
                p.payment_method,
                p.status,
                p.created_at,
                u.username          AS customer_name,
                u.email             AS customer_email,
                u.contact_number    AS customer_contact,
                r.reservation_date,
                r.total_price,
                r.status            AS reservation_status,
                b.branch_name
            FROM payments p
            INNER JOIN reservations r ON p.reservation_id = r.reservation_id
            INNER JOIN users u        ON r.user_id = u.user_id
            INNER JOIN branch b       ON r.branch_id = b.branch_id
            WHERE r.branch_id = ?
            ORDER BY p.created_at DESC
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $db->error]);
            exit;
        }

        $stmt->bind_param('i', $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        $stmt->close();

        echo json_encode([
            'status'  => 'success',
            'success' => true,
            'data'    => $transactions
        ]);

        exit;
    }
}
