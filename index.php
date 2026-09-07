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
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status IN ('pending','preparing','ready')");
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

// ✅ Ambil pesanan kiosk yang masih aktif (pending, preparing, ready)
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

// ✅ Ambil setting pajak untuk JavaScript
$taxEnabled = getSetting($pdo, 'tax_enabled', '0') == '1';
$taxRate = floatval(getSetting($pdo, 'tax_rate', '0'));
$taxLabel = getSetting($pdo, 'tax_label', 'Pajak');

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
    <title>Kasir - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Search */
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

        @keyframes slideIn {
            from {
                transform: translateX(150%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
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

        <!-- ALERT PROSES PESANAN KIOSK -->
        <?php if ($processOrder): ?>
            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>🖥️ <strong>Memproses Pesanan Kiosk</strong> — <?= htmlspecialchars($processOrder['customer_name']) ?> | Meja <?= htmlspecialchars($processOrder['table_number']) ?> | <code><?= $processOrder['invoice_number'] ?></code></span>
                <a href="index.php" class="btn btn-sm btn-outline-secondary fw-bold">✕ Batal</a>
            </div>
        <?php endif; ?>

        <!-- PANEL PESANAN KIOSK AKTIF -->
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
                                            $badge = match ($order['status']) {
                                                'pending' => '<span class="badge bg-danger">PENDING</span>',
                                                'preparing' => '<span class="badge bg-warning text-dark">DIMASAK</span>',
                                                'ready' => '<span class="badge bg-success">SIAP</span>',
                                                default => '<span class="badge bg-secondary">-</span>'
                                            };
                                            echo $badge;
                                            ?>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $order['item_count'] ?> item</span></td>
                                        <td class="fw-bold text-primary"><?= formatRupiah($order['total_amount']) ?></td>
                                        <td class="text-center">
                                            <?php if ($order['status'] === 'ready'): ?>
                                                <a href="index.php?process_order=<?= $order['id'] ?>"
                                                    class="btn btn-sm btn-success fw-bold px-4 py-2">
                                                    💰 Bayar
                                                </a>
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
                    <div class="card-footer bg-white border-top p-3 p-md-4" id="cart-footer">
                        <!-- ✅ Tempat untuk subtotal dan pajak (akan di-generate JS) -->
                        <div id="cart-summary"></div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3" id="grand-total-wrapper">
                            <span class="text-muted fw-semibold">Total</span>
                            <h3 class="fw-bold text-dark m-0" id="grand-total">Rp 0</h3>
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
        
        // ✅ Setting pajak dari PHP
        const TAX_ENABLED = <?= $taxEnabled ? 'true' : 'false' ?>;
        const TAX_RATE = <?= $taxRate ?>;
        const TAX_LABEL = '<?= addslashes($taxLabel) ?>';

        function formatCurrency(amount) {
            return amount.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
        }

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
            const summary = document.getElementById('cart-summary');
            tbody.innerHTML = '';
            summary.innerHTML = '';
            let subtotal = 0;

            if (cart.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
                    <div style="font-size:2rem;">🛒</div><small>Keranjang kosong</small>
                </td></tr>`;
                document.getElementById('grand-total').innerText = 'Rp 0';
                document.getElementById('cart-count').innerText = '0 item';
                document.getElementById('cart-data').value = '[]';
                document.getElementById('btn-checkout').disabled = true;
                return;
            }

            cart.forEach(item => {
                const sub = item.price * item.qty;
                subtotal += sub;
                tbody.innerHTML += `<tr>
                    <td class="ps-3"><div class="fw-semibold">${item.name}</div></td>
                    <td>
                        <div class="qty-btn" onclick="updateQty(${item.id},-1)">−</div>
                        <span class="mx-1 mx-md-2 fw-bold">${item.qty}</span>
                        <div class="qty-btn" onclick="updateQty(${item.id},1)">+</div>
                    </td>
                    <td class="text-end fw-semibold">${formatCurrency(sub)}</td>
                    <td class="text-center"><button class="btn btn-sm text-danger p-0" onclick="removeFromCart(${item.id})">✕</button></td>
                </tr>`;
            });

            // ✅ Hitung pajak
            let taxAmount = 0;
            if (TAX_ENABLED && TAX_RATE > 0) {
                taxAmount = Math.round(subtotal * TAX_RATE / 100);
                
                // Tampilkan baris subtotal
                summary.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Subtotal</span>
                        <span class="small fw-semibold">${formatCurrency(subtotal)}</span>
                    </div>
                `;
                
                // Tampilkan baris pajak
                summary.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">${TAX_LABEL}</span>
                        <span class="small fw-semibold">${formatCurrency(taxAmount)}</span>
                    </div>
                `;
            }

            const grandTotal = subtotal + taxAmount;

            document.getElementById('grand-total').innerText = formatCurrency(grandTotal);
            document.getElementById('cart-count').innerText = cart.reduce((a, b) => a + b.qty, 0) + ' item';
            document.getElementById('cart-data').value = JSON.stringify(cart);
            document.getElementById('btn-checkout').disabled = cart.length === 0;
        }

        // ==================== AUTO-LOAD CART DARI PESANAN KIOSK ====================
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
                    setTimeout(() => {
                        document.querySelector('[name="pay_amount"]').focus();
                        document.querySelector('[name="pay_amount"]').scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 500);
                })();
        <?php endif; ?>

        // ==================== AUTO-POLLING PESANAN KIOSK BARU ====================
        let knownPendingIds = <?= json_encode(array_column($pendingOrders, 'id')) ?>;

        setInterval(function() {
            fetch('index.php?ajax_pending=1')
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPanel = doc.querySelector('.pending-panel');
                    const existingPanel = document.querySelector('.pending-panel');

                    const newIds = [];
                    if (newPanel) {
                        newPanel.querySelectorAll('a[href*="process_order"]').forEach(link => {
                            const id = parseInt(link.getAttribute('href').split('=')[1]);
                            newIds.push(id);
                        });
                    }

                    const hasNew = newIds.some(id => !knownPendingIds.includes(id));
                    if (hasNew) {
                        playCashierAlert();
                        showCashierToast();
                    }

                    if (newPanel && !existingPanel) {
                        const mobileHeader = document.querySelector('.mobile-header');
                        if (mobileHeader) mobileHeader.insertAdjacentElement('afterend', newPanel);
                    } else if (newPanel && existingPanel) {
                        existingPanel.outerHTML = newPanel.outerHTML;
                    } else if (!newPanel && existingPanel) {
                        existingPanel.remove();
                    }

                    knownPendingIds = newIds;
                })
                .catch(() => {});
        }, 5000);

        function playCashierAlert() {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 600;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.8);
            } catch (e) {}
        }

        function showCashierToast() {
            let toast = document.getElementById('cashierToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'cashierToast';
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#e63946;color:white;padding:16px 24px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 30px rgba(230,57,70,0.4);animation:slideIn 0.3s ease;';
                document.body.appendChild(toast);
            }
            toast.textContent = '🔔 Pesanan kiosk baru masuk!';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4000);
        }
    </script>
</body>

</html>