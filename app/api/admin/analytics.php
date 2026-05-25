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

try {
    // 1. Revenue Analytics (Last 7 Days)
    $revenue_data = [];
    $revenue_labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $revenue_labels[] = date('M d', strtotime($date));
        
        $stmt = $conn->prepare("SELECT SUM(total_price) FROM sales WHERE DATE(sale_date) = ?");
        $stmt->execute([$date]);
        $revenue_data[] = (float)($stmt->fetchColumn() ?: 0);
    }

    // 2. Inventory Distribution (By Category)
    $stmt = $conn->query("SELECT category, COUNT(*) as count FROM medicines GROUP BY category LIMIT 5");
    $inventory_data = [];
    $inventory_labels = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $inventory_labels[] = $row['category'];
        $inventory_data[] = (int)$row['count'];
    }

    echo json_encode([
        "success" => true,
        "revenue" => [
            "labels" => $revenue_labels,
            "data" => $revenue_data
        ],
        "inventory" => [
            "labels" => empty($inventory_labels) ? ['Empty'] : $inventory_labels,
            "data" => empty($inventory_data) ? [1] : $inventory_data
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
