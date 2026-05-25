<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Shopping Cart</h1>
                <p>Review your items before checkout.</p>
            </div>
            <a href="dashboard.php" class="btn-primary" style="background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--glass-border-light); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>

        <div style="display: flex; gap: 30px;">
            <!-- Cart Items -->
            <div style="flex-grow: 1;">
                <div class="chart-card" style="padding: 20px;">
                    <table class="data-table" id="cartTable">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div style="width: 350px; flex-shrink: 0;">
                <div class="chart-card" style="padding: 25px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">Order Summary</h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-secondary);">Subtotal (<span id="summaryItems">0</span> items)</span>
                        <span style="font-weight: bold;">$<span id="summarySubtotal">0.00</span></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-secondary);">Delivery Fee</span>
                        <span style="font-weight: bold;">$<span id="summaryDelivery">5.00</span></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-secondary);">Estimated Tax (5%)</span>
                        <span style="font-weight: bold;">$<span id="summaryTax">0.00</span></span>
                    </div>
                    
                    <hr style="border: 0; height: 1px; background: var(--glass-border-light); margin: 20px 0;">
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 25px;">
                        <span style="font-size: 1.2rem; font-weight: bold; color: var(--accent-color);">Total</span>
                        <span style="font-size: 1.2rem; font-weight: bold; color: var(--accent-color);">$<span id="summaryTotal">0.00</span></span>
                    </div>

                    <a href="checkout.php" class="submit-btn" id="checkoutBtn" style="width: 100%; text-align: center; text-decoration: none; display: block; box-sizing: border-box;">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadCart);

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

async function loadCart() {
    try {
        const res = await fetch('../../api/customer/cart_api.php?action=get_cart');
        const result = await res.json();
        
        const tbody = document.querySelector('#cartTable tbody');
        tbody.innerHTML = '';
        
        if(!result.success || result.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px;">Your cart is empty. <br><br><a href="dashboard.php" class="btn-primary" style="text-decoration:none; display:inline-block; margin-top:10px;">Start Shopping</a></td></tr>`;
            document.getElementById('checkoutBtn').style.opacity = '0.5';
            document.getElementById('checkoutBtn').style.pointerEvents = 'none';
            updateSummary(0, 0);
            return;
        }

        document.getElementById('checkoutBtn').style.opacity = '1';
        document.getElementById('checkoutBtn').style.pointerEvents = 'auto';

        let subtotal = 0;
        let totalItems = 0;

        result.data.forEach(item => {
            const lineTotal = item.quantity * item.price;
            subtotal += lineTotal;
            totalItems += item.quantity;
            
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div style="font-weight: 600; color: var(--accent-color);">${item.medicine_name}</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">${item.category}</div>
                    </td>
                    <td>$${item.price}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button class="btn-icon" style="width: 25px; height: 25px;" onclick="updateQty(${item.cart_id}, ${item.quantity - 1})">-</button>
                            <span style="font-weight: bold; width: 20px; text-align: center;">${item.quantity}</span>
                            <button class="btn-icon" style="width: 25px; height: 25px;" onclick="updateQty(${item.cart_id}, ${item.quantity + 1})" ${item.quantity >= item.available_stock ? 'disabled style="opacity:0.5"' : ''}>+</button>
                        </div>
                        ${item.quantity >= item.available_stock ? `<div style="font-size: 0.75rem; color: #F59E0B; margin-top: 5px;">Max stock reached</div>` : ''}
                    </td>
                    <td style="font-weight: bold;">$${lineTotal.toFixed(2)}</td>
                    <td>
                        <button class="btn-icon delete" onclick="removeItem(${item.cart_id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });
        
        updateSummary(subtotal, totalItems);
        
    } catch(err) {
        showToast('Error loading cart', 'error');
    }
}

function updateSummary(subtotal, itemsCount) {
    document.getElementById('summaryItems').textContent = itemsCount;
    document.getElementById('summarySubtotal').textContent = subtotal.toFixed(2);
    
    if (subtotal === 0) {
        document.getElementById('summaryDelivery').textContent = '0.00';
        document.getElementById('summaryTax').textContent = '0.00';
        document.getElementById('summaryTotal').textContent = '0.00';
        return;
    }

    const delivery = 5.00;
    const tax = subtotal * 0.05;
    const total = subtotal + delivery + tax;

    document.getElementById('summaryTax').textContent = tax.toFixed(2);
    document.getElementById('summaryTotal').textContent = total.toFixed(2);
}

async function updateQty(cart_id, new_qty) {
    if(new_qty < 1) {
        removeItem(cart_id);
        return;
    }
    try {
        const res = await fetch('../../api/customer/cart_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'update', cart_id, quantity: new_qty})
        });
        const result = await res.json();
        if(result.success) {
            loadCart();
        } else {
            showToast(result.message, 'error');
        }
    } catch(err) {
        showToast('Error updating quantity', 'error');
    }
}

async function removeItem(cart_id) {
    try {
        const res = await fetch('../../api/customer/cart_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'remove', cart_id})
        });
        const result = await res.json();
        if(result.success) {
            showToast('Item removed');
            loadCart();
        }
    } catch(err) {}
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
