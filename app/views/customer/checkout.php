<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Checkout</h1>
            <p>Complete your order.</p>
        </div>

        <div style="display: flex; gap: 30px;">
            <!-- Checkout Form -->
            <div style="flex-grow: 1;">
                <!-- Shipping Address -->
                <div class="chart-card" style="padding: 25px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 20px; color: var(--accent-color); border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">1. Shipping Address</h3>
                    <div id="addressList" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <!-- Populated by JS -->
                    </div>
                    <button class="btn-primary" style="margin-top: 15px; background: rgba(255,255,255,0.1); border: 1px solid var(--glass-border-light); color: var(--text-primary);" onclick="window.location.href='profile.php'">+ Add New Address</button>
                </div>

                <!-- Payment Method -->
                <div class="chart-card" style="padding: 25px;">
                    <h3 style="margin-bottom: 20px; color: var(--accent-color); border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">2. Payment Method</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--glass-border-light); border-radius: 8px; cursor: pointer;" onclick="selectPayment('Card')">
                            <input type="radio" name="payment_method" value="Card" checked>
                            <i class="fas fa-credit-card" style="font-size: 1.5rem; color: #8B5CF6;"></i>
                            <div>
                                <div style="font-weight: bold;">Credit / Debit Card</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Pay securely now</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--glass-border-light); border-radius: 8px; cursor: pointer;" onclick="selectPayment('COD')">
                            <input type="radio" name="payment_method" value="COD">
                            <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--success-color);"></i>
                            <div>
                                <div style="font-weight: bold;">Cash on Delivery (COD)</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">Pay when you receive the order</div>
                            </div>
                        </label>
                    </div>

                    <!-- Simulated Card Form -->
                    <div id="cardForm" style="margin-top: 20px; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border-light);">
                        <h4 style="margin-bottom: 15px;">Card Details</h4>
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" class="form-control" placeholder="0000 0000 0000 0000">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM/YY">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="password" class="form-control" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div style="width: 350px; flex-shrink: 0;">
                <div class="chart-card" style="padding: 25px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">Order Summary</h3>
                    
                    <div id="checkoutItems" style="margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
                        <!-- Items -->
                    </div>

                    <hr style="border: 0; height: 1px; background: var(--glass-border-light); margin: 20px 0;">

                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-secondary);">Subtotal</span>
                        <span style="font-weight: bold;">$<span id="summarySubtotal">0.00</span></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-secondary);">Delivery Fee</span>
                        <span style="font-weight: bold;">$5.00</span>
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

                    <button class="submit-btn" id="placeOrderBtn" style="width: 100%; text-align: center; font-size: 1.1rem; padding: 15px;" onclick="placeOrder()">
                        Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedAddressId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadAddresses();
    loadCartSummary();
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

function selectPayment(method) {
    document.getElementById('cardForm').style.display = method === 'Card' ? 'block' : 'none';
}

async function loadAddresses() {
    try {
        const res = await fetch('../../api/customer/profile_api.php?action=get_addresses');
        const result = await res.json();
        const container = document.getElementById('addressList');
        
        if(!result.success || result.data.length === 0) {
            container.innerHTML = `<p style="color: var(--error-color);">No addresses found. Please add an address to checkout.</p>`;
            document.getElementById('placeOrderBtn').disabled = true;
            document.getElementById('placeOrderBtn').style.opacity = '0.5';
            return;
        }

        container.innerHTML = '';
        result.data.forEach((addr, index) => {
            if (addr.default_flag == 1 || index === 0) selectedAddressId = addr.id;
            
            const isSelected = selectedAddressId === addr.id;
            const border = isSelected ? 'border: 2px solid var(--accent-color);' : 'border: 1px solid var(--glass-border-light);';
            
            container.innerHTML += `
                <div style="padding: 15px; border-radius: 8px; cursor: pointer; ${border}" onclick="selectAddress(${addr.id})">
                    <div style="font-weight: bold; margin-bottom: 5px;"><i class="fas fa-map-marker-alt" style="color: var(--accent-color);"></i> ${addr.type.toUpperCase()}</div>
                    <div style="font-size: 0.9rem; margin-bottom: 5px;">${addr.full_name} - ${addr.phone_number}</div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">${addr.full_address}, ${addr.city} - ${addr.postal_code}</div>
                </div>
            `;
        });
    } catch(err) {
        console.error(err);
    }
}

function selectAddress(id) {
    selectedAddressId = id;
    loadAddresses(); // Re-render to update border
}

async function loadCartSummary() {
    try {
        const res = await fetch('../../api/customer/cart_api.php?action=get_cart');
        const result = await res.json();
        
        if(!result.success || result.data.length === 0) {
            window.location.href = 'cart.php';
            return;
        }

        let subtotal = 0;
        const container = document.getElementById('checkoutItems');
        container.innerHTML = '';

        result.data.forEach(item => {
            const lineTotal = item.quantity * item.price;
            subtotal += lineTotal;
            container.innerHTML += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem;">
                    <div>${item.quantity}x ${item.medicine_name}</div>
                    <div style="font-weight: bold;">$${lineTotal.toFixed(2)}</div>
                </div>
            `;
        });
        
        const tax = subtotal * 0.05;
        const total = subtotal + 5.00 + tax;

        document.getElementById('summarySubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('summaryTax').textContent = tax.toFixed(2);
        document.getElementById('summaryTotal').textContent = total.toFixed(2);
        
    } catch(err) {}
}

async function placeOrder() {
    if(!selectedAddressId) {
        showToast('Please select a shipping address', 'error');
        return;
    }

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const btn = document.getElementById('placeOrderBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    try {
        const res = await fetch('../../api/customer/checkout_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'process_checkout',
                shipping_address_id: selectedAddressId,
                payment_method: paymentMethod
            })
        });
        
        const result = await res.json();
        
        if(result.success) {
            showToast('Order placed successfully!');
            setTimeout(() => {
                window.location.href = 'orders.php';
            }, 1500);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Place Order';
        }
    } catch(err) {
        showToast('Network error processing checkout', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Place Order';
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
