<?php
require_once 'config/database.php';
requireLogin();

// ==================== AJAX HANDLER: Polling Pesanan Kiosk ====================
if (isset($_GET['ajax_pending'])) {
    $pendingOrders = $pdo->query("
        SELECT t.*, COUNT(td.id) as item_count 
        FROM transactions t 
        LEFT JOIN transaction_details td ON t.id = td.transaction_id 
        WHERE t.status IN ('pending', 'preparing', 'ready') AND t.order_type = 'kiosk'
        GROUP BY t.id 
        ORDER BY t.transaction_date ASC
    ")->fetchAll();
    
    if (!empty($pendingOrders)): ?>
        <div class="card border-0 shadow-sm mb-4 pending-panel">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold m-0 text-danger">🔔 Pesanan Kiosk Aktif (<?= count($pendingOrders) ?>)</h5>
                    <span class="badge bg-danger px-3 py-2">Perlu Diproses</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Antrian</th>
                                <th>Pelanggan</th>
                                <th>Meja</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $order): ?>
                                <tr>
                                    <td><span class="badge bg-warning text-dark fw-bold px-3 py-2">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['table_number']) ?></td>
                                    <td>
                                        <?php
                                        echo match ($order['status']) {
                                            'pending' => '<span class="badge bg-danger">PENDING</span>',
                                            'preparing' => '<span class="badge bg-warning text-dark">DIMASAK</span>',
                                            'ready' => '<span class="badge bg-success">SIAP</span>',
                                            default => '<span class="badge bg-secondary">-</span>'
                                        };
                                        ?>
                                    </td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $order['item_count'] ?> item</span></td>
                                    <td class="fw-bold text-primary"><?= formatRupiah($order['total_amount']) ?></td>
                                    <td class="text-center">
                                        <?php if ($order['status'] === 'ready'): ?>
                                            <a href="index.php?process_order=<?= $order['id'] ?>" class="btn btn-sm btn-success fw-bold px-4 py-2">💰 Bayar</a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2"><?= $order['status'] === 'pending' ? '⏳ Menunggu' : '🔥 Dimasak' ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif;
    exit;
}

// ==================== DATA UNTUK HALAMAN UTAMA ====================

// Ambil semua produk
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$allProducts = $stmt->fetchAll();
$products = array_filter($allProducts, fn($p) => $p['stock'] > 0);

// Load pesanan kiosk ke cart jika ada parameter process_order
$processOrder = null;
$processItems = [];
if (isset($_GET['process_order'])) {
    $orderId = intval($_GET['process_order']);
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status IN ('pending','preparing','ready')");
    $stmt->execute([$orderId]);
    $processOrder = $stmt->fetch();
    if ($processOrder) {
        $stmtItems = $pdo->prepare("SELECT td.product_id, td.quantity, p.name, p.price FROM transaction_details td JOIN products p ON td.product_id = p.id WHERE td.transaction_id = ?");
        $stmtItems->execute([$orderId]);
        $processItems = $stmtItems->fetchAll();
    }
}

// Ambil pesanan kiosk aktif
$pendingOrders = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    WHERE t.status IN ('pending', 'preparing', 'ready') AND t.order_type = 'kiosk'
    GROUP BY t.id 
    ORDER BY t.transaction_date ASC
")->fetchAll();

// Alert sukses
$successMsg = '';
$invoiceNumber = '';
if (isset($_GET['success'])) {
    $change = isset($_GET['change']) ? floatval($_GET['change']) : 0;
    $successMsg = "Transaksi Berhasil! Kembalian: " . formatRupiah($change);
    $invoiceNumber = $_GET['invoice'] ?? '';
}

// Setting pajak
$taxEnabled = getSetting($pdo, 'tax_enabled', '0') == '1';
$taxRate = floatval(getSetting($pdo, 'tax_rate', '0'));
$taxLabel = getSetting($pdo, 'tax_label', 'Pajak');

// Data User
$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';

// Data kalender (90 hari terakhir)
$calendarData = [];
try {
    $stmt = $pdo->query("
        SELECT DATE(transaction_date) as date, 
               COUNT(*) as tx_count, 
               SUM(total_amount) as total_revenue, 
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count 
        FROM transactions 
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) 
        GROUP BY DATE(transaction_date) 
        ORDER BY date DESC
    ");
    foreach ($stmt->fetchAll() as $row) {
        $calendarData[$row['date']] = [
            'count' => (int)$row['tx_count'], 
            'revenue' => (float)$row['total_revenue'], 
            'completed' => (int)$row['completed_count']
        ];
    }
} catch (Exception $e) {}

$todayStats = $calendarData[date('Y-m-d')] ?? ['count' => 0, 'revenue' => 0, 'completed' => 0];

// Siapkan config untuk JavaScript
$kioskItems = [];
if ($processOrder && !empty($processItems)) {
    foreach ($processItems as $item) {
        $kioskItems[] = [
            'id' => (int)$item['product_id'], 
            'name' => $item['name'], 
            'price' => (float)$item['price'], 
            'qty' => (int)$item['quantity']
        ];
    }
}
$pendingOrderIds = array_column($pendingOrders, 'id');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Mini PoS</title>
    <meta name="description" content="Halaman kasir Mini PoS - Point of Sale System">
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/css/kasir.css">
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <!-- Toast Sukses -->
        <?php include 'components/toast-success.php'; ?>

        <!-- Alert Proses Pesanan Kiosk -->
        <?php if ($processOrder): ?>
            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>🖥️ <strong>Memproses Pesanan Kiosk</strong> — <?= htmlspecialchars($processOrder['customer_name']) ?> | Meja <?= htmlspecialchars($processOrder['table_number']) ?> | <code><?= $processOrder['invoice_number'] ?></code></span>
                <a href="index.php" class="btn btn-sm btn-outline-secondary fw-bold">✕ Batal</a>
            </div>
        <?php endif; ?>

        <!-- Panel Pesanan Kiosk Aktif -->
        <?php if (!empty($pendingOrders)): ?>
            <div class="card border-0 shadow-sm mb-4 pending-panel">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold m-0 text-danger">🔔 Pesanan Kiosk Aktif (<?= count($pendingOrders) ?>)</h5>
                        <span class="badge bg-danger px-3 py-2">Perlu Diproses</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Meja</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark fw-bold px-3 py-2">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['table_number']) ?></td>
                                        <td>
                                            <?php
                                            echo match ($order['status']) {
                                                'pending' => '<span class="badge bg-danger">PENDING</span>',
                                                'preparing' => '<span class="badge bg-warning text-dark">DIMASAK</span>',
                                                'ready' => '<span class="badge bg-success">SIAP</span>',
                                                default => '<span class="badge bg-secondary">-</span>'
                                            };
                                            ?>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $order['item_count'] ?> item</span></td>
                                        <td class="fw-bold text-primary"><?= formatRupiah($order['total_amount']) ?></td>
                                        <td class="text-center">
                                            <?php if ($order['status'] === 'ready'): ?>
                                                <a href="index.php?process_order=<?= $order['id'] ?>" class="btn btn-sm btn-success fw-bold px-4 py-2">💰 Bayar</a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">
                                                    <?= $order['status'] === 'pending' ? '⏳ Menunggu' : '🔥 Dimasak' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content: Product Grid + Calendar -->
        <div class="row g-3 g-lg-4">
            <!-- Product Grid -->
            <div class="col-12 col-lg-8 col-xl-8">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="productSearch" class="form-control form-control-lg" 
                           placeholder="Cari produk... (Ctrl+K)" autocomplete="off">
                    <span class="shortcut-hint d-none d-md-inline">Ctrl+K</span>
                    <button class="search-clear" id="searchClear" onclick="clearSearch()">✕</button>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0">Pilih Produk</h4>
                    <span class="badge bg-light text-dark border px-3 py-2 d-none d-sm-inline" id="productCount">
                        <?= count($products) ?> produk
                    </span>
                </div>
                
                <div class="no-results" id="noResults">
                    <div style="font-size:3rem;">🔍</div>
                    <h5 class="fw-bold mt-3">Produk tidak ditemukan</h5>
                    <p class="mb-0">Coba kata kunci lain atau cek ejaan</p>
                </div>
                
                <div class="row g-2 g-md-3" id="productGrid">
                    <?php foreach ($allProducts as $p): ?>
                        <div class="col-6 col-md-4 col-xl-3 product-col" 
                             data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                            <div class="product-card <?= $p['stock'] <= 0 ? 'out-of-stock' : '' ?>"
                                onclick="<?= $p['stock'] > 0 ? "addToCart({$p['id']}, '" . addslashes($p['name']) . "', {$p['price']})" : '' ?>">
                                <div class="icon">☕</div>
                                <div class="name text-truncate"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="stock">Stok: <?= $p['stock'] ?></div>
                                <div class="price"><?= formatRupiah($p['price']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Calendar Panel -->
            <?php include 'components/calendar-panel.php'; ?>
        </div>
    </div>

    <!-- Offcanvas Cart -->
    <?php include 'components/offcanvas-cart.php'; ?>

    <!-- Inject PHP Config ke JavaScript -->
    <script>
        window.KASIR_CONFIG = {
            taxEnabled: <?= $taxEnabled ? 'true' : 'false' ?>,
            taxRate: <?= $taxRate ?>,
            taxLabel: '<?= addslashes($taxLabel) ?>',
            calendarData: <?= json_encode($calendarData) ?>,
            kioskItems: <?= json_encode($kioskItems) ?>,
            pendingOrderIds: <?= json_encode($pendingOrderIds) ?>
        };
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/kasir.js"></script>
</body>
</html>