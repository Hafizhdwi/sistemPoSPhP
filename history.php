<?php
require_once 'config/database.php';
requireLogin();

$activeTab = $_GET['tab'] ?? 'transactions';

// ==================== DATA TRANSAKSI ====================
$transactions = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    GROUP BY t.id ORDER BY t.transaction_date DESC LIMIT 100
")->fetchAll();

$totalRevenue = array_sum(array_column($transactions, 'total_amount'));
$avgPerTx = count($transactions) ? $totalRevenue / count($transactions) : 0;
$pendingCount = count(array_filter($transactions, fn($t) => $t['status'] === 'pending'));
$kioskCount = count(array_filter($transactions, fn($t) => $t['order_type'] === 'kiosk'));

// ==================== DATA MUTASI STOK ====================
$stockHistory = $pdo->query("
    SELECT sh.*, p.name as product_name 
    FROM stock_history sh JOIN products p ON sh.product_id = p.id 
    ORDER BY sh.created_at DESC LIMIT 100
")->fetchAll();

// ==================== DATA AKTIVITAS USER ====================
$userActivities = [];
if (hasRole('admin')) {
    try {
        $stmtActivities = $pdo->query("
            SELECT 
                al.action,
                al.entity_type,
                al.entity_id,
                al.description,
                al.ip_address,
                al.user_agent,
                al.created_at,
                u.full_name
            FROM activity_logs al
            LEFT JOIN users u ON al.entity_id = u.id AND al.entity_type = 'user'
            WHERE 
                (al.entity_type IS NULL OR al.entity_type NOT IN ('transaction', 'transaction_detail', 'stock', 'stock_history'))
                AND al.action NOT LIKE 'transaction_%'
                AND al.action NOT LIKE 'stock_%'
            ORDER BY al.created_at DESC
            LIMIT 200
        ");
        $userActivities = $stmtActivities->fetchAll();
    } catch (Exception $e) {
        $userActivities = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .activity-badge {
            font-size: 0.7rem;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand">🏪 Mini PoS</div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <div class="role"><?= $_SESSION['role'] ?></div>
        </div>
        <nav>
            <?php if (hasRole('admin')): ?>
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="users.php" class="nav-link">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
                <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
            <?php endif; ?>
            <a href="history.php" class="nav-link active">📜 Riwayat</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link logout">🚪 Logout</a>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0">📜 Pusat Riwayat</h4>
                <small class="text-muted">Semua catatan aktivitas sistem</small>
            </div>
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-danger px-3 py-2 fw-bold">⚠️ <?= $pendingCount ?> pending</span>
            <?php endif; ?>
        </div>

        <!-- STAT CARDS -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body p-3 p-md-4">
                        <small class="opacity-75 fw-semibold d-block">Total Transaksi</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= count($transactions) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body p-3 p-md-4">
                        <small class="opacity-75 fw-semibold d-block">Pendapatan</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= formatRupiah($totalRevenue) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body p-3 p-md-4">
                        <small class="opacity-75 fw-semibold d-block">Rata-rata</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= formatRupiah($avgPerTx) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="card-body p-3 p-md-4">
                        <small class="opacity-75 fw-semibold d-block">Pesanan Kiosk</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= $kioskCount ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <ul class="nav nav-pills mb-4 gap-2 flex-wrap">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'transactions' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=transactions">💰 Transaksi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'stock' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=stock">📦 Mutasi Stok</a>
            </li>
            <?php if (hasRole('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=users">👥 Aktivitas User</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- TAB: TRANSAKSI -->
        <?php if ($activeTab === 'transactions'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>💰 Riwayat Transaksi</span>
                    <span class="badge bg-light text-dark border px-3 py-2"><?= count($transactions) ?> transaksi</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 ps-md-4">Invoice</th>
                                    <th>Tipe</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end pe-3 pe-md-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): ?>
                                    <tr class="<?= $t['status'] === 'pending' ? 'table-warning' : '' ?>">
                                        <td class="ps-3 ps-md-4">
                                            <span class="fw-semibold font-monospace small"><?= htmlspecialchars($t['invoice_number']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($t['order_type'] === 'kiosk'): ?>
                                                <span class="badge bg-warning text-dark">🖥️ KIOSK</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">🛒 KASIR</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($t['customer_name']): ?>
                                                <div class="fw-semibold"><?= htmlspecialchars($t['customer_name']) ?></div>
                                                <small class="text-muted">Meja <?= htmlspecialchars($t['table_number']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= date('d M Y', strtotime($t['transaction_date'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($t['transaction_date'])) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $t['item_count'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $statusBadge = match ($t['status']) {
                                                'pending'   => '<span class="badge bg-danger">PENDING</span>',
                                                'preparing' => '<span class="badge bg-warning text-dark">DIMASAK</span>',
                                                'ready'     => '<span class="badge bg-info text-dark">SIAP</span>',
                                                'completed' => '<span class="badge bg-success">SELESAI</span>',
                                                'cancelled' => '<span class="badge bg-dark">BATAL</span>',
                                                default     => '<span class="badge bg-secondary">-</span>',
                                            };
                                            echo $statusBadge;
                                            ?>
                                        </td>
                                        <td class="text-end fw-bold text-primary"><?= formatRupiah($t['total_amount']) ?></td>
                                        <td class="text-end pe-3 pe-md-4">
                                            <?php if ($t['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-warning fw-bold px-3" onclick="updateOrderStatus(<?= $t['id'] ?>, 'preparing')">🔥 Proses</button>
                                            <?php elseif ($t['status'] === 'preparing'): ?>
                                                <button class="btn btn-sm btn-info fw-bold px-3 text-white" onclick="updateOrderStatus(<?= $t['id'] ?>, 'ready')">✅ Siap</button>
                                            <?php elseif ($t['status'] === 'ready'): ?>
                                                <a href="index.php?process_order=<?= $t['id'] ?>" class="btn btn-sm btn-success fw-bold px-3">💰 Bayar</a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <div style="font-size:3rem;">📭</div>
                                            <h6 class="fw-bold mt-3">Belum ada transaksi</h6>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: MUTASI STOK -->
        <?php if ($activeTab === 'stock'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>📦 Riwayat Mutasi Stok</span>
                    <span class="badge bg-light text-dark border px-3 py-2"><?= count($stockHistory) ?> catatan</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 ps-md-4">Waktu</th>
                                    <th>Produk</th>
                                    <th class="text-center">Tipe</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Referensi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockHistory as $sh): ?>
                                    <tr>
                                        <td class="ps-3 ps-md-4">
                                            <div class="fw-semibold"><?= date('d M Y', strtotime($sh['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($sh['created_at'])) ?></small>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($sh['product_name']) ?></td>
                                        <td class="text-center">
                                            <?php
                                            $typeBadge = match ($sh['type']) {
                                                'in'         => '<span class="badge bg-success">MASUK</span>',
                                                'out'        => '<span class="badge bg-danger">KELUAR</span>',
                                                'adjustment' => '<span class="badge bg-warning text-dark">ADJUST</span>',
                                            };
                                            echo $typeBadge;
                                            ?>
                                        </td>
                                        <td class="text-center fw-bold <?= $sh['type'] === 'out' ? 'text-danger' : 'text-success' ?>">
                                            <?= $sh['type'] === 'out' ? '-' : '+' ?><?= $sh['quantity'] ?>
                                        </td>
                                        <td><code class="small"><?= htmlspecialchars($sh['reference'] ?? '-') ?></code></td>
                                        <td class="text-muted small"><?= htmlspecialchars($sh['notes'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($stockHistory)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div style="font-size:3rem;">📦</div>
                                            <h6 class="fw-bold mt-3">Belum ada mutasi stok</h6>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB: AKTIVITAS USER -->
        <?php if ($activeTab === 'users' && hasRole('admin')): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>👥 Log Aktivitas User & Sistem</span>
                    <span class="badge bg-light text-dark border px-3 py-2"><?= count($userActivities) ?> aktivitas</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($userActivities)): ?>
                        <div class="text-center py-5 text-muted">
                            <div style="font-size:3rem;">📭</div>
                            <h6 class="fw-bold mt-3">Belum ada aktivitas</h6>
                            <small>Aktivitas login, perubahan user, dan perubahan sistem akan muncul di sini.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th class="text-center">Tipe</th>
                                        <th>Aktivitas</th>
                                        <th>Target</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userActivities as $log): ?>
                                        <?php
                                        $action = $log['action'];
                                        $badgeClass = 'bg-secondary';
                                        $icon = 'ℹ️';
                                        $label = ucwords(str_replace('_', ' ', $action));

                                        if (str_contains($action, 'login_success') || str_contains($action, 'created')) {
                                            $badgeClass = 'bg-success';
                                            $icon = '✅';
                                        } elseif (str_contains($action, 'failed') || str_contains($action, 'deleted')) {
                                            $badgeClass = 'bg-danger';
                                            $icon = '❌';
                                        } elseif (str_contains($action, 'password')) {
                                            $badgeClass = 'bg-warning text-dark';
                                            $icon = '🔑';
                                        } elseif (str_contains($action, 'updated') || str_contains($action, 'toggled')) {
                                            $badgeClass = 'bg-info text-dark';
                                            $icon = '✏️';
                                        } elseif (str_contains($action, 'logout')) {
                                            $badgeClass = 'bg-dark';
                                            $icon = '🚪';
                                        } elseif (str_contains($action, 'system')) {
                                            $badgeClass = 'bg-primary';
                                            $icon = '⚙️';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeClass ?> activity-badge">
                                                    <?= $icon ?> <?= htmlspecialchars($label) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($log['description'] ?: $label) ?>
                                                </div>
                                                <?php if (!empty($log['entity_type'])): ?>
                                                    <small class="text-muted">
                                                        Entity: <?= htmlspecialchars($log['entity_type']) ?>
                                                        <?php if (!empty($log['entity_id'])): ?>
                                                            #<?= htmlspecialchars($log['entity_id']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['full_name'])): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        👤 <?= htmlspecialchars($log['full_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['ip_address'])): ?>
                                                    <code><?= htmlspecialchars($log['ip_address']) ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- LEGEND -->
        <div class="mt-3 d-flex flex-wrap gap-3 align-items-center">
            <small class="text-muted fw-semibold">Status Transaksi:</small>
            <span class="badge bg-danger">PENDING</span>
            <span class="badge bg-warning text-dark">DIMASAK</span>
            <span class="badge bg-info text-dark">SIAP</span>
            <span class="badge bg-success">SELESAI</span>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        function updateOrderStatus(orderId, newStatus) {
            if (!confirm('Yakin ubah status pesanan ini?')) return;
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            fetch('process_order_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('❌ ' + data.error);
                })
                .catch(() => alert('Terjadi kesalahan!'));
        }
    </script>
</body>

</html>