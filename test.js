
document.addEventListener('DOMContentLoaded', () => {
    loadMedicines();
    loadSuppliersIntoDropdown();
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

async function loadSuppliersIntoDropdown() {
    try {
        const response = await fetch('../../api/admin/inventory_crud.php?action=get_suppliers');
        const result = await response.json();
        if (result.success) {
            const select = document.getElementById('medSupplier');
            select.innerHTML = '<option value="">No Supplier</option>';
            result.data.forEach(s => {
                select.innerHTML += `<option value="${s.id}">${s.company_name}</option>`;
            });
        }
    } catch (e) {
        console.error("Failed to load suppliers for dropdown");
    }
}

async function loadMedicines() {
    try {
        const response = await fetch('../../api/admin/inventory_crud.php?action=read');
        const result = await response.json();
        if (result.success) renderMedicines(result.data);
        else showToast(result.message, 'error');
    } catch (error) {
        showToast('Failed to load inventory', 'error');
    }
}

function renderMedicines(medicines) {
    const tbody = document.querySelector('#medTable tbody');
    tbody.innerHTML = '';
    
    const today = new Date();
    
    medicines.forEach(med => {
        const expDate = new Date(med.expiry_date);
        
        let rowStyle = '';
        let qtyLabel = med.quantity;
        let expLabel = med.expiry_date;
        
        // Low Stock Alert
        if (med.quantity < 10) {
            qtyLabel = `<span style="color: var(--error-color); font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> ${med.quantity}</span>`;
        }
        
        // Expiry Alert
        if (expDate < today) {
            rowStyle = 'background: rgba(239, 68, 68, 0.1);';
            expLabel = `<span style="color: var(--error-color); font-weight: bold;">Expired</span>`;
        }
            
        const tr = document.createElement('tr');
        if(rowStyle) tr.style = rowStyle;
        
        tr.innerHTML = `
            <td>#${med.id}</td>
            <td style="font-weight: 600;">${med.name}</td>
            <td>${med.category}</td>
            <td>${med.supplier_name || 'N/A'}</td>
            <td>${qtyLabel}</td>
            <td>$${med.price}</td>
            <td>${expLabel}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon edit" onclick='editMed(${JSON.stringify(med).replace(/'/g, "&apos;")})' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteMed(${med.id})" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('medSearch').addEventListener('input', filterMeds);
document.getElementById('categoryFilter').addEventListener('change', filterMeds);

function filterMeds() {
    const search = document.getElementById('medSearch').value.toLowerCase();
    const cat = document.getElementById('categoryFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#medTable tbody tr');
    
    rows.forEach(row => {
        const name = row.children[1].textContent.toLowerCase();
        const category = row.children[2].textContent.toLowerCase();
        const expiryLabel = row.children[6].textContent.toLowerCase();
        
        const matchSearch = name.includes(search);
        const matchCat = cat === '' || category === cat;
        const matchExpired = showExpiredOnly ? expiryLabel.includes('expired') : true;
        
        row.style.display = (matchSearch && matchCat && matchExpired) ? '' : 'none';
    });
}

let showExpiredOnly = false;
function toggleExpiredFilter() {
    showExpiredOnly = !showExpiredOnly;
    const extractBtn = document.getElementById('extractExpiredBtn');
    const removeBtn = document.getElementById('removeAllExpiredBtn');
    
    if (showExpiredOnly) {
        extractBtn.style.background = 'var(--error-color)';
        extractBtn.style.color = '#fff';
        removeBtn.style.display = 'inline-block';
    } else {
        extractBtn.style.background = 'transparent';
        extractBtn.style.color = 'var(--error-color)';
        removeBtn.style.display = 'none';
    }
    
    filterMeds();
}

async function removeAllExpired() {
    if (!confirm('Are you sure you want to permanently delete all expired medicines from the website? This action cannot be undone.')) return;
    
    const btn = document.getElementById('removeAllExpiredBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
    btn.disabled = true;
    
    try {
        const response = await fetch('../../api/admin/inventory_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_expired' })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            // Reset filter
            if(showExpiredOnly) toggleExpiredFilter();
            loadMedicines();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Network error occurred.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function openMedModal() {
    document.getElementById('medForm').reset();
    document.getElementById('medId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Medicine';
    document.getElementById('medModal').classList.add('active');
}

function closeMedModal() {
    document.getElementById('medModal').classList.remove('active');
}

function editMed(med) {
    document.getElementById('modalTitle').textContent = 'Edit Medicine';
    document.getElementById('medId').value = med.id;
    document.getElementById('medName').value = med.name;
    document.getElementById('medCategory').value = med.category;
    document.getElementById('medSupplier').value = med.supplier_id || '';
    document.getElementById('medQty').value = med.quantity;
    document.getElementById('medPrice').value = med.price;
    document.getElementById('medExpiry').value = med.expiry_date;
    
    document.getElementById('medModal').classList.add('active');
}

document.getElementById('medForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('medId').value;
    const action = id ? 'update' : 'create';
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.action = action;
    
    const btn = document.getElementById('saveMedBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    try {
        const response = await fetch('../../api/admin/inventory_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            closeMedModal();
            loadMedicines();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Network error occurred.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

async function deleteMed(id) {
    if (!confirm('Delete this medicine? This cannot be undone.')) return;
    try {
        const response = await fetch('../../api/admin/inventory_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadMedicines();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Network error occurred.', 'error');
    }
}
