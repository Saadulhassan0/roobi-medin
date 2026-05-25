<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

// Ensure only admin can access this API
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$db = new \App\Core\Database();
$conn = $db->getConnection();

// Handle GET requests (Read)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'read') {
    try {
        $stmt = $conn->query("SELECT id, full_name, email, role, status, branch, created_at FROM users ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $users]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
    exit;
}

// Handle POST requests (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($data['full_name']);
            $email = trim($data['email']);
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = $data['role'];
            $status = $data['status'];
            $branch = !empty($data['branch']) ? trim($data['branch']) : null;

            // Check if email exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Email already exists"]);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, status, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $status, $branch]);
            
            echo json_encode(["success" => true, "message" => "User created successfully"]);
            
        } elseif ($action === 'update') {
            $id = $data['id'];
            $name = trim($data['full_name']);
            $email = trim($data['email']);
            $role = $data['role'];
            $status = $data['status'];
            $branch = !empty($data['branch']) ? trim($data['branch']) : null;

            // Check email uniqueness excluding current user
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Email already used by another user"]);
                exit;
            }

            if (!empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, password_hash=?, role=?, status=?, branch=? WHERE id=?");
                $stmt->execute([$name, $email, $password, $role, $status, $branch, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, status=?, branch=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $status, $branch, $id]);
            }
            
            echo json_encode(["success" => true, "message" => "User updated successfully"]);
            
        } elseif ($action === 'delete') {
            $id = $data['id'];
            
            // Prevent self-deletion
            if ($id == $_SESSION['user_id']) {
                echo json_encode(["success" => false, "message" => "You cannot delete your own account"]);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(["success" => true, "message" => "User deleted successfully"]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
