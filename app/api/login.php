<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';

use App\Core\Database;
use App\Core\Session;

function sendJson($data, $statusCode = 200) {
    ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(["success" => false, "message" => "Method not allowed"], 405);
    }

    Session::init();

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!$data || !isset($data->email) || !isset($data->password) || !isset($data->role)) {
        sendJson(["success" => false, "message" => "Missing required fields"]);
    }

    $email = filter_var(trim($data->email), FILTER_SANITIZE_EMAIL);
    $password = trim($data->password);
    $role = trim($data->role);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJson(["success" => false, "message" => "Invalid email format"]);
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role, status FROM users WHERE email = :email AND role = :role");
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":role", $role);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user['status'] !== 'active') {
            sendJson(["success" => false, "message" => "Account is " . $user['status']]);
        }

        if (password_verify($password, $user['password_hash'])) {
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->bindParam(":id", $user['id']);
            $updateStmt->execute();

            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['full_name']);
            Session::set('user_role', $user['role']);

            sendJson([
                "success" => true,
                "message" => "Login successful",
                "redirect" => "../app/views/{$user['role']}/dashboard.php"
            ]);
        } else {
            sendJson(["success" => false, "message" => "Invalid email or password"]);
        }
    } else {
        sendJson(["success" => false, "message" => "Invalid email, password, or role"]);
    }
} catch (\Throwable $e) {
    error_log("Login error: " . $e->getMessage());
    sendJson(["success" => false, "message" => "An error occurred during login. Please try again later."], 500);
}
?>
