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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Low Stock (< 10)
        $stmtLowStock = $conn->query("
            SELECT id, name, category, quantity, 'Low Stock' as type 
            FROM medicines 
            WHERE quantity < 10 AND quantity > 0
            ORDER BY quantity ASC
        ");
        $low_stock = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);

        // Out of Stock (0)
        $stmtOutOfStock = $conn->query("
            SELECT id, name, category, quantity, 'Out of Stock' as type 
            FROM medicines 
            WHERE quantity = 0
        ");
        $out_of_stock = $stmtOutOfStock->fetchAll(PDO::FETCH_ASSOC);

        // Expiry (Within 30 days)
        $stmtExpiry = $conn->query("
            SELECT id, name, category, expiry_date, 'Expiring Soon' as type 
            FROM medicines 
            WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE()
            ORDER BY expiry_date ASC
        ");
        $expiring = $stmtExpiry->fetchAll(PDO::FETCH_ASSOC);

        // Already Expired
        $stmtExpired = $conn->query("
            SELECT id, name, category, expiry_date, 'Expired' as type 
            FROM medicines 
            WHERE expiry_date <= CURDATE()
            ORDER BY expiry_date DESC
        ");
        $expired = $stmtExpired->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => [
                "low_stock" => $low_stock,
                "out_of_stock" => $out_of_stock,
                "expiring" => $expiring,
                "expired" => $expired
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
}
