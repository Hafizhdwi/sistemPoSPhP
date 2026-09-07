<div class="kiosk-grid" id="productGrid">
    <?php foreach ($products as $p): 
        $category = 'snack';
        if (preg_match('/kopi|teh|air|minum|jus|soda/i', $p['name'])) {
            $category = 'minuman';
        } elseif (preg_match('/nasi|mie|ayam|goreng|bakso|soto|rendang/i', $p['name'])) {
            $category = 'makanan';
        }
    ?>
        <div class="kiosk-card"
            data-id="<?= $p['id'] ?>"
            data-name="<?= htmlspecialchars($p['name']) ?>"
            data-price="<?= $p['price'] ?>"
            data-category="<?= $category ?>"
            onclick="toggleItem(this)">
            <div class="qty-badge" id="badge-<?= $p['id'] ?>">0</div>
            <span class="emoji">
                <?= $category === 'minuman' ? '☕' : ($category === 'makanan' ? '🍽️' : '🥐') ?>
            </span>
            <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
            <div class="pprice"><?= formatRupiah($p['price']) ?></div>
        </div>
    <?php endforeach; ?>
</div>