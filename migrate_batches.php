<?php
require_once 'app/core/Database.php';

$database = new \App\Core\Database();
$conn = $database->getConnection();

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create medicine_batches table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS medicine_batches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medicine_id INT NOT NULL,
            batch_number VARCHAR(100) NOT NULL,
            supplier_id INT,
            quantity INT NOT NULL DEFAULT 0,
            expiry_date DATE NOT NULL,
            status ENUM('ACTIVE', 'EXPIRED', 'QUARANTINED', 'DISPOSED', 'RETURNED') DEFAULT 'ACTIVE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        )
    ");
    echo "medicine_batches table created/exists.\n";

    // Check if we already migrated
    $stmt = $conn->query("SELECT COUNT(*) FROM medicine_batches");
    if ($stmt->fetchColumn() == 0) {
        // Migrate existing data
        $medicines = $conn->query("SELECT * FROM medicines")->fetchAll(PDO::FETCH_ASSOC);
        
        $insertBatch = $conn->prepare("
            INSERT INTO medicine_batches (medicine_id, batch_number, supplier_id, quantity, expiry_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($medicines as $med) {
            $batch_number = !empty($med['batch_number']) ? $med['batch_number'] : 'LEGACY-' . $med['id'];
            // Determine status
            $status = 'ACTIVE';
            if (strtotime($med['expiry_date']) < time()) {
                $status = 'EXPIRED';
            }
            if ($med['quantity'] > 0) {
                $insertBatch->execute([
                    $med['id'],
                    $batch_number,
                    $med['supplier_id'],
                    $med['quantity'],
                    $med['expiry_date'],
                    $status
                ]);
            }
        }
        echo "Data migrated successfully.\n";
    } else {
        echo "Data already migrated.\n";
    }

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
