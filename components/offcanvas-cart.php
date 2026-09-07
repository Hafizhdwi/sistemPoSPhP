<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
    <div class="cart-offcanvas-header">
        <h5 class="cart-offcanvas-title">
            🛒 Keranjang
            <span class="badge bg-white text-primary" id="offcanvas-cart-count">0 item</span>
        </h5>
        <button type="button" class="btn-close-offcanvas" data-bs-dismiss="offcanvas">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-offcanvas-body">
        <div class="cart-items-list" id="cart-items-list">
            <div class="cart-empty">
                <div class="icon">🛒</div>
                <div class="fw-semibold mb-1">Keranjang Kosong</div>
                <div class="small">Klik produk untuk menambahkan</div>
            </div>
        </div>
        <div class="cart-footer" id="cart-footer-offcanvas" style="display:none;">
            <div id="cart-summary-offcanvas"></div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-semibold">Total</span>
                <h3 class="fw-bold text-dark m-0" id="grand-total-offcanvas">Rp 0</h3>
            </div>
            <form id="checkout-form" action="process_sale.php" method="POST">
                <input type="hidden" name="cart_data" id="cart-data">
                <?php if ($processOrder): ?>
                    <input type="hidden" name="kiosk_order_id" value="<?= $processOrder['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Uang Diterima</label>
                    <input type="number" name="pay_amount" class="form-control form-control-lg fw-bold" required min="0" step="any" placeholder="0">
                </div>
                <button type="submit" class="btn btn-pay w-100" id="btn-checkout" disabled>💰 BAYAR SEKARANG</button>
            </form>
        </div>
    </div>
</div>

<button class="floating-cart-btn empty" id="floatingCartBtn" 
        data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" title="Lihat Keranjang">
    🛒
    <span class="floating-cart-badge" id="floatingCartBadge" style="display:none;">0</span>
</button>