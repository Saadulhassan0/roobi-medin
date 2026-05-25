<?php
require_once 'app/core/Database.php';

$database = new \App\Core\Database();
$conn = $database->getConnection();

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Suppliers Table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Suppliers table created/exists.\n";

    // Create Medicines Table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS medicines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            supplier_id INT,
            quantity INT NOT NULL DEFAULT 0,
            price DECIMAL(10,2) NOT NULL,
            expiry_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        )
    ");
    echo "Medicines table created/exists.\n";

    // Create Sales Table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            medicine_id INT,
            quantity INT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE SET NULL
        )
    ");
    echo "Sales table created/exists.\n";

    // Insert some mock data for testing analytics (if empty)
    $stmt = $conn->query("SELECT COUNT(*) FROM medicines");
    if ($stmt->fetchColumn() == 0) {
        $conn->exec("INSERT INTO suppliers (company_name, contact_person, email, phone, address) VALUES 
            ('MedSupply Co', 'John Doe', 'supply@medsupply.com', '1234567890', '123 Main St')");
        $supplier_id = $conn->lastInsertId();

        $conn->exec("INSERT INTO medicines (name, category, supplier_id, quantity, price, expiry_date) VALUES 
            ('Paracetamol', 'Painkiller', $supplier_id, 500, 5.00, '2026-12-31'),
            ('Amoxicillin', 'Antibiotic', $supplier_id, 5, 12.50, '2024-01-01'), -- low stock & expired
            ('Vitamin C', 'Supplement', $supplier_id, 200, 8.00, '2025-06-30')");
        
        $medicine_id = $conn->lastInsertId();
        
        // Mock sales
        $conn->exec("INSERT INTO sales (user_id, medicine_id, quantity, total_price) VALUES 
            (NULL, $medicine_id, 2, 16.00),
            (NULL, $medicine_id, 1, 8.00)");
            
        echo "Mock data inserted.\n";
    }

} catch (PDOException $e) {
    echo "DB Creation Error: " . $e->getMessage() . "\n";
}
