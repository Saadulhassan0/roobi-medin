<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
require_once '../../core/Database.php';

$db = new \App\Core\Database();
$conn = $db->getConnection();

// Fetch Dynamic Stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_medicines = $conn->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$total_revenue = $conn->query("SELECT SUM(total_price) FROM sales")->fetchColumn() ?: 0;
$low_stock = $conn->query("SELECT COUNT(*) FROM medicines WHERE quantity < 10")->fetchColumn();
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <p>System Overview & Analytics</p>
        </div>

        <div class="widgets-grid">
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-users"></i></div>
                <div class="widget-info">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo number_format($total_users); ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-pills"></i></div>
                <div class="widget-info">
                    <h3>Total Medicines</h3>
                    <div class="number"><?php echo number_format($total_medicines); ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-chart-line"></i></div>
                <div class="widget-info">
                    <h3>Total Revenue</h3>
                    <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="color: var(--error-color); background: rgba(239, 68, 68, 0.1);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="widget-info">
                    <h3>Low Stock</h3>
                    <div class="number"><?php echo number_format($low_stock); ?></div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Revenue Analytics <button class="icon-btn"><i class="fas fa-ellipsis-h"></i></button></h3>
                <div style="height: 300px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Inventory Distribution <button class="icon-btn"><i class="fas fa-ellipsis-h"></i></button></h3>
                <div style="height: 300px;">
                    <canvas id="secondaryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
