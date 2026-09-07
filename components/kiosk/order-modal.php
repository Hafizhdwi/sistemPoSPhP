<div class="order-modal-overlay" id="orderModal" onclick="closeOrderModal(event)">
    <div class="order-modal" onclick="event.stopPropagation()">
        <h3 style="font-weight:900; margin-bottom:5px;">📋 Review Pesanan</h3>
        <p class="text-muted small mb-3">Periksa pesanan, atur jumlah, dan pilih metode pembayaran</p>

        <div id="orderItems"></div>

        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
            <span style="font-weight:700; font-size:1.1rem;">TOTAL</span>
            <span style="font-weight:900; font-size:1.4rem; color:var(--primary);" id="modalTotal">Rp 0</span>
        </div>

        <label class="form-label fw-bold mt-3 mb-2">💳 Metode Pembayaran</label>
        <div class="payment-options">
            <div class="pay-option selected" onclick="selectPayment('kasir', this)">
                <span class="pay-icon">🏪</span>
                <div class="pay-label">Bayar di Kasir</div>
                <div class="pay-desc">Tunai / Debit / Kredit</div>
            </div>
            <div class="pay-option" onclick="selectPayment('qris', this)">
                <span class="pay-icon">📱</span>
                <div class="pay-label">QRIS</div>
                <div class="pay-desc">Scan QR langsung</div>
            </div>
            <div class="pay-option" onclick="selectPayment('transfer', this)">
                <span class="pay-icon">🏦</span>
                <div class="pay-label">Transfer Bank</div>
                <div class="pay-desc">BCA / Mandiri / BRI</div>
            </div>
            <div class="pay-option" onclick="selectPayment('ewallet', this)">
                <span class="pay-icon">💜</span>
                <div class="pay-label">E-Wallet</div>
                <div class="pay-desc">GoPay / OVO / Dana</div>
            </div>
        </div>

        <div class="qris-info" id="qrisInfo">
            <strong>📱 Scan QRIS untuk membayar</strong>
            <div class="qr-placeholder">📷</div>
            <small class="text-muted">Tunjukkan bukti pembayaran ke kasir</small>
        </div>

        <div class="qris-info" id="transferInfo" style="background:#eff6ff; border-color:#93c5fd;">
            <strong>🏦 Transfer ke:</strong>
            <div style="font-size:1.3rem; font-weight:900; margin:8px 0; letter-spacing:2px;">1234-5678-90</div>
            <small>a.n. Mini PoS Restaurant</small><br>
            <small class="text-muted">Konfirmasi transfer ke kasir</small>
        </div>

        <input type="hidden" id="selectedPayment" value="kasir">

        <div class="row g-2 mt-2">
            <div class="col-6">
                <label class="form-label fw-semibold small text-muted mb-1">Nama Anda</label>
                <input type="text" id="customerName" class="form-control form-control-lg kiosk-input"
                    placeholder="Contoh: Budi" inputmode="text" autocomplete="off"
                    autocapitalize="words" maxlength="50" required>
                <small class="input-hint">Masukkan nama pemesan</small>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small text-muted mb-1">No. Meja</label>
                <input type="text" id="tableNumber" class="form-control form-control-lg kiosk-input"
                    placeholder="Contoh: 01 / A1 / VIP" inputmode="text" autocomplete="off"
                    maxlength="10" required>
                <small class="input-hint">Bisa angka/huruf (01, A1, VIP)</small>
            </div>
        </div>

        <button class="btn-order" id="btnOrder" onclick="submitOrder()" disabled>🚀 KIRIM PESANAN</button>
        <button class="btn-cancel" onclick="cancelOrder()">❌ Batal & Hapus Semua Pesanan</button>
    </div>
</div>