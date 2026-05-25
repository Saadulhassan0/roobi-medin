<?php require_once '../layouts/header.php'; ?>
<?php require_once '../layouts/sidebar.php'; ?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Pharmacist Dashboard</h1>
            <p>Inventory & Billing Management</p>
        </div>

        <div class="widgets-grid">
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-boxes"></i></div>
                <div class="widget-info">
                    <h3>Total Medicines</h3>
                    <div class="number" id="w_total_medicines">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="widget-info">
                    <h3>Bills Generated (Today)</h3>
                    <div class="number" id="w_bills_generated">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);"><i class="fas fa-hourglass-half"></i></div>
                <div class="widget-info">
                    <h3>Near Expiry (< 30 days)</h3>
                    <div class="number" id="w_near_expiry">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="color: var(--error-color); background: rgba(239, 68, 68, 0.1);"><i class="fas fa-box-open"></i></div>
                <div class="widget-info">
                    <h3>Out of Stock</h3>
                    <div class="number" id="w_out_of_stock">...</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Dispensed Medicines Trend <button class="icon-btn"><i class="fas fa-ellipsis-h"></i></button></h3>
                <div style="height: 300px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Top Categories <button class="icon-btn"><i class="fas fa-ellipsis-h"></i></button></h3>
                <div style="height: 300px;">
                    <canvas id="secondaryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', loadDashboardData);

async function loadDashboardData() {
    try {
        const res = await fetch('../../api/pharmacist/dashboard_api.php?action=stats');
        const result = await res.json();
        
        if (result.success) {
            const d = result.data;
            document.getElementById('w_total_medicines').innerText = d.total_medicines;
            document.getElementById('w_bills_generated').innerText = d.bills_generated;
            document.getElementById('w_near_expiry').innerText = d.near_expiry;
            document.getElementById('w_out_of_stock').innerText = d.out_of_stock;
            
            renderTrendChart(d.sales_trend);
            renderCategoryChart(d.top_categories);
        }
    } catch (e) {
        console.error("Failed to load dashboard data");
    }
}

function renderTrendChart(salesTrend) {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    const labels = salesTrend.map(item => item.date);
    const data = salesTrend.map(item => item.total_qty);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            datasets: [{
                label: 'Items Dispensed',
                data: data.length ? data : [0,0,0,0,0],
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
}

function renderCategoryChart(categories) {
    const ctx = document.getElementById('secondaryChart').getContext('2d');
    
    const labels = categories.map(item => item.category);
    const data = categories.map(item => item.count);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['N/A'],
            datasets: [{
                data: data.length ? data : [1],
                backgroundColor: ['#0ea5e9', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 20 } }
            },
            cutout: '75%'
        }
    });
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
