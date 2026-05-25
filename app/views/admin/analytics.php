<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
require_once '../../core/Database.php';

$db = new \App\Core\Database();
$conn = $db->getConnection();

// Fetch expanded stats
$total_customers = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$total_revenue = $conn->query("SELECT SUM(total_price) FROM sales")->fetchColumn() ?: 0;
$total_sold_items = $conn->query("SELECT SUM(quantity) FROM sales")->fetchColumn() ?: 0;
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Analytics Overview</h1>
            <p>Deep dive into your pharmacy's performance metrics.</p>
        </div>

        <div class="widgets-grid" style="margin-bottom: 30px;">
            <div class="widget-card">
                <div class="widget-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);"><i class="fas fa-dollar-sign"></i></div>
                <div class="widget-info">
                    <h3>Total Revenue</h3>
                    <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6;"><i class="fas fa-shopping-cart"></i></div>
                <div class="widget-info">
                    <h3>Items Sold</h3>
                    <div class="number"><?php echo number_format($total_sold_items); ?></div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-users"></i></div>
                <div class="widget-info">
                    <h3>Registered Customers</h3>
                    <div class="number"><?php echo number_format($total_customers); ?></div>
                </div>
            </div>
        </div>

        <div class="charts-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div class="chart-card">
                <h3>Revenue Trend (Last 7 Days)</h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Inventory Distribution</h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Selling Medicines Table -->
        <div class="table-container" style="margin-top: 30px;">
            <h3>Top Selling Medicines</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Total Sold</th>
                        <th>Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $top = $conn->query("
                            SELECT m.name, m.category, SUM(s.quantity) as total_qty, SUM(s.total_price) as total_rev 
                            FROM sales s 
                            JOIN medicines m ON s.medicine_id = m.id 
                            GROUP BY m.id 
                            ORDER BY total_qty DESC LIMIT 5
                        ");
                        if ($top->rowCount() > 0) {
                            while($row = $top->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>
                                        <td style='font-weight:600;'>{$row['name']}</td>
                                        <td>{$row['category']}</td>
                                        <td>{$row['total_qty']} units</td>
                                        <td>$" . number_format($row['total_rev'], 2) . "</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center;'>No sales data available.</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    fetch('../../api/admin/analytics.php')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Revenue Trend Chart
                new Chart(document.getElementById('revenueTrendChart'), {
                    type: 'line',
                    data: {
                        labels: data.revenue.labels,
                        datasets: [{
                            label: 'Revenue ($)',
                            data: data.revenue.data,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.7)' } },
                            x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.7)' } }
                        }
                    }
                });

                // Category Chart
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.inventory.labels,
                        datasets: [{
                            data: data.inventory.data,
                            backgroundColor: ['#3B82F6', '#22D3EE', '#10B981', '#F59E0B', '#8B5CF6'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: 'rgba(255, 255, 255, 0.7)' } }
                        },
                        cutout: '75%'
                    }
                });
            }
        });
});
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
