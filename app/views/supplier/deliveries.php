<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Delivery Management</h1>
            <p>Track active orders and update delivery status.</p>
        </div>

        <div id="deliveriesContainer" style="display: flex; flex-direction: column; gap: 20px;">
            <p style="color: var(--text-secondary); text-align: center;">Loading deliveries...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadDeliveries);

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

async function loadDeliveries() {
    try {
        const res = await fetch('../../api/supplier/orders_api.php?action=active');
        const result = await res.json();
        
        const container = document.getElementById('deliveriesContainer');
        container.innerHTML = '';
        
        if (result.success && result.data.length > 0) {
            result.data.forEach(order => {
                
                let itemsHtml = '';
                order.items.forEach(i => {
                    itemsHtml += `<div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;">
                        <span>${i.medicine_name}</span>
                        <span style="font-weight: bold;">Qty: ${i.quantity_requested}</span>
                    </div>`;
                });

                let statusBadge = order.status === 'Accepted' 
                    ? '<span class="badge-status" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border: 1px solid #0ea5e9;"><i class="fas fa-check-circle"></i> Accepted</span>'
                    : '<span class="badge-status" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; border: 1px solid #F59E0B;"><i class="fas fa-truck"></i> Shipped</span>';
                    
                let actionBtns = '';
                if (order.status === 'Accepted') {
                    actionBtns = `<button class="btn-primary" style="background: #F59E0B;" onclick="updateStatus(${order.id}, 'Shipped')"><i class="fas fa-truck"></i> Mark as Shipped</button>`;
                } else if (order.status === 'Shipped') {
                    actionBtns = `<span style="color: var(--text-secondary); font-style: italic;"><i class="fas fa-hourglass-half"></i> Awaiting Admin Confirmation</span>`;
                }

                container.innerHTML += `
                    <div class="table-container" style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin: 0 0 5px 0;">Order #PO-${String(order.id).padStart(4, '0')}</h3>
                                <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                    Accepted on: ${order.created_at}
                                </div>
                            </div>
                            ${statusBadge}
                        </div>
                        
                        <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <h4 style="margin: 0 0 10px 0; font-size: 0.9rem; color: var(--text-secondary);">Items to Deliver</h4>
                            ${itemsHtml}
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <button class="btn-chat" onclick="openChatModal(${order.id})">
                                <i class="fas fa-comment-dots"></i> Chat
                            </button>
                            ${actionBtns}
                        </div>
                    </div>
                `;
            });
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px dashed var(--glass-border-light);">
                    <i class="fas fa-truck-loading" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 15px;"></i>
                    <h3 style="margin: 0; color: var(--text-secondary);">No Active Deliveries</h3>
                </div>
            `;
        }
    } catch (e) {
        console.error(e);
        document.getElementById('deliveriesContainer').innerHTML = '<p style="color: var(--error-color);">Error loading deliveries.</p>';
    }
}

async function updateStatus(po_id, status) {
    if (!confirm(`Are you sure you want to mark this order as ${status}?`)) return;
    
    // Warning for shipping
    if (status === 'Shipped') {
        if (!confirm('Marking this as Shipped will send it to the Admin for final confirmation. Proceed?')) return;
    }

    try {
        const res = await fetch('../../api/supplier/orders_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_status', po_id, status })
        });
        const result = await res.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            loadDeliveries();
        } else {
            showToast(result.message, 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

// Chat System Functions
let currentChatPO = null;

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
                const isMe = msg.sender_role === 'supplier';
                const align = isMe ? 'flex-end' : 'flex-start';
                const bg = isMe ? 'var(--accent-color)' : 'rgba(255,255,255,0.1)';
                const color = isMe ? '#000' : '#fff';
                const senderName = isMe ? 'You' : 'Admin';
                container.innerHTML += `
                    <div style="display: flex; flex-direction: column; align-items: ${align}; margin-bottom: 10px;">
                        <span style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 3px;">${senderName} • ${msg.created_at}</span>
                        <div style="background: ${bg}; color: ${color}; padding: 10px 15px; border-radius: 12px; max-width: 80%; word-wrap: break-word;">
                            ${msg.message}
                        </div>
                    </div>
                `;
            });
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) { console.error(e); }
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
        if (result.success) loadChatMessages();
    } catch(e) {}
}

setInterval(() => {
    if (document.getElementById('chatModal') && document.getElementById('chatModal').classList.contains('active')) {
        loadChatMessages();
    }
}, 5000);
</script>

<!-- Chat Modal Wrapper -->
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

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
