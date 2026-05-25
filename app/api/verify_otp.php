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

    if (!$data || !isset($data->action) || !isset($data->email)) {
        sendJson(["success" => false, "message" => "Missing required fields"]);
    }

    $action = trim($data->action);
    $email = trim($data->email);

    $db = new Database();
    $conn = $db->getConnection();

    // Check user exists
    $stmt = $conn->prepare("SELECT id, email_verified, verification_code, verification_expiry, full_name FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$user) {
        sendJson(["success" => false, "message" => "User not found"]);
    }

    if ($user['email_verified']) {
        sendJson(["success" => false, "message" => "Account is already verified"]);
    }

    if ($action === 'verify') {
        if (!isset($data->code)) {
            sendJson(["success" => false, "message" => "Missing OTP code"]);
        }
        
        $code = trim($data->code);
        
        // Check if expired
        if (strtotime($user['verification_expiry']) < time()) {
            sendJson(["success" => false, "message" => "OTP has expired. Please request a new one.", "expired" => true]);
        }
        
        // Verify Code
        if ($code === $user['verification_code']) {
            $upd = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expiry = NULL WHERE email = :email");
            $upd->execute([':email' => $email]);
            sendJson(["success" => true, "message" => "Email verified successfully! You can now log in."]);
        } else {
            sendJson(["success" => false, "message" => "Invalid OTP code"]);
        }
    } elseif ($action === 'resend') {
        $otp_code = sprintf("%06d", mt_rand(100000, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $upd = $conn->prepare("UPDATE users SET verification_code = :code, verification_expiry = :expiry WHERE email = :email");
        $upd->execute([':code' => $otp_code, ':expiry' => $expiry, ':email' => $email]);

        // Send Email
        $subject = "MedIn AI Pharmacy - Your New Verification Code";
        $message = "
        <html>
        <head><title>Verification Code</title></head>
        <body style='font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4;'>
            <div style='background: #fff; padding: 30px; border-radius: 10px; text-align: center;'>
                <h2 style='color: #8B5CF6;'>MedIn AI Pharmacy</h2>
                <p>Hello {$user['full_name']},</p>
                <p>You requested a new verification code. Please use the following 6-digit code. It will expire in 5 minutes.</p>
                <h1 style='background: #f3f4f6; padding: 15px; letter-spacing: 5px; color: #111;'>{$otp_code}</h1>
            </div>
        </body>
        </html>
        ";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@medin-pharmacy.local" . "\r\n";
        
        @mail($email, $subject, $message, $headers);

        sendJson(["success" => true, "message" => "A new verification code has been sent to your email."]);
    } else {
        sendJson(["success" => false, "message" => "Invalid action"]);
    }
} catch (\Throwable $e) {
    sendJson(["success" => false, "message" => "Server Error"], 500);
}
