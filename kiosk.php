<?php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name ASC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Order Here - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        :root {
            --primary: #e63946;
            --primary-dark: #c1121f;
            --accent: #ffb703;
            --dark: #1d3557;
        }

        * {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            box-sizing: border-box;
        }

        body {
            background: var(--dark);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
            padding-bottom: 100px;
        }

        .kiosk-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 20px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .kiosk-header h1 {
            font-size: 1.8rem;
            font-weight: 900;
            margin: 0;
        }

        .kiosk-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .category-bar {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .category-bar::-webkit-scrollbar {
            display: none;
        }

        .cat-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            white-space: nowrap;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .cat-btn.active,
        .cat-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--dark);
        }

        .kiosk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            padding: 15px 20px;
        }

        .kiosk-card {
            background: white;
            border-radius: 16px;
            padding: 20px 15px;
            text-align: center;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.2s;
            border: 3px solid transparent;
            position: relative;
        }

        .kiosk-card:active {
            transform: scale(0.96);
        }

        .kiosk-card.selected {
            border-color: var(--primary);
            background: #fff5f5;
        }

        .kiosk-card .emoji {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }

        .kiosk-card .pname {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .kiosk-card .pprice {
            color: var(--primary);
            font-weight: 800;
            font-size: 1rem;
        }

        .kiosk-card .qty-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--primary);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
        }

        .kiosk-card.selected .qty-badge {
            opacity: 1;
            transform: scale(1);
        }

        .floating-cart {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: var(--primary);
            border-radius: 20px;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 30px rgba(230, 57, 70, 0.4);
            z-index: 200;
            transform: translateY(150%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .floating-cart.show {
            transform: translateY(0);
        }

        .floating-cart .cart-total {
            font-size: 1.3rem;
            font-weight: 900;
            color: white;
        }

        .floating-cart .cart-label {
            font-size: 0.8rem;
            opacity: 0.9;
            color: white;
        }

        /* ORDER MODAL */
        .order-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 300;
            display: none;
            align-items: flex-end;
            justify-content: center;
        }

        .order-modal-overlay.show {
            display: flex;
        }

        .order-modal {
            background: white;
            color: var(--dark);
            width: 100%;
            max-width: 500px;
            border-radius: 24px 24px 0 0;
            padding: 30px 24px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            gap: 10px;
        }

        .order-item .oi-info {
            flex: 1;
            min-width: 0;
        }

        .order-item .oi-name {
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-item .oi-price-unit {
            font-size: 0.75rem;
            color: #999;
        }

        .order-item .oi-qty-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .order-item .oi-qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            padding: 0;
            line-height: 1;
        }

        .order-item .oi-qty-btn:hover {
            background: var(--primary);
            color: white;
        }

        .order-item .oi-qty-btn:active {
            transform: scale(0.9);
        }

        .order-item .oi-qty-btn.delete {
            border-color: #ef4444;
            color: #ef4444;
        }

        .order-item .oi-qty-btn.delete:hover {
            background: #ef4444;
            color: white;
        }

        .order-item .oi-qty {
            font-weight: 800;
            font-size: 1rem;
            min-width: 24px;
            text-align: center;
        }

        .order-item .oi-subtotal {
            font-weight: 800;
            color: var(--primary);
            font-size: 0.9rem;
            min-width: 80px;
            text-align: right;
            flex-shrink: 0;
        }

        /* PAYMENT OPTIONS */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }

        .pay-option {
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .pay-option:hover {
            border-color: var(--primary);
            background: #fff5f5;
        }

        .pay-option.selected {
            border-color: var(--primary);
            background: #fff5f5;
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.15);
        }

        .pay-option .pay-icon {
            font-size: 2rem;
            margin-bottom: 6px;
            display: block;
        }

        .pay-option .pay-label {
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--dark);
        }

        .pay-option .pay-desc {
            font-size: 0.65rem;
            color: #999;
            margin-top: 2px;
        }

        .qris-info {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 14px;
            padding: 16px;
            margin: 10px 0;
            display: none;
            text-align: center;
        }

        .qris-info.show {
            display: block;
        }

        .qris-info .qr-placeholder {
            width: 150px;
            height: 150px;
            background: white;
            border: 2px dashed #86efac;
            border-radius: 12px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .btn-order {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 800;
            margin-top: 15px;
            cursor: pointer;
        }

        .btn-order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-order:active:not(:disabled) {
            transform: scale(0.98);
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
            border: 2px solid #e5e7eb;
            width: 100%;
            padding: 14px;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-cancel:active {
            transform: scale(0.98);
        }

        /* INPUT FIELDS */
        .kiosk-input {
            border-radius: 12px !important;
            font-weight: 600;
            border: 2px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .kiosk-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.15);
            outline: none;
        }

        .kiosk-input.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .input-hint {
            font-size: 0.7rem;
            color: #999;
            margin-top: 4px;
            display: block;
        }

        .empty-cart-modal {
            text-align: center;
            padding: 30px 20px;
            color: #999;
        }

        .empty-cart-modal .icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        /* ✅ CUSTOM CONFIRM MODAL */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .confirm-overlay.show {
            display: flex;
            opacity: 1;
        }

        .confirm-box {
            background: white;
            color: var(--dark);
            width: 100%;
            max-width: 380px;
            border-radius: 24px;
            padding: 36px 28px 28px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: confirmPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes confirmPop {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .confirm-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            background: #fef2f2;
            border: 3px solid #fecaca;
            animation: iconPulse 0.6s ease;
        }

        @keyframes iconPulse {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        .confirm-title {
            font-weight: 900;
            font-size: 1.3rem;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .confirm-message {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 6px;
            line-height: 1.5;
        }

        .confirm-detail {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px;
            margin: 16px 0;
            font-size: 0.8rem;
            color: #991b1b;
            font-weight: 600;
        }

        .confirm-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .confirm-btn {
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .confirm-btn:active {
            transform: scale(0.96);
        }

        .confirm-btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .confirm-btn-cancel:hover {
            background: #e5e7eb;
        }

        .confirm-btn-yes {
            background: #ef4444;
            color: white;
        }

        .confirm-btn-yes:hover {
            background: #dc2626;
        }

        /* SUCCESS SCREEN */
        .success-screen {
            position: fixed;
            inset: 0;
            background: var(--dark);
            z-index: 400;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            color: white;
        }

        .success-screen.show {
            display: flex;
        }

        .success-check {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: popIn 0.5s ease;
        }

        @keyframes popIn {
            0% {
                transform: scale(0)
            }

            70% {
                transform: scale(1.2)
            }

            100% {
                transform: scale(1)
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-8px);
            }

            40%,
            80% {
                transform: translateX(8px);
            }
        }

        @media (min-width: 768px) {
            .kiosk-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
            }

            .floating-cart {
                left: 50%;
                right: auto;
                transform: translateX(-50%) translateY(150%);
                width: 400px;
            }

            .floating-cart.show {
                transform: translateX(-50%) translateY(0);
            }

            .order-modal {
                border-radius: 24px;
                margin-bottom: 20px;
            }

            .order-modal-overlay {
                align-items: center;
            }
        }

        @media (max-width: 400px) {
            .order-item {
                flex-wrap: wrap;
            }

            .order-item .oi-info {
                width: 100%;
                margin-bottom: 8px;
            }

            .order-item .oi-subtotal {
                width: 100%;
                text-align: left;
                margin-top: 4px;
            }

            .confirm-box {
                padding: 28px 20px 20px;
            }

            .confirm-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="kiosk-header">
        <h1>🍔 MINI POS KIOSK</h1>
        <p>Sentuh produk untuk memesan • Pilih metode pembayaran</p>
    </div>

    <div class="category-bar">
        <div class="cat-btn active" onclick="filterCategory('all', this)">Semua</div>
        <div class="cat-btn" onclick="filterCategory('minuman', this)">☕ Minuman</div>
        <div class="cat-btn" onclick="filterCategory('makanan', this)">🍽️ Makanan</div>
        <div class="cat-btn" onclick="filterCategory('snack', this)">🥐 Snack</div>
    </div>

    <div class="kiosk-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
            <div class="kiosk-card"
                data-id="<?= $p['id'] ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>"
                data-price="<?= $p['price'] ?>"
                data-category="<?= strtolower(preg_match('/kopi|teh|air|minum/i', $p['name']) ? 'minuman' : (preg_match('/nasi|mie|ayam|goreng/i', $p['name']) ? 'makanan' : 'snack')) ?>"
                onclick="toggleItem(this)">
                <div class="qty-badge" id="badge-<?= $p['id'] ?>">0</div>
                <span class="emoji">☕</span>
                <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
                <div class="pprice"><?= formatRupiah($p['price']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="floating-cart" id="floatingCart" onclick="openOrderModal()">
        <div>
            <div class="cart-label">Total Pesanan</div>
            <div class="cart-total" id="cartTotal">Rp 0</div>
        </div>
        <div style="font-size:1.5rem;">👉</div>
    </div>

    <!-- ORDER MODAL -->
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

    <!-- ✅ CUSTOM CONFIRM MODAL -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <div class="confirm-icon" id="confirmIcon">⚠️</div>
            <div class="confirm-title" id="confirmTitle">Batalkan Pesanan?</div>
            <div class="confirm-message" id="confirmMessage">
                Semua item yang sudah Anda pilih akan dihapus dan tidak bisa dikembalikan.
            </div>
            <div class="confirm-detail" id="confirmDetail"></div>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-btn-cancel" onclick="hideConfirm()">
                    Kembali
                </button>
                <button class="confirm-btn confirm-btn-yes" id="confirmYesBtn" onclick="">
                    Ya, Batalkan
                </button>
            </div>
        </div>
    </div>

    <!-- SUCCESS SCREEN -->
    <div class="success-screen" id="successScreen">
        <div class="success-check">✅</div>
        <h2 style="font-weight:900; font-size:2rem;">Pesanan Terkirim!</h2>
        <p style="opacity:0.8; font-size:1.1rem; margin-top:10px;" id="successMessage">Silakan bayar di kasir</p>
        <h1 style="font-weight:900; font-size:4rem; color:var(--accent); margin:10px 0;" id="queueNumber">#000</h1>
        <p style="opacity:0.5; font-size:0.8rem; margin-top:30px;">Halaman kembali otomatis...</p>
    </div>

    <script>
        let cart = {};
        let selectedPaymentMethod = 'kasir';

        // ==================== CONFIRM MODAL ====================
        function showConfirm({
            icon,
            title,
            message,
            detail,
            yesText,
            yesAction
        }) {
            document.getElementById('confirmIcon').textContent = icon || '⚠️';
            document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
            document.getElementById('confirmMessage').textContent = message || 'Apakah Anda yakin?';

            const detailEl = document.getElementById('confirmDetail');
            if (detail) {
                detailEl.innerHTML = detail;
                detailEl.style.display = 'block';
            } else {
                detailEl.style.display = 'none';
            }

            const yesBtn = document.getElementById('confirmYesBtn');
            yesBtn.textContent = yesText || 'Ya';
            yesBtn.onclick = function() {
                hideConfirm();
                if (yesAction) yesAction();
            };

            const overlay = document.getElementById('confirmOverlay');
            overlay.style.display = 'flex';
            // Trigger reflow agar animasi jalan
            requestAnimationFrame(() => {
                overlay.classList.add('show');
            });
        }

        function hideConfirm() {
            const overlay = document.getElementById('confirmOverlay');
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 250);
        }

        // Tutup confirm modal saat klik overlay
        document.getElementById('confirmOverlay').addEventListener('click', function(e) {
            if (e.target === this) hideConfirm();
        });

        // ==================== PRODUCT & CART ====================
        function toggleItem(el) {
            const id = el.dataset.id;
            if (cart[id]) cart[id].qty++;
            else cart[id] = {
                name: el.dataset.name,
                price: parseFloat(el.dataset.price),
                qty: 1
            };
            updateUI();
        }

        function updateUI() {
            let total = 0,
                totalQty = 0;
            document.querySelectorAll('.kiosk-card').forEach(card => {
                const id = card.dataset.id;
                const badge = document.getElementById('badge-' + id);
                if (cart[id] && cart[id].qty > 0) {
                    card.classList.add('selected');
                    badge.textContent = cart[id].qty;
                    total += cart[id].price * cart[id].qty;
                    totalQty += cart[id].qty;
                } else {
                    card.classList.remove('selected');
                    badge.textContent = '0';
                }
            });
            document.getElementById('cartTotal').textContent = total.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            const fc = document.getElementById('floatingCart');
            totalQty > 0 ? fc.classList.add('show') : fc.classList.remove('show');
        }

        function updateItemQty(id, delta) {
            if (!cart[id]) return;
            cart[id].qty += delta;
            if (cart[id].qty <= 0) delete cart[id];
            updateUI();
            refreshOrderModal();
        }

        function refreshOrderModal() {
            const container = document.getElementById('orderItems');
            const entries = Object.entries(cart).filter(([id, item]) => item.qty > 0);

            if (entries.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart-modal">
                        <div class="icon">🛒</div>
                        <h6 class="fw-bold">Keranjang kosong</h6>
                        <p class="small mb-0">Pilih produk untuk memulai pesanan</p>
                    </div>`;
                document.getElementById('modalTotal').textContent = 'Rp 0';
                document.getElementById('btnOrder').disabled = true;
                return;
            }

            let total = 0;
            container.innerHTML = '';

            entries.forEach(([id, item]) => {
                const sub = item.price * item.qty;
                total += sub;
                container.innerHTML += `
                    <div class="order-item">
                        <div class="oi-info">
                            <div class="oi-name">${item.name}</div>
                            <div class="oi-price-unit">${item.price.toLocaleString('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0})} / item</div>
                        </div>
                        <div class="oi-qty-control">
                            <button class="oi-qty-btn ${item.qty <= 1 ? 'delete' : ''}"
                                    onclick="updateItemQty('${id}', -1)"
                                    title="${item.qty <= 1 ? 'Hapus item' : 'Kurangi'}">
                                ${item.qty <= 1 ? '🗑' : '−'}
                            </button>
                            <span class="oi-qty">${item.qty}</span>
                            <button class="oi-qty-btn" onclick="updateItemQty('${id}', 1)" title="Tambah">+</button>
                        </div>
                        <div class="oi-subtotal">${sub.toLocaleString('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0})}</div>
                    </div>`;
            });

            document.getElementById('modalTotal').textContent = total.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            checkOrderBtn();
        }

        function filterCategory(cat, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.kiosk-card').forEach(card => {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        }

        function selectPayment(method, el) {
            selectedPaymentMethod = method;
            document.getElementById('selectedPayment').value = method;
            document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('qrisInfo').classList.toggle('show', method === 'qris');
            document.getElementById('transferInfo').classList.toggle('show', method === 'transfer');
        }

        function openOrderModal() {
            refreshOrderModal();
            document.getElementById('orderModal').classList.add('show');
            checkOrderBtn();
            setTimeout(() => {
                document.getElementById('customerName').focus();
            }, 300);
        }

        function closeOrderModal(e) {
            if (e.target === document.getElementById('orderModal'))
                document.getElementById('orderModal').classList.remove('show');
        }

        // ✅ CANCEL ORDER - Custom Confirm Modal
        function cancelOrder() {
            const entries = Object.entries(cart).filter(([id, item]) => item.qty > 0);

            if (entries.length === 0) {
                document.getElementById('orderModal').classList.remove('show');
                return;
            }

            // Hitung total item & total harga
            let totalQty = 0,
                totalPrice = 0;
            entries.forEach(([id, item]) => {
                totalQty += item.qty;
                totalPrice += item.price * item.qty;
            });

            const itemList = entries.map(([id, item]) => `${item.qty}x ${item.name}`).join('<br>');

            showConfirm({
                icon: '🗑️',
                title: 'Batalkan Pesanan?',
                message: 'Semua item yang sudah Anda pilih akan dihapus dan tidak bisa dikembalikan.',
                detail: `
                    <div style="text-align:left; margin-bottom:8px;">
                        ${itemList}
                    </div>
                    <div style="border-top:1px dashed #fecaca; padding-top:8px; text-align:right;">
                        <strong>${totalQty} item</strong> — ${totalPrice.toLocaleString('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0})}
                    </div>
                `,
                yesText: '🗑️ Ya, Hapus Semua',
                yesAction: function() {
                    resetCart();
                    document.getElementById('orderModal').classList.remove('show');
                }
            });
        }

        function resetCart() {
            cart = {};
            updateUI();
            document.getElementById('customerName').value = '';
            document.getElementById('tableNumber').value = '';
            document.getElementById('customerName').classList.remove('is-invalid');
            document.getElementById('tableNumber').classList.remove('is-invalid');
            selectedPaymentMethod = 'kasir';
            document.getElementById('selectedPayment').value = 'kasir';
            document.querySelectorAll('.pay-option').forEach((o, i) => o.classList.toggle('selected', i === 0));
            document.getElementById('qrisInfo').classList.remove('show');
            document.getElementById('transferInfo').classList.remove('show');
            document.getElementById('btnOrder').disabled = true;
            document.getElementById('btnOrder').textContent = '🚀 KIRIM PESANAN';
        }

        // ==================== INPUT HANDLING ====================
        const customerNameInput = document.getElementById('customerName');
        const tableNumberInput = document.getElementById('tableNumber');

        customerNameInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            checkOrderBtn();
        });
        tableNumberInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            checkOrderBtn();
        });

        [customerNameInput, tableNumberInput].forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.id === 'customerName') tableNumberInput.focus();
                    else if (!document.getElementById('btnOrder').disabled) submitOrder();
                }
            });
        });

        function checkOrderBtn() {
            const name = customerNameInput.value.trim();
            const table = tableNumberInput.value.trim();
            const hasItems = Object.keys(cart).some(id => cart[id].qty > 0);
            document.getElementById('btnOrder').disabled = !(name && table && hasItems);
        }

        // ==================== SUBMIT ORDER ====================
        function submitOrder() {
            const name = customerNameInput.value.trim();
            const table = tableNumberInput.value.trim();

            let hasError = false;
            if (!name) {
                customerNameInput.classList.add('is-invalid');
                customerNameInput.focus();
                hasError = true;
            }
            if (!table) {
                tableNumberInput.classList.add('is-invalid');
                if (!hasError) tableNumberInput.focus();
                hasError = true;
            }
            if (hasError) {
                if (!name) shakeElement(customerNameInput);
                if (!table) shakeElement(tableNumberInput);
                return;
            }

            const cartItems = Object.entries(cart).filter(([id, item]) => item.qty > 0);
            if (cartItems.length === 0) {
                alert('Keranjang kosong!');
                return;
            }

            const btn = document.getElementById('btnOrder');
            btn.disabled = true;
            btn.textContent = '⏳ Mengirim...';

            const formData = new FormData();
            formData.append('cart_data', JSON.stringify(cartItems.map(([id, item]) => ({
                id: parseInt(id),
                name: item.name,
                price: item.price,
                qty: item.qty
            }))));
            formData.append('customer_name', name);
            formData.append('table_number', table);
            formData.append('payment_method', selectedPaymentMethod);

            fetch('kiosk_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('orderModal').classList.remove('show');
                        document.getElementById('queueNumber').textContent = '#' + data.queue;
                        const msgs = {
                            'kasir': '💰 Silakan bayar di kasir',
                            'qris': '📱 Tunjukkan bukti scan ke kasir',
                            'transfer': '🏦 Konfirmasi transfer ke kasir',
                            'ewallet': '💜 Tunjukkan bukti bayar ke kasir'
                        };
                        document.getElementById('successMessage').textContent = msgs[selectedPaymentMethod] || 'Silakan bayar di kasir';
                        document.getElementById('successScreen').classList.add('show');
                        setTimeout(() => {
                            resetCart();
                            document.getElementById('successScreen').classList.remove('show');
                        }, 5000);
                    } else {
                        alert('❌ ' + data.error);
                        btn.disabled = false;
                        btn.textContent = '🚀 KIRIM PESANAN';
                    }
                })
                .catch(() => {
                    alert('Terjadi kesalahan!');
                    btn.disabled = false;
                    btn.textContent = '🚀 KIRIM PESANAN';
                });
        }

        function shakeElement(el) {
            el.style.animation = 'none';
            setTimeout(() => {
                el.style.animation = 'shake 0.4s ease';
            }, 10);
        }
    </script>
</body>

</html>