<div class="kitchen-header">
    <div>
        <h1>🍳 Kitchen Display</h1>
        <div class="kitchen-nav mt-2">
            <a href="index.php">← Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="dashboard.php">📊 Dashboard</a>
            <?php endif; ?>
            <a href="history.php">📜 Riwayat</a>
            <?php if (hasRole('admin')): ?>
                <a href="settings.php">⚙️ Pengaturan</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="live-indicator">
            <div class="live-dot"></div>
            LIVE
        </div>
        <span class="badge bg-light text-dark px-3 py-2" id="orderCount">
            <?= count($orders) ?> pesanan aktif
        </span>
        <?php include 'components/kitchen/user-dropdown.php'; ?>
    </div>
</div>