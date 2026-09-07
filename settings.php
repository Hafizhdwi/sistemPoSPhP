<?php
require_once 'config/database.php';
requireAdmin();

$msg = $_GET['msg'] ?? '';
$activeTab = $_GET['tab'] ?? 'general';
$settings = getAllSettings($pdo);

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
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0">⚙️ Pengaturan Toko</h4>
                <small class="text-muted">Konfigurasi informasi toko, pajak, dan struk</small>
            </div>
        </div>

        <?php if ($msg === 'saved'): ?>
            <div class="alert alert-success shadow-sm fade show">✅ Pengaturan berhasil disimpan!</div>
        <?php elseif ($msg === 'error'): ?>
            <div class="alert alert-danger shadow-sm fade show">❌ Gagal menyimpan pengaturan!</div>
        <?php endif; ?>

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
                <div class="col-12 col-lg-7">
                    <?php if ($activeTab === 'general'): ?>
                        <?php include 'components/settings/tab-general.php'; ?>
                    <?php elseif ($activeTab === 'tax'): ?>
                        <?php include 'components/settings/tab-tax.php'; ?>
                    <?php elseif ($activeTab === 'receipt'): ?>
                        <?php include 'components/settings/tab-receipt.php'; ?>
                    <?php endif; ?>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary py-3 px-5 fw-bold flex-grow-1">💾 Simpan Pengaturan</button>
                        <a href="settings.php?tab=<?= $activeTab ?>" class="btn btn-outline-secondary py-3 px-4 fw-bold">Batal</a>
                    </div>
                </div>

                <?php include 'components/settings/preview-panel.php'; ?>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/settings.js"></script>
</body>
</html>