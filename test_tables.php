<?php
require 'app/core/Database.php';
$db = new \App\Core\Database();
$conn = $db->getConnection();
print_r($conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
