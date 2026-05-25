<?php require_once '../layouts/header.php'; ?>
<?php require_once '../layouts/sidebar.php'; ?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Supplier Dashboard</h1>
            <p>Supply Chain & Deliveries</p>
        </div>

        <div class="widgets-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="widget-info">
                    <h3>New Requests</h3>
                    <div class="number" id="w_new_requests">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);"><i class="fas fa-truck-loading"></i></div>
                <div class="widget-info">
                    <h3>Pending Delivery</h3>
                    <div class="number" id="w_pending_deliveries">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon" style="color: var(--success-color); background: rgba(16, 185, 129, 0.1);"><i class="fas fa-check-circle"></i></div>
                <div class="widget-info">
                    <h3>Delivered (All Time)</h3>
                    <div class="number" id="w_delivered">...</div>
                </div>
            </div>
            <div class="widget-card">
                <div class="widget-icon"><i class="fas fa-star"></i></div>
                <div class="widget-info">
                    <h3>Quality Rating</h3>
                    <div class="number">4.9</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card" style="grid-column: span 2;">
                <h3>Recent Actions</h3>
                <div id="recentActionsList" style="padding: 20px; color: var(--text-secondary);">
                    Loading recent supply chain activity...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadDashboardStats);

async function loadDashboardStats() {
    try {
        const [reqRes, activeRes, histRes] = await Promise.all([
            fetch('../../api/supplier/orders_api.php?action=requests').then(r => r.json()),
            fetch('../../api/supplier/orders_api.php?action=active').then(r => r.json()),
            fetch('../../api/supplier/orders_api.php?action=history').then(r => r.json())
        ]);
        
        document.getElementById('w_new_requests').innerText = reqRes.success ? reqRes.data.length : 0;
        document.getElementById('w_pending_deliveries').innerText = activeRes.success ? activeRes.data.length : 0;
        
        let deliveredCount = 0;
        if (histRes.success) {
            deliveredCount = histRes.data.filter(i => i.status === 'Delivered').length;
        }
        document.getElementById('w_delivered').innerText = deliveredCount;

        // Populate recent actions
        const actionsContainer = document.getElementById('recentActionsList');
        actionsContainer.innerHTML = '';
        
        let allActivity = [];
        if (reqRes.success) allActivity = [...allActivity, ...reqRes.data];
        if (activeRes.success) allActivity = [...allActivity, ...activeRes.data];
        if (histRes.success) allActivity = [...allActivity, ...histRes.data];
        
        allActivity.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
        
        if (allActivity.length === 0) {
            actionsContainer.innerHTML = 'No recent activity found.';
        } else {
            allActivity.slice(0, 5).forEach(act => {
                let statusColor = 'var(--text-secondary)';
                if(act.status === 'Accepted' || act.status === 'Shipped') statusColor = '#0ea5e9';
                if(act.status === 'Delivered') statusColor = 'var(--success-color)';
                if(act.status === 'Rejected') statusColor = 'var(--error-color)';
                
                actionsContainer.innerHTML += `
                    <div style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between;">
                        <span>Order #PO-${String(act.id).padStart(4, '0')} (Admin: ${act.admin_name || 'System'})</span>
                        <span style="color: ${statusColor}; font-weight: 500;">${act.status}</span>
                    </div>
                `;
            });
        }
    } catch(e) {
        console.error(e);
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
