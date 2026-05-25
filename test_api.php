<?php
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_cart';
require 'app/api/customer/cart_api.php';
