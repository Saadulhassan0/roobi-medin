<?php
$role = $_SESSION['user_role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

// DO NOT render sidebar for customer e-commerce profile
if ($role !== 'customer'):
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-plus-square logo-icon"></i>
        <h2>MedIn AI</h2>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard is common for all -->
        <a href="dashboard.php" class="menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($role === 'admin'): ?>
            <a href="users.php" class="menu-item <?= $current_page == 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i><span>Manage Users</span>
            </a>
            <a href="pharmacists.php" class="menu-item <?= $current_page == 'pharmacists.php' ? 'active' : '' ?>">
                <i class="fas fa-user-md"></i><span>Pharmacists</span>
            </a>
            <a href="suppliers.php" class="menu-item <?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
                <i class="fas fa-truck"></i><span>Suppliers</span>
            </a>
            <a href="analytics.php" class="menu-item <?= $current_page == 'analytics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i><span>Analytics</span>
            </a>
            <a href="inventory.php" class="menu-item <?= $current_page == 'inventory.php' ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i><span>Inventory</span>
            </a>
            <a href="purchase_orders.php" class="menu-item <?= $current_page == 'purchase_orders.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i><span>Purchase Orders</span>
            </a>
            <a href="reports.php" class="menu-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i><span>System Reports</span>
            </a>
            
        <?php elseif ($role === 'pharmacist'): ?>
            <a href="inventory.php" class="menu-item <?= $current_page == 'inventory.php' ? 'active' : '' ?>">
                <i class="fas fa-pills"></i><span>Inventory</span>
            </a>
            <a href="billing.php" class="menu-item <?= $current_page == 'billing.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i><span>Billing System</span>
            </a>
            <a href="alerts.php" class="menu-item <?= $current_page == 'alerts.php' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i><span>Alerts</span>
            </a>
            
        <?php elseif ($role === 'supplier'): ?>
            <a href="purchase_orders.php" class="menu-item <?= $current_page == 'purchase_orders.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i><span>Purchase Orders</span>
            </a>
            <a href="deliveries.php" class="menu-item <?= $current_page == 'deliveries.php' ? 'active' : '' ?>">
                <i class="fas fa-truck-loading"></i><span>Deliveries</span>
            </a>
            <a href="history.php" class="menu-item <?= $current_page == 'history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i><span>History</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="../../api/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<?php endif; ?>
