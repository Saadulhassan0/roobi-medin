<?php require_once '../layouts/header.php'; ?>
<?php require_once '../layouts/sidebar.php'; ?>

<!-- Inline style to override margin for customer since sidebar is hidden -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
<style>
    .main-wrapper { margin-left: 0 !important; width: 100% !important; }
</style>
<?php endif; ?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area" style="padding: 30px;">
        <!-- Horizontal Filter Bar -->
        <div class="chart-card" style="padding: 15px 25px; margin-bottom: 30px; display: flex; gap: 20px; align-items: center; justify-content: space-between;">
            <div style="display: flex; gap: 20px; align-items: center;">
                <span style="font-weight: bold; color: var(--accent-color);"><i class="fas fa-filter"></i> Filters:</span>
                
                <select id="shopCategory" class="form-control" style="width: 200px; padding: 8px 15px;">
                    <option value="">All Categories</option>
                    <option value="Painkiller">Painkiller</option>
                    <option value="Antibiotic">Antibiotic</option>
                    <option value="Supplement">Supplement</option>
                    <option value="Others">Others</option>
                </select>

                <select id="shopAvailability" class="form-control" style="width: 200px; padding: 8px 15px;">
                    <option value="">Any Availability</option>
                    <option value="in_stock">In Stock Only</option>
                </select>
            </div>
            <div style="color: var(--text-secondary); font-size: 0.9rem;">
                Showing <span id="resultCount">0</span> medicines
            </div>
        </div>

        <!-- Products Grid (Full Width) -->
        <div class="widgets-grid" id="productsList" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Check for URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search');
    if (initialSearch) {
        const searchInput = document.getElementById('globalShopSearch');
        if (searchInput) searchInput.value = initialSearch;
    }
    
    loadMedicines();
    
    // Bind to the global search bar in the topbar
    const globalSearch = document.getElementById('globalShopSearch');
    if (globalSearch) {
        globalSearch.addEventListener('input', loadMedicines);
    }
    
    document.getElementById('shopCategory').addEventListener('change', loadMedicines);
    document.getElementById('shopAvailability').addEventListener('change', loadMedicines);
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

async function loadMedicines() {
    try {
        const searchInput = document.getElementById('globalShopSearch');
        const search = searchInput ? searchInput.value : '';
        const category = document.getElementById('shopCategory').value;
        const availability = document.getElementById('shopAvailability').value;
        
        const res = await fetch(`../../api/customer/shop_api.php?action=get_medicines&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`);
        const result = await res.json();
        
        if(result.success) {
            let data = result.data;
            // Client-side filter for availability
            if (availability === 'in_stock') {
                data = data.filter(med => med.stock > 0);
            }
            renderProducts(data, 'productsList');
        }
    } catch(err) {
        console.error(err);
    }
}

function renderProducts(medicines, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    document.getElementById('resultCount').textContent = medicines.length;
    
    if(medicines.length === 0) {
        container.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: var(--text-secondary);">
            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No medicines found</h3>
            <p>Try adjusting your search or filters.</p>
        </div>`;
        return;
    }

    medicines.forEach(med => {
        const outOfStock = med.stock == 0;
        
        container.innerHTML += `
            <div class="chart-card" style="padding: 25px; text-align: center; position: relative; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                ${med.expiry_date ? '<span class="badge-status badge-active" style="position: absolute; top: 15px; left: 15px; font-size: 0.7rem;"><i class="fas fa-shield-alt"></i> Expiry Safe</span>' : ''}
                <button class="btn-icon edit" style="position: absolute; top: 15px; right: 15px; color: #F59E0B; border: none; background: rgba(245, 158, 11, 0.1); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;" onclick="addToWishlist(${med.id})" title="Add to Wishlist">
                    <i class="far fa-heart"></i>
                </button>
                <div style="font-size: 4rem; color: var(--accent-color); margin: 30px 0;"><i class="fas fa-pills"></i></div>
                <h3 style="margin-bottom: 5px; font-size: 1.2rem;">${med.name}</h3>
                <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.9rem;">${med.category}</p>
                <p style="color: var(--accent-color); font-weight: bold; font-size: 1.5rem; margin-bottom: 15px;">$${med.price}</p>
                <p style="margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; ${outOfStock ? 'color: var(--error-color);' : 'color: var(--success-color);'}">
                    ${outOfStock ? '<i class="fas fa-times-circle"></i> Out of Stock' : `<i class="fas fa-check-circle"></i> In Stock (${med.stock})`}
                </p>
                <button class="submit-btn" style="width: 100%; padding: 12px; font-size: 1rem; ${outOfStock ? 'opacity: 0.5; cursor: not-allowed;' : ''}" onclick="addToCart(${med.id})" ${outOfStock ? 'disabled' : ''}>
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        `;
    });
}

async function addToWishlist(medicine_id) {
    try {
        const res = await fetch('../../api/customer/profile_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'add_wishlist', medicine_id})
        });
        const result = await res.json();
        if(result.success) showToast(result.message);
        else showToast(result.message, 'error');
    } catch(err) {
        showToast('Error', 'error');
    }
}

async function addToCart(medicine_id) {
    try {
        const res = await fetch('../../api/customer/cart_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'add', medicine_id, quantity: 1})
        });
        const result = await res.json();
        if(result.success) {
            showToast('Added to cart!');
            if (typeof updateGlobalCartBadge === 'function') {
                updateGlobalCartBadge();
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch(err) {
        showToast('Error', 'error');
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
