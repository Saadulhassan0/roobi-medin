<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
<header class="topbar" style="display: flex; justify-content: space-between; align-items: center; padding: 0 30px; border-bottom: 1px solid var(--glass-border-light); background: rgba(0,0,0,0.2);">
    <!-- Logo -->
    <div style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="window.location.href='dashboard.php'">
        <i class="fas fa-plus-square" style="color: var(--accent-color); font-size: 1.5rem;"></i>
        <h2 style="margin: 0; font-size: 1.5rem; letter-spacing: 1px;">MedIn<span style="color: var(--accent-color);"> AI</span></h2>
    </div>

    <!-- Centered Search -->
    <div style="flex-grow: 1; max-width: 500px; margin: 0 20px;">
        <div class="search-bar" style="width: 100%;">
            <i class="fas fa-search"></i>
            <input type="text" id="globalShopSearch" placeholder="Search medicines, categories..." style="width: 100%;">
        </div>
    </div>

    <!-- Actions -->
    <div class="topbar-right" style="display: flex; align-items: center; gap: 20px;">
        <button class="icon-btn" onclick="window.location.href='profile.php#cart'" style="position: relative;">
            <i class="fas fa-shopping-cart"></i>
            <span class="badge" id="globalCartBadge">0</span>
        </button>
        
        <div class="user-profile" style="position: relative; cursor: pointer; padding: 5px 15px; border-radius: 20px; border: 1px solid var(--glass-border-light); transition: 0.3s;" onclick="toggleDropdown('customerProfileDropdown')">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="avatar" style="width: 30px; height: 30px; font-size: 0.9rem; background: var(--accent-color); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <span style="font-weight: 500; font-size: 0.9rem;">My Profile</span>
            </div>
            
            <!-- Customer Profile Dropdown -->
            <div id="customerProfileDropdown" class="topbar-dropdown" style="display: none; position: absolute; top: 120%; right: 0; width: 250px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000;">
                <div style="text-align: center; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 15px; margin-bottom: 10px;">
                    <div class="avatar" style="width: 60px; height: 60px; font-size: 1.5rem; background: var(--accent-color); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 10px;">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <h4 style="margin: 0; color: #fff;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h4>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--text-secondary);">Customer</p>
                </div>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 5px;"><a href="profile.php" style="color: var(--text-primary); text-decoration: none; display: block; padding: 8px 10px; border-radius: 8px; transition: 0.2s;"><i class="fas fa-user-circle" style="width: 20px; color: var(--accent-color);"></i> Profile Hub</a></li>
                    <li style="margin-bottom: 5px;"><a href="profile.php#orders" style="color: var(--text-primary); text-decoration: none; display: block; padding: 8px 10px; border-radius: 8px; transition: 0.2s;"><i class="fas fa-box" style="width: 20px; color: var(--accent-color);"></i> My Orders</a></li>
                    <li><a href="../../api/logout.php" style="color: var(--error-color); text-decoration: none; display: block; padding: 8px 10px; border-radius: 8px; transition: 0.2s;"><i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
<style>
    .topbar-dropdown a:hover { background: rgba(255,255,255,0.1); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', updateGlobalCartBadge);
    
    // Global function to update cart badge from anywhere
    async function updateGlobalCartBadge() {
        try {
            const res = await fetch('../../api/customer/cart_api.php?action=get_cart');
            const result = await res.json();
            if(result.success) {
                let total = 0;
                result.data.forEach(item => total += item.quantity);
                const badge = document.getElementById('globalCartBadge');
                if (badge) badge.textContent = total;
            }
        } catch(err) {}
    }
    
    function toggleDropdown(id) {
        const dd = document.getElementById(id);
        if (dd.style.display === 'none') {
            document.querySelectorAll('.topbar-dropdown').forEach(el => el.style.display = 'none');
            dd.style.display = 'block';
        } else {
            dd.style.display = 'none';
        }
    }
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-profile') && !e.target.closest('.icon-btn')) {
            document.querySelectorAll('.topbar-dropdown').forEach(el => el.style.display = 'none');
        }
    });

    const shopSearch = document.getElementById('globalShopSearch');
    if (shopSearch) {
        shopSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    if (window.location.pathname.includes('dashboard.php')) {
                        // If already on dashboard, just set the value and trigger search
                        const ev = new Event('input');
                        document.getElementById('searchInput').value = query;
                        document.getElementById('searchInput').dispatchEvent(ev);
                    } else {
                        window.location.href = `dashboard.php?search=${encodeURIComponent(query)}`;
                    }
                }
            }
        });
    }
</script>

<?php else: ?>
<header class="topbar">
    <button class="toggle-btn" id="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="globalSystemSearch" placeholder="Search system inventory...">
    </div>

    <div class="topbar-right" style="display: flex; align-items: center; gap: 20px;">
        <!-- Notifications Bell -->
        <div style="position: relative;">
            <button class="icon-btn" onclick="toggleDropdown('notificationDropdown'); fetchNotifications();">
                <i class="fas fa-bell"></i>
                <span class="badge" id="notifBadge" style="display: none;">0</span>
            </button>
            
            <div id="notificationDropdown" class="topbar-dropdown" style="display: none; position: absolute; top: 120%; right: 0; width: 320px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 12px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000; overflow: hidden;">
                <div style="padding: 15px; border-bottom: 1px solid var(--glass-border-light); background: rgba(0,0,0,0.2);">
                    <h4 style="margin: 0; color: #fff; font-size: 1rem;"><i class="fas fa-bell" style="color: var(--accent-color); margin-right: 8px;"></i> Notifications</h4>
                </div>
                <div id="notifList" style="max-height: 300px; overflow-y: auto; padding: 10px;">
                    <div style="text-align: center; padding: 20px; color: var(--text-secondary);">Loading...</div>
                </div>
            </div>
        </div>
        
        <!-- User Profile Dropdown -->
        <div class="user-profile" style="position: relative; cursor: pointer;" onclick="toggleDropdown('adminProfileDropdown')">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="avatar" style="background: var(--accent-color); color: #000; font-weight: bold; display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 50%;">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <span style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: var(--text-secondary);"></i>
            </div>
            
            <div id="adminProfileDropdown" class="topbar-dropdown" style="display: none; position: absolute; top: 120%; right: 0; width: 250px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 12px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000;">
                <div style="text-align: center; border-bottom: 1px solid var(--glass-border-light); padding-bottom: 15px; margin-bottom: 10px;">
                    <div class="avatar" style="width: 60px; height: 60px; font-size: 1.5rem; background: var(--accent-color); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 10px;">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <h4 style="margin: 0; color: #fff;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h4>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--text-secondary); text-transform: capitalize;"><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></p>
                </div>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li><a href="../../api/logout.php" style="color: var(--error-color); text-decoration: none; display: block; padding: 8px 10px; border-radius: 8px; transition: 0.2s;"><i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
<style>
    .topbar-dropdown a:hover { background: rgba(255,255,255,0.1); }
    .notif-item { padding: 12px 10px; border-bottom: 1px solid var(--glass-border-light); border-radius: 8px; transition: 0.2s; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; }
    .notif-item:hover { background: rgba(255,255,255,0.05); }
    .notif-item:last-child { border-bottom: none; }
    .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
</style>
<script>
    function toggleDropdown(id) {
        const dd = document.getElementById(id);
        if (dd.style.display === 'none') {
            document.querySelectorAll('.topbar-dropdown').forEach(el => el.style.display = 'none');
            dd.style.display = 'block';
        } else {
            dd.style.display = 'none';
        }
    }
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-profile') && !e.target.closest('.icon-btn')) {
            document.querySelectorAll('.topbar-dropdown').forEach(el => el.style.display = 'none');
        }
    });

    // Check notifications on load
    document.addEventListener('DOMContentLoaded', fetchNotifications);
    
    async function fetchNotifications() {
        try {
            const res = await fetch('../../api/notifications_api.php');
            const result = await res.json();
            
            const badge = document.getElementById('notifBadge');
            const list = document.getElementById('notifList');
            
            if (result.success) {
                if (result.data.length > 0) {
                    badge.style.display = 'inline-flex';
                    badge.textContent = result.data.length;
                    
                    list.innerHTML = '';
                    result.data.forEach(n => {
                        let iconBg = 'rgba(139, 92, 246, 0.2)';
                        let iconColor = 'var(--accent-color)';
                        let iconClass = 'fas fa-info-circle';
                        
                        if (n.type === 'chat') {
                            iconBg = 'rgba(59, 130, 246, 0.2)';
                            iconColor = '#3b82f6';
                            iconClass = 'fas fa-comment-dots';
                        } else if (n.type === 'expiry') {
                            iconBg = 'rgba(239, 68, 68, 0.2)';
                            iconColor = 'var(--error-color)';
                            iconClass = 'fas fa-exclamation-triangle';
                        }
                        
                        list.innerHTML += `
                            <div class="notif-item" onclick="window.location.href='${n.link}'">
                                <div class="notif-icon" style="background: ${iconBg}; color: ${iconColor};">
                                    <i class="${iconClass}"></i>
                                </div>
                                <div>
                                    <h5 style="margin: 0 0 4px; color: #fff; font-size: 0.9rem;">${n.title}</h5>
                                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4;">${n.message}</p>
                                    <small style="color: var(--accent-color); font-size: 0.7rem; margin-top: 5px; display: block;">${n.time}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    badge.style.display = 'none';
                    list.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);">No new notifications.</div>';
                }
            }
        } catch(err) {
            console.error(err);
        }
    }
    
    // Global Search
    const searchInput = document.getElementById('globalSystemSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    // Redirect based on role
                    const role = '<?php echo $_SESSION['user_role'] ?? ""; ?>';
                    if (role === 'admin' || role === 'pharmacist') {
                        window.location.href = `inventory.php?search=${encodeURIComponent(query)}`;
                    } else if (role === 'supplier') {
                        window.location.href = `orders.php?search=${encodeURIComponent(query)}`;
                    }
                }
            }
        });
    }
</script>
<?php endif; ?>
