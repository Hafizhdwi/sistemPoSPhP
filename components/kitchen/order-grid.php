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