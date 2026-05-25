<?php
require_once '../../core/Database.php';
require_once '../../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new \App\Core\Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'get_medicines') {
        try {
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            
            $query = "
                SELECT m.id, m.name, m.category, m.price,
                COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as stock,
                (SELECT MIN(expiry_date) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE') as expiry_date
                FROM medicines m 
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND m.name LIKE ?";
                $params[] = "%$search%";
            }
            if (!empty($category)) {
                $query .= " AND m.category = ?";
                $params[] = $category;
            }
            
            $query .= " ORDER BY m.name ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(["success" => true, "data" => $medicines]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_recommendations') {
        try {
            // Recommendation Engine Logic:
            // 1. Based on past purchases (frequently bought categories by this user)
            $stmtCategories = $conn->prepare("
                SELECT m.category, COUNT(*) as freq 
                FROM customer_order_items coi
                JOIN customer_orders co ON coi.order_id = co.id
                JOIN medicines m ON coi.medicine_id = m.id
                WHERE co.user_id = ?
                GROUP BY m.category
                ORDER BY freq DESC LIMIT 2
            ");
            $stmtCategories->execute([$user_id]);
            $top_categories = $stmtCategories->fetchAll(PDO::FETCH_COLUMN);
            
            $recommendations = [];
            
            if (!empty($top_categories)) {
                // Fetch active medicines from these categories
                $inQuery = implode(',', array_fill(0, count($top_categories), '?'));
                $stmtRec = $conn->prepare("
                    SELECT m.id, m.name, m.category, m.price,
                    COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as stock
                    FROM medicines m
                    WHERE m.category IN ($inQuery)
                    AND m.id NOT IN (
                        SELECT medicine_id FROM customer_order_items coi 
                        JOIN customer_orders co ON coi.order_id = co.id 
                        WHERE co.user_id = $user_id
                    )
                    LIMIT 4
                ");
                $stmtRec->execute($top_categories);
                $recommendations = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Fallback: Trending top-selling medicines overall
            if (count($recommendations) < 4) {
                $limit = 4 - count($recommendations);
                $stmtFallback = $conn->query("
                    SELECT m.id, m.name, m.category, m.price,
                    COALESCE((SELECT SUM(quantity) FROM medicine_batches WHERE medicine_id = m.id AND status = 'ACTIVE'), 0) as stock
                    FROM medicines m
                    LEFT JOIN customer_order_items coi ON m.id = coi.medicine_id
                    GROUP BY m.id
                    ORDER BY COUNT(coi.id) DESC
                    LIMIT $limit
                ");
                $fallback = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
                $recommendations = array_merge($recommendations, $fallback);
            }
            
            echo json_encode(["success" => true, "data" => $recommendations]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
}
