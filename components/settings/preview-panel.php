<div class="col-12 col-lg-5">
    <div class="card settings-card">
        <div class="card-header">👁️ Preview Struk</div>
        <div class="card-body p-4">
            <div class="receipt-preview" id="receiptPreview">
                <?php if (($settings['receipt_show_logo'] ?? '1') == '1' && !empty($settings['store_logo'])): ?>
                    <div class="center" style="margin-bottom:8px;">
                        <img src="<?= htmlspecialchars($settings['store_logo']) ?>" style="max-height:40px;" alt="Logo">
                    </div>
                <?php endif; ?>
                
                <div class="center bold" style="font-size:0.9rem;">
                    <?= htmlspecialchars($settings['store_name'] ?? 'Mini PoS') ?>
                </div>
                
                <?php if (($settings['receipt_show_address'] ?? '1') == '1'): ?>
                    <p class="center"><?= htmlspecialchars($settings['store_address'] ?? '') ?></p>
                    <p class="center"><?= htmlspecialchars($settings['store_phone'] ?? '') ?></p>
                <?php endif; ?>
                
                <?php if (!empty($settings['receipt_header'])): ?>
                    <p class="center" style="color:#666;"><?= htmlspecialchars($settings['receipt_header']) ?></p>
                <?php endif; ?>
                
                <div class="divider"></div>
                
                <div style="display:flex; justify-content:space-between;">
                    <span>No:</span><span>INV-20250101001</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Tgl:</span><span><?= date('d/m/Y H:i') ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Kasir:</span><span><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                </div>
                
                <div class="divider"></div>
                
                <div style="display:flex; justify-content:space-between;">
                    <span>Kopi Susu x2</span><span>36.000</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Croissant x1</span><span>25.000</span>
                </div>
                
                <div class="divider"></div>
                
                <div style="display:flex; justify-content:space-between;">
                    <span>Subtotal</span><span>61.000</span>
                </div>
                
                <?php if (($settings['tax_enabled'] ?? '0') == '1'): ?>
                <div style="display:flex; justify-content:space-between;">
                    <span><?= htmlspecialchars($settings['tax_label'] ?? 'PPN 11%') ?></span>
                    <span>6.710</span>
                </div>
                <?php endif; ?>
                
                <div style="display:flex; justify-content:space-between; font-size:0.85rem;" class="bold">
                    <span>TOTAL</span>
                    <span><?= ($settings['tax_enabled'] ?? '0') == '1' ? '67.710' : '61.000' ?></span>
                </div>
                
                <div style="display:flex; justify-content:space-between;">
                    <span>Tunai</span><span>100.000</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Kembali</span>
                    <span><?= ($settings['tax_enabled'] ?? '0') == '1' ? '32.290' : '39.000' ?></span>
                </div>
                
                <div class="divider"></div>
                
                <?php if (!empty($settings['receipt_footer'])): ?>
                    <p class="center" style="color:#666;"><?= htmlspecialchars($settings['receipt_footer']) ?></p>
                <?php endif; ?>
            </div>
            <small class="text-muted text-center d-block mt-3">* Preview menggunakan data contoh</small>
        </div>
    </div>

    <div class="card settings-card mt-3 bg-light">
        <div class="card-body">
            <h6 class="fw-bold mb-2">ℹ️ Informasi</h6>
            <ul class="small mb-0 text-muted">
                <li>Pengaturan ini berlaku untuk seluruh sistem</li>
                <li>Logo akan tampil di kiosk dan struk cetak</li>
                <li>Perubahan pajak berlaku untuk transaksi baru</li>
                <li>Transaksi lama tidak terpengaruh perubahan pajak</li>
            </ul>
        </div>
    </div>
</div>