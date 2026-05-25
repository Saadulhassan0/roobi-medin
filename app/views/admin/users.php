<?php 
require_once '../layouts/header.php'; 
require_once '../layouts/sidebar.php'; 
?>

<div class="main-wrapper">
    <?php require_once '../layouts/topbar.php'; ?>

    <div class="content-area">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Manage Users</h1>
                <p>Add, edit, and control system access.</p>
            </div>
            <button class="btn-primary" onclick="openUserModal()"><i class="fas fa-plus"></i> Add New User</button>
        </div>

        <div class="table-container">
            <div class="table-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="userSearch" placeholder="Search by name or email...">
                </div>
                <select id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="supplier">Supplier</option>
                    <option value="customer">Customer</option>
                </select>
            </div>

            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
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

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New User</h2>
            <button class="close-modal" onclick="closeUserModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="userForm">
            <input type="hidden" id="userId" name="id">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="userFullName" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="userEmail" name="email" class="form-control" required>
            </div>

            <div class="form-group" id="passwordGroup">
                <label>Password (Leave blank to keep current)</label>
                <div class="password-wrapper">
                    <input type="password" id="userPassword" name="password" class="form-control">
                    <button type="button" class="password-toggle" id="togglePasswordBtn">
                        <i class="fas fa-eye-slash" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select id="userRole" name="role" class="form-control" required>
                    <option value="admin">Admin</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="supplier">Supplier</option>
                    <option value="customer">Customer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <div class="switch-wrapper">
                    <label class="switch">
                        <input type="checkbox" id="userStatusToggle" value="active">
                        <span class="slider"></span>
                    </label>
                    <span id="statusLabelText">Inactive</span>
                    <input type="hidden" id="userStatus" name="status" value="inactive">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="saveUserBtn"><i class="fas fa-save"></i> Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
// Load users on page load
document.addEventListener('DOMContentLoaded', loadUsers);

// Toast notification (reusing logic from app.js if available, else simple fallback)
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

// Load Users from API
async function loadUsers() {
    try {
        const response = await fetch('../../api/admin/user_crud.php?action=read');
        const result = await response.json();
        
        if (result.success) {
            renderUsers(result.data);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Failed to load users', 'error');
    }
}

// Render Users to Table
function renderUsers(users) {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const statusBadge = user.status === 'active' 
            ? `<span class="badge-status badge-active">Active</span>`
            : `<span class="badge-status badge-inactive">Inactive</span>`;
            
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${user.id}</td>
            <td>${user.full_name}</td>
            <td>${user.email}</td>
            <td style="text-transform: capitalize;">${user.role}</td>
            <td>${statusBadge}</td>
            <td>${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon edit" onclick='editUser(${JSON.stringify(user).replace(/'/g, "&apos;")})' title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" onclick="deleteUser(${user.id})" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Search and Filter logic
document.getElementById('userSearch').addEventListener('input', filterUsers);
document.getElementById('roleFilter').addEventListener('change', filterUsers);

function filterUsers() {
    const searchTerm = document.getElementById('userSearch').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
    
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const name = row.children[1].textContent.toLowerCase();
        const email = row.children[2].textContent.toLowerCase();
        const role = row.children[3].textContent.toLowerCase();
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesRole = roleFilter === '' || role === roleFilter;
        
        row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });
}

// Modal Functions
function openUserModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('passwordGroup').querySelector('label').textContent = 'Password';
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.add('active');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

// UI Toggles
document.getElementById('togglePasswordBtn').addEventListener('click', function() {
    const pwdInput = document.getElementById('userPassword');
    const icon = document.getElementById('togglePasswordIcon');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        pwdInput.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
});

document.getElementById('userStatusToggle').addEventListener('change', function() {
    const text = document.getElementById('statusLabelText');
    const hidden = document.getElementById('userStatus');
    if (this.checked) {
        text.textContent = 'Active';
        hidden.value = 'active';
    } else {
        text.textContent = 'Inactive';
        hidden.value = 'inactive';
    }
});

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = user.id;
    document.getElementById('userFullName').value = user.full_name;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRole').value = user.role;
    
    // Status Switch logic
    const statusToggle = document.getElementById('userStatusToggle');
    const statusText = document.getElementById('statusLabelText');
    const statusHidden = document.getElementById('userStatus');
    
    if (user.status === 'active') {
        statusToggle.checked = true;
        statusText.textContent = 'Active';
        statusHidden.value = 'active';
    } else {
        statusToggle.checked = false;
        statusText.textContent = 'Inactive';
        statusHidden.value = 'inactive';
    }
    
    // Reset password toggle just in case
    document.getElementById('userPassword').type = 'password';
    document.getElementById('togglePasswordIcon').classList.replace('fa-eye', 'fa-eye-slash');
    
    document.getElementById('passwordGroup').querySelector('label').textContent = 'Password (Leave blank to keep current)';
    document.getElementById('userPassword').required = false;
    document.getElementById('userPassword').value = '';
    
    document.getElementById('userModal').classList.add('active');
}

// Handle Form Submission
document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const id = document.getElementById('userId').value;
    const action = id ? 'update' : 'create';
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.action = action;
    
    const btn = document.getElementById('saveUserBtn');
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
            closeUserModal();
            loadUsers();
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

// Delete User
async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('../../api/admin/user_crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            loadUsers();
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
