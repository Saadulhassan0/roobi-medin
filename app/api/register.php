<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;

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

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!$data || !isset($data->full_name) || !isset($data->email) || !isset($data->phone_number) || !isset($data->password) || !isset($data->role)) {
        sendJson(["success" => false, "message" => "Missing required fields"]);
    }

    $full_name = trim($data->full_name);
    $email = filter_var(trim($data->email), FILTER_SANITIZE_EMAIL);
    $phone_number = trim($data->phone_number);
    $password = trim($data->password);
    $role = trim($data->role);

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJson(["success" => false, "message" => "Invalid email format"]);
    }

    if (strlen($password) < 8) {
        sendJson(["success" => false, "message" => "Password must be at least 8 characters"]);
    }

    $validRoles = ['admin', 'pharmacist', 'supplier', 'customer'];
    if (!in_array($role, $validRoles)) {
        sendJson(["success" => false, "message" => "Invalid role selected"]);
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $checkStmt->bindParam(":email", $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        sendJson(["success" => false, "message" => "Email is already registered"]);
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $otp_code = sprintf("%06d", mt_rand(100000, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, password_hash, role, verification_code, verification_expiry, email_verified) VALUES (:full_name, :email, :phone_number, :password_hash, :role, :code, :expiry, 0)");
    
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":phone_number", $phone_number);
    $stmt->bindParam(":password_hash", $password_hash);
    $stmt->bindParam(":role", $role);
    $stmt->bindParam(":code", $otp_code);
    $stmt->bindParam(":expiry", $expiry);

    if ($stmt->execute()) {
        // Send Email (Simulated/Basic Mail for now)
        $subject = "MedIn AI Pharmacy - Verify Your Account";
        $message = "
        <html>
        <head>
            <title>Verification Code</title>
        </head>
        <body style='font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4;'>
            <div style='background: #fff; padding: 30px; border-radius: 10px; text-align: center;'>
                <h2 style='color: #8B5CF6;'>MedIn AI Pharmacy</h2>
                <p>Hello {$full_name},</p>
                <p>Thank you for registering. Please use the following 6-digit code to verify your email address. This code will expire in 5 minutes.</p>
                <h1 style='background: #f3f4f6; padding: 15px; letter-spacing: 5px; color: #111;'>{$otp_code}</h1>
                <p style='color: #666; font-size: 12px;'>If you did not request this, please ignore this email.</p>
            </div>
        </body>
        </html>
        ";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@medin-pharmacy.local" . "\r\n";
        
        @mail($email, $subject, $message, $headers);

        sendJson(["success" => true, "message" => "Account created. Please check your email for the verification code.", "require_verification" => true, "email" => $email]);
    } else {
        sendJson(["success" => false, "message" => "Registration failed"]);
    }
} catch (\Throwable $e) {
    error_log("Register error: " . $e->getMessage());
    sendJson(["success" => false, "message" => "An error occurred during registration."], 500);
}
?>
