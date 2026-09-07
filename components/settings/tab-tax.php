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
                    <label class="form-check-label fw-semibold" for="taxEnabled">Aktifkan Pajak</label>
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