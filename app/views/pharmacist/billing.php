<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header">
            <h1>Billing & POS System</h1>
            <p>Generate bills, apply discounts, and manage checkout.</p>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
            
            <!-- Left Panel: Search & Add -->
            <div class="table-container" style="padding: 25px;">
                <h3 style="margin-top: 0;">Search Medicines</h3>
                
                <div class="search-bar" style="width: 100%; max-width: none; margin-bottom: 20px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="posSearch" placeholder="Type medicine name or barcode..." autocomplete="off">
                </div>

                <div id="searchResults" style="display: flex; flex-direction: column; gap: 10px; max-height: 500px; overflow-y: auto; padding-right: 10px;">
                    <p style="color: var(--text-secondary); text-align: center; margin-top: 20px;">Type to search available inventory...</p>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="table-container" style="padding: 25px; position: sticky; top: 100px;">
                <h3 style="margin-top: 0; display: flex; justify-content: space-between;">
                    Current Bill
                    <span id="cartCount" class="badge-status badge-active" style="font-size: 0.8rem;">0 items</span>
                </h3>

                <div id="cartItems" style="min-height: 150px; max-height: 250px; overflow-y: auto; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 10px;">
                    <p style="color: var(--text-secondary); text-align: center; margin-top: 50px;">Cart is empty.</p>
                </div>

                <!-- Extras Form -->
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--glass-border-light);">
                    <div style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.85rem; color: var(--text-secondary);">Discount Type</label>
                            <select id="discountType" class="form-control" style="padding: 8px;">
                                <option value="none">None</option>
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.85rem; color: var(--text-secondary);">Value</label>
                            <input type="number" id="discountVal" class="form-control" style="padding: 8px;" placeholder="0" min="0">
                        </div>
                    </div>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                        <input type="checkbox" id="bagCharge"> Include Plastic Bag ($0.50)
                    </label>
                </div>

                <!-- Totals -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--text-secondary);">
                    <span>Subtotal:</span>
                    <span id="subtotalAmt">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--success-color);">
                    <span>Discount:</span>
                    <span id="discountAmt">-$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--text-secondary);">
                    <span>Bag Charge:</span>
                    <span id="bagAmt">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-secondary);">
                    <span>Tax (5%):</span>
                    <span id="taxAmt">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 1.4rem; font-weight: bold; color: var(--accent-color);">
                    <span>Total:</span>
                    <span id="totalAmt">$0.00</span>
                </div>

                <button class="btn-primary" id="checkoutBtn" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 15px;" disabled>
                    <i class="fas fa-print"></i> Generate Bill
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Receipt Modal Wrapper -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Bill Generated</h2>
            <button class="close-modal" onclick="closeReceiptModal()"><i class="fas fa-times"></i></button>
        </div>
        <div style="text-align: center; padding: 20px;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 15px;"></i>
            <p>Transaction completed successfully. Stock has been updated.</p>
            <div style="margin-top: 25px; display: flex; gap: 15px; justify-content: center;">
                <button class="btn-primary" id="printReceiptBtn"><i class="fas fa-print"></i> Print Receipt</button>
                <button class="btn-secondary" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let searchTimeout = null;

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

// Recalculate totals on extra inputs change
document.getElementById('discountType').addEventListener('change', updateCartUI);
document.getElementById('discountVal').addEventListener('input', updateCartUI);
document.getElementById('bagCharge').addEventListener('change', updateCartUI);

document.getElementById('posSearch').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    if (q.length < 2) {
        document.getElementById('searchResults').innerHTML = '<p style="color: var(--text-secondary); text-align: center; margin-top: 20px;">Type to search available inventory...</p>';
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`../../api/pharmacist/billing_api.php?action=search&q=${encodeURIComponent(q)}`);
            const result = await res.json();
            
            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '';
            
            if (result.success && result.data.length > 0) {
                result.data.forEach(med => {
                    const isExpired = med.status === 'expired';
                    const isOutOfStock = med.status === 'out_of_stock';
                    const isDisabled = isExpired || isOutOfStock;
                    
                    let badge = '';
                    if (isExpired) {
                        badge = `<span class="badge-status badge-inactive" style="font-size: 0.7rem;">Expired</span>`;
                    } else if (isOutOfStock) {
                        badge = `<span class="badge-status badge-inactive" style="font-size: 0.7rem;">Out of Stock</span>`;
                    } else if (med.stock < 10) {
                        badge = `<span class="badge-status" style="background: rgba(245,158,11,0.1); color: #F59E0B; border: 1px solid #F59E0B; font-size: 0.7rem;">Low Stock</span>`;
                    }

                    const div = document.createElement('div');
                    div.style = `display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border-light); border-radius: 12px; opacity: ${isDisabled ? '0.5' : '1'};`;
                    
                    let buttonHTML = isDisabled 
                        ? `<button class="btn-icon" style="background: #333; color: #666; border: none; cursor: not-allowed;" disabled><i class="fas fa-ban"></i></button>`
                        : `<button class="btn-icon" style="background: var(--accent-color); color: #000; border: none;" onclick='addToCart(${JSON.stringify(med).replace(/'/g, "&apos;")})'><i class="fas fa-plus"></i></button>`;

                    div.innerHTML = `
                        <div>
                            <h4 style="margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;">
                                ${med.name} ${badge}
                            </h4>
                            <span style="color: var(--text-secondary); font-size: 0.85rem;">Stock: ${med.stock} | Price: $${med.price} | Exp: ${med.expiry_date}</span>
                        </div>
                        ${buttonHTML}
                    `;
                    resultsDiv.appendChild(div);
                });
            } else {
                resultsDiv.innerHTML = '<p style="color: var(--error-color); text-align: center; margin-top: 20px;">No matching medicines found.</p>';
            }
        } catch (e) {
            console.error(e);
        }
    }, 300);
});

function addToCart(med) {
    if (med.status === 'expired' || med.status === 'out_of_stock') {
        showToast('Cannot add invalid item', 'error');
        return;
    }

    const existing = cart.find(item => item.id === med.id);
    if (existing) {
        if (existing.qty >= med.stock) {
            showToast('Cannot add more than available stock', 'error');
            return;
        }
        existing.qty++;
        existing.total = existing.qty * existing.price;
    } else {
        cart.push({
            id: med.id,
            name: med.name,
            price: parseFloat(med.price),
            stock: parseInt(med.stock),
            qty: 1,
            total: parseFloat(med.price)
        });
    }
    updateCartUI();
}

function updateCartQty(id, change) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    
    if (change > 0 && item.qty >= item.stock) {
        showToast('Maximum stock reached', 'error');
        return;
    }
    
    item.qty += change;
    
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== id);
    } else {
        item.total = item.qty * item.price;
    }
    updateCartUI();
}

function updateCartUI() {
    const cartDiv = document.getElementById('cartItems');
    cartDiv.innerHTML = '';
    
    if (cart.length === 0) {
        cartDiv.innerHTML = '<p style="color: var(--text-secondary); text-align: center; margin-top: 50px;">Cart is empty.</p>';
        resetTotals();
        return;
    }
    
    let subtotal = 0;
    let itemCount = 0;
    
    cart.forEach(item => {
        subtotal += item.total;
        itemCount += item.qty;
        
        const div = document.createElement('div');
        div.style = 'display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);';
        div.innerHTML = `
            <div style="flex: 1;">
                <h5 style="margin: 0; font-size: 0.95rem;">${item.name}</h5>
                <span style="color: var(--text-secondary); font-size: 0.8rem;">$${item.price.toFixed(2)} x ${item.qty}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="display: flex; align-items: center; background: rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
                    <button style="border: none; background: transparent; color: white; padding: 5px 8px; cursor: pointer;" onclick="updateCartQty(${item.id}, -1)">-</button>
                    <span style="padding: 0 5px; font-size: 0.9rem;">${item.qty}</span>
                    <button style="border: none; background: transparent; color: white; padding: 5px 8px; cursor: pointer;" onclick="updateCartQty(${item.id}, 1)">+</button>
                </div>
                <div style="font-weight: bold; width: 60px; text-align: right;">$${item.total.toFixed(2)}</div>
            </div>
        `;
        cartDiv.appendChild(div);
    });
    
    // Apply discount
    const discountType = document.getElementById('discountType').value;
    const discountVal = parseFloat(document.getElementById('discountVal').value) || 0;
    let discountAmt = 0;
    
    if (discountType === 'percent') {
        discountAmt = subtotal * (discountVal / 100);
    } else if (discountType === 'fixed') {
        discountAmt = discountVal;
    }
    if (discountAmt > subtotal) discountAmt = subtotal;

    // Plastic bag
    const bagCharge = document.getElementById('bagCharge').checked ? 0.50 : 0;
    
    const tax = (subtotal - discountAmt) * 0.05;
    const total = (subtotal - discountAmt) + tax + bagCharge;
    
    document.getElementById('cartCount').textContent = `${itemCount} items`;
    document.getElementById('subtotalAmt').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('discountAmt').textContent = `-$${discountAmt.toFixed(2)}`;
    document.getElementById('bagAmt').textContent = `$${bagCharge.toFixed(2)}`;
    document.getElementById('taxAmt').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('totalAmt').textContent = `$${total.toFixed(2)}`;
    document.getElementById('checkoutBtn').disabled = false;
}

function resetTotals() {
    document.getElementById('cartCount').textContent = '0 items';
    document.getElementById('subtotalAmt').textContent = '$0.00';
    document.getElementById('discountAmt').textContent = '-$0.00';
    document.getElementById('bagAmt').textContent = '$0.00';
    document.getElementById('taxAmt').textContent = '$0.00';
    document.getElementById('totalAmt').textContent = '$0.00';
    document.getElementById('checkoutBtn').disabled = true;
}

let generatedBillId = null;

document.getElementById('checkoutBtn').addEventListener('click', async () => {
    if (cart.length === 0) return;
    
    const btn = document.getElementById('checkoutBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    const payload = {
        action: 'process_bill',
        cart: cart,
        discount_type: document.getElementById('discountType').value,
        discount_val: document.getElementById('discountVal').value,
        bag_charge: document.getElementById('bagCharge').checked ? 0.50 : 0
    };
    
    try {
        const response = await fetch('../../api/pharmacist/billing_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        
        if (result.success) {
            generatedBillId = result.bill_id;
            
            // Show Success Modal
            document.getElementById('receiptModal').classList.add('active');
            
            // Clear cart
            cart = [];
            updateCartUI();
            document.getElementById('posSearch').value = '';
            document.getElementById('searchResults').innerHTML = '<p style="color: var(--text-secondary); text-align: center; margin-top: 20px;">Type to search available inventory...</p>';
            
            document.getElementById('discountVal').value = '';
            document.getElementById('bagCharge').checked = false;
            
        } else {
            showToast(result.message, 'error');
        }
    } catch (e) {
        showToast('Network error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.remove('active');
}

document.getElementById('printReceiptBtn').addEventListener('click', () => {
    if (generatedBillId) {
        window.open(`receipt.php?id=${generatedBillId}`, '_blank', 'width=800,height=600');
        closeReceiptModal();
    }
});
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
