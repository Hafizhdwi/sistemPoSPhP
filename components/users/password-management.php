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
                                    <?= match($ph['change_method']) { 'default_reset'=>'⚡ Reset Default', 'initial'=>'🆕 Password Awal', 'custom'=>'✏️ Custom', default=>'-' } ?>
                                </span>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($ph['created_at'])) ?></small>
                            </div>
                            <div class="small"><strong>Diubah oleh:</strong> <?= htmlspecialchars($ph['changed_by_name'] ?? 'System') ?></div>
                            <?php if ($ph['notes']): ?><div class="small text-muted mt-1">💬 <?= htmlspecialchars($ph['notes']) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light border small mb-3">ℹ️ Belum ada riwayat perubahan password.</div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label fw-semibold small text-muted">Password Default Sistem</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" value="password123" readonly id="defaultPasswordInput" style="background:#f9fafb;">
                <button class="btn btn-outline-primary" type="button" onclick="copyDefaultPassword()"><i class="bi bi-clipboard"></i> Copy</button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small text-muted">Generate Password Acak</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="generatedPassword" readonly style="background:#f9fafb;" placeholder="Klik generate">
                <button class="btn btn-outline-success" type="button" onclick="generateRandomPassword()">🎲 Generate</button>
                <button class="btn btn-outline-primary" type="button" onclick="copyGeneratedPassword()"><i class="bi bi-clipboard"></i></button>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-warning fw-semibold" onclick="openChangePassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">🔑 Ganti Password Manual</button>
            <button class="btn btn-outline-danger fw-semibold" onclick="quickResetPassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">⚡ Quick Reset ke Default</button>
        </div>

        <div class="alert alert-warning border-0 small mt-3 mb-0">
            🔒 <strong>Catatan:</strong> Password asli tidak dapat ditampilkan karena disimpan sebagai hash terenkripsi.
        </div>
    </div>
</div>