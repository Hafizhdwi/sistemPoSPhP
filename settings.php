<?php
require_once 'config/database.php';
requireAdmin();

$msg = $_GET['msg'] ?? '';
$activeTab = $_GET['tab'] ?? 'general';

// Ambil semua settings
$settings = getAllSettings($pdo);

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
    <title>Pengaturan Toko - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .settings-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .settings-card .card-header {
            background: white;
            border-bottom: 2px solid #f3f4f6;
            padding: 20px 24px;
            font-weight: 700;
        }
        .setting-group-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            font-weight: 700;
            margin-top: 24px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .setting-group-title:first-child { margin-top: 0; }
        
        .logo-preview {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            border: 2px dashed #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9fafb;
            margin-bottom: 12px;
        }
        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .logo-preview .placeholder {
            text-align: center;
            color: #9ca3af;
            font-size: 0.8rem;
        }
        .logo-preview .placeholder i {
            font-size: 2rem;
            display: block;
            margin-bottom: 4px;
        }
        
        /* Receipt preview */
        .receipt-preview {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            max-width: 300px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .receipt-preview .divider {
            border-top: 1px dashed #ccc;
            margin: 8px 0;
        }
        .receipt-preview .center { text-align: center; }
        .receipt-preview .bold { font-weight: bold; }
        .receipt-preview .right { text-align: right; }
        .receipt-preview h3 { font-size: 1rem; margin: 0 0 4px; }
        .receipt-preview p { margin: 2px 0; font-size: 0.7rem; }
        
        .switch-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
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
        <a href="index.php" class="nav-link">🛒 Kasir</a>
        <?php if (hasRole('admin')): ?>
            <a href="products.php" class="nav-link">📦 Produk</a>
            <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
            <a href="settings.php" class="nav-link active">⚙️ Pengaturan</a>
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
            <h4 class="fw-bold m-0">⚙️ Pengaturan Toko</h4>
            <small class="text-muted">Konfigurasi informasi toko, pajak, dan struk</small>
        </div>
    </div>

    <!-- ALERT -->
    <?php if ($msg === 'saved'): ?>
        <div class="alert alert-success shadow-sm fade show">✅ Pengaturan berhasil disimpan!</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-danger shadow-sm fade show">❌ Gagal menyimpan pengaturan!</div>
    <?php endif; ?>

    <!-- TAB NAVIGATION -->
    <ul class="nav nav-pills mb-4 gap-2 flex-wrap">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=general">🏪 Informasi Toko</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'tax' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=tax">💰 Pajak</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'receipt' ? 'active' : '' ?> px-3 px-md-4 fw-semibold" href="?tab=receipt">🧾 Struk</a>
        </li>
    </ul>

    <form action="process_settings.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="active_tab" value="<?= $activeTab ?>">
        
        <div class="row g-4">
            <!-- FORM SETTINGS -->
            <div class="col-12 col-lg-7">

                <!-- ==================== TAB: INFORMASI TOKO ==================== -->
                <?php if ($activeTab === 'general'): ?>
                <div class="card settings-card">
                    <div class="card-header">🏪 Informasi Umum Toko</div>
                    <div class="card-body p-4">
                        
                        <!-- Logo -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Logo Toko</label>
                            <div class="d-flex align-items-start gap-3 flex-wrap">
                                <div class="logo-preview" id="logoPreview">
                                    <?php if (!empty($settings['store_logo'])): ?>
                                        <img src="<?= htmlspecialchars($settings['store_logo']) ?>" alt="Logo">
                                    <?php else: ?>
                                        <div class="placeholder">
                                            <i class="bi bi-image"></i>
                                            Belum ada logo
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="file" name="store_logo" class="form-control form-control-sm mb-2" 
                                           accept="image/png,image/jpeg,image/svg+xml" onchange="previewLogo(this)">
                                    <small class="text-muted">Format: PNG, JPG, SVG. Maks: 2MB</small><br>
                                    <?php if (!empty($settings['store_logo'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeLogo()">
                                            🗑️ Hapus Logo
                                        </button>
                                        <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nama Toko</label>
                            <input type="text" name="store_name" class="form-control form-control-lg" 
                                   value="<?= htmlspecialchars($settings['store_name'] ?? 'Mini PoS') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Alamat Toko</label>
                            <textarea name="store_address" class="form-control" rows="2"><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Nomor Telepon</label>
                                <input type="text" name="store_phone" class="form-control" 
                                       value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Email</label>
                                <input type="email" name="store_email" class="form-control" 
                                       value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== TAB: PAJAK ==================== -->
                <?php if ($activeTab === 'tax'): ?>
                <div class="card settings-card">
                    <div class="card-header">💰 Pengaturan Pajak</div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Status Pajak</label>
                            <div class="switch-wrapper">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           name="tax_enabled" id="taxEnabled" value="1"
                                           <?= ($settings['tax_enabled'] ?? '0') == '1' ? 'checked' : '' ?>
                                           onchange="toggleTaxFields()">
                                    <label class="form-check-label fw-semibold" for="taxEnabled">
                                        Aktifkan Pajak
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted">Jika diaktifkan, pajak akan dihitung otomatis saat transaksi</small>
                        </div>

                        <div id="taxFields" style="<?= ($settings['tax_enabled'] ?? '0') == '1' ? '' : 'opacity:0.5; pointer-events:none;' ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Tarif Pajak (%)</label>
                                    <div class="input-group">
                                        <input type="number" name="tax_rate" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($settings['tax_rate'] ?? '11') ?>" 
                                               min="0" max="100" step="0.5">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Label Pajak</label>
                                    <input type="text" name="tax_label" class="form-control form-control-lg" 
                                           value="<?= htmlspecialchars($settings['tax_label'] ?? 'PPN 11%') ?>"
                                           placeholder="Contoh: PPN 11%">
                                    <small class="text-muted">Teks yang ditampilkan di struk</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 mt-4 small">
                            <strong>ℹ️ Cara Kerja:</strong> Pajak dihitung dari subtotal sebelum total akhir.
                            <br>Contoh: Subtotal Rp 100.000 × 11% = Pajak Rp 11.000 → Total Rp 111.000
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ==================== TAB: STRUK ==================== -->
                <?php if ($activeTab === 'receipt'): ?>
                <div class="card settings-card">
                    <div class="card-header">🧾 Pengaturan Struk / Invoice</div>
                    <div class="card-body p-4">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Pesan Header Struk</label>
                            <input type="text" name="receipt_header" class="form-control" 
                                   value="<?= htmlspecialchars($settings['receipt_header'] ?? '') ?>"
                                   placeholder="Contoh: Terima kasih atas kunjungan Anda!">
                            <small class="text-muted">Ditampilkan di bagian atas struk setelah info toko</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Pesan Footer Struk</label>
                            <textarea name="receipt_footer" class="form-control" rows="2"
                                      placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                            <small class="text-muted">Ditampilkan di bagian bawah struk</small>
                        </div>

                        <div class="setting-group-title">Opsi Tampilan Struk</div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="receipt_show_logo" id="receiptShowLogo" value="1"
                                   <?= ($settings['receipt_show_logo'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="receiptShowLogo">
                                Tampilkan Logo di Struk
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   name="receipt_show_address" id="receiptShowAddress" value="1"
                                   <?= ($settings['receipt_show_address'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="receiptShowAddress">
                                Tampilkan Alamat & Telepon di Struk
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SAVE BUTTON -->
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary py-3 px-5 fw-bold flex-grow-1">
                        💾 Simpan Pengaturan
                    </button>
                    <a href="settings.php?tab=<?= $activeTab ?>" class="btn btn-outline-secondary py-3 px-4 fw-bold">
                        Batal
                    </a>
                </div>
            </div>

            <!-- PREVIEW PANEL -->
            <div class="col-12 col-lg-5">
                
                <!-- Preview Struk -->
                <div class="card settings-card">
                    <div class="card-header">👁️ Preview Struk</div>
                    <div class="card-body p-4">
                        <div class="receipt-preview" id="receiptPreview">
                            <!-- Logo -->
                            <?php if (($settings['receipt_show_logo'] ?? '1') == '1' && !empty($settings['store_logo'])): ?>
                                <div class="center" style="margin-bottom:8px;">
                                    <img src="<?= htmlspecialchars($settings['store_logo']) ?>" style="max-height:40px;" alt="Logo">
                                </div>
                            <?php endif; ?>
                            
                            <div class="center bold" style="font-size:0.9rem;">
                                <?= htmlspecialchars($settings['store_name'] ?? 'Mini PoS') ?>
                            </div>
                            
                            <?php if (($settings['receipt_show_address'] ?? '1') == '1'): ?>
                                <p class="center"><?= htmlspecialchars($settings['store_address'] ?? '') ?></p>
                                <p class="center"><?= htmlspecialchars($settings['store_phone'] ?? '') ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($settings['receipt_header'])): ?>
                                <p class="center" style="color:#666;"><?= htmlspecialchars($settings['receipt_header']) ?></p>
                            <?php endif; ?>
                            
                            <div class="divider"></div>
                            
                            <div style="display:flex; justify-content:space-between;">
                                <span>No:</span>
                                <span>INV-20250101001</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>Tgl:</span>
                                <span><?= date('d/m/Y H:i') ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>Kasir:</span>
                                <span><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div style="display:flex; justify-content:space-between;">
                                <span>Kopi Susu x2</span>
                                <span>36.000</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>Croissant x1</span>
                                <span>25.000</span>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div style="display:flex; justify-content:space-between;">
                                <span>Subtotal</span>
                                <span>61.000</span>
                            </div>
                            
                            <?php if (($settings['tax_enabled'] ?? '0') == '1'): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span><?= htmlspecialchars($settings['tax_label'] ?? 'PPN 11%') ?></span>
                                <span>6.710</span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem;" class="bold">
                                <span>TOTAL</span>
                                <span><?= ($settings['tax_enabled'] ?? '0') == '1' ? '67.710' : '61.000' ?></span>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between;">
                                <span>Tunai</span>
                                <span>100.000</span>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>Kembali</span>
                                <span><?= ($settings['tax_enabled'] ?? '0') == '1' ? '32.290' : '39.000' ?></span>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <?php if (!empty($settings['receipt_footer'])): ?>
                                <p class="center" style="color:#666;"><?= htmlspecialchars($settings['receipt_footer']) ?></p>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted text-center d-block mt-3">
                            * Preview menggunakan data contoh
                        </small>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card settings-card mt-3 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">ℹ️ Informasi</h6>
                        <ul class="small mb-0 text-muted">
                            <li>Pengaturan ini berlaku untuk seluruh sistem</li>
                            <li>Logo akan tampil di kiosk dan struk cetak</li>
                            <li>Perubahan pajak berlaku untuk transaksi baru</li>
                            <li>Transaksi lama tidak terpengaruh perubahan pajak</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

// ==================== LOGO PREVIEW ====================
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==================== REMOVE LOGO ====================
function removeLogo() {
    if (confirm('Yakin ingin menghapus logo toko?')) {
        document.getElementById('removeLogoInput').value = '1';
        document.getElementById('logoPreview').innerHTML = `
            <div class="placeholder">
                <i class="bi bi-image"></i>
                Belum ada logo
            </div>`;
    }
}

// ==================== TOGGLE TAX FIELDS ====================
function toggleTaxFields() {
    const enabled = document.getElementById('taxEnabled').checked;
    const fields = document.getElementById('taxFields');
    if (enabled) {
        fields.style.opacity = '1';
        fields.style.pointerEvents = 'auto';
    } else {
        fields.style.opacity = '0.5';
        fields.style.pointerEvents = 'none';
    }
}
</script>
</body>
</html> 