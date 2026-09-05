<?php
require_once 'config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $pdo->beginTransaction();

    // === TAMBAH PRODUK BARU ===
    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if (empty($name) || $price < 0) throw new Exception('Data produk tidak valid!');

        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $stock]);
        $productId = $pdo->lastInsertId();

        // Catat stok awal sebagai adjustment
        if ($stock > 0) {
            $stmtHist = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, 'adjustment', ?, 'INITIAL', 'Stok awal produk baru')");
            $stmtHist->execute([$productId, $stock]);
        }

        $pdo->commit();
        header('Location: products.php?msg=added');
        exit;
    }

    // === EDIT PRODUK (Nama & Harga) ===
    if ($action === 'update') {
        $id    = intval($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if (!$id || empty($name)) throw new Exception('Data tidak valid!');

        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);

        $pdo->commit();
        header('Location: products.php?msg=updated');
        exit;
    }

    // === ✅ BARU: EDIT STOK LANGSUNG (ADJUSTMENT) ===
    if ($action === 'adjust_stock') {
        $id       = intval($_POST['product_id'] ?? 0);
        $newStock = intval($_POST['new_stock'] ?? 0);
        $notes    = trim($_POST['notes'] ?? 'Penyesuaian stok manual');

        if (!$id || $newStock < 0) throw new Exception('Data stok tidak valid!');

        // Ambil stok lama
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) throw new Exception('Produk tidak ditemukan!');

        $oldStock = $product['stock'];
        $diff = $newStock - $oldStock;

        // Jika tidak ada perubahan, tidak perlu proses
        if ($diff === 0) {
            $pdo->commit();
            header('Location: products.php?msg=nochange');
            exit;
        }

        // Update stok produk
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$newStock, $id]);

        // Catat riwayat adjustment
        // type: 'in' jika stok bertambah, 'out' jika berkurang
        $type = $diff > 0 ? 'in' : 'out';
        $qty  = abs($diff);
        $ref  = 'ADJ-' . date('YmdHis');

        $stmtHist = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, ?, ?, ?, ?)");
        $stmtHist->execute([$id, $type, $qty, $ref, $notes]);

        $pdo->commit();
        header('Location: products.php?msg=stock_adjusted');
        exit;
    }

    // === RESTOCK (STOK MASUK) ===
    if ($action === 'restock') {
        $id    = intval($_POST['product_id'] ?? 0);
        $qty   = intval($_POST['quantity'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (!$id || $qty <= 0) throw new Exception('Jumlah restock harus lebih dari 0!');

        $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$qty, $id]);

        $ref = 'RESTOCK-' . date('YmdHis');
        $stmtHist = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, 'in', ?, ?, ?)");
        $stmtHist->execute([$id, $qty, $ref, $notes ?: 'Restock manual']);

        $pdo->commit();
        header('Location: products.php?msg=restocked');
        exit;
    }

    throw new Exception('Aksi tidak dikenali!');
} catch (Exception $e) {
    $pdo->rollBack();
    die("<script>alert('❌ " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
