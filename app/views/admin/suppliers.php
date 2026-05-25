<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Manage Suppliers</h1>
                <p>Add, edit, and control supplier contacts.</p>
            </div>
            <button class="btn-primary" onclick="openSupplierModal()"><i class="fas fa-plus"></i> Add Supplier</button>
        </div>

        <div class="table-container">
            <div class="table-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="supplierSearch" placeholder="Search suppliers...">
                </div>
            </div>

            <table class="data-table" id="suppliersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
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

<!-- Add/Edit Supplier Modal -->
<div class="modal-overlay" id="supplierModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Supplier</h2>
            <button class="close-modal" onclick="closeSupplierModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="supplierForm">
            <input type="hidden" id="supplierId" name="id">
            
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" id="companyName" name="company_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" id="contactPerson" name="contact_person" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="supplierEmail" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="supplierPhone" name="phone" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" id="supplierAddress" name="address" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <div class="switch-wrapper">
                    <label class="switch">
                        <input type="checkbox" id="supplierStatusToggle" value="active" checked>
                        <span class="slider"></span>
                    </label>
                    <span id="statusLabelText">Active</span>
                    <input type="hidden" id="supplierStatus" name="status" value="active">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="saveSupplierBtn"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadSuppliers);

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

document.getElementById('supplierStatusToggle').addEventListener('change', function() {
    const text = document.getElementById('statusLabelText');
    const hidden = document.getElementById('supplierStatus');
    if (this.checked) {
        text.textContent = 'Active';
        hidden.value = 'active';
    } else {
        text.textContent = 'Inactive';
        hidden.value = 'inactive';
    }
});

async function loadSuppliers() {
    try {
        const response = await fetch('../../api/admin/supplier_crud.php?action=read');
        const result = await response.json();
        if (result.success) renderSuppliers(result.data);
        else showToast(result.message, 'error');
    } catch (error) {
        showToast('Failed to load suppliers', 'error');
    }
}

function renderSuppliers(suppliers) {
    const tbody = document.querySelector('#suppliersTable tbody');
    tbody.innerHTML = '';
    
    suppliers.forEach(supp => {
        const statusBadge = supp.status === 'active' 
            ? `<span class="badge-status badge-active">Active</span>`
            : `<span class="badge-status badge-inactive">Inactive</span>`;
            
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${supp.id}</td>
            <td>${supp.company_name}</td>
            <td>${supp.contact_person}</td>
            <td>${supp.email}</td>
            <td>${supp.phone}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon edit" onclick='editSupplier(${JSON.stringify(supp).replace(/'/g, "&apos;")})' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteSupplier(${supp.id})" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('supplierSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#suppliersTable tbody tr');
    
    rows.forEach(row => {
        const company = row.children[1].textContent.toLowerCase();
        const contact = row.children[2].textContent.toLowerCase();
        const email = row.children[3].textContent.toLowerCase();
        row.style.display = (company.includes(searchTerm) || contact.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none';
    });
});

function openSupplierModal() {
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    
    document.getElementById('supplierStatusToggle').checked = true;
    document.getElementById('statusLabelText').textContent = 'Active';
    document.getElementById('supplierStatus').value = 'active';
    
    document.getElementById('supplierModal').classList.add('active');
}

function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('active');
}

function editSupplier(supp) {
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    document.getElementById('supplierId').value = supp.id;
    document.getElementById('companyName').value = supp.company_name;
    document.getElementById('contactPerson').value = supp.contact_person;
    document.getElementById('supplierEmail').value = supp.email;
    document.getElementById('supplierPhone').value = supp.phone;
    document.getElementById('supplierAddress').value = supp.address;
    
    const toggle = document.getElementById('supplierStatusToggle');
    const text = document.getElementById('statusLabelText');
    const hidden = document.getElementById('supplierStatus');
    if (supp.status === 'active') {
        toggle.checked = true; text.textContent = 'Active'; hidden.value = 'active';
    } else {
        toggle.checked = false; text.textContent = 'Inactive'; hidden.value = 'inactive';
    }
    
    document.getElementById('supplierModal').classList.add('active');
}

document.getElementById('supplierForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('supplierId').value;
    const action = id ? 'update' : 'create';
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.action = action;
    
    const btn = document.getElementById('saveSupplierBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    try {
        const response = await fetch('../../api/admin/supplier_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            closeSupplierModal();
            loadSuppliers();
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

async function deleteSupplier(id) {
    if (!confirm('Delete this supplier?')) return;
    try {
        const response = await fetch('../../api/admin/supplier_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadSuppliers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Network error occurred.', 'error');
    }
}
</script>

<?php require_once '../layouts/ai_assistant.php'; ?>
<?php require_once '../layouts/footer.php'; ?>
