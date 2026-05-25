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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'read') {
    try {
        $stmt = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $suppliers]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'create') {
            $company = trim($data['company_name']);
            $contact = trim($data['contact_person']);
            $email = trim($data['email']);
            $phone = trim($data['phone']);
            $address = trim($data['address']);
            $status = $data['status'];

            $check = $conn->prepare("SELECT id FROM suppliers WHERE email = ?");
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Supplier email already exists"]);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO suppliers (company_name, contact_person, email, phone, address, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company, $contact, $email, $phone, $address, $status]);
            
            echo json_encode(["success" => true, "message" => "Supplier added successfully"]);
            
        } elseif ($action === 'update') {
            $id = $data['id'];
            $company = trim($data['company_name']);
            $contact = trim($data['contact_person']);
            $email = trim($data['email']);
            $phone = trim($data['phone']);
            $address = trim($data['address']);
            $status = $data['status'];

            $check = $conn->prepare("SELECT id FROM suppliers WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Email used by another supplier"]);
                exit;
            }

            $stmt = $conn->prepare("UPDATE suppliers SET company_name=?, contact_person=?, email=?, phone=?, address=?, status=? WHERE id=?");
            $stmt->execute([$company, $contact, $email, $phone, $address, $status, $id]);
            
            echo json_encode(["success" => true, "message" => "Supplier updated successfully"]);
            
        } elseif ($action === 'delete') {
            $id = $data['id'];
            
            // Check if supplier is linked to medicines
            $check = $conn->prepare("SELECT id FROM medicines WHERE supplier_id = ?");
            $check->execute([$id]);
            if ($check->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Cannot delete supplier linked to inventory"]);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(["success" => true, "message" => "Supplier deleted successfully"]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
