<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>System Alerts</h1>
                <p>Critical inventory warnings requiring immediate attention.</p>
            </div>
            <button class="btn-primary" onclick="loadAlerts()"><i class="fas fa-sync-alt"></i> Refresh Alerts</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- Expiry Alerts -->
            <div class="table-container" style="padding: 20px;">
                <h3 style="color: var(--error-color); margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-hourglass-end"></i> Expiry Alerts
                </h3>
                
                <div id="expiredAlertsList" style="margin-bottom: 20px;">
                    <!-- Expired items -->
                </div>
                
                <div id="expiringAlertsList">
                    <!-- Expiring items -->
                </div>
            </div>

            <!-- Stock Alerts -->
            <div class="table-container" style="padding: 20px;">
                <h3 style="color: #F59E0B; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-box-open"></i> Stock Alerts
                </h3>
                
                <div id="outOfStockList" style="margin-bottom: 20px;">
                    <!-- Out of stock items -->
                </div>

                <div id="lowStockList">
                    <!-- Low stock items -->
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadAlerts);

async function loadAlerts() {
    try {
        const res = await fetch('../../api/pharmacist/alerts_api.php');
        const result = await res.json();
        
        if (result.success) {
            renderAlerts('expiredAlertsList', result.data.expired, 'danger');
            renderAlerts('expiringAlertsList', result.data.expiring, 'warning');
            
            renderAlerts('outOfStockList', result.data.out_of_stock, 'danger');
            renderAlerts('lowStockList', result.data.low_stock, 'warning');
        }
    } catch (e) {
        console.error("Failed to load alerts");
    }
}

function renderAlerts(elementId, items, styleType) {
    const container = document.getElementById(elementId);
    container.innerHTML = '';
    
    if (items.length === 0) {
        container.innerHTML = `<p style="color: var(--text-secondary); font-size: 0.9rem; font-style: italic;">No alerts in this category.</p>`;
        return;
    }
    
    items.forEach(item => {
        const div = document.createElement('div');
        
        let bg = styleType === 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)';
        let border = styleType === 'danger' ? 'var(--error-color)' : '#F59E0B';
        let icon = styleType === 'danger' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
        
        let details = '';
        if (item.quantity !== undefined) {
            details = `Current Stock: <strong>${item.quantity}</strong>`;
        } else if (item.expiry_date !== undefined) {
            details = `Date: <strong>${item.expiry_date}</strong>`;
        }

        div.style = `
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px; margin-bottom: 10px; border-radius: 8px;
            background: ${bg}; border-left: 4px solid ${border};
        `;
        
        div.innerHTML = `
            <div>
                <h4 style="margin: 0 0 5px 0; display: flex; align-items: center; gap: 8px; color: ${border};">
                    <i class="fas ${icon}"></i> ${item.type}
                </h4>
                <div style="font-size: 0.95rem;"><strong>${item.name}</strong> (${item.category})</div>
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px;">${details}</div>
            </div>
            <a href="inventory.php" class="btn-secondary" style="padding: 8px 12px; font-size: 0.85rem;">View</a>
        `;
        container.appendChild(div);
    });
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
