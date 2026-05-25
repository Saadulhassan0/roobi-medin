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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    if ($action === 'process_checkout') {
        $shipping_id = $data['shipping_address_id'] ?? null;
        $payment_method = $data['payment_method'] ?? 'COD'; // COD, Card
        
        if (!$shipping_id) {
            echo json_encode(["success" => false, "message" => "Please select a shipping address"]);
            exit;
        }

        try {
            $conn->beginTransaction();

            // 1. Fetch Cart Items
            $stmtCart = $conn->prepare("
                SELECT c.id as cart_id, c.medicine_id, c.quantity, m.name as medicine_name, m.price,
                COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as available_stock
                FROM customer_cart c
                JOIN medicines m ON c.medicine_id = m.id
                WHERE c.user_id = ? FOR UPDATE
            ");
            $stmtCart->execute([$user_id]);
            $cart = $stmtCart->fetchAll(PDO::FETCH_ASSOC);

            if (empty($cart)) {
                $conn->rollBack();
                echo json_encode(["success" => false, "message" => "Cart is empty"]);
                exit;
            }

            // 2. Calculate Totals & Check Stock
            $subtotal = 0;
            foreach ($cart as $item) {
                if ($item['quantity'] > $item['available_stock']) {
                    $conn->rollBack();
                    echo json_encode(["success" => false, "message" => "Not enough stock for " . htmlspecialchars($item['medicine_name'])]);
                    exit;
                }
                $subtotal += ($item['quantity'] * $item['price']);
            }
            
            $delivery = 5.00;
            $tax = $subtotal * 0.05;
            $total_amount = $subtotal + $delivery + $tax;

            // 3. Create Order
            $orderStatus = 'Pending';
            $stmtOrder = $conn->prepare("INSERT INTO customer_orders (user_id, total_amount, status, shipping_address_id, billing_address_id) VALUES (?, ?, ?, ?, ?)");
            $stmtOrder->execute([$user_id, $total_amount, $orderStatus, $shipping_id, $shipping_id]);
            $order_id = $conn->lastInsertId();

            // 4. Create Payment Record
            $paymentStatus = ($payment_method === 'Card') ? 'Success' : 'Pending';
            $txRef = 'TXN-' . strtoupper(uniqid());
            $stmtPayment = $conn->prepare("INSERT INTO customer_payments (order_id, method, status, transaction_reference, amount) VALUES (?, ?, ?, ?, ?)");
            $stmtPayment->execute([$order_id, $payment_method, $paymentStatus, $txRef, $total_amount]);

            // 5. Create Order Items (Snapshots) and Deduct Stock if Payment is Success (FIFO)
            $stmtInsertItem = $conn->prepare("INSERT INTO customer_order_items (order_id, medicine_id, medicine_name_snapshot, price_snapshot, batch_id_used, quantity) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($cart as $item) {
                $med_id = $item['medicine_id'];
                $qty_needed = $item['quantity'];

                // If Payment is Success (Card), deduct stock immediately using FIFO
                if ($paymentStatus === 'Success') {
                    $stmtBatches = $conn->prepare("SELECT id, quantity FROM medicine_batches WHERE medicine_id = ? AND status = 'ACTIVE' AND quantity > 0 ORDER BY expiry_date ASC FOR UPDATE");
                    $stmtBatches->execute([$med_id]);
                    $batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);

                    $qty_remaining = $qty_needed;
                    
                    foreach ($batches as $batch) {
                        if ($qty_remaining <= 0) break;
                        
                        $qty_to_take = min($qty_remaining, $batch['quantity']);
                        
                        // Insert snapshot tracking the exact batch used for this portion
                        $stmtInsertItem->execute([$order_id, $med_id, $item['medicine_name'], $item['price'], $batch['id'], $qty_to_take]);
                        
                        // Deduct from batch
                        if ($qty_to_take == $batch['quantity']) {
                            $upd = $conn->prepare("UPDATE medicine_batches SET quantity = 0 WHERE id = ?");
                            $upd->execute([$batch['id']]);
                        } else {
                            $upd = $conn->prepare("UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?");
                            $upd->execute([$qty_to_take, $batch['id']]);
                        }
                        
                        $qty_remaining -= $qty_to_take;
                    }

                    if ($qty_remaining > 0) {
                        $conn->rollBack();
                        echo json_encode(["success" => false, "message" => "Critical error: Stock changed during transaction for " . htmlspecialchars($item['medicine_name'])]);
                        exit;
                    }
                    
                    // Synchronize the master `medicines` table so Admin dashboard is instantly updated
                    $updMed = $conn->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
                    $updMed->execute([$qty_needed, $med_id]);
                } else {
                    // COD - No deduction yet, just log the item without a specific batch_id_used for now
                    $stmtInsertItem->execute([$order_id, $med_id, $item['medicine_name'], $item['price'], null, $qty_needed]);
                }
            }

            // 6. Clear Cart
            $stmtClearCart = $conn->prepare("DELETE FROM customer_cart WHERE user_id = ?");
            $stmtClearCart->execute([$user_id]);

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Order placed successfully!", "order_id" => $order_id]);

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
    }
}
