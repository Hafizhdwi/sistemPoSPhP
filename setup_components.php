<?php
/**
 * SETUP COMPONENTS - Auto Create Missing Files
 * Akses sekali via browser: http://localhost/sistemPoSPhP/setup_components.php
 * Lalu HAPUS file ini setelah selesai!
 */

$basePath = __DIR__;
$componentsDir = $basePath . '/components';

// Pastikan folder components ada
if (!is_dir($componentsDir)) {
    mkdir($componentsDir, 0755, true);
    echo "✅ Folder components/ berhasil dibuat<br>";
} else {
    echo "✅ Folder components/ sudah ada<br>";
}

// Daftar file yang harus ada
$files = [];

// ============= 1. sidebar.php =============
$files['sidebar.php'] = <<<'PHP'
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
PHP;

// ============= 2. calendar-panel.php =============
$files['calendar-panel.php'] = <<<'PHP'
<div class="col-12 col-lg-4 col-xl-4">
    <div class="calendar-card">
        <div class="calendar-header">
            <div class="calendar-header-top">
                <button class="calendar-nav-btn" onclick="changeMonth(-1)" title="Bulan Sebelumnya"><i class="bi bi-chevron-left"></i></button>
                <div class="calendar-month-year" id="calendarMonthYear">-</div>
                <button class="calendar-nav-btn" onclick="changeMonth(1)" title="Bulan Berikutnya"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="calendar-today-box">
                <div class="today-day" id="todayDay"><?= date('d') ?></div>
                <div class="today-info" id="todayInfo"><?= date('l, F Y') ?></div>
            </div>
        </div>
        <div class="calendar-body">
            <div class="calendar-weekdays">
                <div class="calendar-weekday weekend">Min</div>
                <div class="calendar-weekday">Sen</div>
                <div class="calendar-weekday">Sel</div>
                <div class="calendar-weekday">Rab</div>
                <div class="calendar-weekday">Kam</div>
                <div class="calendar-weekday">Jum</div>
                <div class="calendar-weekday weekend">Sab</div>
            </div>
            <div class="calendar-days" id="calendarDays"></div>
        </div>
        <div class="calendar-stats" id="calendarStats">
            <div class="calendar-stats-header">
                <div class="calendar-stats-title">Statistik</div>
                <div class="calendar-stats-date" id="statsDate">Hari Ini</div>
            </div>
            <div id="statsContent">
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon blue">📦</div><span>Total Transaksi</span></div>
                    <div class="calendar-stat-value" id="statTxCount"><?= $todayStats['count'] ?></div>
                </div>
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon green">💰</div><span>Pendapatan</span></div>
                    <div class="calendar-stat-value" id="statRevenue"><?= formatRupiah($todayStats['revenue']) ?></div>
                </div>
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon orange">✅</div><span>Selesai</span></div>
                    <div class="calendar-stat-value" id="statCompleted"><?= $todayStats['completed'] ?></div>
                </div>
            </div>
        </div>
        <div class="calendar-legend">
            <div class="calendar-legend-item"><div class="calendar-legend-dot today"></div><span>Hari Ini</span></div>
            <div class="calendar-legend-item"><div class="calendar-legend-dot has-tx"></div><span>Ada Transaksi</span></div>
            <div class="calendar-legend-item"><div class="calendar-legend-dot many-tx"></div><span>Ramai (>5)</span></div>
        </div>
    </div>
</div>
PHP;

// ============= 3. offcanvas-cart.php =============
$files['offcanvas-cart.php'] = <<<'PHP'
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
    <div class="cart-offcanvas-header">
        <h5 class="cart-offcanvas-title">
            🛒 Keranjang
            <span class="badge bg-white text-primary" id="offcanvas-cart-count">0 item</span>
        </h5>
        <button type="button" class="btn-close-offcanvas" data-bs-dismiss="offcanvas">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-offcanvas-body">
        <div class="cart-items-list" id="cart-items-list">
            <div class="cart-empty">
                <div class="icon">🛒</div>
                <div class="fw-semibold mb-1">Keranjang Kosong</div>
                <div class="small">Klik produk untuk menambahkan</div>
            </div>
        </div>
        <div class="cart-footer" id="cart-footer-offcanvas" style="display:none;">
            <div id="cart-summary-offcanvas"></div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-semibold">Total</span>
                <h3 class="fw-bold text-dark m-0" id="grand-total-offcanvas">Rp 0</h3>
            </div>
            <form id="checkout-form" action="process_sale.php" method="POST">
                <input type="hidden" name="cart_data" id="cart-data">
                <?php if ($processOrder): ?>
                    <input type="hidden" name="kiosk_order_id" value="<?= $processOrder['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Uang Diterima</label>
                    <input type="number" name="pay_amount" class="form-control form-control-lg fw-bold" required min="0" step="any" placeholder="0">
                </div>
                <button type="submit" class="btn btn-pay w-100" id="btn-checkout" disabled>💰 BAYAR SEKARANG</button>
            </form>
        </div>
    </div>
</div>

<button class="floating-cart-btn empty" id="floatingCartBtn" 
        data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" title="Lihat Keranjang">
    🛒
    <span class="floating-cart-badge" id="floatingCartBadge" style="display:none;">0</span>
</button>
PHP;

// ============= 4. toast-success.php =============
$files['toast-success.php'] = <<<'PHP'
<?php if ($successMsg): ?>
<div class="success-toast" id="successToast" data-autohide="5000">
    <div class="toast-content">
        <div class="toast-icon-wrapper">
            <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="toast-body">
            <div class="toast-title">Transaksi Berhasil!</div>
            <p class="toast-message"><?= $successMsg ?></p>
            <?php if ($invoiceNumber): ?>
                <div class="toast-invoice-badge">🧾 <?= htmlspecialchars($invoiceNumber) ?></div>
                <div class="toast-actions">
                    <a href="receipt.php?invoice=<?= urlencode($invoiceNumber) ?>" target="_blank" class="toast-action-btn primary">🖨️ Cetak Struk</a>
                    <button type="button" class="toast-action-btn secondary" onclick="hideSuccessToast()">Tutup</button>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" class="toast-close" onclick="hideSuccessToast()" aria-label="Tutup">✕</button>
    </div>
    <div class="toast-progress">
        <div class="toast-progress-bar" id="toastProgressBar"></div>
    </div>
</div>
<?php endif; ?>
PHP;

// ============= CREATE ALL FILES =============
echo "<h2>📁 Checking & Creating Files...</h2>";
foreach ($files as $filename => $content) {
    $filepath = $componentsDir . '/' . $filename;
    
    // Hapus file lama jika ada (untuk hindari ekstensi ganda)
    if (file_exists($filepath . '.txt')) {
        unlink($filepath . '.txt');
        echo "🗑️ Removed: {$filename}.txt (wrong extension)<br>";
    }
    
    if (!file_exists($filepath)) {
        file_put_contents($filepath, $content);
        echo "✅ Created: components/{$filename}<br>";
    } else {
        echo "✅ Exists: components/{$filename}<br>";
    }
}

// ============= VERIFICATION =============
echo "<h2>🔍 Verification</h2>";
echo "<strong>Files in components/:</strong><ul>";
foreach (scandir($componentsDir) as $file) {
    if ($file !== '.' && $file !== '..') {
        $size = filesize($componentsDir . '/' . $file);
        echo "<li>📄 {$file} ({$size} bytes)</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<h2>✅ SETUP SELESAI!</h2>";
echo "<p style='color:red;'><strong>⚠️ PENTING:</strong> HAPUS file <code>setup_components.php</code> ini setelah berhasil untuk keamanan!</p>";
echo "<p><a href='index.php' style='padding:10px 20px;background:#4f46e5;color:white;text-decoration:none;border-radius:8px;'>👉 Buka Halaman Kasir</a></p>";