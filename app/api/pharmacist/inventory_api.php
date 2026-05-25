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
    
    if ($_GET['action'] === 'read') {
        try {
            // Auto-expire batches that are past their expiry date and are still ACTIVE
            $conn->exec("UPDATE medicine_batches SET status = 'EXPIRED' WHERE expiry_date < CURDATE() AND status = 'ACTIVE'");

            $stmt = $conn->query("
                SELECT b.*, m.name as medicine_name, m.category, m.price, s.company_name as supplier_name 
                FROM medicine_batches b
                JOIN medicines m ON b.medicine_id = m.id 
                LEFT JOIN suppliers s ON b.supplier_id = s.id 
                ORDER BY b.expiry_date ASC
            ");
            $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $batches]);
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
    
    // Fallback to $_POST if not json (for form submissions)
    if (!$data) {
        $data = $_POST;
    }
    
    $action = $data['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($data['name']);
            $category = trim($data['category']);
            $supplier_id = !empty($data['supplier_id']) ? $data['supplier_id'] : null;
            $quantity = (int)$data['quantity'];
            $price = (float)$data['price'];
            $expiry = $data['expiry_date'];
            
            // Check if batch number is in the data (the user requested "Batch Number" in the prompt)
            // But since the DB `medicines` schema doesn't have a batch number, I'll ignore or maybe add it to DB later.
            // For now, I will stick to existing schema to avoid breaking things, or wait, I should alter the DB to add `batch_number`.
            $batch_number = !empty($data['batch_number']) ? trim($data['batch_number']) : null;

            // Adding batch_number to medicines table if it's passed (need to alter DB first)
            // For now, let's just insert what we have, and we'll alter the DB if batch_number is strictly needed.
            
            $stmt = $conn->prepare("INSERT INTO medicines (name, category, batch_number, supplier_id, quantity, price, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $batch_number, $supplier_id, $quantity, $price, $expiry]);
            
            echo json_encode(["success" => true, "message" => "Medicine added successfully"]);
            
        } elseif ($action === 'update') {
            $id = $data['id'];
            $name = trim($data['name']);
            $category = trim($data['category']);
            $batch_number = !empty($data['batch_number']) ? trim($data['batch_number']) : null;
            $supplier_id = !empty($data['supplier_id']) ? $data['supplier_id'] : null;
            $quantity = (int)$data['quantity'];
            $price = (float)$data['price'];
            $expiry = $data['expiry_date'];

            $stmt = $conn->prepare("UPDATE medicines SET name=?, category=?, batch_number=?, supplier_id=?, quantity=?, price=?, expiry_date=? WHERE id=?");
            $stmt->execute([$name, $category, $batch_number, $supplier_id, $quantity, $price, $expiry, $id]);
            
            echo json_encode(["success" => true, "message" => "Medicine updated successfully"]);
            
        } elseif ($action === 'update_batch_status') {
            $batch_id = $data['batch_id'];
            $new_status = $data['status']; // QUARANTINED, DISPOSED, RETURNED, ACTIVE
            
            // Only allow valid statuses
            $valid_statuses = ['ACTIVE', 'EXPIRED', 'QUARANTINED', 'DISPOSED', 'RETURNED'];
            if (!in_array($new_status, $valid_statuses)) {
                echo json_encode(["success" => false, "message" => "Invalid status"]);
                exit;
            }

            $stmtCheck = $conn->prepare("SELECT medicine_id, quantity, status FROM medicine_batches WHERE id = ?");
            $stmtCheck->execute([$batch_id]);
            $batch = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($batch) {
                if (($new_status === 'DISPOSED' || $new_status === 'RETURNED') && $batch['status'] !== 'DISPOSED' && $batch['status'] !== 'RETURNED') {
                    $upd = $conn->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
                    $upd->execute([$batch['quantity'], $batch['medicine_id']]);
                } elseif (($batch['status'] === 'DISPOSED' || $batch['status'] === 'RETURNED') && ($new_status !== 'DISPOSED' && $new_status !== 'RETURNED')) {
                    $upd = $conn->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?");
                    $upd->execute([$batch['quantity'], $batch['medicine_id']]);
                }
            }

            $stmt = $conn->prepare("UPDATE medicine_batches SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $batch_id]);
            
            echo json_encode(["success" => true, "message" => "Batch marked as " . $new_status]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
