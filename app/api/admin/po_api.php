<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$db = new \App\Core\Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'read') {
        try {
            $stmt = $conn->query("
                SELECT po.*, s.company_name as supplier_name, 
                       (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.id) as item_count 
                FROM purchase_orders po 
                LEFT JOIN suppliers s ON po.supplier_id = s.id 
                ORDER BY po.id DESC
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $orders]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }

    if ($_GET['action'] === 'get_suppliers') {
        try {
            $stmt = $conn->query("SELECT id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name ASC");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }

    if ($_GET['action'] === 'get_medicines') {
        try {
            $stmt = $conn->query("SELECT id, name, quantity FROM medicines ORDER BY name ASC");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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

    if ($action === 'create') {
        $supplier_id = $data['supplier_id'];
        $notes = $data['notes'] ?? '';
        $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
        $delivery_location = !empty($data['delivery_location']) ? $data['delivery_location'] : null;
        $items = $data['items'] ?? [];
        $admin_id = $_SESSION['user_id'];

        if (empty($items)) {
            echo json_encode(["success" => false, "message" => "Must include at least one item."]);
            exit;
        }

        try {
            $conn->beginTransaction();

            $stmtPO = $conn->prepare("INSERT INTO purchase_orders (admin_id, supplier_id, notes, due_date, delivery_location) VALUES (?, ?, ?, ?, ?)");
            $stmtPO->execute([$admin_id, $supplier_id, $notes, $due_date, $delivery_location]);
            $po_id = $conn->lastInsertId();

            $stmtItem = $conn->prepare("INSERT INTO purchase_order_items (po_id, medicine_id, quantity_requested, unit_price) VALUES (?, ?, ?, ?)");
            
            foreach ($items as $item) {
                // Fetch unit price to store in PO for historical record
                $medStmt = $conn->prepare("SELECT price FROM medicines WHERE id = ?");
                $medStmt->execute([$item['id']]);
                $med = $medStmt->fetch(PDO::FETCH_ASSOC);
                $price = $med ? $med['price'] : 0;

                $stmtItem->execute([$po_id, $item['id'], $item['qty'], $price]);
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Purchase Order sent to supplier."]);

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
    }

    if ($action === 'approve_delivery') {
        $po_id = $data['po_id'];
        try {
            $conn->beginTransaction();
            // 1. Update status
            $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'Completed', delivered_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$po_id]);
            
            // 2. Fetch items and add to medicine_batches
            $stmtItems = $conn->prepare("SELECT medicine_id, quantity_requested FROM purchase_order_items WHERE po_id = ?");
            $stmtItems->execute([$po_id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate generic batch info based on PO
            $batch_number = 'PO' . str_pad($po_id, 4, '0', STR_PAD_LEFT);
            // Default expiry date: 1 year from now
            $expiry_date = date('Y-m-d', strtotime('+1 year'));
            
            $insertBatch = $conn->prepare("
                INSERT INTO medicine_batches (medicine_id, batch_number, quantity, expiry_date, status) 
                VALUES (?, ?, ?, ?, 'ACTIVE')
            ");
            
            foreach ($items as $item) {
                // Check if medicine has a default supplier, if so we could add it, but for now we just insert batch
                $insertBatch->execute([$item['medicine_id'], $batch_number . '-' . $item['medicine_id'], $item['quantity_requested'], $expiry_date]);
            }
            
            $conn->commit();
            echo json_encode(["success" => true, "message" => "Order Approved. Inventory updated!"]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }

    if ($action === 'reject_delivery') {
        $po_id = $data['po_id'];
        try {
            $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'Rejected' WHERE id = ?");
            $stmt->execute([$po_id]);
            echo json_encode(["success" => true, "message" => "Delivery Rejected."]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
}
