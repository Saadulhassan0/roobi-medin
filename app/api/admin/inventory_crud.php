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
            // Join with suppliers to get company name
            $stmt = $conn->query("
                SELECT m.*, s.company_name as supplier_name 
                FROM medicines m 
                LEFT JOIN suppliers s ON m.supplier_id = s.id 
                ORDER BY m.id DESC
            ");
            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $medicines]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_suppliers') {
        try {
            $stmt = $conn->query("SELECT id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name ASC");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $suppliers]);
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

    try {
        if ($action === 'create') {
            $name = trim($data['name']);
            $category = trim($data['category']);
            $supplier_id = !empty($data['supplier_id']) ? $data['supplier_id'] : null;
            $quantity = (int)$data['quantity'];
            $price = (float)$data['price'];
            $expiry = $data['expiry_date'];

            $stmt = $conn->prepare("INSERT INTO medicines (name, category, supplier_id, quantity, price, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $supplier_id, $quantity, $price, $expiry]);
            
            $med_id = $conn->lastInsertId();
            $batch_number = 'B-' . strtoupper(uniqid());
            $status = (strtotime($expiry) < time()) ? 'EXPIRED' : 'ACTIVE';
            $stmtBatch = $conn->prepare("INSERT INTO medicine_batches (medicine_id, batch_number, supplier_id, quantity, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtBatch->execute([$med_id, $batch_number, $supplier_id, $quantity, $expiry, $status]);
            
            echo json_encode(["success" => true, "message" => "Medicine added successfully"]);
            
        } elseif ($action === 'update') {
            $id = $data['id'];
            $name = trim($data['name']);
            $category = trim($data['category']);
            $supplier_id = !empty($data['supplier_id']) ? $data['supplier_id'] : null;
            $quantity = (int)$data['quantity'];
            $price = (float)$data['price'];
            $expiry = $data['expiry_date'];

            $stmt = $conn->prepare("UPDATE medicines SET name=?, category=?, supplier_id=?, quantity=?, price=?, expiry_date=? WHERE id=?");
            $stmt->execute([$name, $category, $supplier_id, $quantity, $price, $expiry, $id]);
            
            echo json_encode(["success" => true, "message" => "Medicine updated successfully"]);
            
        } elseif ($action === 'delete') {
            $id = $data['id'];
            
            // Note: If we had a strict constraint on sales, we'd need to check that.
            // Currently, ON DELETE SET NULL is active for sales.medicine_id.
            
            $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(["success" => true, "message" => "Medicine deleted successfully"]);
            
        } elseif ($action === 'remove_expired') {
            $stmtBatch = $conn->prepare("DELETE FROM medicine_batches WHERE expiry_date < CURDATE()");
            $stmtBatch->execute();
            
            $stmt = $conn->prepare("DELETE FROM medicines WHERE expiry_date < CURDATE()");
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            echo json_encode(["success" => true, "message" => "Removed $deletedCount expired product(s)"]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
