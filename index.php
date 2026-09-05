<?php
require_once 'config/database.php';
requireLogin();

// Ambil semua produk untuk search
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$allProducts = $stmt->fetchAll();
$products = array_filter($allProducts, fn($p) => $p['stock'] > 0);

// ✅ Load pesanan kiosk ke cart jika ada parameter process_order
$processOrder = null;
$processItems = [];
if (isset($_GET['process_order'])) {
    $orderId = intval($_GET['process_order']);
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    $processOrder = $stmt->fetch();

    if ($processOrder) {
        $stmtItems = $pdo->prepare("
            SELECT td.product_id, td.quantity, p.name, p.price 
            FROM transaction_details td 
            JOIN products p ON td.product_id = p.id 
            WHERE td.transaction_id = ?
        ");
        $stmtItems->execute([$orderId]);
        $processItems = $stmtItems->fetchAll();
    }
}

// ✅ Ambil pesanan kiosk yang masih pending
$pendingOrders = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    WHERE t.status = 'pending' AND t.order_type = 'kiosk'
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .search-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .search-wrapper input {
            padding-left: 48px;
            border-radius: 14px;
            border: 2px solid var(--border-color);
            font-size: 1rem;
            transition: all 0.2s;
        }

        .search-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
            padding: 4px 8px;
            border-radius: 50%;
        }

        .search-clear:hover {
            background: #f3f4f6;
            color: var(--text-main);
        }

        .shortcut-hint {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: #f3f4f6;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: monospace;
            pointer-events: none;
        }

        .product-card.out-of-stock {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(0.5);
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .no-results.show {
            display: block;
        }

        .pending-panel {
            border-left: 5px solid #e63946 !important;
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
            <?php endif; ?>
            <a href="index.php" class="nav-link active">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
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

        <!-- ALERT SUKSES -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2" role="alert">
                <span>✅ <?= $successMsg ?></span>
                <?php if ($invoiceNumber): ?>
                    <a href="receipt.php?invoice=<?= urlencode($invoiceNumber) ?>"
                        target="_blank"
                        class="btn btn-sm btn-dark fw-bold px-4 py-2 no-print">
                        🖨️ CETAK STRUK
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ✅ ALERT JIKA SEDANG PROSES PESANAN KIOSK -->
        <?php if ($processOrder): ?>
            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>🖥️ <strong>Memproses Pesanan Kiosk</strong> — <?= htmlspecialchars($processOrder['customer_name']) ?> | Meja <?= htmlspecialchars($processOrder['table_number']) ?> | Invoice: <code><?= $processOrder['invoice_number'] ?></code></span>
                <a href="index.php" class="btn btn-sm btn-outline-secondary fw-bold">✕ Batal</a>
            </div>
        <?php endif; ?>

        <!-- ✅ PANEL PESANAN KIOSK PENDING -->
        <?php if (!empty($pendingOrders)): ?>
            <div class="card border-0 shadow-sm mb-4 pending-panel">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold m-0 text-danger">🔔 Pesanan Kiosk Pending (<?= count($pendingOrders) ?>)</h5>
                        <span class="badge bg-danger px-3 py-2">Perlu Diproses</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Meja</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Waktu</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark fw-bold px-3 py-2">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['table_number']) ?></td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $order['item_count'] ?> item</span></td>
                                        <td class="fw-bold text-primary"><?= formatRupiah($order['total_amount']) ?></td>
                                        <td><small class="text-muted"><?= date('H:i', strtotime($order['transaction_date'])) ?></small></td>
                                        <td class="text-center">
                                            <a href="index.php?process_order=<?= $order['id'] ?>"
                                                class="btn btn-sm btn-success fw-bold px-4 py-2">
                                                💰 Bayar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3 g-lg-4">
            <!-- PRODUCT GRID -->
            <div class="col-12 col-lg-7 col-xl-8">
                <!-- SEARCH BAR -->
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

            <!-- CART PANEL -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="card cart-panel h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>🧾 Keranjang</span>
                        <span class="badge bg-white text-primary fw-bold" id="cart-count">0 item</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table cart-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th>Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <div style="font-size:2rem;">🛒</div>
                                            <small>Keranjang kosong</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted fw-semibold">Total</span>
                            <h3 class="fw-bold text-dark m-0" id="grand-total">Rp 0</h3>
                        </div>

                        <form id="checkout-form" action="process_sale.php" method="POST">
                            <input type="hidden" name="cart_data" id="cart-data">
                            <!-- ✅ Hidden field untuk kiosk order ID -->
                            <?php if ($processOrder): ?>
                                <input type="hidden" name="kiosk_order_id" value="<?= $processOrder['id'] ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-semibold">Uang Diterima</label>
                                <input type="number" name="pay_amount" class="form-control form-control-lg fw-bold" required min="0" step="any" placeholder="0">
                            </div>
                            <button type="submit" class="btn btn-pay w-100" id="btn-checkout" disabled>
                                💰 BAYAR SEKARANG
                            </button>
                        </form>
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

        // ==================== SEARCH ====================
        const searchInput = document.getElementById('productSearch');
        const searchClear = document.getElementById('searchClear');
        const productCols = document.querySelectorAll('.product-col');
        const noResults = document.getElementById('noResults');
        const productCount = document.getElementById('productCount');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;
            searchClear.style.display = query.length > 0 ? 'block' : 'none';

            productCols.forEach(col => {
                const name = col.getAttribute('data-name');
                const match = name.includes(query);
                col.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            noResults.classList.toggle('show', visibleCount === 0 && query.length > 0);
            productCount.textContent = visibleCount + ' produk';
        });

        function clearSearch() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                clearSearch();
                searchInput.blur();
            }
        });

        // ==================== CART LOGIC ====================
        let cart = [];

        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) existing.qty++;
            else cart.push({
                id,
                name,
                price,
                qty: 1
            });
            renderCart();
        }

        function updateQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            item.qty += delta;
            if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('cart-body');
            tbody.innerHTML = '';
            let total = 0;

            if (cart.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
            <div style="font-size:2rem;">🛒</div><small>Keranjang kosong</small>
        </td></tr>`;
            } else {
                cart.forEach(item => {
                    const sub = item.price * item.qty;
                    total += sub;
                    tbody.innerHTML += `<tr>
                <td class="ps-3"><div class="fw-semibold">${item.name}</div></td>
                <td>
                    <div class="qty-btn" onclick="updateQty(${item.id},-1)">−</div>
                    <span class="mx-1 mx-md-2 fw-bold">${item.qty}</span>
                    <div class="qty-btn" onclick="updateQty(${item.id},1)">+</div>
                </td>
                <td class="text-end fw-semibold">${sub.toLocaleString('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0})}</td>
                <td class="text-center"><button class="btn btn-sm text-danger p-0" onclick="removeFromCart(${item.id})">✕</button></td>
            </tr>`;
                });
            }

            document.getElementById('grand-total').innerText = total.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            document.getElementById('cart-count').innerText = cart.reduce((a, b) => a + b.qty, 0) + ' item';
            document.getElementById('cart-data').value = JSON.stringify(cart);
            document.getElementById('btn-checkout').disabled = cart.length === 0;
        }

        // ==================== ✅ AUTO-LOAD CART DARI PESANAN KIOSK ====================
        <?php if ($processOrder && !empty($processItems)): ?>
                (function() {
                    <?php foreach ($processItems as $item): ?>
                        cart.push({
                            id: <?= $item['product_id'] ?>,
                            name: '<?= addslashes($item['name']) ?>',
                            price: <?= $item['price'] ?>,
                            qty: <?= $item['quantity'] ?>
                        });
                    <?php endforeach; ?>
                    renderCart();

                    // Auto-focus ke input uang diterima
                    setTimeout(() => {
                        document.querySelector('[name="pay_amount"]').focus();
                        document.querySelector('[name="pay_amount"]').scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 500);
                })();
        <?php endif; ?>
    </script>
</body>

</html>