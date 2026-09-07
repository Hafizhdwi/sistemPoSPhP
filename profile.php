<?php
require_once 'config/database.php';
requireLogin();

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    header('Location: logout.php');
    exit;
}

// Hitung statistik user
$stmtStats = $pdo->prepare("
    SELECT COUNT(*) as total_tx 
    FROM transactions 
    WHERE DATE(transaction_date) = CURDATE()
");
$stmtStats->execute();
$stats = $stmtStats->fetch();

// Ambil riwayat password terakhir
$passwordHistory = [];
try {
    $stmtHist = $pdo->prepare("
        SELECT * FROM password_history 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmtHist->execute([$_SESSION['user_id']]);
    $passwordHistory = $stmtHist->fetchAll();
} catch (Exception $e) {}

$msg = $_GET['msg'] ?? '';
$role = $currentUser['role'];
$initials = strtoupper(substr($currentUser['full_name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="brand">🏪 Mini PoS</div>
    
    <!-- USER INFO dengan DROPDOWN -->
    <div class="user-info-wrapper" id="userWrapper">
        <div class="user-info" onclick="toggleUserDropdown(event)">
            <div class="user-avatar <?= $role ?>"><?= $initials ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <span class="user-role-badge <?= $role ?>">
                    <?= $role === 'admin' ? '🛡️' : '🛒' ?> <?= ucfirst($role) ?>
                </span>
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
    <div class="mobile-header">
        <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
        <span class="brand-mobile">🏪 Mini PoS</span>
        <span style="width:30px;"></span>
    </div>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold m-0">👤 Edit Profil</h4>
            <small class="text-muted">Kelola informasi akun Anda</small>
        </div>
    </div>

    <!-- ALERTS -->
    <?php 
    $alerts = [
        'profile_updated' => ['success', '✅ Profil berhasil diperbarui!'],
        'password_changed' => ['success', '🔑 Password berhasil diubah!'],
        'error' => ['danger', '❌ Terjadi kesalahan!'],
    ];
    if (isset($alerts[$msg])): ?>
        <div class="alert alert-<?= $alerts[$msg][0] ?> shadow-sm fade show"><?= $alerts[$msg][1] ?></div>
    <?php endif; ?>

    <!-- PROFILE HEADER CARD -->
    <div class="profile-header-card <?= $role === 'kasir' ? 'kasir-theme' : '' ?>">
        <div class="d-flex align-items-center gap-4 flex-wrap position-relative">
            <div class="profile-avatar-large"><?= $initials ?></div>
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($currentUser['full_name']) ?></h3>
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <span class="badge bg-light text-dark">
                        <?= $role === 'admin' ? '🛡️ Administrator' : '🛒 Kasir' ?>
                    </span>
                    <span class="badge bg-light text-dark">@<?= htmlspecialchars($currentUser['username']) ?></span>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="profile-stat-box">
                        <div class="label">Member Sejak</div>
                        <div class="value"><?= date('d M Y', strtotime($currentUser['created_at'])) ?></div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="label">Status Akun</div>
                        <div class="value"><?= $currentUser['is_active'] ? '✅ Aktif' : '❌ Nonaktif' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- FORM EDIT PROFIL -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold">
                    ✏️ Informasi Profil
                </div>
                <div class="card-body p-4">
                    <form action="process_profile.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-control form-control-lg" 
                                   value="<?= htmlspecialchars($currentUser['full_name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Username</label>
                            <input type="text" name="username" class="form-control form-control-lg" 
                                   value="<?= htmlspecialchars($currentUser['username']) ?>" required
                                   pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                            <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Role</label>
                            <input type="text" class="form-control form-control-lg" 
                                   value="<?= ucfirst($currentUser['role']) ?>" disabled>
                            <small class="text-muted">Role tidak bisa diubah sendiri. Hubungi admin lain.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                            💾 Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- FORM GANTI PASSWORD -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold">
                    🔐 Keamanan Akun
                </div>
                <div class="card-body p-4">
                    <form action="process_profile.php" method="POST" id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Password Lama</label>
                            <div class="position-relative">
                                <input type="password" name="old_password" id="oldPassword" 
                                       class="form-control form-control-lg" required
                                       style="padding-right: 48px;">
                                <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" 
                                        style="border:none;background:none;" 
                                        onclick="togglePass('oldPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Verifikasi identitas Anda</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Password Baru</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="newPassword" 
                                       class="form-control form-control-lg" required minlength="6"
                                       style="padding-right: 48px;">
                                <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" 
                                        style="border:none;background:none;" 
                                        onclick="togglePass('newPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Konfirmasi Password Baru</label>
                            <div class="position-relative">
                                <input type="password" name="confirm_password" id="confirmPassword" 
                                       class="form-control form-control-lg" required
                                       style="padding-right: 48px;">
                                <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" 
                                        style="border:none;background:none;" 
                                        onclick="togglePass('confirmPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-danger" id="matchError" style="display:none;">❌ Password tidak sama</small>
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100 py-3 fw-bold" id="btnChangePass">
                            🔑 Ganti Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIWAYAT PASSWORD -->
            <?php if (!empty($passwordHistory)): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header fw-bold">
                    📜 Riwayat Perubahan Password
                </div>
                <div class="card-body p-3">
                    <?php foreach ($passwordHistory as $ph): ?>
                        <div class="security-item">
                            <div class="security-icon <?= $ph['change_method'] === 'default_reset' ? 'warning' : ($ph['change_method'] === 'initial' ? 'info' : 'success') ?>">
                                <?= $ph['change_method'] === 'default_reset' ? '⚡' : ($ph['change_method'] === 'initial' ? '🆕' : '✏️') ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">
                                    <?= match($ph['change_method']) {
                                        'default_reset' => 'Reset ke Default',
                                        'initial' => 'Password Awal',
                                        'custom' => 'Password Custom',
                                        default => '-'
                                    } ?>
                                </div>
                                <small class="text-muted">
                                    <?= date('d M Y, H:i', strtotime($ph['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
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

// ==================== PASSWORD TOGGLE ====================
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// ==================== PASSWORD MATCH VALIDATION ====================
const newPassInput = document.getElementById('newPassword');
const confirmPassInput = document.getElementById('confirmPassword');
const matchError = document.getElementById('matchError');
const btnChangePass = document.getElementById('btnChangePass');

function checkMatch() {
    if (confirmPassInput.value.length === 0) {
        matchError.style.display = 'none';
        return true;
    }
    const isMatch = newPassInput.value === confirmPassInput.value;
    matchError.style.display = isMatch ? 'none' : 'block';
    return isMatch;
}

newPassInput.addEventListener('input', checkMatch);
confirmPassInput.addEventListener('input', checkMatch);

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    if (!checkMatch()) {
        e.preventDefault();
        alert('❌ Password baru dan konfirmasi tidak sama!');
        return false;
    }
    if (newPassInput.value.length < 6) {
        e.preventDefault();
        alert('❌ Password minimal 6 karakter!');
        return false;
    }
});
</script>
</body>
</html>