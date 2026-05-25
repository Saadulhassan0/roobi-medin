<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Purchase Orders (B2B)</h1>
                <p>Request stock directly from suppliers.</p>
            </div>
            <button class="btn-primary" onclick="openPOModal()"><i class="fas fa-file-invoice"></i> Create New PO</button>
        </div>

        <div class="table-container">
            <table class="data-table" id="poTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Supplier</th>
                        <th>Items Requested</th>
                        <th>Date Sent</th>
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

<!-- Create PO Modal -->
<div class="modal-overlay" id="poModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2>Create Purchase Order</h2>
            <button class="close-modal" onclick="closePOModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="poForm">
                <div class="form-group">
                    <label>Select Supplier <span style="color:var(--error-color)">*</span></label>
                    <select id="supplierSelect" class="form-control" required>
                        <option value="">Loading...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Add Medicines</label>
                    <div style="display: flex; gap: 10px;">
                        <select id="medicineSelect" class="form-control" style="flex: 2;">
                            <option value="">Select Medicine</option>
                        </select>
                        <input type="number" id="medicineQty" class="form-control" placeholder="Qty" min="1" style="flex: 1;">
                        <button type="button" class="btn-secondary" onclick="addMedicineToPO()">Add</button>
                    </div>
                </div>

                <div id="poItemsList" style="margin: 15px 0; max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;">
                    <p style="color: var(--text-secondary); text-align: center; margin: 10px 0;">No items added yet.</p>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Due Date</label>
                        <input type="date" id="poDueDate" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Delivery Location</label>
                        <input type="text" id="poLocation" class="form-control" placeholder="e.g. Main Branch, Warehouse A">
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes / Instructions (Optional)</label>
                    <textarea id="poNotes" class="form-control" rows="3" placeholder="e.g. Urgent delivery needed"></textarea>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closePOModal()" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn-primary" id="savePOBtn">Send Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chat Modal Wrapper (Common component we'll inject via JS or just structure here) -->
<div class="modal-overlay" id="chatModal">
    <div class="modal-content" style="max-width: 500px; padding: 0; display: flex; flex-direction: column; height: 600px;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid var(--glass-border-light);">
            <h2 id="chatTitle">Order Chat</h2>
            <button class="close-modal" onclick="closeChatModal()"><i class="fas fa-times"></i></button>
        </div>
        <div id="chatMessages" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px;">
            <!-- Messages go here -->
        </div>
        <div style="padding: 20px; border-top: 1px solid var(--glass-border-light); background: rgba(0,0,0,0.2);">
            <div style="display: flex; gap: 10px;">
                <input type="text" id="chatInput" class="form-control" placeholder="Type a message..." style="flex: 1;" onkeypress="if(event.key === 'Enter') sendMessage()">
                <button class="btn-primary" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
let allMedicines = [];
let poCart = [];
let currentChatPO = null;

document.addEventListener('DOMContentLoaded', () => {
    loadPOs();
    loadSuppliersAndMedicines();
});

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

async function loadPOs() {
    try {
        const response = await fetch('../../api/admin/po_api.php?action=read');
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.querySelector('#poTable tbody');
            tbody.innerHTML = '';
            
            result.data.forEach(po => {
                let badgeClass = '';
                if (po.status === 'Pending') badgeClass = 'badge-inactive';
                else if (po.status === 'Accepted' || po.status === 'Shipped') badgeClass = 'badge-status'; 
                else if (po.status === 'Delivered') badgeClass = 'badge-active';
                else if (po.status === 'Rejected') badgeClass = 'badge-inactive'; 

                let style = '';
                if (po.status === 'Accepted' || po.status === 'Shipped') style = 'background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border: 1px solid #0ea5e9;';
                if (po.status === 'Rejected') style = 'background: rgba(239, 68, 68, 0.1); color: var(--error-color); border: 1px solid var(--error-color);';

                tbody.innerHTML += `
                    <tr>
                        <td><strong>#PO-${String(po.id).padStart(4, '0')}</strong></td>
                        <td>${po.supplier_name}</td>
                        <td>${po.item_count} items</td>
                        <td>${po.created_at}</td>
                        <td><span class="badge-status ${badgeClass}" style="${style}">${po.status === 'Shipped' ? 'Awaiting Confirmation' : po.status}</span></td>
                        <td style="display: flex; gap: 5px;">
                            <button class="btn-chat" onclick="openChatModal(${po.id})">
                                <i class="fas fa-comment-dots"></i> Chat
                            </button>
                            ${po.status === 'Shipped' ? `
                            <button class="btn-primary" style="background: var(--success-color); padding: 5px 10px;" onclick="approveDelivery(${po.id})"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn-primary" style="background: var(--error-color); padding: 5px 10px;" onclick="rejectDelivery(${po.id})"><i class="fas fa-times"></i> Reject</button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error(error);
    }
}

async function approveDelivery(po_id) {
    if(!confirm("Are you sure you want to approve this delivery? The inventory will be updated automatically.")) return;
    try {
        const response = await fetch('../../api/admin/po_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'approve_delivery', po_id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadPOs();
        } else {
            showToast(result.message, 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
}

async function rejectDelivery(po_id) {
    if(!confirm("Are you sure you want to reject this delivery?")) return;
    try {
        const response = await fetch('../../api/admin/po_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject_delivery', po_id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadPOs();
        } else {
            showToast(result.message, 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
}

async function loadSuppliersAndMedicines() {
    try {
        // Fetch from the correct Admin APIs we just created
        const suppRes = await fetch('../../api/admin/po_api.php?action=get_suppliers');
        const suppResult = await suppRes.json();
        if (suppResult.success) {
            const select = document.getElementById('supplierSelect');
            select.innerHTML = '<option value="">Select Supplier</option>';
            suppResult.data.forEach(s => {
                select.innerHTML += `<option value="${s.id}">${s.company_name}</option>`;
            });
        }

        const medRes = await fetch('../../api/admin/po_api.php?action=get_medicines');
        const medResult = await medRes.json();
        if (medResult.success) {
            allMedicines = medResult.data;
            const mSelect = document.getElementById('medicineSelect');
            mSelect.innerHTML = '<option value="">Select Medicine</option>';
            allMedicines.forEach(m => {
                mSelect.innerHTML += `<option value="${m.id}">${m.name} (Stock: ${m.quantity})</option>`;
            });
        }
    } catch (e) {
        console.error("Error loading dropdowns");
    }
}

function openPOModal() {
    poCart = [];
    renderPOCart();
    document.getElementById('poForm').reset();
    document.getElementById('poModal').classList.add('active');
}

function closePOModal() {
    document.getElementById('poModal').classList.remove('active');
}

function addMedicineToPO() {
    const mSelect = document.getElementById('medicineSelect');
    const qInput = document.getElementById('medicineQty');
    
    const id = mSelect.value;
    if (!id) {
        showToast('Please select a medicine', 'error');
        return;
    }
    
    const name = mSelect.options[mSelect.selectedIndex].text.split(' (')[0];
    const qty = parseInt(qInput.value);

    if (!qty || qty <= 0) {
        showToast('Please enter a valid quantity (> 0)', 'error');
        return;
    }

    const existing = poCart.find(i => i.id == id);
    if (existing) {
        existing.qty += qty;
    } else {
        poCart.push({ id, name, qty });
    }
    
    mSelect.value = '';
    qInput.value = '';
    renderPOCart();
}

function removeMedicineFromPO(index) {
    poCart.splice(index, 1);
    renderPOCart();
}

function renderPOCart() {
    const list = document.getElementById('poItemsList');
    if (poCart.length === 0) {
        list.innerHTML = '<p style="color: var(--text-secondary); text-align: center; margin: 10px 0;">No items added yet.</p>';
        return;
    }
    
    list.innerHTML = '';
    poCart.forEach((item, index) => {
        list.innerHTML += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span>${item.name}</span>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-weight: bold;">Qty: ${item.qty}</span>
                    <button type="button" class="btn-icon" style="color: var(--error-color);" onclick="removeMedicineFromPO(${index})"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
    });
}

document.getElementById('poForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (poCart.length === 0) {
        showToast('Cannot submit empty medicine list', 'error');
        return;
    }

    const supplier_id = document.getElementById('supplierSelect').value;
    if (!supplier_id) {
        showToast('Please select a supplier', 'error');
        return;
    }

    const notes = document.getElementById('poNotes').value;
    const due_date = document.getElementById('poDueDate').value;
    const delivery_location = document.getElementById('poLocation').value;
    
    const btn = document.getElementById('savePOBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;

    try {
        const response = await fetch('../../api/admin/po_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', supplier_id, notes, due_date, delivery_location, items: poCart })
        });
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closePOModal();
            loadPOs();
        } else {
            showToast(result.message, 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    } finally {
        btn.innerHTML = 'Send Purchase Order';
        btn.disabled = false;
    }
});

// Chat System Functions
function openChatModal(poId) {
    currentChatPO = poId;
    document.getElementById('chatTitle').innerText = `Order #PO-${String(poId).padStart(4, '0')} Chat`;
    document.getElementById('chatModal').classList.add('active');
    loadChatMessages();
}

function closeChatModal() {
    document.getElementById('chatModal').classList.remove('active');
    currentChatPO = null;
}

async function loadChatMessages() {
    if (!currentChatPO) return;
    
    try {
        const res = await fetch(`../../api/shared/chat_api.php?action=get&po_id=${currentChatPO}`);
        const result = await res.json();
        
        const container = document.getElementById('chatMessages');
        container.innerHTML = '';
        
        if (result.success) {
            if (result.data.length === 0) {
                container.innerHTML = `<p style="text-align:center; color: var(--text-secondary); margin-top: auto; margin-bottom: auto;">No messages yet. Start the conversation!</p>`;
                return;
            }

            result.data.forEach(msg => {
                const isMe = msg.sender_role === 'admin';
                const align = isMe ? 'flex-end' : 'flex-start';
                const bg = isMe ? 'var(--accent-color)' : 'rgba(255,255,255,0.1)';
                const color = isMe ? '#000' : '#fff';
                const senderName = isMe ? 'You' : 'Supplier';

                container.innerHTML += `
                    <div style="display: flex; flex-direction: column; align-items: ${align}; margin-bottom: 10px;">
                        <span style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 3px;">${senderName} • ${msg.created_at}</span>
                        <div style="background: ${bg}; color: ${color}; padding: 10px 15px; border-radius: 12px; max-width: 80%; word-wrap: break-word;">
                            ${msg.message}
                        </div>
                    </div>
                `;
            });
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) {
        console.error(e);
    }
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg || !currentChatPO) return;

    try {
        input.value = '';
        const res = await fetch('../../api/shared/chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', po_id: currentChatPO, message: msg })
        });
        const result = await res.json();
        if (result.success) {
            loadChatMessages();
        } else {
            showToast('Failed to send message', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
}

// Optional: Auto refresh chat if modal is open
setInterval(() => {
    if (document.getElementById('chatModal').classList.contains('active')) {
        loadChatMessages();
    }
}, 5000);

</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
