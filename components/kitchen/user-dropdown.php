<div class="kitchen-user-info-wrapper" id="kitchenUserWrapper" style="position: relative;">
    <div class="kitchen-user-info" onclick="toggleKitchenUserDropdown(event)">
        <div class="kitchen-avatar <?= $role ?>"><?= $initials ?></div>
        <span class="kitchen-user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
    
    <div class="kitchen-user-dropdown">
        <div class="dropdown-header">
            <div class="label">Login sebagai</div>
            <div class="value">@<?= htmlspecialchars($_SESSION['username']) ?> · <?= $roleIcon ?> <?= ucfirst($role) ?></div>
        </div>
        
        <a href="profile.php"><span>👤</span> Edit Profil</a>
        
        <?php if ($role === 'admin'): ?>
            <a href="settings.php"><span>⚙️</span> Pengaturan Toko</a>
            <div class="divider"></div>
        <?php endif; ?>
        
        <a href="logout.php" class="danger"><span>🚪</span> Logout</a>
    </div>
</div>  