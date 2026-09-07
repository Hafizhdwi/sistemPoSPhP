<?php
require_once 'config/database.php';
requireAdmin();

// ==================== STATISTIK UMUM ====================
$today = date('Y-m-d');
$thisMonth = date('Y-m');

// Total penjualan hari ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
                       FROM transactions WHERE DATE(transaction_date) = ?");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

// Total penjualan bulan ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
                       FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$stmt->execute([$thisMonth]);
$monthStats = $stmt->fetch();

// Total produk terjual hari ini
$stmt = $pdo->prepare("SELECT COALESCE(SUM(td.quantity), 0) as total_items 
                       FROM transaction_details td 
                       JOIN transactions t ON td.transaction_id = t.id 
                       WHERE DATE(t.transaction_date) = ?");
$stmt->execute([$today]);
$itemsToday = $stmt->fetch()['total_items'];

// Produk dengan stok menipis (< 10)
$stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock < 10");
$lowStock = $stmt->fetch()['low_stock'];

// ==================== DATA GRAFIK ====================

// 1. Penjualan 7 hari terakhir (Line Chart)
$chartDaily = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total 
                           FROM transactions WHERE DATE(transaction_date) = ?");
    $stmt->execute([$date]);
    $chartDaily[] = [
        'label' => date('d M', strtotime($date)),
        'value' => (float)$stmt->fetch()['total']
    ];
}

// 2. Top 5 Produk Terlaris (Bar Chart)
$stmt = $pdo->query("
    SELECT p.name, SUM(td.quantity) as total_qty, SUM(td.subtotal) as total_revenue
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    GROUP BY td.product_id
    ORDER BY total_qty DESC
    LIMIT 5
");
$topProducts = $stmt->fetchAll();

// 3. Penjualan per Jam (Peak Hours Analysis)
$stmt = $pdo->query("
    SELECT HOUR(transaction_date) as hour, COUNT(*) as tx_count, SUM(total_amount) as total
    FROM transactions
    GROUP BY HOUR(transaction_date)
    ORDER BY hour
");
$hourlyData = [];
foreach ($stmt->fetchAll() as $row) {
    $hourlyData[$row['hour']] = ['count' => $row['tx_count'], 'total' => (float)$row['total']];
}

// 4. Transaksi Terbaru
$stmt = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    GROUP BY t.id 
    ORDER BY t.transaction_date DESC 
    LIMIT 10
");
$recentTransactions = $stmt->fetchAll();

// ✅ Data User untuk Avatar
$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand">🏪 Mini PoS</div>
        
        <!-- ✅ USER INFO MODERN DENGAN DROPDOWN -->
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
                
                <a href="profile.php">
                    <span class="dropdown-icon">👤</span> Edit Profil
                </a>
                
                <?php if ($role === 'admin'): ?>
                    <a href="settings.php">
                        <span class="dropdown-icon">⚙️</span> Pengaturan Toko
                    </a>
                    <a href="users.php?edit=<?= $_SESSION['user_id'] ?>">
                        <span class="dropdown-icon">🔑</span> Ganti Password
                    </a>
                    <div class="divider"></div>
                <?php endif; ?>
                
                <a href="logout.php" class="danger">
                    <span class="dropdown-icon">🚪</span> Logout
                </a>
            </div>
        </div>
        
        <nav>
            <?php if (hasRole('admin')): ?>
                <a href="dashboard.php" class="nav-link active">📊 Dashboard</a>
                <a href="users.php" class="nav-link">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
                <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
                <a href="settings.php" class="nav-link">⚙️ Pengaturan</a>
            <?php endif; ?>
            <a href="history.php" class="nav-link">📜 Riwayat</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link logout">🚪 Logout</a>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <!-- MOBILE HEADER -->
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0">📊 Dashboard Admin</h4>
                <small class="text-muted">Ringkasan performa toko Anda</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2">
                📅 <?= date('d F Y') ?>
            </span>
        </div>

        <!-- STAT CARDS -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="opacity-75 fw-semibold d-block">Penjualan Hari Ini</small>
                                <h4 class="fw-bold mt-2 mb-0"><?= formatRupiah($todayStats['total']) ?></h4>
                                <small class="opacity-75"><?= $todayStats['count'] ?> transaksi</small>
                            </div>
                            <div style="font-size:2rem; opacity:0.3;">💰</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color:white;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="opacity-75 fw-semibold d-block">Penjualan Bulan Ini</small>
                                <h4 class="fw-bold mt-2 mb-0"><?= formatRupiah($monthStats['total']) ?></h4>
                                <small class="opacity-75"><?= $monthStats['count'] ?> transaksi</small>
                            </div>
                            <div style="font-size:2rem; opacity:0.3;">📈</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:white;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="opacity-75 fw-semibold d-block">Item Terjual Hari Ini</small>
                                <h4 class="fw-bold mt-2 mb-0"><?= $itemsToday ?></h4>
                                <small class="opacity-75">produk</small>
                            </div>
                            <div style="font-size:2rem; opacity:0.3;">🛍️</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm <?= $lowStock > 0 ? '' : 'bg-light' ?>">
                    <div class="card-body p-3 p-md-4" style="<?= $lowStock > 0 ? 'background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color:#7c2d12;' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="fw-semibold d-block opacity-75">Stok Menipis</small>
                                <h4 class="fw-bold mt-2 mb-0"><?= $lowStock ?></h4>
                                <small class="opacity-75">produk < 10 unit</small>
                            </div>
                            <div style="font-size:2rem; opacity:0.3;">⚠️</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 1 -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Line Chart: Penjualan 7 Hari -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>📈 Tren Penjualan (7 Hari Terakhir)</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <canvas id="dailySalesChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bar Chart: Top Produk -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">🏆 Top 5 Produk Terlaris</div>
                    <div class="card-body p-3 p-md-4">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 2 -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Hourly Chart -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">⏰ Jam Sibuk (Peak Hours)</div>
                    <div class="card-body p-3 p-md-4">
                        <canvas id="hourlyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Transaksi Terbaru -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>🕐 Transaksi Terbaru</span>
                        <a href="history.php" class="btn btn-sm btn-outline-primary px-3">Lihat Semua →</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Invoice</th>
                                        <th>Waktu</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $t): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="fw-semibold font-monospace small"><?= $t['invoice_number'] ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('d M, H:i', strtotime($t['transaction_date'])) ?></small>
                                            </td>
                                            <td class="text-end fw-bold text-primary"><?= formatRupiah($t['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <small>Belum ada transaksi</small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // ==================== SIDEBAR TOGGLE ====================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        // ==================== USER DROPDOWN ====================
        function toggleUserDropdown(event) {
            event.stopPropagation();
            const wrapper = document.getElementById('userWrapper');
            if (wrapper) wrapper.classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('userWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const wrapper = document.getElementById('userWrapper');
                if (wrapper) wrapper.classList.remove('open');
            }
        });

        // ==================== CHART 1: Penjualan Harian ====================
        const dailyCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chartDaily, 'label')) ?>,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: <?= json_encode(array_column($chartDaily, 'value')) ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        }
                    }
                }
            }
        });

        // ==================== CHART 2: Top Produk ====================
        const topCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topProducts, 'name')) ?>,
                datasets: [{
                    label: 'Qty Terjual',
                    data: <?= json_encode(array_column($topProducts, 'total_qty')) ?>,
                    backgroundColor: [
                        '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // ==================== CHART 3: Jam Sibuk ====================
        const hourlyData = <?= json_encode(array_map(function ($h) use ($hourlyData) {
                                return isset($hourlyData[$h]) ? $hourlyData[$h]['total'] : 0;
                            }, range(6, 22))) ?>;

        const hourlyLabels = <?= json_encode(array_map(function ($h) {
                                    return sprintf('%02d:00', $h);
                                }, range(6, 22))) ?>;

        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: hourlyData,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)',
                    borderColor: '#4f46e5',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>