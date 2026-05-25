<?php
require 'app/core/Database.php';
$db = new \App\Core\Database();
$conn = $db->getConnection();
echo "CART:\n"; print_r($conn->query('SELECT * FROM customer_cart')->fetchAll(PDO::FETCH_ASSOC));
echo "WISHLIST:\n"; print_r($conn->query('SELECT * FROM wishlists')->fetchAll(PDO::FETCH_ASSOC));
echo "ADDRESSES:\n"; print_r($conn->query('SELECT * FROM customer_addresses')->fetchAll(PDO::FETCH_ASSOC));
echo "ORDERS:\n"; print_r($conn->query('SELECT * FROM customer_orders')->fetchAll(PDO::FETCH_ASSOC));
