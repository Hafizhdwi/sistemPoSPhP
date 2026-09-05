<?php
require_once 'config/database.php';
requireLogin();

// Ambil semua transaksi termasuk kiosk
$transactions = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    GROUP BY t.id 
    ORDER BY t.transaction_date DESC 
    LIMIT 100
")->fetchAll();

$totalRevenue = array_sum(array_column($transactions, 'total_amount'));
$avgPerTx = count($transactions) ? $totalRevenue / count($transactions) : 0;
$pendingCount = count(array_filter($transactions, fn($t) => $t['status'] === 'pending'));
$kioskCount = count(array_filter($transactions, fn($t) => $t['order_type'] === 'kiosk'));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
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
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
            <?php endif; ?>
            <a href="history.php" class="nav-link active">📜 Riwayat</a>
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
                <h4 class="fw-bold m-0">📜 Riwayat Transaksi</h4>
                <small class="text-muted">Semua transaksi kasir & kiosk</small>
            </div>
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-danger px-3 py-2 fw-bold">⚠️ <?= $pendingCount ?> pesanan pending</span>
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
                        <small class="opacity-75 fw-semibold d-block">Total Pendapatan</small>
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

        <!-- TABEL RIWAYAT -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>📋 Daftar Transaksi</span>
                <span class="badge bg-light text-dark border px-3 py-2"><?= count($transactions) ?> transaksi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 ps-md-4">Invoice</th>
                                <th>Tipe</th>
                                <th>Pelanggan / Meja</th>
                                <th>Tanggal</th>
                                <th class="text-center">Items</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Total</th>
                                <th class="text-end pe-3 pe-md-4">Kembali</th>
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
                                            'preparing' => '<span class="badge bg-warning text-dark">PREPARING</span>',
                                            'ready'     => '<span class="badge bg-info text-dark">READY</span>',
                                            'completed' => '<span class="badge bg-success">SELESAI</span>',
                                            'cancelled' => '<span class="badge bg-dark">BATAL</span>',
                                            default     => '<span class="badge bg-secondary">-</span>',
                                        };
                                        echo $statusBadge;
                                        ?>
                                    </td>
                                    <td class="text-end fw-bold text-primary"><?= formatRupiah($t['total_amount']) ?></td>
                                    <td class="text-end pe-3 pe-md-4 fw-semibold text-success">
                                        <?= $t['order_type'] === 'kiosk' && $t['status'] === 'pending' ? '-' : formatRupiah($t['change_amount']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div style="font-size:3rem;">📭</div>
                                        <h6 class="fw-bold mt-3">Belum ada transaksi</h6>
                                        <small>Transaksi kasir dan kiosk akan muncul di sini</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- LEGEND -->
        <div class="mt-3 d-flex flex-wrap gap-3 align-items-center">
            <small class="text-muted fw-semibold">Legend:</small>
            <span class="badge bg-danger">PENDING</span>
            <small class="text-muted">Belum dibayar</small>
            <span class="badge bg-warning text-dark">PREPARING</span>
            <small class="text-muted">Sedang disiapkan</small>
            <span class="badge bg-info text-dark">READY</span>
            <small class="text-muted">Siap diambil</small>
            <span class="badge bg-success">SELESAI</span>
            <small class="text-muted">Sudah dibayar & selesai</small>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
    </script>
</body>

</html>