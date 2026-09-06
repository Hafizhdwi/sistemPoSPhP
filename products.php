<?php
require_once 'config/database.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: products.php?msg=deleted');
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll();
$stockHistory = $pdo->query("
    SELECT sh.*, p.name as product_name 
    FROM stock_history sh 
    JOIN products p ON sh.product_id = p.id 
    ORDER BY sh.created_at DESC LIMIT 50
")->fetchAll();

$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$msg = $_GET['msg'] ?? '';
$activeTab = $_GET['tab'] ?? 'products';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Mini PoS</title>
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
                <a href="users.php" class="nav-link">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link active">📦 Produk</a>
            <?php endif; ?>
            <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
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

        <?php
        $alerts = [
            'added' => ['success', '✅ Produk berhasil ditambahkan!'],
            'updated' => ['success', '✅ Produk berhasil diperbarui!'],
            'deleted' => ['warning', '🗑️ Produk berhasil dihapus!'],
            'restocked' => ['success', '📥 Stok berhasil ditambahkan!'],
            'stock_adjusted' => ['success', '📊 Stok berhasil disesuaikan!'],
            'nochange' => ['info', 'ℹ️ Tidak ada perubahan stok.'],
        ];
        if (isset($alerts[$msg])): ?>
            <div class="alert alert-<?= $alerts[$msg][0] ?> shadow-sm fade show"><?= $alerts[$msg][1] ?></div>
        <?php endif; ?>

        <!-- TABS -->
        <ul class="nav nav-pills mb-4 gap-2 flex-wrap">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'products' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=products">📦 Produk</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'restock' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=restock">📥 Restock</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=history">📋 Mutasi</a>
            </li>
        </ul>

        <!-- ==================== TAB: DAFTAR PRODUK ==================== -->
        <?php if ($activeTab === 'products'): ?>
            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header"><?= $editProduct ? '✏️ Edit Produk' : '➕ Tambah Produk Baru' ?></div>
                        <div class="card-body p-3 p-md-4">
                            <form action="process_product.php" method="POST">
                                <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                                <?php if ($editProduct): ?>
                                    <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Nama Produk</label>
                                    <input type="text" name="name" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Harga (Rp)</label>
                                    <input type="number" name="price" class="form-control form-control-lg"
                                        value="<?= $editProduct['price'] ?? '' ?>" required min="0">
                                </div>

                                <?php if (!$editProduct): ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold small text-muted">Stok Awal</label>
                                        <input type="number" name="stock" class="form-control form-control-lg" placeholder="0" required min="0">
                                    </div>
                                <?php else: ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold small text-muted">Stok Saat Ini</label>
                                        <input type="number" class="form-control form-control-lg" value="<?= $editProduct['stock'] ?>" disabled>
                                        <small class="text-muted mt-1 d-block">💡 Untuk mengubah stok, gunakan tombol 📊 di tabel.</small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1 py-3 fw-bold">
                                        <?= $editProduct ? '💾 Update' : '💾 Simpan' ?>
                                    </button>
                                    <?php if ($editProduct): ?>
                                        <a href="products.php?tab=products" class="btn btn-outline-secondary py-3 fw-bold">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>📋 Daftar Produk</span>
                            <span class="badge bg-light text-dark border px-3 py-2"><?= count($products) ?> produk</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 ps-md-4">Nama</th>
                                            <th>Harga</th>
                                            <th class="text-center">Stok</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $p): ?>
                                            <tr>
                                                <td class="ps-3 ps-md-4 fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                                                <td class="fw-semibold text-primary"><?= formatRupiah($p['price']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= $p['stock'] < 10 ? 'bg-danger' : 'bg-success' ?> px-3 py-2">
                                                        <?= $p['stock'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-success px-2 px-md-3"
                                                            onclick="openStockModal(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['stock'] ?>)"
                                                            title="Edit Stok">📊</button>
                                                        <a href="products.php?tab=products&edit=<?= $p['id'] ?>"
                                                            class="btn btn-outline-primary px-2 px-md-3" title="Edit Produk">✏️</a>
                                                        <a href="products.php?delete=<?= $p['id'] ?>"
                                                            class="btn btn-outline-danger px-2 px-md-3"
                                                            onclick="return confirm('Yakin hapus?')" title="Hapus">🗑️</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($products)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <div style="font-size:3rem;">📦</div>
                                                    <h6 class="fw-bold mt-3">Belum ada produk</h6>
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
        <?php endif; ?>

        <!-- ==================== TAB: RESTOCK ==================== -->
        <?php if ($activeTab === 'restock'): ?>
            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-header">📥 Form Restock Produk</div>
                        <div class="card-body p-3 p-md-4">
                            <form action="process_product.php" method="POST">
                                <input type="hidden" name="action" value="restock">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Pilih Produk</label>
                                    <select name="product_id" class="form-select form-select-lg" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Stok: <?= $p['stock'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Jumlah Masuk</label>
                                    <input type="number" name="quantity" class="form-control form-control-lg" placeholder="0" required min="1">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">Catatan (Opsional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Pembelian dari supplier ABC"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold">📥 Tambah Stok</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">ℹ️ Panduan Restock</div>
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4 p-md-5">
                            <div style="font-size:4rem; opacity:0.3;">📥</div>
                            <h5 class="fw-bold mt-3">Cara Restock</h5>
                            <p class="text-muted">Pilih produk, masukkan jumlah masuk, dan catatan. Setiap restock tercatat di Mutasi Stok.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ==================== TAB: MUTASI STOK ==================== -->
        <?php if ($activeTab === 'history'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>📋 Riwayat Mutasi Stok (50 Terakhir)</span>
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
                                            <div style="font-size:3rem;">📋</div>
                                            <h6 class="fw-bold mt-3">Belum ada riwayat mutasi</h6>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL EDIT STOK -->
    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold">📊 Edit Stok Produk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="process_product.php" method="POST" id="stockForm">
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="product_id" id="modalProductId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Produk</label>
                            <input type="text" class="form-control" id="modalProductName" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Stok Saat Ini</label>
                            <input type="number" class="form-control" id="modalCurrentStock" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Stok Baru <span class="text-danger">*</span></label>
                            <input type="number" name="new_stock" class="form-control form-control-lg fw-bold"
                                id="modalNewStock" required min="0" placeholder="0">
                            <small class="text-muted" id="stockDiff"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Catatan Penyesuaian</label>
                            <textarea name="notes" class="form-control" rows="2"
                                placeholder="Contoh: Stok opname, barang rusak, koreksi data"></textarea>
                        </div>
                        <div class="alert alert-warning border-0 small mb-0">
                            ⚠️ Perubahan stok akan tercatat di Mutasi Stok sebagai penyesuaian.
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="stockForm" class="btn btn-success px-4 fw-bold">💾 Simpan Stok</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        let currentStock = 0;

        function openStockModal(id, name, stock) {
            currentStock = stock;
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductName').value = name;
            document.getElementById('modalCurrentStock').value = stock;
            document.getElementById('modalNewStock').value = stock;
            document.getElementById('stockDiff').innerText = '';
            const modal = new bootstrap.Modal(document.getElementById('stockModal'));
            modal.show();
        }

        document.getElementById('modalNewStock').addEventListener('input', function() {
            const newStock = parseInt(this.value) || 0;
            const diff = newStock - currentStock;
            const diffEl = document.getElementById('stockDiff');
            if (diff === 0) {
                diffEl.innerText = '';
                diffEl.className = 'text-muted';
            } else if (diff > 0) {
                diffEl.innerText = `↑ Stok bertambah ${diff}`;
                diffEl.className = 'text-success fw-semibold';
            } else {
                diffEl.innerText = `↓ Stok berkurang ${Math.abs(diff)}`;
                diffEl.className = 'text-danger fw-semibold';
            }
        });
    </script>
</body>

</html>