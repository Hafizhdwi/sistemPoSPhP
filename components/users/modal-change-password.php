<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;">
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
                        <div class="option-content"><strong>Default Password</strong><div class="small text-muted mt-1">Password akan direset ke: <code>password123</code></div></div>
                    </div>
                    <div class="password-option" onclick="selectPasswordOption('custom', this)">
                        <input type="radio" name="password_option" value="custom">
                        <div class="option-content"><strong>Password Custom</strong><div class="small text-muted mt-1">Tentukan password baru sendiri</div></div>
                    </div>
                    <div id="customPasswordFields" style="display:none;margin-top:20px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Password Baru</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" id="newPassword" class="form-control form-control-lg" placeholder="Minimal 6 karakter" minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="password-strength"><div class="password-strength-bar" id="newPasswordStrength"></div></div>
                            <small class="text-muted" id="newPasswordHint">Gunakan kombinasi huruf & angka</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Konfirmasi Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg" placeholder="Ulangi password baru">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <small class="text-danger" id="passwordMatchError" style="display:none;">❌ Password tidak sama</small>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmChange" required>
                        <label class="form-check-label small" for="confirmChange">Saya yakin ingin mengganti password user ini</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="changePasswordForm" class="btn btn-warning px-4 fw-bold" id="btnChangePassword" disabled>🔑 Ganti Password</button>
            </div>
        </div>
    </div>
</div>