<?php
require_once 'config/database.php';
requireLogin();

// Ambil pesanan aktif (pending + preparing)
$orders = $pdo->query("
    SELECT t.*, GROUP_CONCAT(CONCAT(td.quantity, 'x ', p.name) SEPARATOR ', ') as items
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN products p ON td.product_id = p.id
    WHERE t.status IN ('pending', 'preparing', 'ready')
    GROUP BY t.id
    ORDER BY t.transaction_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍳 Kitchen Display - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        :root {
            --kitchen-bg: #0f172a;
            --card-pending: #1e293b;
            --card-preparing: #1e3a5f;
            --card-ready: #14532d;
        }

        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: var(--kitchen-bg);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }

        .kitchen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .kitchen-header h1 {
            font-weight: 900;
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.3);
            }
        }

        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .order-card {
            border-radius: 16px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .order-card.pending {
            background: var(--card-pending);
            border-color: #f59e0b;
        }

        .order-card.preparing {
            background: var(--card-preparing);
            border-color: #3b82f6;
        }

        .order-card.ready {
            background: var(--card-ready);
            border-color: #22c55e;
        }

        .order-card .queue-num {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .order-card .customer-info {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 12px;
        }

        .order-card .items-list {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            line-height: 1.8;
        }

        .order-card .timer {
            font-size: 0.75rem;
            opacity: 0.6;
            position: absolute;
            top: 12px;
            right: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .status-badge.pending {
            background: #f59e0b;
            color: #1a1a1a;
        }

        .status-badge.preparing {
            background: #3b82f6;
            color: white;
        }

        .status-badge.ready {
            background: #22c55e;
            color: white;
        }

        .btn-kitchen {
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }

        .btn-kitchen:active {
            transform: scale(0.97);
        }

        .btn-start {
            background: #3b82f6;
        }

        .btn-start:hover {
            background: #2563eb;
        }

        .btn-ready {
            background: #22c55e;
        }

        .btn-ready:hover {
            background: #16a34a;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            opacity: 0.5;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        .back-link {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .back-link:hover {
            color: white;
        }

        /* Notification toast */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f59e0b;
            color: #1a1a1a;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.4);
            z-index: 999;
            transform: translateX(150%);
            transition: transform 0.3s ease;
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        @media (max-width: 576px) {
            .kitchen-grid {
                grid-template-columns: 1fr;
            }

            body {
                padding: 12px;
            }

            .order-card .queue-num {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="kitchen-header">
        <div>
            <h1>🍳 Kitchen Display</h1>
            <a href="index.php" class="back-link">← Kembali ke Kasir</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
            </div>
            <span class="badge bg-light text-dark px-3 py-2" id="orderCount">
                <?= count($orders) ?> pesanan aktif
            </span>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div class="toast-notification" id="toast">🔔 Pesanan baru masuk!</div>

    <!-- KITCHEN GRID -->
    <div class="kitchen-grid" id="kitchenGrid">
        <?php if (empty($orders)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <div class="icon">👨‍🍳</div>
                <h4 class="fw-bold">Tidak ada pesanan aktif</h4>
                <p class="mb-0">Pesanan baru akan muncul di sini secara otomatis</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card <?= $order['status'] ?>" id="order-<?= $order['id'] ?>">
                    <div class="timer" data-time="<?= strtotime($order['transaction_date']) ?>">
                        <?= date('H:i', strtotime($order['transaction_date'])) ?>
                    </div>

                    <span class="status-badge <?= $order['status'] ?>">
                        <?php
                        echo match ($order['status']) {
                            'pending' => '⏳ MENUNGGU',
                            'preparing' => '🔥 DIMASAK',
                            'ready' => '✅ SIAP',
                            default => $order['status']
                        };
                        ?>
                    </span>

                    <div class="queue-num">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></div>
                    <div class="customer-info">
                        👤 <?= htmlspecialchars($order['customer_name'] ?? 'Kasir') ?>
                        &nbsp;|&nbsp;
                        🪑 Meja <?= htmlspecialchars($order['table_number'] ?? '-') ?>
                    </div>

                    <div class="items-list">
                        <?= htmlspecialchars($order['items'] ?: 'Tidak ada item') ?>
                    </div>

                    <?php if ($order['status'] === 'pending'): ?>
                        <button class="btn-kitchen btn-start" onclick="updateStatus(<?= $order['id'] ?>, 'preparing')">
                            🔥 MULAI MASAK
                        </button>
                    <?php elseif ($order['status'] === 'preparing'): ?>
                        <button class="btn-kitchen btn-ready" onclick="updateStatus(<?= $order['id'] ?>, 'ready')">
                            ✅ SIAP DISAJIKAN
                        </button>
                    <?php elseif ($order['status'] === 'ready'): ?>
                        <div class="text-center py-2 fw-bold" style="color:#22c55e; font-size:1.1rem;">
                            ✅ Siap! Tunggu kasir proses pembayaran
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // ==================== UPDATE STATUS ====================
        function updateStatus(orderId, newStatus) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            fetch('process_order_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh untuk update tampilan
                    } else {
                        alert('❌ ' + data.error);
                    }
                })
                .catch(() => alert('Terjadi kesalahan koneksi!'));
        }

        // ==================== AUTO-REFRESH (POLLING) ====================
        let knownOrderIds = <?= json_encode(array_column($orders, 'id')) ?>;

        setInterval(function() {
            fetch('kitchen.php?ajax=1')
                .then(r => r.text())
                .then(html => {
                    // Parse response untuk ambil order IDs baru
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const cards = doc.querySelectorAll('.order-card');
                    const newIds = Array.from(cards).map(c => parseInt(c.id.replace('order-', '')));

                    // Cek apakah ada order baru
                    const hasNew = newIds.some(id => !knownOrderIds.includes(id));

                    if (hasNew) {
                        showToast();
                        playNotificationSound();
                    }

                    // Update grid
                    const newGrid = doc.getElementById('kitchenGrid');
                    if (newGrid) {
                        document.getElementById('kitchenGrid').innerHTML = newGrid.innerHTML;
                        document.getElementById('orderCount').textContent = cards.length + ' pesanan aktif';
                    }

                    knownOrderIds = newIds;
                })
                .catch(() => {});
        }, 5000); // Polling setiap 5 detik

        // ==================== NOTIFICATION ====================
        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function playNotificationSound() {
            // Beep sound menggunakan Web Audio API
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const oscillator = ctx.createOscillator();
                const gain = ctx.createGain();
                oscillator.connect(gain);
                gain.connect(ctx.destination);
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                oscillator.start(ctx.currentTime);
                oscillator.stop(ctx.currentTime + 0.5);
            } catch (e) {}
        }
    </script>
</body>

</html>