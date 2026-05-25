<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Batch-Based Inventory</h1>
                <p>Track active stock, expiries, and quarantined batches.</p>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <button class="btn-primary" id="tab-active" onclick="switchTab('active')" style="background: var(--accent-color);">Active Stock</button>
            <button class="btn-primary" id="tab-expired" onclick="switchTab('expired')" style="background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light);">Expired Stock</button>
            <button class="btn-primary" id="tab-quarantined" onclick="switchTab('quarantined')" style="background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light);">Quarantined</button>
        </div>

        <div class="table-container">
            <div class="table-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="medSearch" placeholder="Search batches...">
                </div>
            </div>

            <table class="data-table" id="batchTable">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Batch No.</th>
                        <th>Supplier</th>
                        <th>Stock Qty</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Actions</th>
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
let allBatches = [];
let currentTab = 'active'; // 'active', 'expired', 'quarantined'

document.addEventListener('DOMContentLoaded', loadBatches);

function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

function switchTab(tab) {
    currentTab = tab;
    // Reset buttons
    document.getElementById('tab-active').style = "background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light);";
    document.getElementById('tab-expired').style = "background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light);";
    document.getElementById('tab-quarantined').style = "background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light);";
    
    // Set active button
    if(tab === 'active') document.getElementById('tab-active').style = "background: var(--accent-color); color: #000;";
    if(tab === 'expired') document.getElementById('tab-expired').style = "background: var(--error-color); color: #fff; border: none;";
    if(tab === 'quarantined') document.getElementById('tab-quarantined').style = "background: #F59E0B; color: #fff; border: none;";
    
    renderBatches();
}

async function loadBatches() {
    try {
        const response = await fetch('../../api/pharmacist/inventory_api.php?action=read');
        const result = await response.json();
        if (result.success) {
            allBatches = result.data;
            renderBatches();
        } else showToast(result.message, 'error');
    } catch (error) {
        showToast('Failed to load inventory batches', 'error');
    }
}

function renderBatches() {
    const tbody = document.querySelector('#batchTable tbody');
    tbody.innerHTML = '';
    
    let filteredBatches = allBatches.filter(b => {
        if(currentTab === 'active') return b.status === 'ACTIVE';
        if(currentTab === 'expired') return b.status === 'EXPIRED';
        if(currentTab === 'quarantined') return b.status === 'QUARANTINED';
        return false;
    });

    const search = document.getElementById('medSearch').value.toLowerCase();
    if(search) {
        filteredBatches = filteredBatches.filter(b => 
            b.medicine_name.toLowerCase().includes(search) || 
            b.batch_number.toLowerCase().includes(search)
        );
    }
    
    if (filteredBatches.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--text-secondary);">No batches found in this section.</td></tr>`;
        return;
    }

    filteredBatches.forEach(batch => {
        let statusBadge = '';
        let actionBtns = '';

        if (batch.status === 'ACTIVE') {
            statusBadge = `<span class="badge-status badge-active">ACTIVE</span>`;
            actionBtns = `
                <button class="btn-icon" style="color: #F59E0B; border-color: #F59E0B;" onclick="updateBatchStatus(${batch.id}, 'QUARANTINED')" title="Quarantine Batch">
                    <i class="fas fa-biohazard"></i>
                </button>
            `;
        } else if (batch.status === 'EXPIRED') {
            statusBadge = `<span class="badge-status badge-inactive">EXPIRED</span>`;
            actionBtns = `
                <button class="btn-icon delete" title="Mark as Waste (Dispose)" onclick="updateBatchStatus(${batch.id}, 'DISPOSED')">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button class="btn-icon edit" title="Return to Supplier" onclick="updateBatchStatus(${batch.id}, 'RETURNED')">
                    <i class="fas fa-undo"></i>
                </button>
            `;
        } else if (batch.status === 'QUARANTINED') {
            statusBadge = `<span class="badge-status" style="background: rgba(245,158,11,0.1); color: #F59E0B; border: 1px solid #F59E0B;">QUARANTINED</span>`;
            actionBtns = `
                <button class="btn-icon" style="color: var(--success-color); border-color: var(--success-color);" onclick="updateBatchStatus(${batch.id}, 'ACTIVE')" title="Restore to Active">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn-icon delete" title="Mark as Waste (Dispose)" onclick="updateBatchStatus(${batch.id}, 'DISPOSED')">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        }
            
        tbody.innerHTML += `
            <tr>
                <td style="font-weight: 600;">${batch.medicine_name}</td>
                <td style="font-family: monospace;">${batch.batch_number}</td>
                <td>${batch.supplier_name || 'N/A'}</td>
                <td style="font-weight: bold; ${batch.quantity == 0 ? 'color: var(--error-color);' : ''}">${batch.quantity}</td>
                <td>${batch.expiry_date}</td>
                <td>${statusBadge}</td>
                <td><div class="action-btns">${actionBtns}</div></td>
            </tr>
        `;
    });
}

document.getElementById('medSearch').addEventListener('input', renderBatches);

async function updateBatchStatus(batch_id, status) {
    if(!confirm(`Are you sure you want to mark this batch as ${status}?`)) return;

    try {
        const res = await fetch('../../api/pharmacist/inventory_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_batch_status', batch_id, status })
        });
        const result = await res.json();
        if(result.success) {
            showToast(result.message, 'success');
            loadBatches();
        } else {
            showToast(result.message, 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
