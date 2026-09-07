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

// ✅ Data User untuk Avatar Mini di Header
$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';
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
            0%, 100% {
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

        /* Navigation links */
        .kitchen-nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .kitchen-nav a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
            padding: 4px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .kitchen-nav a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        /* ✅ Kitchen User Info - Mini Version */
        .kitchen-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.08);
            padding: 8px 14px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .kitchen-user-info:hover {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .kitchen-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 0.85rem;
            position: relative;
            flex-shrink: 0;
        }

        .kitchen-avatar.admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .kitchen-avatar.kasir {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .kitchen-avatar::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background: #22c55e;
            border: 2px solid var(--kitchen-bg);
            border-radius: 50%;
        }

        .kitchen-user-name {
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .kitchen-user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            padding: 6px;
            z-index: 1000;
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.98);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kitchen-user-info-wrapper.open .kitchen-user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .kitchen-user-dropdown .dropdown-header {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 6px;
        }

        .kitchen-user-dropdown .dropdown-header .label {
            font-size: 0.65rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .kitchen-user-dropdown .dropdown-header .value {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
        }

        .kitchen-user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: #374151;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .kitchen-user-dropdown a:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .kitchen-user-dropdown a.danger {
            color: #dc2626;
        }

        .kitchen-user-dropdown a.danger:hover {
            background: #fef2f2;
        }

        .kitchen-user-dropdown .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 6px 4px;
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

            .kitchen-user-name {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="kitchen-header">
        <div>
            <h1>🍳 Kitchen Display</h1>
            <div class="kitchen-nav mt-2">
                <a href="index.php">← Kasir</a>
                <?php if (hasRole('admin')): ?>
                    <a href="dashboard.php">📊 Dashboard</a>
                <?php endif; ?>
                <a href="history.php">📜 Riwayat</a>
                <?php if (hasRole('admin')): ?>
                    <a href="settings.php">⚙️ Pengaturan</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="live-indicator">
                <div class="live-dot"></div>
                LIVE
            </div>
            <span class="badge bg-light text-dark px-3 py-2" id="orderCount">
                <?= count($orders) ?> pesanan aktif
            </span>
            
            <!-- ✅ USER INFO MINI UNTUK KITCHEN -->
            <div class="kitchen-user-info-wrapper" id="kitchenUserWrapper" style="position: relative;">
                <div class="kitchen-user-info" onclick="toggleKitchenUserDropdown(event)">
                    <div class="kitchen-avatar <?= $role ?>"><?= $initials ?></div>
                    <span class="kitchen-user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                </div>
                
                <div class="kitchen-user-dropdown">
                    <div class="dropdown-header">
                        <div class="label">Login sebagai</div>
                        <div class="value">@<?= htmlspecialchars($_SESSION['username']) ?> · <?= $roleIcon ?> <?= ucfirst($role) ?></div>
                    </div>
                    
                    <a href="profile.php">
                        <span>👤</span> Edit Profil
                    </a>
                    
                    <?php if ($role === 'admin'): ?>
                        <a href="settings.php">
                            <span>⚙️</span> Pengaturan Toko
                        </a>
                        <div class="divider"></div>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="danger">
                        <span>🚪</span> Logout
                    </a>
                </div>
            </div>
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
        // ==================== USER DROPDOWN (KITCHEN VERSION) ====================
        function toggleKitchenUserDropdown(event) {
            event.stopPropagation();
            const wrapper = document.getElementById('kitchenUserWrapper');
            if (wrapper) wrapper.classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('kitchenUserWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const wrapper = document.getElementById('kitchenUserWrapper');
                if (wrapper) wrapper.classList.remove('open');
            }
        });

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
                        location.reload();
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
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const cards = doc.querySelectorAll('.order-card');
                    const newIds = Array.from(cards).map(c => parseInt(c.id.replace('order-', '')));

                    const hasNew = newIds.some(id => !knownOrderIds.includes(id));

                    if (hasNew) {
                        showToast();
                        playNotificationSound();
                    }

                    const newGrid = doc.getElementById('kitchenGrid');
                    if (newGrid) {
                        document.getElementById('kitchenGrid').innerHTML = newGrid.innerHTML;
                        document.getElementById('orderCount').textContent = cards.length + ' pesanan aktif';
                    }

                    knownOrderIds = newIds;
                })
                .catch(() => {});
        }, 5000);

        // ==================== NOTIFICATION ====================
        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function playNotificationSound() {
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