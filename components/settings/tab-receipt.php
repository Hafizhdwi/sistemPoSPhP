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
            <label class="form-check-label" for="receiptShowLogo">Tampilkan Logo di Struk</label>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" 
                   name="receipt_show_address" id="receiptShowAddress" value="1"
                   <?= ($settings['receipt_show_address'] ?? '1') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="receiptShowAddress">Tampilkan Alamat & Telepon di Struk</label>
        </div>
    </div>
</div>