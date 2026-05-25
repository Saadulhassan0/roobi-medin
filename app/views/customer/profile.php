<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
<style>
    .main-wrapper { margin-left: 0 !important; width: 100% !important; }
    
    .profile-tabs {
        display: flex;
        border-bottom: 1px solid var(--glass-border-light);
        margin-bottom: 30px;
        overflow-x: auto;
    }
    .profile-tab {
        padding: 15px 30px;
        cursor: pointer;
        font-weight: 600;
        color: var(--text-secondary);
        border-bottom: 3px solid transparent;
        transition: 0.3s;
        white-space: nowrap;
    }
    .profile-tab:hover { color: var(--text-primary); }
    .profile-tab.active {
        color: var(--accent-color);
        border-bottom-color: var(--accent-color);
    }
    .profile-section { display: none; }
    .profile-section.active { display: block; animation: fadeIn 0.3s ease-in-out; }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Print Styles for Invoices */
    @media print {
        body * { visibility: hidden; }
        #invoicePrintArea, #invoicePrintArea * { visibility: visible; }
        #invoicePrintArea {
            position: absolute; left: 0; top: 0; width: 100%;
            background: #fff !important; color: #000 !important; padding: 20mm;
        }
        .print-hide { display: none !important; }
        #invoicePrintArea h2 { color: #000 !important; }
        #invoicePrintArea table th { background: #f0f0f0 !important; color: #000 !important; border: 1px solid #ddd; }
        #invoicePrintArea table td { border: 1px solid #ddd; color: #000 !important; }
    }
    
    /* Timeline styles */
    .timeline { display: flex; justify-content: space-between; align-items: center; position: relative; padding: 20px 0; margin-bottom: 20px; }
    .timeline::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 4px; background: var(--glass-border-light); z-index: 1; }
    .timeline-step { position: relative; z-index: 2; background: var(--bg-primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid var(--glass-border-light); color: var(--text-secondary); }
    .timeline-step.active { border-color: var(--accent-color); background: var(--accent-color); color: #000; }
    .timeline-label { position: absolute; top: 50px; white-space: nowrap; font-size: 0.85rem; color: var(--text-secondary); left: 50%; transform: translateX(-50%); }
    .timeline-step.active .timeline-label { color: var(--accent-color); font-weight: bold; }
</style>
<?php endif; ?>

<div class="main-wrapper print-hide">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area" style="padding: 30px; max-width: 1400px; margin: 0 auto;">
        
        <div class="profile-tabs">
            <div class="profile-tab active" onclick="switchProfileTab('cart')" id="tab-cart"><i class="fas fa-shopping-cart"></i> My Cart</div>
            <div class="profile-tab" onclick="switchProfileTab('orders')" id="tab-orders"><i class="fas fa-box-open"></i> My Orders & Bills</div>
            <div class="profile-tab" onclick="switchProfileTab('wishlist')" id="tab-wishlist"><i class="fas fa-heart"></i> Wishlist</div>
            <div class="profile-tab" onclick="switchProfileTab('addresses')" id="tab-addresses"><i class="fas fa-map-marker-alt"></i> My Addresses</div>
        </div>

        <!-- =================== CART & CHECKOUT SECTION =================== -->
        <div id="section-cart" class="profile-section active">
            <div id="cartView">
                <div style="display: flex; gap: 30px;">
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
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div style="width: 350px; flex-shrink: 0;">
                        <div class="chart-card" style="padding: 25px;">
                            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">Cart Summary</h3>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span style="color: var(--text-secondary);">Subtotal (<span id="cartItemsCount">0</span>)</span>
                                <span style="font-weight: bold;">$<span id="cartSubtotal">0.00</span></span>
                            </div>
                            <button class="submit-btn" id="proceedCheckoutBtn" style="width: 100%; margin-top: 15px; padding: 15px; font-size: 1.1rem; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);" onclick="showCheckoutView()">Proceed to Checkout</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="checkoutView" style="display: none;">
                <div style="display: flex; gap: 30px;">
                    <div style="flex-grow: 1;">
                        <!-- Addresses -->
                        <div class="chart-card" style="padding: 25px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">
                                <h3 style="color: var(--accent-color);">1. Shipping Address</h3>
                                <button class="btn-primary" style="padding: 5px 15px; font-size: 0.85rem;" onclick="switchProfileTab('addresses')">Manage Addresses</button>
                            </div>
                            <div id="checkoutAddressList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;"></div>
                        </div>

                        <!-- Payment -->
                        <div class="chart-card" style="padding: 25px;">
                            <h3 style="margin-bottom: 20px; color: var(--accent-color); border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">2. Payment Method</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--accent-color); border-radius: 8px; cursor: pointer; transition: 0.3s;" id="labelCard">
                                    <input type="radio" name="payment_method" value="Card" checked onchange="handlePaymentMethodChange()">
                                    <i class="fas fa-credit-card" style="font-size: 1.5rem; color: #8B5CF6;"></i>
                                    <div>
                                        <div style="font-weight: bold;">Card Payment</div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Secure online payment</div>
                                    </div>
                                </label>
                                <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--glass-border-light); border-radius: 8px; cursor: pointer; transition: 0.3s;" id="labelCOD">
                                    <input type="radio" name="payment_method" value="COD" onchange="handlePaymentMethodChange()">
                                    <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--success-color);"></i>
                                    <div>
                                        <div style="font-weight: bold;">Cash on Delivery</div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Pay at doorstep</div>
                                    </div>
                                </label>
                            </div>
                            <div id="cardForm" style="margin-top: 20px; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border-light);">
                                <h4 style="margin-bottom: 15px;">Card Details (Simulation)</h4>
                                <div class="form-group"><input type="text" id="cardNumber" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19" oninput="formatCardNumber(this); validateCheckoutForm()"></div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group"><input type="text" id="cardExpiry" class="form-control" placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this); validateCheckoutForm()"></div>
                                    <div class="form-group"><input type="password" id="cardCvv" class="form-control" placeholder="CVV" maxlength="3" oninput="this.value=this.value.replace(/\\D/g,''); validateCheckoutForm()"></div>
                                </div>
                                <div id="cardError" style="color: var(--error-color); font-size: 0.85rem; margin-top: 10px; display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Summary -->
                    <div style="width: 350px; flex-shrink: 0;">
                        <div class="chart-card" style="padding: 25px;">
                            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">Order Summary</h3>
                            <div id="checkoutItemsList" style="margin-bottom: 15px; max-height: 150px; overflow-y: auto;"></div>
                            <hr style="border: 0; height: 1px; background: var(--glass-border-light); margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-secondary);">Subtotal</span>
                                <strong>$<span id="chkSubtotal">0.00</span></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-secondary);">Delivery</span>
                                <strong>$5.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-secondary);">Tax (5%)</span>
                                <strong>$<span id="chkTax">0.00</span></strong>
                            </div>
                            <hr style="border: 0; height: 1px; background: var(--glass-border-light); margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 1.2rem;">
                                <strong style="color: var(--accent-color);">Total</strong>
                                <strong style="color: var(--accent-color);">$<span id="chkTotal">0.00</span></strong>
                            </div>
                            <button class="submit-btn" id="placeOrderBtn" style="width: 100%; padding: 15px; font-size: 1.1rem; opacity: 0.5; cursor: not-allowed;" onclick="placeOrder()" disabled>Place Order</button>
                            <button class="btn-primary" style="width: 100%; margin-top: 10px; background: transparent; border: 1px solid var(--glass-border-light); color: var(--text-secondary);" onclick="hideCheckoutView()">Back to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== ORDERS & BILLS SECTION =================== -->
        <div id="section-orders" class="profile-section">
            <div style="display: flex; gap: 30px;">
                <div style="width: 350px; flex-shrink: 0;">
                    <div class="chart-card" style="padding: 20px;">
                        <h3 style="margin-bottom: 15px;">Order History</h3>
                        <div id="orderList" style="display: flex; flex-direction: column; gap: 10px; max-height: 600px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div style="flex-grow: 1;">
                    <div class="chart-card" style="padding: 30px;" id="orderDetailsContainer">
                        <div style="text-align: center; color: var(--text-secondary); padding: 50px;">
                            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                            <p>Select an order to view details and download bills.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== WISHLIST SECTION =================== -->
        <div id="section-wishlist" class="profile-section">
            <div class="widgets-grid" id="wishlistList" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;"></div>
        </div>

        <!-- =================== ADDRESSES SECTION =================== -->
        <div id="section-addresses" class="profile-section">
            <div class="chart-card" style="padding: 30px; margin-bottom: 30px;">
                <h3 style="color: var(--accent-color); margin-bottom: 20px; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">Add / Edit Address</h3>
                <form id="addressForm">
                    <input type="hidden" id="addr_id">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Address Type</label>
                            <select id="addr_type" class="form-control" required>
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px;">Default Address</label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border-light);">
                                <input type="checkbox" id="addr_default"> Set as default shipping address
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="addr_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" id="addr_phone" class="form-control" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Full Address</label>
                            <input type="text" id="addr_full" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" id="addr_city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" id="addr_zip" class="form-control" required>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn-primary" style="background: transparent; border: 1px solid var(--glass-border-light); color: var(--text-primary);" onclick="resetAddressForm()">Clear</button>
                        <button type="submit" class="submit-btn" style="padding: 10px 30px;">Save Address</button>
                    </div>
                </form>
            </div>

            <h3 style="margin-bottom: 15px;">Saved Addresses</h3>
            <div class="widgets-grid" id="addressList" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;"></div>
        </div>

    </div>
</div>

<!-- Hidden Invoice Template -->
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
        <tbody id="invItems"></tbody>
    </table>

    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 300px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;"><span>Subtotal:</span><strong>$<span id="invSubtotal"></span></strong></div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;"><span>Delivery:</span><strong>$5.00</strong></div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;"><span>Tax (5%):</span><strong>$<span id="invTax"></span></strong></div>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 1.2rem;"><strong>Grand Total:</strong><strong>$<span id="invTotal"></span></strong></div>
        </div>
    </div>
</div>

<script>
let selectedCheckoutAddressId = null;

document.addEventListener('DOMContentLoaded', () => {
    // Check hash for direct tab linking
    const hash = window.location.hash.replace('#', '') || 'cart';
    switchProfileTab(hash);
});

function switchProfileTab(tabName) {
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
    
    document.getElementById(`tab-${tabName}`).classList.add('active');
    document.getElementById(`section-${tabName}`).classList.add('active');
    
    window.location.hash = tabName;

    // Load data based on tab
    if(tabName === 'cart') { hideCheckoutView(); loadCart(); }
    if(tabName === 'orders') loadOrders();
    if(tabName === 'wishlist') loadWishlist();
    if(tabName === 'addresses') loadAddresses();
}

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
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);
}

// --- CART LOGIC ---
async function loadCart() {
    try {
        const res = await fetch('../../api/customer/cart_api.php?action=get_cart');
        const result = await res.json();
        const tbody = document.querySelector('#cartTable tbody');
        tbody.innerHTML = '';
        
        if(!result.success || !result.data || result.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px;">Your cart is empty.</td></tr>`;
            document.getElementById('proceedCheckoutBtn').disabled = true;
            document.getElementById('proceedCheckoutBtn').style.opacity = '0.5';
            document.getElementById('cartSubtotal').textContent = '0.00';
            document.getElementById('cartItemsCount').textContent = '0';
            return;
        }

        document.getElementById('proceedCheckoutBtn').disabled = false;
        document.getElementById('proceedCheckoutBtn').style.opacity = '1';

        let subtotal = 0; let totalQty = 0;
        result.data.forEach(item => {
            const lineTotal = item.quantity * item.price;
            subtotal += lineTotal; totalQty += item.quantity;
            tbody.innerHTML += `
                <tr>
                    <td><div style="font-weight: bold; color: var(--accent-color);">${item.medicine_name}</div><div style="font-size: 0.85rem; color: var(--text-secondary);">${item.category}</div></td>
                    <td>$${item.price}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button class="btn-icon" style="width: 25px; height: 25px;" onclick="updateQty(${item.cart_id}, ${item.quantity - 1})">-</button>
                            <span>${item.quantity}</span>
                            <button class="btn-icon" style="width: 25px; height: 25px;" onclick="updateQty(${item.cart_id}, ${item.quantity + 1})" ${item.quantity >= item.available_stock ? 'disabled' : ''}>+</button>
                        </div>
                    </td>
                    <td style="font-weight: bold;">$${lineTotal.toFixed(2)}</td>
                    <td><button class="btn-icon delete" onclick="removeCartItem(${item.cart_id})"><i class="fas fa-trash"></i></button></td>
                </tr>`;
        });
        document.getElementById('cartSubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('cartItemsCount').textContent = totalQty;
        if(typeof updateGlobalCartBadge === 'function') updateGlobalCartBadge();
    } catch(err) {}
}

async function updateQty(cart_id, qty) {
    if(qty < 1) { removeCartItem(cart_id); return; }
    try {
        const res = await fetch('../../api/customer/cart_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'update', cart_id, quantity: qty}) });
        const result = await res.json();
        if(result.success) loadCart(); else showToast(result.message, 'error');
    } catch(err) {}
}

async function removeCartItem(cart_id) {
    try {
        const res = await fetch('../../api/customer/cart_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'remove', cart_id}) });
        const result = await res.json();
        if(result.success) { showToast('Removed'); loadCart(); }
    } catch(err) {}
}

// --- CHECKOUT LOGIC ---
function showCheckoutView() {
    document.getElementById('cartView').style.display = 'none';
    document.getElementById('checkoutView').style.display = 'block';
    handlePaymentMethodChange(); // Init payment state
    loadCheckoutData();
}
function hideCheckoutView() {
    document.getElementById('cartView').style.display = 'block';
    document.getElementById('checkoutView').style.display = 'none';
}

async function loadCheckoutData() {
    try {
        const resA = await fetch('../../api/customer/profile_api.php?action=get_addresses');
        const resultA = await resA.json();
        const addrContainer = document.getElementById('checkoutAddressList');
        if(!resultA.success || resultA.data.length === 0) {
            addrContainer.innerHTML = `<p style="color: var(--error-color);">No addresses found. Please add an address.</p>`;
        } else {
            addrContainer.innerHTML = '';
            resultA.data.forEach((addr, i) => {
                if(addr.default_flag == 1 || i===0) selectedCheckoutAddressId = addr.id;
                const isSelected = selectedCheckoutAddressId === addr.id;
                const border = isSelected ? 'border: 2px solid var(--accent-color);' : 'border: 1px solid var(--glass-border-light);';
                addrContainer.innerHTML += `
                    <div style="padding: 15px; border-radius: 8px; cursor: pointer; ${border}" onclick="selectCheckoutAddr(${addr.id})">
                        <div style="font-weight: bold; margin-bottom: 5px;"><i class="fas fa-map-marker-alt"></i> ${addr.type.toUpperCase()}</div>
                        <div style="font-size: 0.9rem;">${addr.full_address}, ${addr.city} - ${addr.postal_code}</div>
                    </div>`;
            });
        }

        const resC = await fetch('../../api/customer/cart_api.php?action=get_cart');
        const resultC = await resC.json();
        let subtotal = 0;
        const itemsContainer = document.getElementById('checkoutItemsList');
        itemsContainer.innerHTML = '';
        resultC.data.forEach(item => {
            subtotal += item.quantity * item.price;
            itemsContainer.innerHTML += `<div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px;"><span>${item.quantity}x ${item.medicine_name}</span><span>$${(item.quantity * item.price).toFixed(2)}</span></div>`;
        });
        const tax = subtotal * 0.05;
        const total = subtotal + 5.00 + tax;
        document.getElementById('chkSubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('chkTax').textContent = tax.toFixed(2);
        document.getElementById('chkTotal').textContent = total.toFixed(2);
        
        validateCheckoutForm();

    } catch(err) {}
}

function selectCheckoutAddr(id) { 
    selectedCheckoutAddressId = id; 
    loadCheckoutData(); 
}

function handlePaymentMethodChange() {
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    const cardForm = document.getElementById('cardForm');
    const labelCard = document.getElementById('labelCard');
    const labelCOD = document.getElementById('labelCOD');
    
    if(method === 'Card') {
        cardForm.style.display = 'block';
        if(labelCard) labelCard.style.borderColor = 'var(--accent-color)';
        if(labelCOD) labelCOD.style.borderColor = 'var(--glass-border-light)';
    } else {
        cardForm.style.display = 'none';
        if(labelCOD) labelCOD.style.borderColor = 'var(--accent-color)';
        if(labelCard) labelCard.style.borderColor = 'var(--glass-border-light)';
    }
    validateCheckoutForm();
}

function formatCardNumber(input) {
    let val = input.value.replace(/\\s+/g, '').replace(/[^0-9]/gi, '');
    let parts = [];
    for(let i=0; i<val.length; i+=4) { parts.push(val.substring(i, i+4)); }
    input.value = parts.length > 1 ? parts.join(' ') : val;
}

function formatExpiry(input) {
    let val = input.value.replace(/\\D/g, '');
    if(val.length >= 2) { val = val.substring(0,2) + '/' + val.substring(2,4); }
    input.value = val;
}

function validateCheckoutForm() {
    const methodElem = document.querySelector('input[name="payment_method"]:checked');
    if(!methodElem) return;
    const method = methodElem.value;
    const btn = document.getElementById('placeOrderBtn');
    const errorDiv = document.getElementById('cardError');
    let isValid = true;
    let errorMsg = '';

    if(!selectedCheckoutAddressId) { isValid = false; }

    if(method === 'Card') {
        const numElem = document.getElementById('cardNumber');
        const expElem = document.getElementById('cardExpiry');
        const cvvElem = document.getElementById('cardCvv');
        
        if(!numElem || !expElem || !cvvElem) return;
        
        const num = numElem.value.replace(/\\s+/g, '');
        const exp = expElem.value;
        const cvv = cvvElem.value;
        
        if(num.length !== 16 || !/^\d+$/.test(num)) { isValid = false; errorMsg = 'Card number must be 16 digits.'; }
        else if(!/^(0[1-9]|1[0-2])\/\d{2}$/.test(exp)) { isValid = false; errorMsg = 'Invalid Expiry (MM/YY).'; }
        else if(cvv.length !== 3 || !/^\d+$/.test(cvv)) { isValid = false; errorMsg = 'CVV must be 3 digits.'; }
        else {
            const [month, year] = exp.split('/');
            const now = new Date();
            const expDate = new Date(`20${year}`, month - 1);
            if(expDate < now) { isValid = false; errorMsg = 'Card has expired.'; }
        }
    }

    if(!isValid && method === 'Card' && errorMsg !== '') {
        if(errorDiv) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = errorMsg;
        }
    } else {
        if(errorDiv) errorDiv.style.display = 'none';
    }

    if(isValid && selectedCheckoutAddressId) {
        if(btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        }
    } else {
        if(btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        }
    }
}

async function placeOrder() {
    if(!selectedCheckoutAddressId) { showToast('Select a shipping address', 'error'); return; }
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true; btn.innerHTML = 'Processing...';

    try {
        const res = await fetch('../../api/customer/checkout_api.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'process_checkout', shipping_address_id: selectedCheckoutAddressId, payment_method: method })
        });
        const result = await res.json();
        if(result.success) {
            showToast('Order Placed!');
            if(typeof updateGlobalCartBadge === 'function') updateGlobalCartBadge();
            setTimeout(() => switchProfileTab('orders'), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false; btn.innerHTML = 'Place Order';
        }
    } catch(err) { btn.disabled = false; btn.innerHTML = 'Place Order'; }
}

// --- ADDRESSES LOGIC ---
async function loadAddresses() {
    try {
        const res = await fetch('../../api/customer/profile_api.php?action=get_addresses');
        const result = await res.json();
        const container = document.getElementById('addressList');
        container.innerHTML = '';
        if(!result.success || !result.data || result.data.length === 0) { container.innerHTML = `<p>No addresses found.</p>`; return; }
        result.data.forEach(addr => {
            container.innerHTML += `
                <div class="chart-card" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <h3 style="color: var(--accent-color); margin: 0;"><i class="fas fa-map-marker-alt"></i> ${addr.type.toUpperCase()}</h3>
                        ${addr.default_flag == 1 ? '<span class="badge-status badge-active" style="font-size: 0.75rem;">Default</span>' : ''}
                    </div>
                    <p style="font-weight: bold;">${addr.full_name}</p>
                    <p style="color: var(--text-secondary); margin-bottom: 10px;">${addr.phone_number}</p>
                    <p style="color: var(--text-secondary); margin-bottom: 15px;">${addr.full_address}, ${addr.city} - ${addr.postal_code}</p>
                    <button class="btn-primary" style="background: var(--error-color); padding: 5px 10px;" onclick="deleteAddress(${addr.id})"><i class="fas fa-trash"></i> Delete</button>
                </div>`;
        });
    } catch(err) {}
}

document.getElementById('addressForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        action: 'save_address', id: document.getElementById('addr_id').value,
        type: document.getElementById('addr_type').value, full_name: document.getElementById('addr_name').value,
        phone_number: document.getElementById('addr_phone').value, full_address: document.getElementById('addr_full').value,
        city: document.getElementById('addr_city').value, postal_code: document.getElementById('addr_zip').value,
        default_flag: document.getElementById('addr_default').checked ? 1 : 0
    };
    try {
        const res = await fetch('../../api/customer/profile_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        const result = await res.json();
        if(result.success) { showToast('Address saved'); resetAddressForm(); loadAddresses(); }
    } catch(err) {}
});

function resetAddressForm() { document.getElementById('addressForm').reset(); document.getElementById('addr_id').value = ''; }

async function deleteAddress(id) {
    if(!confirm("Delete?")) return;
    try {
        const res = await fetch('../../api/customer/profile_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'delete_address', id}) });
        const result = await res.json();
        if(result.success) { showToast('Deleted'); loadAddresses(); }
    } catch(err) {}
}

// --- WISHLIST LOGIC ---
async function loadWishlist() {
    try {
        const res = await fetch('../../api/customer/profile_api.php?action=get_wishlist');
        const result = await res.json();
        const container = document.getElementById('wishlistList');
        container.innerHTML = '';
        if(!result.success || !result.data || result.data.length === 0) { container.innerHTML = `<p>Your wishlist is empty.</p>`; return; }
        result.data.forEach(item => {
            container.innerHTML += `
                <div class="chart-card" style="padding: 20px; text-align: center;">
                    <h3 style="margin-bottom: 5px;">${item.medicine_name}</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 10px;">${item.category}</p>
                    <p style="color: var(--accent-color); font-weight: bold; font-size: 1.2rem; margin-bottom: 15px;">$${item.price}</p>
                    <div style="display: flex; gap: 10px;">
                        <button class="submit-btn" style="flex: 1;" onclick="wishToCart(${item.medicine_id}, ${item.id})">To Cart</button>
                        <button class="btn-icon delete" onclick="removeWishlist(${item.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
        });
    } catch(err) {}
}

async function removeWishlist(id) {
    try {
        const res = await fetch('../../api/customer/profile_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'remove_wishlist', id}) });
        const result = await res.json();
        if(result.success) { showToast('Removed'); loadWishlist(); }
    } catch(err) {}
}

async function wishToCart(med_id, wish_id) {
    try {
        const res = await fetch('../../api/customer/cart_api.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'add', medicine_id: med_id, quantity: 1}) });
        const result = await res.json();
        if(result.success) { 
            showToast('Moved to cart!'); 
            if(typeof updateGlobalCartBadge === 'function') updateGlobalCartBadge();
            removeWishlist(wish_id); 
        } else { showToast(result.message, 'error'); }
    } catch(err) {}
}

// --- ORDERS LOGIC ---
async function loadOrders() {
    try {
        const res = await fetch('../../api/customer/orders_api.php?action=get_orders');
        const result = await res.json();
        const container = document.getElementById('orderList');
        container.innerHTML = '';
        if(!result.success || !result.data || result.data.length === 0) { container.innerHTML = `<p>No orders found.</p>`; return; }
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
                </div>`;
        });
    } catch(err) {}
}

async function loadOrderDetails(id) {
    try {
        const res = await fetch(`../../api/customer/orders_api.php?action=get_order_details&order_id=${id}`);
        const result = await res.json();
        if(!result.success) return;
        const order = result.order; const items = result.items;
        
        const stages = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered'];
        let currentIndex = stages.indexOf(order.status); if(currentIndex === -1) currentIndex = 0;

        let timelineHTML = `<div class="timeline" style="margin: 40px 20px;">`;
        stages.forEach((stage, index) => {
            const isActive = index <= currentIndex ? 'active' : '';
            const icon = index === 0 ? 'fa-clock' : (index === 1 ? 'fa-check' : (index === 2 ? 'fa-box' : (index === 3 ? 'fa-truck' : 'fa-home')));
            timelineHTML += `<div class="timeline-step ${isActive}"><i class="fas ${icon}"></i><div class="timeline-label">${stage}</div></div>`;
        });
        timelineHTML += `</div>`;

        let itemsHTML = '';
        items.forEach(item => {
            itemsHTML += `
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--glass-border-light);">
                    <div><div style="font-weight: bold;">${item.medicine_name_snapshot}</div><div style="font-size: 0.85rem; color: var(--text-secondary);">Qty: ${item.quantity} × $${item.price_snapshot}</div></div>
                    <div style="font-weight: bold;">$${(item.quantity * item.price_snapshot).toFixed(2)}</div>
                </div>`;
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
            <div style="margin-top: 30px;"><h4 style="margin-bottom: 15px; color: var(--accent-color);">Items Ordered</h4>${itemsHTML}
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;"><h3 style="color: var(--accent-color);">Total: $${order.total_amount}</h3></div>
            </div>`;

        // Populate hidden invoice
        document.getElementById('invOrderId').textContent = order.id;
        document.getElementById('invDate').textContent = order.created_at.split(' ')[0];
        document.getElementById('invName').textContent = order.full_name || 'N/A';
        document.getElementById('invPhone').textContent = order.phone_number || 'N/A';
        document.getElementById('invAddress').textContent = `${order.full_address || ''}, ${order.city || ''} - ${order.postal_code || ''}`;
        document.getElementById('invMethod').textContent = order.payment_method;
        document.getElementById('invStatus').textContent = order.payment_status;
        if(order.transaction_reference) { document.getElementById('invTx').textContent = order.transaction_reference; document.getElementById('invTxWrap').style.display = 'block'; } else { document.getElementById('invTxWrap').style.display = 'none'; }
        
        let invItemsHTML = ''; let subtotal = 0;
        items.forEach(item => {
            const lineTotal = item.quantity * item.price_snapshot; subtotal += lineTotal;
            invItemsHTML += `<tr><td style="padding: 12px; border: 1px solid #ddd;">${item.medicine_name_snapshot}</td><td style="padding: 12px; border: 1px solid #ddd; text-align: center;">${item.quantity}</td><td style="padding: 12px; border: 1px solid #ddd; text-align: right;">$${item.price_snapshot}</td><td style="padding: 12px; border: 1px solid #ddd; text-align: right;">$${lineTotal.toFixed(2)}</td></tr>`;
        });
        document.getElementById('invItems').innerHTML = invItemsHTML;
        document.getElementById('invSubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('invTax').textContent = (subtotal * 0.05).toFixed(2);
        document.getElementById('invTotal').textContent = order.total_amount;

    } catch(err) {}
}

function printInvoice() {
    document.getElementById('invoicePrintArea').style.display = 'block';
    window.print();
    setTimeout(() => { document.getElementById('invoicePrintArea').style.display = 'none'; }, 1000);
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
