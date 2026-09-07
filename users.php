<?php
require_once 'config/database.php';
requireAdmin();

// ==================== PAGINATION SETUP USERS ====================
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $perPage));

if ($page > $totalPages && $totalPages > 0) {
    header("Location: users.php?page=$totalPages");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY role ASC, full_name ASC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();

// ==================== EDIT MODE ====================
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

// ==================== PAGINATION LOG AKTIVITAS ====================
$logPerPage = 5; // ✅ 5 log per halaman, scrollable container
$logPage = max(1, intval($_GET['log_page'] ?? 1));
$logOffset = ($logPage - 1) * $logPerPage;

// Hitung total log aktivitas
$totalLogs = 0;
try {
    $countStmt = $pdo->query("
        SELECT COUNT(*) 
        FROM activity_logs al
        WHERE 
            (al.entity_type IS NULL OR al.entity_type NOT IN ('transaction', 'transaction_detail', 'stock', 'stock_history'))
            AND al.action NOT LIKE 'transaction_%'
            AND al.action NOT LIKE 'stock_%'
    ");
    $totalLogs = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalLogs = 0;
}

$logTotalPages = max(1, ceil($totalLogs / $logPerPage));

// Ambil log dengan pagination dan urutkan terbaru ke terlama
$activityLogs = [];
try {
    $stmtLogs = $pdo->prepare("
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
        LIMIT ? OFFSET ?
    ");
    $stmtLogs->execute([$logPerPage, $logOffset]);
    $activityLogs = $stmtLogs->fetchAll();
} catch (Exception $e) {
    $activityLogs = [];
}

// ==================== PASSWORD HISTORY ====================
$passwordHistory = [];
if ($editUser) {
    try {
        $stmtHist = $pdo->prepare("
            SELECT ph.*, u.full_name as changer_name 
            FROM password_history ph 
            LEFT JOIN users u ON ph.changed_by_user_id = u.id 
            WHERE ph.user_id = ? 
            ORDER BY ph.created_at DESC 
            LIMIT 5
        ");
        $stmtHist->execute([$editUser['id']]);
        $passwordHistory = $stmtHist->fetchAll();
    } catch (Exception $e) {
        $passwordHistory = [];
    }
}

$msg = $_GET['msg'] ?? '';

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
    <title>Manajemen User - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            z-index: 5;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            background: #ef4444;
            width: 33%;
        }

        .strength-medium {
            background: #f59e0b;
            width: 66%;
        }

        .strength-strong {
            background: #22c55e;
            width: 100%;
        }

        .password-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .password-option:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.02);
        }

        .password-option.selected {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }

        .password-option input[type="radio"] {
            margin-top: 4px;
            accent-color: var(--primary);
        }

        .password-option .option-content {
            flex: 1;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .page-btn:hover:not(.disabled):not(.active) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .page-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .page-info {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0 10px;
        }

        .password-history-item {
            background: #f9fafb;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }

        /* ✅ COMPACT ACTIVITY ITEM */
        .activity-item-compact {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s;
        }

        .activity-item-compact:last-child {
            border-bottom: none;
        }

        .activity-item-compact:hover {
            background: #f0f4ff !important;
        }

        .activity-icon-compact {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .activity-icon-compact.success {
            background: #dcfce7;
            color: #16a34a;
        }

        .activity-icon-compact.failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .activity-icon-compact.info {
            background: #dbeafe;
            color: #2563eb;
        }

        .activity-icon-compact.warning {
            background: #fef3c7;
            color: #d97706;
        }

        .activity-icon-compact.dark {
            background: #e5e7eb;
            color: #111827;
        }

        .activity-icon-compact.primary {
            background: #e0e7ff;
            color: #4f46e5;
        }

        /* ✅ COMPACT LOG PAGINATION */
        .log-page-btn {
            min-width: 28px;
            height: 28px;
            padding: 0 6px;
            border-radius: 6px;
            border: 1.5px solid #e5e7eb;
            background: white;
            color: #374151;
            font-weight: 600;
            font-size: 0.72rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .log-page-btn:hover:not(.disabled):not(.active) {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }

        .log-page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .log-page-btn.disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* ✅ CUSTOM SCROLLBAR */
        .log-scroll-container::-webkit-scrollbar {
            width: 6px;
        }

        .log-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .log-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .log-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand">🏪 Mini PoS</div>
        
        <!-- USER INFO MODERN DENGAN DROPDOWN -->
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
                <a href="users.php" class="nav-link active">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
                <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
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

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0">👥 Manajemen User</h4>
                <small class="text-muted">Kelola akun admin dan kasir</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2"><?= $totalUsers ?> user terdaftar</span>
        </div>

        <?php
        $alerts = [
            'added' => ['success', '✅ User berhasil ditambahkan!'],
            'updated' => ['success', '✅ User berhasil diperbarui!'],
            'deleted' => ['warning', '🗑️ User berhasil dihapus!'],
            'toggled' => ['info', '🔄 Status user berhasil diubah!'],
            'password_reset' => ['success', '🔑 Password berhasil direset ke default!'],
            'password_changed' => ['success', '🔑 Password berhasil diubah!'],
            'cannot_delete_self' => ['danger', '❌ Tidak bisa menghapus akun sendiri!'],
            'cannot_delete_last_admin' => ['danger', '❌ Tidak bisa menghapus admin terakhir!'],
        ];
        if (isset($alerts[$msg])): ?>
            <div class="alert alert-<?= $alerts[$msg][0] ?> shadow-sm fade show"><?= $alerts[$msg][1] ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- KOLOM KIRI -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">
                        <?= $editUser ? '✏️ Edit User' : '➕ Tambah User Baru' ?>
                        <?php if ($editUser && $editUser['id'] == $_SESSION['user_id']): ?>
                            <span class="badge bg-primary ms-2">Akun Anda</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <form action="process_user.php" method="POST">
                            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                            <?php if ($editUser): ?>
                                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Nama Lengkap</label>
                                <input type="text" name="full_name" class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>"
                                    placeholder="Contoh: Budi Santoso" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Username</label>
                                <input type="text" name="username" class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($editUser['username'] ?? '') ?>"
                                    placeholder="Contoh: budi123" required
                                    pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                                <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">
                                    <?= $editUser ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password' ?>
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="editPassword"
                                        class="form-control form-control-lg"
                                        placeholder="<?= $editUser ? 'Kosongkan = tidak diubah' : 'Minimal 6 karakter' ?>"
                                        <?= $editUser ? '' : 'required' ?>
                                        minlength="<?= $editUser ? '0' : '6' ?>"
                                        autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('editPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if ($editUser): ?>
                                    <small class="text-warning">⚠️ Kosongkan field ini jika tidak ingin mengubah password</small>
                                <?php else: ?>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="editPasswordStrength"></div>
                                    </div>
                                    <small class="text-muted" id="editPasswordHint">Gunakan kombinasi huruf & angka</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Role</label>
                                <select name="role" class="form-select form-select-lg" required>
                                    <option value="kasir" <?= ($editUser['role'] ?? '') === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                    <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>

                            <?php if ($editUser): ?>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">Status</label>
                                    <select name="is_active" class="form-select form-select-lg">
                                        <option value="1" <?= $editUser['is_active'] ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= !$editUser['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1 py-3 fw-bold">
                                    <?= $editUser ? '💾 Update User' : '💾 Simpan User' ?>
                                </button>
                                <?php if ($editUser): ?>
                                    <a href="users.php" class="btn btn-outline-secondary py-3 fw-bold">Batal</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($editUser): ?>
                    <!-- QUICK ACTIONS -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header">⚡ Aksi Cepat</div>
                        <div class="card-body">
                            <button class="btn btn-warning w-100 mb-2 py-2 fw-semibold"
                                onclick="openChangePassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">
                                🔑 Ganti Password
                            </button>
                            <?php if ($editUser['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-outline-info w-100 py-2 fw-semibold"
                                    onclick="toggleStatus(<?= $editUser['id'] ?>, <?= $editUser['is_active'] ? 0 : 1 ?>)">
                                    <?= $editUser['is_active'] ? '🔒 Nonaktifkan User' : '🔓 Aktifkan User' ?>
                                </button>
                            <?php else: ?>
                                <div class="alert alert-info border-0 small mb-0 mt-2">
                                    ℹ️ Anda sedang mengedit akun sendiri. Tombol nonaktifkan disembunyikan untuk keamanan.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- PASSWORD MANAGEMENT -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header">🔐 Manajemen Password</div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                                <div style="font-size:2rem;">🔑</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small text-muted">STATUS PASSWORD</div>
                                    <div class="fw-semibold" id="passwordStatus">Memuat...</div>
                                </div>
                            </div>

                            <?php if (!empty($passwordHistory)): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted mb-2">📜 Riwayat Perubahan Password</label>
                                    <div style="max-height: 250px; overflow-y: auto;">
                                        <?php foreach ($passwordHistory as $ph): ?>
                                            <div class="password-history-item">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <span class="badge <?= $ph['change_method'] === 'default_reset' ? 'bg-warning text-dark' : ($ph['change_method'] === 'initial' ? 'bg-info text-dark' : 'bg-success') ?>">
                                                        <?php
                                                        echo match ($ph['change_method']) {
                                                            'default_reset' => '⚡ Reset Default',
                                                            'initial' => '🆕 Password Awal',
                                                            'custom' => '✏️ Custom',
                                                            default => '-'
                                                        };
                                                        ?>
                                                    </span>
                                                    <small class="text-muted"><?= date('d/m H:i', strtotime($ph['created_at'])) ?></small>
                                                </div>
                                                <div class="small"><strong>Diubah oleh:</strong> <?= htmlspecialchars($ph['changed_by_name'] ?? 'System') ?></div>
                                                <?php if ($ph['notes']): ?>
                                                    <div class="small text-muted mt-1">💬 <?= htmlspecialchars($ph['notes']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border small mb-3">ℹ️ Belum ada riwayat perubahan password untuk user ini.</div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Password Default Sistem</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" value="password123" readonly
                                        id="defaultPasswordInput" style="background:#f9fafb;">
                                    <button class="btn btn-outline-primary" type="button" onclick="copyDefaultPassword()">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                <small class="text-muted">Gunakan ini untuk reset atau berikan ke user baru</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Generate Password Acak</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="generatedPassword"
                                        readonly style="background:#f9fafb;" placeholder="Klik generate">
                                    <button class="btn btn-outline-success" type="button" onclick="generateRandomPassword()">🎲 Generate</button>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyGeneratedPassword()">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-warning fw-semibold"
                                    onclick="openChangePassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">
                                    🔑 Ganti Password Manual
                                </button>
                                <button class="btn btn-outline-danger fw-semibold"
                                    onclick="quickResetPassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">
                                    ⚡ Quick Reset ke Default
                                </button>
                            </div>

                            <div class="alert alert-warning border-0 small mt-3 mb-0">
                                🔒 <strong>Catatan Keamanan:</strong> Password asli tidak dapat ditampilkan karena disimpan sebagai hash terenkripsi. Riwayat di atas mencatat <em>kapan</em> dan <em>bagaimana</em> password diubah.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- KOLOM KANAN -->
            <div class="col-12 col-lg-8">
                <!-- TABEL USER -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>📋 Daftar User</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border px-3 py-2"><?= $totalUsers ?> user</span>
                            <?php if ($totalPages > 1): ?>
                                <span class="badge bg-primary px-3 py-2">Halaman <?= $page ?>/<?= $totalPages ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 ps-md-4">User</th>
                                        <th>Username</th>
                                        <th class="text-center">Role</th>
                                        <th class="text-center">Status</th>
                                        <th>Dibuat</th>
                                        <th class="text-center pe-3 pe-md-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr class="<?= !$u['is_active'] ? 'table-secondary' : '' ?>">
                                            <td class="ps-3 ps-md-4">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="width:40px;height:40px;border-radius:50%;background:<?= $u['role'] === 'admin' ? '#667eea' : '#10b981' ?>;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">
                                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                            <small class="text-primary">(Anda)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="font-monospace small"><?= htmlspecialchars($u['username']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : 'bg-success' ?>">
                                                    <?= strtoupper($u['role']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($u['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></small>
                                            </td>
                                            <td class="text-center pe-3 pe-md-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="users.php?edit=<?= $u['id'] ?>&page=<?= $page ?>"
                                                        class="btn btn-outline-primary px-2 px-md-3" title="Edit">✏️</a>
                                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-outline-danger px-2 px-md-3"
                                                            onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')"
                                                            title="Hapus">🗑️</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary px-2 px-md-3" disabled title="Tidak bisa hapus diri sendiri">🗑️</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <div style="font-size:3rem;">👥</div>
                                                <h6 class="fw-bold mt-3">Belum ada user</h6>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-container border-top">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= $editUser ? '&edit=' . $editUser['id'] : '' ?>" class="page-btn">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class="bi bi-chevron-left"></i></span>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                if ($startPage > 1) echo '<a href="?page=1" class="page-btn">1</a>';
                                if ($startPage > 2) echo '<span class="page-info">...</span>';
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?page=<?= $i ?><?= $editUser ? '&edit=' . $editUser['id'] : '' ?>"
                                        class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor;
                                if ($endPage < $totalPages - 1) echo '<span class="page-info">...</span>';
                                if ($endPage < $totalPages) echo '<a href="?page=' . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= $editUser ? '&edit=' . $editUser['id'] : '' ?>" class="page-btn">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                                <?php endif; ?>

                                <div class="page-info w-100 text-center mt-2">
                                    Menampilkan <?= (($page - 1) * $perPage) + 1 ?> - <?= min($page * $perPage, $totalUsers) ?> dari <?= $totalUsers ?> user
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- INFO CARD -->
                <div class="card border-0 shadow-sm mt-3 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">ℹ️ Informasi</h6>
                        <ul class="small mb-0 text-muted">
                            <li><strong>Admin:</strong> Bisa akses semua fitur</li>
                            <li><strong>Kasir:</strong> Hanya bisa akses Kasir dan Riwayat</li>
                            <li><strong>Nonaktif:</strong> User tidak bisa login</li>
                            <li>Password default saat reset: <code>password123</code></li>
                            <li>Admin bisa mengedit akun sendiri termasuk username dan password</li>
                            <li>Klik icon 👁️ untuk lihat/sembunyikan password saat mengetik</li>
                            <li>Daftar user ditampilkan 10 per halaman dengan navigasi pagination</li>
                            <li>Log aktivitas mencatat login, perubahan user, dan aksi penting lainnya</li>
                        </ul>
                    </div>
                </div>

                <!-- ✅ LOG AKTIVITAS - COMPACT VERSION -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                        <div>
                            <span class="fw-bold" style="font-size:0.9rem;">📋 Log Aktivitas</span>
                            <small class="text-muted ms-2" style="font-size:0.7rem;">
                                Terbaru → Terlama · <?= $logPerPage ?> per halaman
                            </small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border" style="font-size:0.7rem;"><?= $totalLogs ?> total</span>
                            <?php if ($logTotalPages > 1): ?>
                                <span class="badge bg-primary" style="font-size:0.65rem;">
                                    Hal <?= $logPage ?>/<?= $logTotalPages ?>
                                </span>
                            <?php endif; ?>
                            <a href="history.php?tab=users" class="btn btn-sm btn-outline-primary px-2 py-1" style="font-size:0.7rem;">
                                Semua →
                            </a>
                        </div>
                    </div>

                    <!-- ✅ PAGINATION DI ATAS (mudah dijangkau) -->
                    <?php if ($logTotalPages > 1 && !empty($activityLogs)): ?>
                        <?php
                        $buildLogUrl = function($p) use ($editUser, $page) {
                            $params = ['log_page' => $p];
                            if ($page > 1) $params['page'] = $page;
                            if ($editUser) $params['edit'] = $editUser['id'];
                            return '?' . http_build_query($params);
                        };
                        ?>
                        <div class="d-flex justify-content-center align-items-center gap-1 py-2 px-3 border-bottom" style="background:#f9fafb;">
                            <?php if ($logPage > 1): ?>
                                <a href="<?= $buildLogUrl(1) ?>" class="log-page-btn" title="Pertama">
                                    <i class="bi bi-chevron-bar-left"></i>
                                </a>
                                <a href="<?= $buildLogUrl($logPage - 1) ?>" class="log-page-btn" title="Sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="log-page-btn disabled"><i class="bi bi-chevron-bar-left"></i></span>
                                <span class="log-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
                            <?php endif; ?>

                            <?php
                            $logStartPage = max(1, $logPage - 2);
                            $logEndPage = min($logTotalPages, $logPage + 2);
                            
                            if ($logStartPage > 1) {
                                echo '<a href="' . $buildLogUrl(1) . '" class="log-page-btn">1</a>';
                                if ($logStartPage > 2) echo '<span class="log-page-btn disabled" style="border:none;">…</span>';
                            }
                            
                            for ($i = $logStartPage; $i <= $logEndPage; $i++):
                            ?>
                                <a href="<?= $buildLogUrl($i) ?>" 
                                   class="log-page-btn <?= $i === $logPage ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
                            
                            if ($logEndPage < $logTotalPages - 1) echo '<span class="log-page-btn disabled" style="border:none;">…</span>';
                            if ($logEndPage < $logTotalPages) echo '<a href="' . $buildLogUrl($logTotalPages) . '" class="log-page-btn">' . $logTotalPages . '</a>';
                            ?>

                            <?php if ($logPage < $logTotalPages): ?>
                                <a href="<?= $buildLogUrl($logPage + 1) ?>" class="log-page-btn" title="Berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                                <a href="<?= $buildLogUrl($logTotalPages) ?>" class="log-page-btn" title="Terakhir">
                                    <i class="bi bi-chevron-bar-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="log-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                                <span class="log-page-btn disabled"><i class="bi bi-chevron-bar-right"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ✅ CONTAINER SCROLLABLE -->
                    <div class="card-body p-0 log-scroll-container" style="max-height: 420px; overflow-y: auto;">
                        <?php if (empty($activityLogs)): ?>
                            <div class="text-center py-4 text-muted">
                                <div style="font-size:2rem;">📭</div>
                                <small class="d-block mt-1" style="font-size:0.75rem;">Belum ada aktivitas</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activityLogs as $index => $log): ?>
                                <?php
                                $action = $log['action'];
                                $badgeClass = 'bg-secondary';
                                $iconClass = 'info';
                                $icon = 'ℹ️';
                                $label = ucwords(str_replace('_', ' ', $action));

                                if (str_contains($action, 'login_success') || str_contains($action, 'created')) {
                                    $badgeClass = 'bg-success';
                                    $iconClass = 'success';
                                    $icon = '✅';
                                } elseif (str_contains($action, 'failed') || str_contains($action, 'deleted')) {
                                    $badgeClass = 'bg-danger';
                                    $iconClass = 'failed';
                                    $icon = '❌';
                                } elseif (str_contains($action, 'password')) {
                                    $badgeClass = 'bg-warning text-dark';
                                    $iconClass = 'warning';
                                    $icon = '🔑';
                                } elseif (str_contains($action, 'updated') || str_contains($action, 'toggled')) {
                                    $badgeClass = 'bg-info text-dark';
                                    $iconClass = 'info';
                                    $icon = '✏️';
                                } elseif (str_contains($action, 'logout')) {
                                    $badgeClass = 'bg-dark';
                                    $iconClass = 'dark';
                                    $icon = '🚪';
                                } elseif (str_contains($action, 'system')) {
                                    $badgeClass = 'bg-primary';
                                    $iconClass = 'primary';
                                    $icon = '⚙️';
                                }
                                
                                // Alternating background untuk readability
                                $bgStyle = ($index % 2 === 0) ? '' : 'style="background:#f9fafb;"';
                                ?>
                                <div class="activity-item-compact" <?= $bgStyle ?>>
                                    <div class="activity-icon-compact <?= $iconClass ?>"><?= $icon ?></div>
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                            <span class="badge <?= $badgeClass ?>" style="font-size:0.6rem; padding:2px 8px;">
                                                <?= htmlspecialchars($label) ?>
                                            </span>
                                            <small class="text-muted" style="font-size:0.65rem; white-space:nowrap;">
                                                <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                                            </small>
                                        </div>

                                        <div class="fw-semibold text-truncate" style="font-size:0.78rem; color:#374151;">
                                            <?= htmlspecialchars($log['description'] ?: $label) ?>
                                        </div>

                                        <div class="d-flex gap-1 mt-1 flex-wrap">
                                            <?php if (!empty($log['full_name'])): ?>
                                                <span class="badge bg-light text-dark border" style="font-size:0.6rem; padding:1px 6px;">
                                                    👤 <?= htmlspecialchars($log['full_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($log['ip_address'])): ?>
                                                <span class="badge bg-light text-dark border" style="font-size:0.6rem; padding:1px 6px;">
                                                    🌐 <?= htmlspecialchars($log['ip_address']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ✅ FOOTER INFO -->
                    <?php if (!empty($activityLogs) && $logTotalPages > 1): ?>
                        <div class="card-footer py-2 px-3 text-center border-top" style="background:#f9fafb;">
                            <small class="text-muted" style="font-size:0.7rem;">
                                Menampilkan <?= (($logPage - 1) * $logPerPage) + 1 ?>-<?= min($logPage * $logPerPage, $totalLogs) ?> dari <?= $totalLogs ?> log
                                · Scroll untuk lihat lebih banyak ↓
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GANTI PASSWORD -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold">🔑 Ganti Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="process_user.php" method="POST" id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="id" id="changePasswordUserId">

                        <div class="alert alert-info border-0 mb-3">
                            <strong>User:</strong> <span id="changePasswordUsername" class="fw-bold"></span>
                            <span id="selfEditBadge" class="badge bg-primary ms-2" style="display:none;">Akun Anda</span>
                        </div>

                        <label class="form-label fw-semibold small text-muted mb-2">Pilih Metode</label>

                        <div class="password-option selected" onclick="selectPasswordOption('default', this)">
                            <input type="radio" name="password_option" value="default" checked>
                            <div class="option-content">
                                <strong>Default Password</strong>
                                <div class="small text-muted mt-1">Password akan direset ke: <code>password123</code></div>
                            </div>
                        </div>

                        <div class="password-option" onclick="selectPasswordOption('custom', this)">
                            <input type="radio" name="password_option" value="custom">
                            <div class="option-content">
                                <strong>Password Custom</strong>
                                <div class="small text-muted mt-1">Tentukan password baru sendiri</div>
                            </div>
                        </div>

                        <div id="customPasswordFields" style="display:none; margin-top:20px;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Password Baru</label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="newPassword"
                                        class="form-control form-control-lg"
                                        placeholder="Minimal 6 karakter" minlength="6">
                                    <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="newPasswordStrength"></div>
                                </div>
                                <small class="text-muted" id="newPasswordHint">Gunakan kombinasi huruf & angka</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Konfirmasi Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirmPassword"
                                        class="form-control form-control-lg"
                                        placeholder="Ulangi password baru">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-danger" id="passwordMatchError" style="display:none;">❌ Password tidak sama</small>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmChange" required>
                            <label class="form-check-label small" for="confirmChange">
                                Saya yakin ingin mengganti password user ini
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="changePasswordForm" class="btn btn-warning px-4 fw-bold" id="btnChangePassword" disabled>
                        🔑 Ganti Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DELETE USER -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">🗑️ Hapus User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="process_user.php" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteUserId">
                        <div class="text-center mb-3">
                            <div style="font-size:4rem;">⚠️</div>
                        </div>
                        <div class="alert alert-danger border-0 text-center">
                            <strong>Hapus user <span id="deleteUserName"></span>?</strong>
                        </div>
                        <p class="text-muted small text-center">Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label small" for="confirmDelete">Saya yakin ingin menghapus user ini</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="deleteForm" class="btn btn-danger px-4 fw-bold" id="btnDelete" disabled>🗑️ Hapus User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIRM CUSTOM -->
    <div id="confirmOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:500;display:none;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity 0.25s ease;">
        <div style="background:white;width:100%;max-width:380px;border-radius:24px;padding:36px 28px 28px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:confirmPop 0.35s cubic-bezier(0.34,1.56,0.64,1);">
            <div id="confirmIcon" style="width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem;background:#fef2f2;border:3px solid #fecaca;">⚠️</div>
            <div id="confirmTitle" style="font-weight:900;font-size:1.3rem;margin-bottom:8px;color:#1f2937;">Konfirmasi</div>
            <div id="confirmMessage" style="font-size:0.9rem;color:#6b7280;margin-bottom:6px;line-height:1.5;">Apakah Anda yakin?</div>
            <div id="confirmDetail" style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px;margin:16px 0;font-size:0.85rem;color:#991b1b;font-weight:600;display:none;"></div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button style="flex:1;padding:14px;border-radius:14px;font-weight:800;cursor:pointer;background:#f3f4f6;color:#374151;border:2px solid #e5e7eb;" onclick="hideConfirm()">Kembali</button>
                <button id="confirmYesBtn" style="flex:1;padding:14px;border-radius:14px;font-weight:800;cursor:pointer;background:#ef4444;color:white;border:none;">Ya</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes confirmPop {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        #confirmOverlay.show {
            display: flex !important;
            opacity: 1;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

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

        function togglePassword(inputId, btn) {
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

        function checkPasswordStrength(password, barId, hintId) {
            const bar = document.getElementById(barId);
            const hint = document.getElementById(hintId);
            if (!bar) return;
            bar.className = 'password-strength-bar';
            if (password.length === 0) {
                if (hint) {
                    hint.textContent = 'Gunakan kombinasi huruf & angka';
                    hint.className = 'text-muted small';
                }
                return;
            }
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            if (strength <= 2) {
                bar.classList.add('strength-weak');
                if (hint) {
                    hint.textContent = '🔴 Lemah';
                    hint.className = 'text-danger small';
                }
            } else if (strength <= 3) {
                bar.classList.add('strength-medium');
                if (hint) {
                    hint.textContent = '🟡 Sedang';
                    hint.className = 'text-warning small';
                }
            } else {
                bar.classList.add('strength-strong');
                if (hint) {
                    hint.textContent = '🟢 Kuat';
                    hint.className = 'text-success small';
                }
            }
        }

        document.getElementById('editPassword')?.addEventListener('input', function() {
            checkPasswordStrength(this.value, 'editPasswordStrength', 'editPasswordHint');
        });
        document.getElementById('newPassword')?.addEventListener('input', function() {
            checkPasswordStrength(this.value, 'newPasswordStrength', 'newPasswordHint');
            checkPasswordMatch();
        });
        document.getElementById('confirmPassword')?.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            const errorEl = document.getElementById('passwordMatchError');
            if (confirmPass.length === 0) {
                errorEl.style.display = 'none';
                return;
            }
            errorEl.style.display = (newPass !== confirmPass) ? 'block' : 'none';
            updateChangeButtonState();
        }

        let selectedPasswordOption = 'default';
        const currentAdminId = <?= $_SESSION['user_id'] ?>;

        function openChangePassword(userId, username) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordUsername').textContent = username;
            document.getElementById('confirmChange').checked = false;
            document.getElementById('btnChangePassword').disabled = true;
            document.getElementById('selfEditBadge').style.display = (userId === currentAdminId) ? 'inline-block' : 'none';
            selectPasswordOption('default', document.querySelector('.password-option'));
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordMatchError').style.display = 'none';
            document.getElementById('newPasswordStrength').className = 'password-strength-bar';
            const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        }

        function selectPasswordOption(option, clickedElement) {
            selectedPasswordOption = option;
            const radio = document.querySelector(`input[name="password_option"][value="${option}"]`);
            if (radio) radio.checked = true;
            document.querySelectorAll('.password-option').forEach(el => el.classList.remove('selected'));
            if (clickedElement) clickedElement.classList.add('selected');
            else if (radio) radio.closest('.password-option').classList.add('selected');
            const customFields = document.getElementById('customPasswordFields');
            customFields.style.display = option === 'custom' ? 'block' : 'none';
            const newPassInput = document.getElementById('newPassword');
            const confirmPassInput = document.getElementById('confirmPassword');
            if (option === 'custom') {
                newPassInput.required = true;
                confirmPassInput.required = true;
                setTimeout(() => newPassInput.focus(), 100);
            } else {
                newPassInput.required = false;
                confirmPassInput.required = false;
                newPassInput.value = '';
                confirmPassInput.value = '';
                document.getElementById('passwordMatchError').style.display = 'none';
            }
            updateChangeButtonState();
        }

        document.querySelectorAll('input[name="password_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                selectPasswordOption(this.value, this.closest('.password-option'));
            });
        });

        document.getElementById('confirmChange').addEventListener('change', updateChangeButtonState);

        function updateChangeButtonState() {
            const isConfirmed = document.getElementById('confirmChange').checked;
            const isCustom = selectedPasswordOption === 'custom';
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            let isValid = isConfirmed;
            if (isCustom) isValid = isValid && newPass.length >= 6 && newPass === confirmPass;
            document.getElementById('btnChangePassword').disabled = !isValid;
        }

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const option = document.querySelector('input[name="password_option"]:checked').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            if (option === 'custom') {
                if (newPass.length < 6) {
                    e.preventDefault();
                    alert('❌ Password minimal 6 karakter!');
                    return false;
                }
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('❌ Password tidak sama!');
                    return false;
                }
            }
        });

        function deleteUser(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('confirmDelete').checked = false;
            document.getElementById('btnDelete').disabled = true;
            const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            modal.show();
        }

        document.getElementById('confirmDelete').addEventListener('change', function() {
            document.getElementById('btnDelete').disabled = !this.checked;
        });

        function toggleStatus(userId, newStatus) {
            const action = newStatus ? 'mengaktifkan' : 'menonaktifkan';
            if (!confirm(`Yakin ingin ${action} user ini?`)) return;
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', userId);
            formData.append('is_active', newStatus);
            fetch('process_user.php', {
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

        function showConfirm({
            icon,
            title,
            message,
            detail,
            yesText,
            yesAction
        }) {
            document.getElementById('confirmIcon').textContent = icon || '⚠️';
            document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
            document.getElementById('confirmMessage').textContent = message || 'Apakah Anda yakin?';
            const detailEl = document.getElementById('confirmDetail');
            if (detail) {
                detailEl.innerHTML = detail;
                detailEl.style.display = 'block';
            } else {
                detailEl.style.display = 'none';
            }
            const yesBtn = document.getElementById('confirmYesBtn');
            yesBtn.textContent = yesText || 'Ya';
            yesBtn.onclick = function() {
                hideConfirm();
                if (yesAction) yesAction();
            };
            const overlay = document.getElementById('confirmOverlay');
            overlay.style.display = 'flex';
            requestAnimationFrame(() => overlay.classList.add('show'));
        }

        function hideConfirm() {
            const overlay = document.getElementById('confirmOverlay');
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 250);
        }

        document.getElementById('confirmOverlay').addEventListener('click', function(e) {
            if (e.target === this) hideConfirm();
        });

        <?php if ($editUser): ?>
                (function() {
                    const statusEl = document.getElementById('passwordStatus');
                    fetch('process_user.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                action: 'check_password_status',
                                id: <?= $editUser['id'] ?>
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.is_default) {
                                statusEl.innerHTML = '<span class="text-warning">⚠️ Masih menggunakan password default</span>';
                            } else {
                                statusEl.innerHTML = '<span class="text-success">✅ Password sudah diubah dari default</span>';
                            }
                        })
                        .catch(() => {
                            statusEl.innerHTML = '<span class="text-muted">ℹ️ Status tidak tersedia</span>';
                        });
                })();
        <?php endif; ?>

        function copyDefaultPassword() {
            const input = document.getElementById('defaultPasswordInput');
            navigator.clipboard.writeText(input.value).then(() => {
                    showToastMessage('✅ Password default berhasil dicopy!');
                })
                .catch(() => {
                    input.select();
                    document.execCommand('copy');
                    showToastMessage('✅ Password default berhasil dicopy!');
                });
        }

        function generateRandomPassword() {
            const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('generatedPassword').value = password;
        }

        function copyGeneratedPassword() {
            const input = document.getElementById('generatedPassword');
            if (!input.value) {
                showToastMessage('⚠️ Generate password terlebih dahulu!');
                return;
            }
            navigator.clipboard.writeText(input.value).then(() => {
                    showToastMessage('✅ Password acak berhasil dicopy!');
                })
                .catch(() => {
                    input.select();
                    document.execCommand('copy');
                    showToastMessage('✅ Password acak berhasil dicopy!');
                });
        }

        function quickResetPassword(userId, username) {
            showConfirm({
                icon: '⚡',
                title: 'Quick Reset Password?',
                message: `Password user "${username}" akan direset ke password default sistem.`,
                detail: `
            <div style="text-align:center;">
                <div style="font-size:0.8rem; color:#6b7280; margin-bottom:6px;">Password akan diubah menjadi:</div>
                <div style="font-size:1.3rem; font-weight:900; font-family:monospace; background:#f3f4f6; padding:10px; border-radius:8px;">password123</div>
                <div style="font-size:0.75rem; color:#6b7280; margin-top:6px;">User harus login ulang dengan password baru</div>
            </div>
        `,
                yesText: '⚡ Ya, Reset Sekarang',
                yesAction: function() {
                    const formData = new FormData();
                    formData.append('action', 'change_password');
                    formData.append('id', userId);
                    formData.append('password_option', 'default');
                    fetch('process_user.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                showToastMessage('✅ Password berhasil direset!');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                alert('❌ ' + data.error);
                            }
                        })
                        .catch(() => alert('Terjadi kesalahan!'));
                }
            });
        }

        function showToastMessage(message) {
            let toast = document.getElementById('globalToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'globalToast';
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#1e1b4b;color:white;padding:14px 24px;border-radius:12px;font-weight:600;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,0.2);transition:all 0.3s ease;font-size:0.9rem;';
                document.body.appendChild(toast);
            }
            toast.textContent = message;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
            }, 2500);
        }
    </script>
</body>

</html>