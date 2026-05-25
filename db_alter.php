<?php
require_once 'app/core/Database.php';
try {
    $db = new \App\Core\Database();
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE, ADD COLUMN verification_code VARCHAR(10) NULL, ADD COLUMN verification_expiry DATETIME NULL");
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
