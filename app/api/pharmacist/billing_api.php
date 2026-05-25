<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacist') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$db = new \App\Core\Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'search') {
        $q = $_GET['q'] ?? '';
        try {
            // Auto-expire batches just in case
            $conn->exec("UPDATE medicine_batches SET status = 'EXPIRED' WHERE expiry_date < CURDATE() AND status = 'ACTIVE'");

            $stmt = $conn->prepare("
                SELECT m.id, m.name, m.category, m.price,
                COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as stock,
                (SELECT MIN(expiry_date) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE') as expiry_date,
                CASE 
                    WHEN COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) = 0 THEN 'out_of_stock'
                    ELSE 'valid'
                END as status
                FROM medicines m 
                WHERE m.name LIKE ? 
                LIMIT 10
            ");
            $stmt->execute(["%$q%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $results]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    if ($action === 'process_bill') {
        $cart = $data['cart'] ?? [];
        $discount_type = $data['discount_type'] ?? 'none';
        $discount_val = (float)($data['discount_val'] ?? 0);
        $bag_charge = (float)($data['bag_charge'] ?? 0);
        $pharmacist_id = $_SESSION['user_id'];
        
        if (empty($cart)) {
            echo json_encode(["success" => false, "message" => "Cart is empty"]);
            exit;
        }

        try {
            $conn->beginTransaction();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += ($item['qty'] * $item['price']);
            }
            
            $discount_amount = 0;
            if ($discount_type === 'percent') {
                $discount_amount = $subtotal * ($discount_val / 100);
            } elseif ($discount_type === 'fixed') {
                $discount_amount = $discount_val;
            }
            
            // Ensure discount isn't more than subtotal
            if ($discount_amount > $subtotal) $discount_amount = $subtotal;
            
            $tax = ($subtotal - $discount_amount) * 0.05; // 5% tax
            $grand_total = ($subtotal - $discount_amount) + $tax + $bag_charge;

            // Insert into bills table
            $insertBill = $conn->prepare("INSERT INTO bills (pharmacist_id, subtotal, discount_amount, bag_charge, tax, grand_total) VALUES (?, ?, ?, ?, ?, ?)");
            $insertBill->execute([$pharmacist_id, $subtotal, $discount_amount, $bag_charge, $tax, $grand_total]);
            $bill_id = $conn->lastInsertId();

            $insertSale = $conn->prepare("INSERT INTO sales (bill_id, user_id, medicine_id, quantity, total_price) VALUES (?, ?, ?, ?, ?)");
            $customer_id = null;

            foreach ($cart as $item) {
                $med_id = $item['id'];
                $qty_needed = (int)$item['qty'];
                $line_total = (float)($qty_needed * $item['price']);

                // FIFO Logic: Fetch active batches sorted by expiry
                $stmtBatches = $conn->prepare("SELECT id, quantity FROM medicine_batches WHERE medicine_id = ? AND status = 'ACTIVE' AND quantity > 0 ORDER BY expiry_date ASC FOR UPDATE");
                $stmtBatches->execute([$med_id]);
                $batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);

                $qty_remaining = $qty_needed;
                
                foreach ($batches as $batch) {
                    if ($qty_remaining <= 0) break;
                    
                    if ($batch['quantity'] >= $qty_remaining) {
                        // This batch can fulfill the remaining needed
                        $upd = $conn->prepare("UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?");
                        $upd->execute([$qty_remaining, $batch['id']]);
                        $qty_remaining = 0;
                    } else {
                        // Take everything from this batch and continue
                        $qty_remaining -= $batch['quantity'];
                        $upd = $conn->prepare("UPDATE medicine_batches SET quantity = 0 WHERE id = ?");
                        $upd->execute([$batch['id']]);
                    }
                }

                if ($qty_remaining > 0) {
                    $conn->rollBack();
                    echo json_encode(["success" => false, "message" => "Not enough active stock for " . htmlspecialchars($item['name'])]);
                    exit;
                }

                // Insert sale record with bill_id
                $insertSale->execute([$bill_id, $customer_id, $med_id, $qty, $line_total]);
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Bill generated successfully!", "bill_id" => $bill_id]);

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
    }
}
