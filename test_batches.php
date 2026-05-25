<?php
require 'app/core/Database.php';
$db = new \App\Core\Database();
$conn = $db->getConnection();
print_r($conn->query("SELECT * FROM medicine_batches")->fetchAll(PDO::FETCH_ASSOC));
