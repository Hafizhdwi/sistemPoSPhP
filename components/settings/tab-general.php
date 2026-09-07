<div class="card settings-card">
    <div class="card-header">🏪 Informasi Umum Toko</div>
    <div class="card-body p-4">
        
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