<?php
require_once 'config/database.php';
requireLogin(); // ✅ Harus login untuk transaksi

// Cegah akses langsung tanpa POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cart = json_decode($_POST['cart_data'] ?? '[]', true);
$payAmount = floatval($_POST['pay_amount'] ?? 0);

if (empty($cart)) {
    die("<script>alert('Keranjang kosong!');window.location.href='index.php';</script>");
}

// Hitung total belanja
$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

// Validasi pembayaran
if ($payAmount < $totalAmount) {
    die("<script>alert('Uang pembayaran kurang!');window.history.back();</script>");
}

$changeAmount = $payAmount - $totalAmount;
$invoice = 'INV-' . date('YmdHis');

try {
    $pdo->beginTransaction();

    // 1. Insert ke tabel transactions
    $stmt = $pdo->prepare("INSERT INTO transactions (invoice_number, total_amount, pay_amount, change_amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$invoice, $totalAmount, $payAmount, $changeAmount]);
    $txId = $pdo->lastInsertId();

    // 2. Prepare statements untuk detail, stok, dan history
    $stmtDetail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
    $stmtStock  = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmtHist   = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, 'out', ?, ?, 'Penjualan kasir')");

    // 3. Looping setiap item di keranjang
    foreach ($cart as $item) {
        $subtotal = $item['price'] * $item['qty'];

        // Insert detail transaksi
        $stmtDetail->execute([$txId, $item['id'], $item['qty'], $subtotal]);

        // Kurangi stok produk (dengan validasi stok cukup)
        $stmtStock->execute([$item['qty'], $item['id'], $item['qty']]);
        if ($stmtStock->rowCount() === 0) {
            throw new Exception("Stok produk '{$item['name']}' tidak mencukupi!");
        }

        // Catat riwayat stok keluar
        $stmtHist->execute([$item['id'], $item['qty'], $invoice]);
    }

    $pdo->commit();

    // ✅ Redirect kembali ke kasir dengan notifikasi sukses + nomor invoice untuk cetak struk
    header('Location: index.php?success=1&change=' . $changeAmount . '&invoice=' . urlencode($invoice));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("<script>alert('❌ Gagal memproses transaksi: " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
