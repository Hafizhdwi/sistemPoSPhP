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
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Nama Lengkap</label>
                <input type="text" name="full_name" class="form-control form-control-lg" value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>" placeholder="Contoh: Budi Santoso" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Username</label>
                <input type="text" name="username" class="form-control form-control-lg" value="<?= htmlspecialchars($editUser['username'] ?? '') ?>" placeholder="Contoh: budi123" required pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted"><?= $editUser ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password' ?></label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="editPassword" class="form-control form-control-lg" placeholder="<?= $editUser ? 'Kosongkan = tidak diubah' : 'Minimal 6 karakter' ?>" <?= $editUser ? '' : 'required' ?> minlength="<?= $editUser ? '0' : '6' ?>" autocomplete="new-password">
                    <button type="button" class="password-toggle" onclick="togglePassword('editPassword', this)"><i class="bi bi-eye"></i></button>
                </div>
                <?php if ($editUser): ?>
                    <small class="text-warning">⚠️ Kosongkan jika tidak ingin mengubah password</small>
                <?php else: ?>
                    <div class="password-strength"><div class="password-strength-bar" id="editPasswordStrength"></div></div>
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
                <button type="submit" class="btn btn-primary flex-grow-1 py-3 fw-bold"><?= $editUser ? '💾 Update User' : '💾 Simpan User' ?></button>
                <?php if ($editUser): ?><a href="users.php" class="btn btn-outline-secondary py-3 fw-bold">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>