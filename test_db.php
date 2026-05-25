<?php
require_once 'app/core/Database.php';
$db = new \App\Core\Database();
$conn = $db->getConnection();

try {
    // 1. Add columns to purchase_orders
    $stmt = $conn->query("SHOW COLUMNS FROM purchase_orders LIKE 'due_date'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE purchase_orders ADD COLUMN due_date DATE NULL");
        $conn->exec("ALTER TABLE purchase_orders ADD COLUMN delivery_location VARCHAR(255) NULL");
        echo "Added due_date and delivery_location to purchase_orders.\n";
    }

    // 2. Create po_messages table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS po_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            po_id INT NOT NULL,
            sender_id INT NOT NULL,
            sender_role VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "po_messages table created.\n";

} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
