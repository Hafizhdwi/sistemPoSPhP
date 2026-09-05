<?php
require_once 'config/database.php';
requireLogin();

$stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name ASC");
$products = $stmt->fetchAll();

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

    <!-- OVERLAY MOBILE -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- MAIN CONTENT -->
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

        <div class="row g-3 g-lg-4">
            <!-- PRODUCT GRID -->
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0">Pilih Produk</h4>
                    <span class="badge bg-light text-dark border px-3 py-2 d-none d-sm-inline"><?= count($products) ?> produk</span>
                </div>

                <div class="row g-2 g-md-3">
                    <?php foreach ($products as $p): ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="product-card" onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['price'] ?>)">
                                <div class="icon">☕</div>
                                <div class="name text-truncate"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="stock">Stok: <?= $p['stock'] ?></div>
                                <div class="price"><?= formatRupiah($p['price']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="card p-4 p-md-5 text-center">
                                <div style="font-size:3rem;">😔</div>
                                <h5 class="fw-bold mt-3">Tidak ada produk tersedia</h5>
                                <p class="text-muted mb-0">Tambahkan produk atau restock di menu Produk</p>
                            </div>
                        </div>
                    <?php endif; ?>
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
        // Toggle Sidebar Mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        // Cart Logic
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
    </script>
</body>

</html>