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
        }

        .order-item .oi-name {
            font-weight: 700;
            font-size: 0.9rem;
        }

        .order-item .oi-detail {
            font-size: 0.8rem;
            color: #666;
        }

        .order-item .oi-price {
            font-weight: 800;
            color: var(--primary);
        }

        /* ✅ PAYMENT METHOD SELECTOR */
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

        /* QRIS Info Box */
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
            <p class="text-muted small mb-3">Periksa pesanan dan pilih metode pembayaran</p>

            <div id="orderItems"></div>

            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                <span style="font-weight:700; font-size:1.1rem;">TOTAL</span>
                <span style="font-weight:900; font-size:1.4rem; color:var(--primary);" id="modalTotal">Rp 0</span>
            </div>

            <!-- ✅ PAYMENT METHOD -->
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

            <!-- QRIS INFO (muncul saat pilih QRIS) -->
            <div class="qris-info" id="qrisInfo">
                <strong>📱 Scan QRIS untuk membayar</strong>
                <div class="qr-placeholder">📷</div>
                <small class="text-muted">Tunjukkan bukti pembayaran ke kasir</small>
            </div>

            <!-- TRANSFER INFO -->
            <div class="qris-info" id="transferInfo" style="background:#eff6ff; border-color:#93c5fd;">
                <strong>🏦 Transfer ke:</strong>
                <div style="font-size:1.3rem; font-weight:900; margin:8px 0; letter-spacing:2px;">1234-5678-90</div>
                <small>a.n. Mini PoS Restaurant</small><br>
                <small class="text-muted">Konfirmasi transfer ke kasir</small>
            </div>

            <input type="hidden" id="selectedPayment" value="kasir">

            <div class="row g-2 mt-2">
                <div class="col-6">
                    <input type="text" id="customerName" class="form-control form-control-lg" placeholder="Nama Anda" style="border-radius:12px; font-weight:600;">
                </div>
                <div class="col-6">
                    <input type="text" id="tableNumber" class="form-control form-control-lg" placeholder="No. Meja" style="border-radius:12px; font-weight:600;">
                </div>
            </div>

            <button class="btn-order" id="btnOrder" onclick="submitOrder()" disabled>🚀 KIRIM PESANAN</button>
            <button class="btn-order mt-2" style="background:#6c757d;" onclick="closeOrderModalDirect()">Batal</button>
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

        function filterCategory(cat, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.kiosk-card').forEach(card => {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        }

        // ✅ PAYMENT METHOD SELECTION
        function selectPayment(method, el) {
            selectedPaymentMethod = method;
            document.getElementById('selectedPayment').value = method;
            document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');

            // Show/hide info boxes
            document.getElementById('qrisInfo').classList.toggle('show', method === 'qris');
            document.getElementById('transferInfo').classList.toggle('show', method === 'transfer');
        }

        function openOrderModal() {
            const container = document.getElementById('orderItems');
            container.innerHTML = '';
            let total = 0;
            Object.entries(cart).forEach(([id, item]) => {
                if (item.qty <= 0) return;
                const sub = item.price * item.qty;
                total += sub;
                container.innerHTML += `<div class="order-item">
            <div><div class="oi-name">${item.name}</div><div class="oi-detail">${item.qty}x ${item.price.toLocaleString('id-ID')}</div></div>
            <div class="oi-price">${sub.toLocaleString('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0})}</div>
        </div>`;
            });
            document.getElementById('modalTotal').textContent = total.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });
            document.getElementById('orderModal').classList.add('show');
            checkOrderBtn();
        }

        function closeOrderModal(e) {
            if (e.target === document.getElementById('orderModal')) document.getElementById('orderModal').classList.remove('show');
        }

        function closeOrderModalDirect() {
            document.getElementById('orderModal').classList.remove('show');
        }

        document.getElementById('customerName').addEventListener('input', checkOrderBtn);
        document.getElementById('tableNumber').addEventListener('input', checkOrderBtn);

        function checkOrderBtn() {
            const name = document.getElementById('customerName').value.trim();
            const table = document.getElementById('tableNumber').value.trim();
            document.getElementById('btnOrder').disabled = !(name && table);
        }

        function submitOrder() {
            const name = document.getElementById('customerName').value.trim();
            const table = document.getElementById('tableNumber').value.trim();
            if (!name || !table) return;

            const btn = document.getElementById('btnOrder');
            btn.disabled = true;
            btn.textContent = '⏳ Mengirim...';

            const formData = new FormData();
            formData.append('cart_data', JSON.stringify(Object.entries(cart).map(([id, item]) => ({
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

                        // Pesan sukses berbeda per metode bayar
                        const msgs = {
                            'kasir': '💰 Silakan bayar di kasir',
                            'qris': '📱 Tunjukkan bukti scan ke kasir',
                            'transfer': '🏦 Konfirmasi transfer ke kasir',
                            'ewallet': '💜 Tunjukkan bukti bayar ke kasir'
                        };
                        document.getElementById('successMessage').textContent = msgs[selectedPaymentMethod] || 'Silakan bayar di kasir';

                        document.getElementById('successScreen').classList.add('show');
                        setTimeout(() => {
                            cart = {};
                            updateUI();
                            document.getElementById('customerName').value = '';
                            document.getElementById('tableNumber').value = '';
                            document.getElementById('successScreen').classList.remove('show');
                            btn.disabled = false;
                            btn.textContent = '🚀 KIRIM PESANAN';
                            // Reset payment to kasir
                            selectedPaymentMethod = 'kasir';
                            document.getElementById('selectedPayment').value = 'kasir';
                            document.querySelectorAll('.pay-option').forEach((o, i) => o.classList.toggle('selected', i === 0));
                            document.getElementById('qrisInfo').classList.remove('show');
                            document.getElementById('transferInfo').classList.remove('show');
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
    </script>
</body>

</html>