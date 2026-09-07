<?php if ($successMsg): ?>
<div class="success-toast" id="successToast" data-autohide="5000">
    <div class="toast-content">
        <div class="toast-icon-wrapper">
            <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="toast-body">
            <div class="toast-title">Transaksi Berhasil!</div>
            <p class="toast-message"><?= $successMsg ?></p>
            <?php if ($invoiceNumber): ?>
                <div class="toast-invoice-badge">🧾 <?= htmlspecialchars($invoiceNumber) ?></div>
                <div class="toast-actions">
                    <a href="receipt.php?invoice=<?= urlencode($invoiceNumber) ?>" target="_blank" class="toast-action-btn primary">🖨️ Cetak Struk</a>
                    <button type="button" class="toast-action-btn secondary" onclick="hideSuccessToast()">Tutup</button>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" class="toast-close" onclick="hideSuccessToast()" aria-label="Tutup">✕</button>
    </div>
    <div class="toast-progress">
        <div class="toast-progress-bar" id="toastProgressBar"></div>
    </div>
</div>
<?php endif; ?>