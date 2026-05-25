<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new \App\Core\Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'get_orders') {
        try {
            $stmt = $conn->prepare("
                SELECT id, total_amount, status, created_at 
                FROM customer_orders 
                WHERE user_id = ? 
                ORDER BY id DESC
            ");
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $orders]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_order_details') {
        $order_id = $_GET['order_id'];
        try {
            // Get Order Info
            $stmt = $conn->prepare("
                SELECT o.*, 
                a.full_name, a.full_address, a.city, a.postal_code, a.phone_number,
                p.method as payment_method, p.status as payment_status, p.transaction_reference
                FROM customer_orders o
                LEFT JOIN customer_addresses a ON o.shipping_address_id = a.id
                LEFT JOIN customer_payments p ON p.order_id = o.id
                WHERE o.id = ? AND o.user_id = ?
            ");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(["success" => false, "message" => "Order not found"]);
                exit;
            }

            // Get Items (Snapshots)
            $stmtItems = $conn->prepare("
                SELECT medicine_name_snapshot, price_snapshot, quantity
                FROM customer_order_items 
                WHERE order_id = ?
            ");
            $stmtItems->execute([$order_id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true, 
                "order" => $order,
                "items" => $items
            ]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
}
