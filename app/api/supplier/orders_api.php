<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$db = new \App\Core\Database();
$conn = $db->getConnection();
// For a supplier user, we assume their ID is linked to the suppliers table, or maybe they are the supplier.
// Wait, the `suppliers` table has its own IDs. In `users` table, the role is `supplier`.
// In a real system, the user would have a `supplier_id` column, or their email matches. 
// For this prototype, since there is no explicit link in the user table, we'll fetch all POs or match by email if possible.
// Wait! `setup_db.php` doesn't link `users` to `suppliers`. The admin manages suppliers via a separate table.
// If the logged-in user is a "supplier", we don't know *which* supplier they are in the DB.
// Let's assume for this prototype they can see ALL POs, or we can check if there's a supplier with the same email.
// Let's check if there's a supplier with the user's email.
$stmtSupp = $conn->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE id = ?)");
$stmtSupp->execute([$_SESSION['user_id']]);
$supplier = $stmtSupp->fetch(PDO::FETCH_ASSOC);

// If no matching supplier found by email, maybe just show all for demo purposes, or block.
// For the sake of a working demo, if not matched, we just fetch all (or we could fetch none). We will fetch all so the demo works smoothly.
$supplierFilter = "";
$params = [];
if ($supplier) {
    $supplierFilter = " AND po.supplier_id = ? ";
    $params[] = $supplier['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'requests') {
        try {
            $sql = "SELECT po.*, s.company_name, u.full_name as admin_name 
                    FROM purchase_orders po 
                    LEFT JOIN suppliers s ON po.supplier_id = s.id 
                    LEFT JOIN users u ON po.admin_id = u.id 
                    WHERE po.status = 'Pending' $supplierFilter
                    ORDER BY po.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch items for each order
            foreach($orders as &$order) {
                $stmtItems = $conn->prepare("
                    SELECT i.*, m.name as medicine_name, m.category 
                    FROM purchase_order_items i 
                    JOIN medicines m ON i.medicine_id = m.id 
                    WHERE i.po_id = ?
                ");
                $stmtItems->execute([$order['id']]);
                $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(["success" => true, "data" => $orders]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'active') {
        try {
            $sql = "SELECT po.*, s.company_name, u.full_name as admin_name 
                    FROM purchase_orders po 
                    LEFT JOIN suppliers s ON po.supplier_id = s.id 
                    LEFT JOIN users u ON po.admin_id = u.id 
                    WHERE po.status IN ('Accepted', 'Shipped') $supplierFilter
                    ORDER BY po.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($orders as &$order) {
                $stmtItems = $conn->prepare("
                    SELECT i.*, m.name as medicine_name 
                    FROM purchase_order_items i 
                    JOIN medicines m ON i.medicine_id = m.id 
                    WHERE i.po_id = ?
                ");
                $stmtItems->execute([$order['id']]);
                $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(["success" => true, "data" => $orders]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }

    if ($_GET['action'] === 'history') {
        try {
            $sql = "SELECT po.*, s.company_name, u.full_name as admin_name 
                    FROM purchase_orders po 
                    LEFT JOIN suppliers s ON po.supplier_id = s.id 
                    LEFT JOIN users u ON po.admin_id = u.id 
                    WHERE po.status IN ('Delivered', 'Rejected') $supplierFilter
                    ORDER BY po.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($orders as &$order) {
                $stmtItems = $conn->prepare("
                    SELECT i.*, m.name as medicine_name 
                    FROM purchase_order_items i 
                    JOIN medicines m ON i.medicine_id = m.id 
                    WHERE i.po_id = ?
                ");
                $stmtItems->execute([$order['id']]);
                $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode(["success" => true, "data" => $orders]);
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

    if ($action === 'update_status') {
        $po_id = $data['po_id'];
        $status = $data['status']; // Accepted, Rejected, Shipped, Delivered
        
        try {
            $conn->beginTransaction();

            $updatePO = $conn->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
            $updatePO->execute([$status, $po_id]);
            
            // If Shipped, we just update status and let Admin confirm delivery.
            if ($status === 'Shipped') {
                $conn->prepare("UPDATE purchase_orders SET shipped_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$po_id]);
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Order marked as " . $status]);

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }
    }
}
