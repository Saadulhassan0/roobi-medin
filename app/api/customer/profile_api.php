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
    
    if ($_GET['action'] === 'get_addresses') {
        try {
            $stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY default_flag DESC, id DESC");
            $stmt->execute([$user_id]);
            $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $addresses]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_wishlist') {
        try {
            $stmt = $conn->prepare("
                SELECT w.id, w.medicine_id, m.name as medicine_name, m.category, m.price 
                FROM wishlists w
                JOIN medicines m ON w.medicine_id = m.id
                WHERE w.user_id = ?
                ORDER BY w.id DESC
            ");
            $stmt->execute([$user_id]);
            $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $wishlist]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB Error"]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    try {
        if ($action === 'save_address') {
            $type = $data['type'];
            $full_name = trim($data['full_name']);
            $phone = trim($data['phone_number']);
            $address = trim($data['full_address']);
            $city = trim($data['city']);
            $zip = trim($data['postal_code']);
            $default_flag = (int)$data['default_flag'];
            
            if ($default_flag == 1) {
                // Remove default from other addresses
                $upd = $conn->prepare("UPDATE customer_addresses SET default_flag = 0 WHERE user_id = ?");
                $upd->execute([$user_id]);
            }

            if (!empty($data['id'])) {
                // Update
                $stmt = $conn->prepare("UPDATE customer_addresses SET type=?, full_name=?, phone_number=?, full_address=?, city=?, postal_code=?, default_flag=? WHERE id=? AND user_id=?");
                $stmt->execute([$type, $full_name, $phone, $address, $city, $zip, $default_flag, $data['id'], $user_id]);
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO customer_addresses (user_id, type, full_name, phone_number, full_address, city, postal_code, default_flag) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $type, $full_name, $phone, $address, $city, $zip, $default_flag]);
            }
            echo json_encode(["success" => true, "message" => "Address saved"]);
            
        } elseif ($action === 'delete_address') {
            $id = $data['id'];
            $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(["success" => true, "message" => "Address deleted"]);
            
        } elseif ($action === 'add_wishlist') {
            $med_id = $data['medicine_id'];
            $stmt = $conn->prepare("INSERT IGNORE INTO wishlists (user_id, medicine_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $med_id]);
            echo json_encode(["success" => true, "message" => "Added to wishlist"]);
            
        } elseif ($action === 'remove_wishlist') {
            $id = $data['id'];
            $stmt = $conn->prepare("DELETE FROM wishlists WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(["success" => true, "message" => "Removed from wishlist"]);
            
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
