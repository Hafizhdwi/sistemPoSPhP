<?php
require_once 'config/database.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, full_name ASC");
$users = $stmt->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

$msg = $_GET['msg'] ?? '';
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
                <a href="users.php" class="nav-link active">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
                <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
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
                <h4 class="fw-bold m-0">👥 Manajemen User</h4>
                <small class="text-muted">Kelola akun admin dan kasir</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2"><?= count($users) ?> user terdaftar</span>
        </div>

        <!-- ALERT MESSAGES -->
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
            <!-- FORM TAMBAH / EDIT USER -->
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

                            <!-- ✅ USERNAME SEKARANG BISA DIEDIT -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Username</label>
                                <input type="text" name="username" class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($editUser['username'] ?? '') ?>"
                                    placeholder="Contoh: budi123" required
                                    pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                                <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                            </div>

                            <?php if (!$editUser): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Password</label>
                                    <div class="password-wrapper">
                                        <input type="password" name="password" id="createPassword"
                                            class="form-control form-control-lg"
                                            placeholder="Minimal 6 karakter" required minlength="6">
                                        <button type="button" class="password-toggle" onclick="togglePassword('createPassword', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="createPasswordStrength"></div>
                                    </div>
                                    <small class="text-muted" id="createPasswordHint">Gunakan kombinasi huruf & angka</small>
                                </div>
                            <?php endif; ?>

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

                <!-- QUICK ACTIONS CARD -->
                <?php if ($editUser): ?>
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header">⚡ Aksi Cepat</div>
                        <div class="card-body">
                            <button class="btn btn-warning w-100 mb-2 py-2 fw-semibold"
                                onclick="openChangePassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">
                                🔑 Ganti Password
                            </button>
                            <!-- ✅ Hanya tampilkan toggle jika bukan diri sendiri -->
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
                <?php endif; ?>
            </div>

            <!-- TABEL DAFTAR USER -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>📋 Daftar User</span>
                        <span class="badge bg-light text-dark border px-3 py-2"><?= count($users) ?> user</span>
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
                                                    <a href="users.php?edit=<?= $u['id'] ?>"
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
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">ℹ️ Informasi</h6>
                        <ul class="small mb-0 text-muted">
                            <li><strong>Admin:</strong> Bisa akses semua fitur</li>
                            <li><strong>Kasir:</strong> Hanya bisa akses Kasir dan Riwayat</li>
                            <li><strong>Nonaktif:</strong> User tidak bisa login</li>
                            <li>Password default saat reset: <code>password123</code></li>
                            <li>Admin bisa mengedit akun sendiri termasuk username dan password</li>
                        </ul>
                    </div>
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
                                <small class="text-danger" id="passwordMatchError" style="display:none;">
                                    ❌ Password tidak sama
                                </small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

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
                hint.textContent = 'Gunakan kombinasi huruf & angka';
                hint.className = 'text-muted small';
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
                hint.textContent = '🔴 Lemah';
                hint.className = 'text-danger small';
            } else if (strength <= 3) {
                bar.classList.add('strength-medium');
                hint.textContent = '🟡 Sedang';
                hint.className = 'text-warning small';
            } else {
                bar.classList.add('strength-strong');
                hint.textContent = '🟢 Kuat';
                hint.className = 'text-success small';
            }
        }

        document.getElementById('createPassword')?.addEventListener('input', function() {
            checkPasswordStrength(this.value, 'createPasswordStrength', 'createPasswordHint');
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
            if (newPass !== confirmPass) errorEl.style.display = 'block';
            else errorEl.style.display = 'none';
            updateChangeButtonState();
        }

        let selectedPasswordOption = 'default';
        const currentAdminId = <?= $_SESSION['user_id'] ?>;

        function openChangePassword(userId, username) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordUsername').textContent = username;
            document.getElementById('confirmChange').checked = false;
            document.getElementById('btnChangePassword').disabled = true;

            // ✅ Tampilkan badge jika edit diri sendiri
            document.getElementById('selfEditBadge').style.display = (userId === currentAdminId) ? 'inline-block' : 'none';

            selectPasswordOption('default', document.querySelector('.password-option'));
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordMatchError').style.display = 'none';
            document.getElementById('newPasswordStrength').className = 'password-strength-bar';
            document.getElementById('newPasswordHint').textContent = 'Gunakan kombinasi huruf & angka';
            document.getElementById('newPasswordHint').className = 'text-muted small';

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
    </script>
</body>

</html>