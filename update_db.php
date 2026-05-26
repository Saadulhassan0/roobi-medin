<?php
require_once 'app/core/Database.php';

$database = new \App\Core\Database();
$conn = $database->getConnection();

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add branch to users if not exists
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN branch VARCHAR(255) DEFAULT NULL");
        echo "Added branch to users.\n";
    } catch(PDOException $e) {
        echo "branch exists or error: " . $e->getMessage() . "\n";
    }

    // Add missing verification columns to users
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        $conn->exec("ALTER TABLE users ADD COLUMN verification_expiry DATETIME DEFAULT NULL");
        $conn->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE");
        echo "Added verification columns to users.\n";
    } catch(PDOException $e) {
        echo "verification columns exist or error: " . $e->getMessage() . "\n";
    }

    // Add status to users if not exists
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        echo "Added status to users.\n";
    } catch(PDOException $e) {
        echo "status exists or error: " . $e->getMessage() . "\n";
    }

    // Insert Default Admin
    try {
        $conn->exec("INSERT INTO users (full_name, email, phone_number, password_hash, role, is_verified, email_verified) 
        VALUES ('Super Admin', 'admin@medin.com', '1234567890', '\$2y\$10\$W9M7.E4kQcQ.s9rOaR.O..W.QoG5Qp/zXq8m9QO8tB3B5C.QjYqXG', 'admin', TRUE, TRUE)
        ON DUPLICATE KEY UPDATE id=id");
        echo "Default admin user ensured.\n";
    } catch(PDOException $e) {
        echo "admin error: " . $e->getMessage() . "\n";
    }

    // Add status to suppliers if not exists
    try {
        $conn->exec("ALTER TABLE suppliers ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        echo "Added status to suppliers.\n";
    } catch(PDOException $e) {
        echo "supplier status exists or error: " . $e->getMessage() . "\n";
    }

    // Add more medicines
    $stmt = $conn->query("SELECT id FROM suppliers LIMIT 1");
    $supplier_id = $stmt->fetchColumn();
    if ($supplier_id) {
        $meds = [
            ['name' => 'Panadol', 'category' => 'Painkiller', 'qty' => 500, 'price' => 2.50, 'expiry' => '2027-05-01'],
            ['name' => 'Augmentin', 'category' => 'Antibiotic', 'qty' => 100, 'price' => 15.00, 'expiry' => '2026-11-20'],
            ['name' => 'Brufen', 'category' => 'Painkiller', 'qty' => 300, 'price' => 4.00, 'expiry' => '2025-08-15'],
            ['name' => 'Disprin', 'category' => 'Painkiller', 'qty' => 8, 'price' => 1.50, 'expiry' => '2025-01-10'], // Low stock
            ['name' => 'Rigix', 'category' => 'Allergy', 'qty' => 150, 'price' => 3.20, 'expiry' => '2026-03-12'],
            ['name' => 'Paracetamol', 'category' => 'Painkiller', 'qty' => 1000, 'price' => 1.00, 'expiry' => '2028-01-01'],
            ['name' => 'Omeprazole', 'category' => 'Stomach', 'qty' => 250, 'price' => 6.50, 'expiry' => '2027-07-22'],
            ['name' => 'Cefspan', 'category' => 'Antibiotic', 'qty' => 5, 'price' => 12.00, 'expiry' => '2024-05-15'], // Low stock + expired
            ['name' => 'Flagyl', 'category' => 'Antibiotic', 'qty' => 400, 'price' => 5.50, 'expiry' => '2026-09-30'],
            ['name' => 'Vitamin D', 'category' => 'Supplement', 'qty' => 600, 'price' => 8.00, 'expiry' => '2027-12-31'],
            ['name' => 'Insulin', 'category' => 'Diabetic', 'qty' => 50, 'price' => 25.00, 'expiry' => '2025-06-01'],
            ['name' => 'Aspirin', 'category' => 'Painkiller', 'qty' => 200, 'price' => 3.00, 'expiry' => '2026-04-10']
        ];
        
        $insert = $conn->prepare("INSERT INTO medicines (name, category, supplier_id, quantity, price, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
        
        // check if panadol exists
        $check = $conn->query("SELECT id FROM medicines WHERE name = 'Panadol'");
        if ($check->rowCount() == 0) {
            foreach ($meds as $m) {
                $insert->execute([$m['name'], $m['category'], $supplier_id, $m['qty'], $m['price'], $m['expiry']]);
            }
            echo "Added realistic medicines.\n";
        }
    }

} catch (PDOException $e) {
    echo "DB Update Error: " . $e->getMessage() . "\n";
}
