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
    
    if ($_GET['action'] === 'get_cart') {
        try {
            $stmt = $conn->prepare("
                SELECT c.id as cart_id, c.medicine_id, c.quantity, m.name as medicine_name, m.price, m.category,
                COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as available_stock
                FROM customer_cart c
                JOIN medicines m ON c.medicine_id = m.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check stock limits dynamically
            $filtered_cart = [];
            foreach($cart_items as $item) {
                if ($item['available_stock'] == 0) {
                    // Out of stock, maybe remove it or set to 0
                    $upd = $conn->prepare("DELETE FROM customer_cart WHERE id = ?");
                    $upd->execute([$item['cart_id']]);
                    continue; // Skip returning it
                }
                if ($item['quantity'] > $item['available_stock']) {
                    $item['quantity'] = $item['available_stock'];
                    $upd = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE id = ?");
                    $upd->execute([$item['quantity'], $item['cart_id']]);
                }
                $filtered_cart[] = $item;
            }
            
            echo json_encode(["success" => true, "data" => $filtered_cart]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'add') {
            $med_id = $data['medicine_id'];
            $qty = (int)($data['quantity'] ?? 1);
            
            // Check stock
            $stmtStock = $conn->prepare("SELECT COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = ? AND status = 'ACTIVE'), 0) as stock");
            $stmtStock->execute([$med_id]);
            $stock = $stmtStock->fetchColumn();
            
            if ($stock < $qty) {
                echo json_encode(["success" => false, "message" => "Not enough stock available."]);
                exit;
            }

            // Check if already in cart
            $stmtCheck = $conn->prepare("SELECT id, quantity FROM customer_cart WHERE user_id = ? AND medicine_id = ?");
            $stmtCheck->execute([$user_id, $med_id]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $new_qty = $existing['quantity'] + $qty;
                if ($new_qty > $stock) {
                    echo json_encode(["success" => false, "message" => "Cannot exceed available stock."]);
                    exit;
                }
                $stmt = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_qty, $existing['id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO customer_cart (user_id, medicine_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $med_id, $qty]);
            }
            
            echo json_encode(["success" => true, "message" => "Added to cart"]);
            
        } elseif ($action === 'update') {
            $cart_id = $data['cart_id'];
            $qty = (int)$data['quantity'];
            
            if ($qty <= 0) {
                $stmt = $conn->prepare("DELETE FROM customer_cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
            } else {
                // Check stock
                $stmtMed = $conn->prepare("SELECT medicine_id FROM customer_cart WHERE id = ?");
                $stmtMed->execute([$cart_id]);
                $med_id = $stmtMed->fetchColumn();
                
                $stmtStock = $conn->prepare("SELECT COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = ? AND status = 'ACTIVE'), 0) as stock");
                $stmtStock->execute([$med_id]);
                $stock = $stmtStock->fetchColumn();
                
                if ($qty > $stock) {
                    echo json_encode(["success" => false, "message" => "Cannot exceed available stock ($stock max)."]);
                    exit;
                }
                
                $stmt = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$qty, $cart_id, $user_id]);
            }
            echo json_encode(["success" => true, "message" => "Cart updated"]);
            
        } elseif ($action === 'remove') {
            $cart_id = $data['cart_id'];
            $stmt = $conn->prepare("DELETE FROM customer_cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            echo json_encode(["success" => true, "message" => "Item removed"]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
