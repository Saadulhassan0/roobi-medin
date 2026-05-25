<?php
require_once '../core/Database.php';
require_once '../core/Session.php';

\App\Core\Session::init();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$db = new \App\Core\Database();
$conn = $db->getConnection();

$notifications = [];

try {
    // 1. Expiry Notifications (For Admin and Pharmacist)
    if ($user_role === 'admin' || $user_role === 'pharmacist') {
        $stmtExp = $conn->query("
            SELECT mb.batch_number, m.name, mb.expiry_date 
            FROM medicine_batches mb
            JOIN medicines m ON mb.medicine_id = m.id
            WHERE mb.status = 'ACTIVE' AND mb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY mb.expiry_date ASC LIMIT 5
        ");
        $expiring = $stmtExp->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expiring as $exp) {
            $notifications[] = [
                "type" => "expiry",
                "title" => "Expiring Soon",
                "message" => "Batch {$exp['batch_number']} of {$exp['name']} will expire on {$exp['expiry_date']}.",
                "time" => "Action Required",
                "link" => "inventory.php"
            ];
        }
    }
    
    // 2. Chat Notifications (From Suppliers to Admins/Pharmacists, or Admins to Suppliers)
    if ($user_role === 'supplier') {
        // Find latest messages from admins/pharmacists on this supplier's POs
        $stmtChat = $conn->prepare("
            SELECT pm.message, pm.created_at, po.po_number, u.username as sender_name
            FROM po_messages pm
            JOIN purchase_orders po ON pm.po_id = po.id
            JOIN users u ON pm.sender_id = u.id
            WHERE po.supplier_id = ? AND pm.sender_id != ?
            ORDER BY pm.created_at DESC LIMIT 5
        ");
        $stmtChat->execute([$user_id, $user_id]);
    } else {
        // Admin / Pharmacist: Find latest messages from suppliers
        $stmtChat = $conn->prepare("
            SELECT pm.message, pm.created_at, po.po_number, u.username as sender_name
            FROM po_messages pm
            JOIN purchase_orders po ON pm.po_id = po.id
            JOIN users u ON pm.sender_id = u.id
            WHERE pm.sender_id != ? AND pm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY pm.created_at DESC LIMIT 5
        ");
        $stmtChat->execute([$user_id]);
    }
    
    $chats = $stmtChat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($chats as $chat) {
        $shortMsg = (strlen($chat['message']) > 40) ? substr($chat['message'], 0, 40) . '...' : $chat['message'];
        
        // Format time dynamically
        $timeStr = date('M d, H:i', strtotime($chat['created_at']));
        
        $notifications[] = [
            "type" => "chat",
            "title" => "New Message: {$chat['po_number']}",
            "message" => "<b>{$chat['sender_name']}</b>: $shortMsg",
            "time" => $timeStr,
            "link" => "orders.php"
        ];
    }
    
    // 3. New Orders (For Admin)
    if ($user_role === 'admin') {
        $stmtOrd = $conn->query("
            SELECT id, total_amount, created_at 
            FROM customer_orders 
            WHERE status = 'Pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY created_at DESC LIMIT 3
        ");
        $orders = $stmtOrd->fetchAll(PDO::FETCH_ASSOC);
        foreach ($orders as $ord) {
            $notifications[] = [
                "type" => "info",
                "title" => "New Customer Order",
                "message" => "Order #{$ord['id']} for $" . number_format($ord['total_amount'], 2) . " needs approval.",
                "time" => date('M d, H:i', strtotime($ord['created_at'])),
                "link" => "orders.php"
            ];
        }
    }

    echo json_encode(["success" => true, "data" => $notifications]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Error"]);
}
