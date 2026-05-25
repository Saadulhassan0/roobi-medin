<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Delivery History</h1>
            <p>Log of all completed or rejected purchase orders.</p>
        </div>

        <div class="table-container">
            <table class="data-table" id="historyTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Pharmacy / Admin</th>
                        <th>Items Supplied</th>
                        <th>Delivered On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadHistory);

async function loadHistory() {
    try {
        const response = await fetch('../../api/supplier/orders_api.php?action=history');
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.querySelector('#historyTable tbody');
            tbody.innerHTML = '';
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No history available.</td></tr>';
                return;
            }

            result.data.forEach(po => {
                let badgeClass = po.status === 'Delivered' ? 'badge-active' : 'badge-inactive';
                let style = po.status === 'Rejected' ? 'background: rgba(239, 68, 68, 0.1); color: var(--error-color); border: 1px solid var(--error-color);' : '';
                
                let itemsList = po.items.map(i => `${i.quantity_requested}x ${i.medicine_name}`).join(', ');
                if (itemsList.length > 50) itemsList = itemsList.substring(0, 50) + '...';

                let dateDisplay = po.status === 'Delivered' ? po.delivered_at : po.created_at;

                tbody.innerHTML += `
                    <tr>
                        <td><strong>#PO-${String(po.id).padStart(4, '0')}</strong></td>
                        <td>${po.admin_name || 'Admin'}</td>
                        <td title="${po.items.map(i => i.medicine_name).join(', ')}">${itemsList}</td>
                        <td>${dateDisplay || 'N/A'}</td>
                        <td><span class="badge-status ${badgeClass}" style="${style}">${po.status}</span></td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error(error);
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
