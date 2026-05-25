<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$db = new \App\Core\Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get') {
        $po_id = $_GET['po_id'] ?? null;
        if (!$po_id) {
            echo json_encode(["success" => false, "message" => "Missing PO ID"]);
            exit;
        }

        try {
            $stmt = $conn->prepare("
                SELECT m.*, u.full_name as sender_name 
                FROM po_messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.po_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$po_id]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action']) && $data['action'] === 'send') {
        $po_id = $data['po_id'] ?? null;
        $message = $data['message'] ?? '';

        if (!$po_id || trim($message) === '') {
            echo json_encode(["success" => false, "message" => "Invalid input"]);
            exit;
        }

        try {
            $stmt = $conn->prepare("INSERT INTO po_messages (po_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$po_id, $user_id, $role, $message]);
            echo json_encode(["success" => true, "message" => "Message sent"]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
}
