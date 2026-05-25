<?php
require_once 'app/core/Database.php';
$db = new \App\Core\Database();
$conn = $db->getConnection();
print_r($conn->query("DESCRIBE po_messages")->fetchAll(PDO::FETCH_ASSOC));
