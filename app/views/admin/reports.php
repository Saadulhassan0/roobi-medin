<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
require_once '../../core/Database.php';

$db = new \App\Core\Database();
$conn = $db->getConnection();

$report_type = $_GET['type'] ?? 'sales';

// Fetch Data based on report type
$report_title = "Sales Report";
$columns = [];
$data = [];

if ($report_type === 'inventory') {
    $report_title = "Inventory Report";
    $columns = ['ID', 'Medicine Name', 'Category', 'Quantity', 'Price', 'Expiry Date'];
    $stmt = $conn->query("SELECT id, name, category, quantity, price, expiry_date FROM medicines ORDER BY name ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($report_type === 'expiry') {
    $report_title = "Expiry Report";
    $columns = ['Medicine', 'Category', 'Quantity', 'Expiry Date', 'Status'];
    $stmt = $conn->query("
        SELECT name, category, quantity, expiry_date, 'Expired' as status
        FROM medicines 
        WHERE expiry_date < CURDATE()
        ORDER BY expiry_date ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($report_type === 'low_stock') {
    $report_title = "Low Stock Report";
    $columns = ['Medicine', 'Category', 'Quantity', 'Expiry Date', 'Status'];
    $stmt = $conn->query("
        SELECT name, category, quantity, expiry_date, 'Low Stock' as status
        FROM medicines 
        WHERE quantity < 10
        ORDER BY quantity ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Default: Sales
    $report_title = "Sales Report";
    $columns = ['Sale ID', 'Medicine', 'Quantity Sold', 'Total Price', 'Date'];
    $stmt = $conn->query("
        SELECT s.id, m.name as medicine, s.quantity, s.total_price, s.sale_date 
        FROM sales s 
        JOIN medicines m ON s.medicine_id = m.id 
        ORDER BY s.sale_date DESC LIMIT 100
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>System Reports</h1>
                <p>Generate, view, and export pharmacy reports.</p>
            </div>
            <div>
                <button class="btn-primary" onclick="window.print()"><i class="fas fa-file-pdf"></i> Export / Print PDF</button>
            </div>
        </div>
        
        <p class="print-only" style="display:none; text-align:center; color:#666;">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>

        <div class="table-container">
            <div class="table-controls">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <label>Select Report Type:</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="sales" <?= $report_type == 'sales' ? 'selected' : '' ?>>Sales Report</option>
                        <option value="inventory" <?= $report_type == 'inventory' ? 'selected' : '' ?>>Inventory Report</option>
                        <option value="expiry" <?= $report_type == 'expiry' ? 'selected' : '' ?>>Expiry Report</option>
                        <option value="low_stock" <?= $report_type == 'low_stock' ? 'selected' : '' ?>>Low Stock Report</option>
                    </select>
                </form>
            </div>

            <h2 style="margin-bottom: 20px; color: var(--accent-color);"><?php echo $report_title; ?></h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach($columns as $col): ?>
                            <th><?php echo $col; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data)): ?>
                        <tr><td colspan="<?php echo count($columns); ?>" style="text-align:center;">No data found for this report.</td></tr>
                    <?php else: ?>
                        <?php foreach($data as $row): ?>
                            <tr>
                                <?php foreach($row as $key => $val): ?>
                                    <td>
                                        <?php 
                                            if ($key === 'price' || $key === 'total_price') echo '$' . number_format($val, 2);
                                            elseif ($key === 'status') {
                                                if ($val == 'Expired') echo "<span style='color:red; font-weight:bold;'>Expired</span>";
                                                elseif ($val == 'Low Stock') echo "<span style='color:orange; font-weight:bold;'>Low Stock</span>";
                                                else echo $val;
                                            }
                                            else echo htmlspecialchars($val); 
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
