<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<style>
/* Invoice Print Styles */
@media print {
    body * { visibility: hidden; }
    #invoicePrintArea, #invoicePrintArea * { visibility: visible; }
    #invoicePrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: #fff !important;
        color: #000 !important;
        padding: 20mm;
    }
    .print-hide { display: none !important; }
    
    /* Force light mode for print */
    #invoicePrintArea h2 { color: #000 !important; }
    #invoicePrintArea table th { background: #f0f0f0 !important; color: #000 !important; border: 1px solid #ddd; }
    #invoicePrintArea table td { border: 1px solid #ddd; color: #000 !important; }
}

.timeline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 20px 0;
    margin-bottom: 20px;
}
.timeline::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--glass-border-light);
    z-index: 1;
}
.timeline-step {
    position: relative;
    z-index: 2;
    background: var(--bg-primary);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 3px solid var(--glass-border-light);
    color: var(--text-secondary);
}
.timeline-step.active {
    border-color: var(--accent-color);
    background: var(--accent-color);
    color: #000;
}
.timeline-label {
    position: absolute;
    top: 50px;
    white-space: nowrap;
    font-size: 0.85rem;
    color: var(--text-secondary);
    left: 50%;
    transform: translateX(-50%);
}
.timeline-step.active .timeline-label { color: var(--accent-color); font-weight: bold; }
</style>

<div class="main-wrapper print-hide">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>My Orders</h1>
            <p>Track deliveries and download invoices.</p>
        </div>

        <div style="display: flex; gap: 30px;">
            <!-- Order List -->
            <div style="width: 350px; flex-shrink: 0;">
                <div class="chart-card" style="padding: 20px;">
                    <h3 style="margin-bottom: 15px;">Order History</h3>
                    <div id="orderList" style="display: flex; flex-direction: column; gap: 10px; max-height: 600px; overflow-y: auto;">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div style="flex-grow: 1;">
                <div class="chart-card" style="padding: 30px;" id="orderDetailsContainer">
                    <div style="text-align: center; color: var(--text-secondary); padding: 50px;">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>Select an order to view details and tracking.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Invoice Template for Printing -->
<div id="invoicePrintArea" style="display: none; background: white; color: black; font-family: Arial, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px;">
        <div>
            <h1 style="color: #000; margin: 0; font-size: 28px;">MEDIN Pharmacy</h1>
            <p style="color: #555; margin: 5px 0;">Official Tax Invoice</p>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0;">INVOICE #<span id="invOrderId"></span></h3>
            <p style="color: #555; margin: 5px 0;">Date: <span id="invDate"></span></p>
        </div>
    </div>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
        <div>
            <h4 style="margin-bottom: 10px; color: #000;">Billed To:</h4>
            <p style="margin: 0; color: #333;" id="invName"></p>
            <p style="margin: 0; color: #333;" id="invPhone"></p>
            <p style="margin: 0; color: #333; max-width: 250px;" id="invAddress"></p>
        </div>
        <div style="text-align: right;">
            <h4 style="margin-bottom: 10px; color: #000;">Payment Info:</h4>
            <p style="margin: 0; color: #333;">Method: <strong id="invMethod"></strong></p>
            <p style="margin: 0; color: #333;">Status: <strong id="invStatus"></strong></p>
            <p style="margin: 0; color: #333;" id="invTxWrap">Txn Ref: <span id="invTx"></span></p>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Item Description</th>
                <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Qty</th>
                <th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Unit Price</th>
                <th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody id="invItems">
            <!-- Items -->
        </tbody>
    </table>

    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 300px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Subtotal:</span>
                <strong>$<span id="invSubtotal"></span></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Delivery:</span>
                <strong>$5.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Tax (5%):</span>
                <strong>$<span id="invTax"></span></strong>
            </div>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 1.2rem;">
                <strong>Grand Total:</strong>
                <strong>$<span id="invTotal"></span></strong>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadOrders);

async function loadOrders() {
    try {
        const res = await fetch('../../api/customer/orders_api.php?action=get_orders');
        const result = await res.json();
        const container = document.getElementById('orderList');
        container.innerHTML = '';
        
        if(!result.success || result.data.length === 0) {
            container.innerHTML = `<p style="color: var(--text-secondary);">No orders found.</p>`;
            return;
        }

        result.data.forEach(order => {
            let statusColor = 'var(--text-secondary)';
            if(order.status === 'Confirmed' || order.status === 'Packed') statusColor = 'var(--accent-color)';
            if(order.status === 'Shipped' || order.status === 'Delivered') statusColor = 'var(--success-color)';
            
            container.innerHTML += `
                <div style="padding: 15px; border: 1px solid var(--glass-border-light); border-radius: 8px; cursor: pointer; transition: 0.3s;" onclick="loadOrderDetails(${order.id})" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-weight: bold;">Order #${order.id}</span>
                        <span style="color: ${statusColor}; font-size: 0.9rem; font-weight: bold;">${order.status}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary);">
                        <span>${order.created_at.split(' ')[0]}</span>
                        <span>$${order.total_amount}</span>
                    </div>
                </div>
            `;
        });
    } catch(err) {
        console.error(err);
    }
}

async function loadOrderDetails(id) {
    try {
        const res = await fetch(`../../api/customer/orders_api.php?action=get_order_details&order_id=${id}`);
        const result = await res.json();
        
        if(!result.success) return;
        
        const order = result.order;
        const items = result.items;
        
        // Prepare tracking timeline
        const stages = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered'];
        let currentIndex = stages.indexOf(order.status);
        if(currentIndex === -1) currentIndex = 0; // fallback

        let timelineHTML = `<div class="timeline" style="margin: 40px 20px;">`;
        stages.forEach((stage, index) => {
            const isActive = index <= currentIndex ? 'active' : '';
            const icon = index === 0 ? 'fa-clock' : (index === 1 ? 'fa-check' : (index === 2 ? 'fa-box' : (index === 3 ? 'fa-truck' : 'fa-home')));
            timelineHTML += `
                <div class="timeline-step ${isActive}">
                    <i class="fas ${icon}"></i>
                    <div class="timeline-label">${stage}</div>
                </div>
            `;
        });
        timelineHTML += `</div>`;

        // Render UI
        let itemsHTML = '';
        items.forEach(item => {
            const lineTotal = item.quantity * item.price_snapshot;
            itemsHTML += `
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--glass-border-light);">
                    <div>
                        <div style="font-weight: bold;">${item.medicine_name_snapshot}</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Qty: ${item.quantity} × $${item.price_snapshot}</div>
                    </div>
                    <div style="font-weight: bold;">$${lineTotal.toFixed(2)}</div>
                </div>
            `;
        });

        document.getElementById('orderDetailsContainer').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Order #${order.id}</h2>
                <button class="submit-btn" style="padding: 10px 20px;" onclick="printInvoice(${order.id})"><i class="fas fa-file-pdf"></i> Download Invoice</button>
            </div>
            
            ${timelineHTML}
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 40px;">
                <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; border: 1px solid var(--glass-border-light);">
                    <h4 style="margin-bottom: 15px; color: var(--accent-color);">Shipping Details</h4>
                    <p style="margin-bottom: 5px;"><strong>${order.full_name || 'N/A'}</strong></p>
                    <p style="margin-bottom: 5px; color: var(--text-secondary);">${order.full_address || 'N/A'}</p>
                    <p style="color: var(--text-secondary);">${order.city || ''} - ${order.postal_code || ''}</p>
                </div>
                <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; border: 1px solid var(--glass-border-light);">
                    <h4 style="margin-bottom: 15px; color: var(--accent-color);">Payment Summary</h4>
                    <p style="margin-bottom: 5px;">Method: <strong>${order.payment_method}</strong></p>
                    <p style="margin-bottom: 5px;">Status: <strong style="${order.payment_status === 'Success' ? 'color: var(--success-color);' : 'color: #F59E0B;'}">${order.payment_status}</strong></p>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Ref: ${order.transaction_reference}</p>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h4 style="margin-bottom: 15px; color: var(--accent-color);">Items Ordered</h4>
                ${itemsHTML}
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <h3 style="color: var(--accent-color);">Total: $${order.total_amount}</h3>
                </div>
            </div>
        `;

        // Populate hidden invoice template for printing
        populateInvoiceData(order, items);

    } catch(err) {
        console.error(err);
    }
}

function populateInvoiceData(order, items) {
    document.getElementById('invOrderId').textContent = order.id;
    document.getElementById('invDate').textContent = order.created_at.split(' ')[0];
    document.getElementById('invName').textContent = order.full_name || 'N/A';
    document.getElementById('invPhone').textContent = order.phone_number || 'N/A';
    document.getElementById('invAddress').textContent = `${order.full_address || ''}, ${order.city || ''} - ${order.postal_code || ''}`;
    
    document.getElementById('invMethod').textContent = order.payment_method;
    document.getElementById('invStatus').textContent = order.payment_status;
    if(order.transaction_reference) {
        document.getElementById('invTx').textContent = order.transaction_reference;
        document.getElementById('invTxWrap').style.display = 'block';
    } else {
        document.getElementById('invTxWrap').style.display = 'none';
    }

    let invItemsHTML = '';
    let subtotal = 0;
    items.forEach(item => {
        const lineTotal = item.quantity * item.price_snapshot;
        subtotal += lineTotal;
        invItemsHTML += `
            <tr>
                <td style="padding: 12px; border: 1px solid #ddd;">${item.medicine_name_snapshot}</td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">${item.quantity}</td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">$${item.price_snapshot}</td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">$${lineTotal.toFixed(2)}</td>
            </tr>
        `;
    });
    
    document.getElementById('invItems').innerHTML = invItemsHTML;
    document.getElementById('invSubtotal').textContent = subtotal.toFixed(2);
    const tax = subtotal * 0.05;
    document.getElementById('invTax').textContent = tax.toFixed(2);
    document.getElementById('invTotal').textContent = order.total_amount;
}

function printInvoice(orderId) {
    document.getElementById('invoicePrintArea').style.display = 'block';
    window.print();
    // After printing dialog closes
    setTimeout(() => {
        document.getElementById('invoicePrintArea').style.display = 'none';
    }, 1000);
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
