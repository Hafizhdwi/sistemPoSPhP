<div class="sidebar" id="sidebar">
    <div class="brand">🏪 Mini PoS</div>
    <div class="user-info-wrapper" id="userWrapper">
        <div class="user-info" onclick="toggleUserDropdown(event)">
            <div class="user-avatar <?= $role ?>"><?= $initials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <span class="user-role-badge <?= $role ?>"><?= $roleIcon ?> <?= ucfirst($role) ?></span>
            </div>
            <span class="user-dropdown-icon">▼</span>
        </div>
        <div class="user-dropdown">
            <div class="dropdown-header">
                <div class="label">Login sebagai</div>
                <div class="value">@<?= htmlspecialchars($_SESSION['username']) ?></div>
            </div>
            <a href="profile.php"><span class="dropdown-icon">👤</span> Edit Profil</a>
            <?php if ($role === 'admin'): ?>
                <a href="settings.php"><span class="dropdown-icon">⚙️</span> Pengaturan Toko</a>
                <a href="users.php?edit=<?= $_SESSION['user_id'] ?>"><span class="dropdown-icon">🔑</span> Ganti Password</a>
                <div class="divider"></div>
            <?php endif; ?>
            <a href="logout.php" class="danger"><span class="dropdown-icon">🚪</span> Logout</a>
        </div>
    </div>
    <nav>
        <?php if (hasRole('admin')): ?>
            <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
            <a href="users.php" class="nav-link">👥 User</a>
        <?php endif; ?>
        <a href="index.php" class="nav-link active">🛒 Kasir</a>
        <?php if (hasRole('admin')): ?>
            <a href="products.php" class="nav-link">📦 Produk</a>
        <?php endif; ?>
        <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
        <?php if (hasRole('admin')): ?>
            <a href="settings.php" class="nav-link">⚙️ Pengaturan</a>
        <?php endif; ?>
        <a href="history.php" class="nav-link">📜 Riwayat</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout">🚪 Logout</a>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>