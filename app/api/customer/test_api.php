<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_cart';
require_once '../../core/Session.php';
\App\Core\Session::init();
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
require 'cart_api.php';
