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
    
    if ($_GET['action'] === 'stats') {
        try {
            // New Prescriptions (dummy for now, or total medicines added today)
            // Bills generated today
            $stmt = $conn->query("SELECT COUNT(DISTINCT id) FROM sales WHERE DATE(sale_date) = CURDATE()");
            $bills_generated = $stmt->fetchColumn();

            // Total Medicines (Unique active products)
            $stmt = $conn->query("SELECT COUNT(DISTINCT medicine_id) FROM medicine_batches WHERE status = 'ACTIVE'");
            $total_medicines = $stmt->fetchColumn();

            // Near Expiry (within 30 days) - only active batches
            $stmt = $conn->query("SELECT COUNT(*) FROM medicine_batches WHERE status = 'ACTIVE' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()");
            $near_expiry = $stmt->fetchColumn();

            // Out of Stock (0 quantity active batches, or medicines with no active batches)
            $stmt = $conn->query("SELECT COUNT(id) FROM medicines WHERE id NOT IN (SELECT medicine_id FROM medicine_batches WHERE status = 'ACTIVE' AND quantity > 0)");
            $out_of_stock = $stmt->fetchColumn();
            
            // Dispensed Medicines Trend (last 7 days sales)
            $stmt = $conn->query("
                SELECT DATE(sale_date) as date, SUM(quantity) as total_qty 
                FROM sales 
                WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date ASC
            ");
            $sales_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top Categories (from inventory count, or sales count)
            $stmt = $conn->query("
                SELECT category, COUNT(*) as count 
                FROM medicines 
                GROUP BY category 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $top_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "success" => true, 
                "data" => [
                    "bills_generated" => $bills_generated,
                    "total_medicines" => $total_medicines,
                    "near_expiry" => $near_expiry,
                    "out_of_stock" => $out_of_stock,
                    "sales_trend" => $sales_trend,
                    "top_categories" => $top_categories
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error"]);
        }
        exit;
    }
}
