<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Manage Pharmacists</h1>
                <p>Add, edit, and assign branches to pharmacists.</p>
            </div>
            <button class="btn-primary" onclick="openPharmModal()"><i class="fas fa-plus"></i> Add Pharmacist</button>
        </div>

        <div class="table-container">
            <div class="table-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="pharmSearch" placeholder="Search by name or email...">
                </div>
            </div>

            <table class="data-table" id="pharmTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Assigned Branch</th>
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

<!-- Add/Edit Pharmacist Modal -->
<div class="modal-overlay" id="pharmModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Pharmacist</h2>
            <button class="close-modal" onclick="closePharmModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="pharmForm">
            <input type="hidden" id="pharmId" name="id">
            <input type="hidden" name="role" value="pharmacist">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="pharmFullName" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="pharmEmail" name="email" class="form-control" required>
            </div>

            <div class="form-group" id="passwordGroup">
                <label>Password (Leave blank to keep current)</label>
                <div class="password-wrapper">
                    <input type="password" id="pharmPassword" name="password" class="form-control" required>
                    <button type="button" class="password-toggle" id="togglePasswordBtn">
                        <i class="fas fa-eye-slash" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Assign Branch</label>
                <input type="text" id="pharmBranch" name="branch" class="form-control" placeholder="e.g. Main Street Branch">
            </div>

            <div class="form-group">
                <label>Status</label>
                <div class="switch-wrapper">
                    <label class="switch">
                        <input type="checkbox" id="pharmStatusToggle" value="active" checked>
                        <span class="slider"></span>
                    </label>
                    <span id="statusLabelText">Active</span>
                    <input type="hidden" id="pharmStatus" name="status" value="active">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closePharmModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="savePharmBtn"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadPharmacists);

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

// UI Toggles
document.getElementById('togglePasswordBtn').addEventListener('click', function() {
    const pwdInput = document.getElementById('pharmPassword');
    const icon = document.getElementById('togglePasswordIcon');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        pwdInput.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
});

document.getElementById('pharmStatusToggle').addEventListener('change', function() {
    const text = document.getElementById('statusLabelText');
    const hidden = document.getElementById('pharmStatus');
    if (this.checked) {
        text.textContent = 'Active';
        hidden.value = 'active';
    } else {
        text.textContent = 'Inactive';
        hidden.value = 'inactive';
    }
});

async function loadPharmacists() {
    try {
        const response = await fetch('../../api/admin/user_crud.php?action=read');
        const result = await response.json();
        
        if (result.success) {
            // Filter to show only pharmacists
            const pharmacists = result.data.filter(u => u.role === 'pharmacist');
            renderPharmacists(pharmacists);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to load pharmacists', 'error');
    }
}

function renderPharmacists(pharmacists) {
    const tbody = document.querySelector('#pharmTable tbody');
    tbody.innerHTML = '';
    
    pharmacists.forEach(pharm => {
        const statusBadge = pharm.status === 'active' 
            ? `<span class="badge-status badge-active">Active</span>`
            : `<span class="badge-status badge-inactive">Inactive</span>`;
            
        const branchDisplay = pharm.branch ? `<i class="fas fa-store-alt"></i> ${pharm.branch}` : `<span style="color:var(--text-secondary)">Unassigned</span>`;
            
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${pharm.id}</td>
            <td><i class="fas fa-user-md" style="color: var(--accent-color); margin-right: 5px;"></i> ${pharm.full_name}</td>
            <td>${pharm.email}</td>
            <td>${branchDisplay}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon edit" onclick='editPharm(${JSON.stringify(pharm).replace(/'/g, "&apos;")})' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deletePharm(${pharm.id})" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('pharmSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#pharmTable tbody tr');
    
    rows.forEach(row => {
        const name = row.children[1].textContent.toLowerCase();
        const email = row.children[2].textContent.toLowerCase();
        row.style.display = (name.includes(searchTerm) || email.includes(searchTerm)) ? '' : 'none';
    });
});

function openPharmModal() {
    document.getElementById('pharmForm').reset();
    document.getElementById('pharmId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Pharmacist';
    document.getElementById('passwordGroup').querySelector('label').textContent = 'Password';
    document.getElementById('pharmPassword').required = true;
    
    document.getElementById('pharmStatusToggle').checked = true;
    document.getElementById('statusLabelText').textContent = 'Active';
    document.getElementById('pharmStatus').value = 'active';
    
    document.getElementById('pharmModal').classList.add('active');
}

function closePharmModal() {
    document.getElementById('pharmModal').classList.remove('active');
}

function editPharm(pharm) {
    document.getElementById('modalTitle').textContent = 'Edit Pharmacist';
    document.getElementById('pharmId').value = pharm.id;
    document.getElementById('pharmFullName').value = pharm.full_name;
    document.getElementById('pharmEmail').value = pharm.email;
    document.getElementById('pharmBranch').value = pharm.branch || '';
    
    const toggle = document.getElementById('pharmStatusToggle');
    const text = document.getElementById('statusLabelText');
    const hidden = document.getElementById('pharmStatus');
    if (pharm.status === 'active') {
        toggle.checked = true; text.textContent = 'Active'; hidden.value = 'active';
    } else {
        toggle.checked = false; text.textContent = 'Inactive'; hidden.value = 'inactive';
    }
    
    document.getElementById('pharmPassword').type = 'password';
    document.getElementById('togglePasswordIcon').classList.replace('fa-eye', 'fa-eye-slash');
    document.getElementById('passwordGroup').querySelector('label').textContent = 'Password (Leave blank to keep current)';
    document.getElementById('pharmPassword').required = false;
    document.getElementById('pharmPassword').value = '';
    
    document.getElementById('pharmModal').classList.add('active');
}

document.getElementById('pharmForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('pharmId').value;
    const action = id ? 'update' : 'create';
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.action = action;
    
    const btn = document.getElementById('savePharmBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    try {
        const response = await fetch('../../api/admin/user_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            closePharmModal();
            loadPharmacists();
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

async function deletePharm(id) {
    if (!confirm('Delete this pharmacist?')) return;
    try {
        const response = await fetch('../../api/admin/user_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadPharmacists();
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
