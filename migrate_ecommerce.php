<?php
require_once 'app/core/Database.php';

$database = new \App\Core\Database();
$conn = $database->getConnection();

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Customer Addresses
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('home', 'work', 'other') DEFAULT 'home',
            full_name VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            full_address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            default_flag BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "customer_addresses table created.\n";

    // 2. Customer Cart
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
            UNIQUE KEY user_med (user_id, medicine_id)
        )
    ");
    echo "customer_cart table created.\n";

    // 3. Wishlists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS wishlists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            medicine_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
            UNIQUE KEY user_wishlist (user_id, medicine_id)
        )
    ");
    echo "wishlists table created.\n";

    // 4. Customer Orders
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
            shipping_address_id INT,
            billing_address_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (shipping_address_id) REFERENCES customer_addresses(id) ON DELETE SET NULL,
            FOREIGN KEY (billing_address_id) REFERENCES customer_addresses(id) ON DELETE SET NULL
        )
    ");
    echo "customer_orders table created.\n";

    // 5. Customer Order Items (With snapshots)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            medicine_id INT,
            medicine_name_snapshot VARCHAR(255) NOT NULL,
            price_snapshot DECIMAL(10,2) NOT NULL,
            batch_id_used INT,
            quantity INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES customer_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE SET NULL,
            FOREIGN KEY (batch_id_used) REFERENCES medicine_batches(id) ON DELETE SET NULL
        )
    ");
    echo "customer_order_items table created.\n";

    // 6. Customer Payments
    $conn->exec("
        CREATE TABLE IF NOT EXISTS customer_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            method ENUM('COD', 'Card', 'Online') NOT NULL,
            status ENUM('Pending', 'Success', 'Failed', 'Refunded') DEFAULT 'Pending',
            transaction_reference VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES customer_orders(id) ON DELETE CASCADE
        )
    ");
    echo "customer_payments table created.\n";

    echo "E-Commerce Database Migration Complete.\n";

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
