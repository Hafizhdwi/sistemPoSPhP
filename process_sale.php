<?php
require_once 'config/database.php';
requireLogin();

// Cegah akses langsung tanpa POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cart = json_decode($_POST['cart_data'] ?? '[]', true);
$payAmount = floatval($_POST['pay_amount'] ?? 0);
$kioskOrderId = intval($_POST['kiosk_order_id'] ?? 0);

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

    // ====================================================
    // ✅ JIKA INI PEMBAYARAN KIOSK:
    // Update transaksi yang sudah ada, bukan buat baru
    // ====================================================
    if ($kioskOrderId > 0) {
        // ✅ FIXED: Terima status pending, preparing, ATAU ready
        // Karena dapur mungkin sudah mengubah status ke preparing/ready
        $stmtUpdate = $pdo->prepare("
            UPDATE transactions 
            SET status = 'completed', 
                pay_amount = ?, 
                change_amount = ?, 
                invoice_number = ?
            WHERE id = ? 
              AND status IN ('pending', 'preparing', 'ready')
              AND order_type = 'kiosk'
        ");
        $stmtUpdate->execute([$payAmount, $changeAmount, $invoice, $kioskOrderId]);

        if ($stmtUpdate->rowCount() === 0) {
            // Cek kenapa gagal: pesanan tidak ada atau sudah dibayar
            $stmtCheck = $pdo->prepare("SELECT status FROM transactions WHERE id = ?");
            $stmtCheck->execute([$kioskOrderId]);
            $order = $stmtCheck->fetch();

            if (!$order) {
                throw new Exception('Pesanan tidak ditemukan!');
            } elseif ($order['status'] === 'completed') {
                throw new Exception('Pesanan ini sudah dibayar sebelumnya!');
            } elseif ($order['status'] === 'cancelled') {
                throw new Exception('Pesanan ini sudah dibatalkan!');
            } else {
                throw new Exception('Pesanan tidak dapat diproses. Status: ' . $order['status']);
            }
        }

        $txId = $kioskOrderId;

        // ✅ Stok sudah dikurangi saat order kiosk dibuat,
        // jadi TIDAK perlu kurangi stok lagi di sini.
        // Tidak perlu juga insert stock_history tambahan karena
        // mutasi stok sudah tercatat saat order dibuat.
    }
    // ====================================================
    // ✅ TRANSAKSI KASIR BIASA:
    // Buat transaksi baru dari awal
    // ====================================================
    else {
        // 1. Insert ke tabel transactions
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
                (invoice_number, total_amount, pay_amount, change_amount, order_type, status) 
            VALUES (?, ?, ?, ?, 'kasir', 'completed')
        ");
        $stmt->execute([$invoice, $totalAmount, $payAmount, $changeAmount]);
        $txId = $pdo->lastInsertId();

        // 2. Prepare statements
        $stmtDetail = $pdo->prepare("
            INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtStock = $pdo->prepare("
            UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
        ");
        $stmtHist = $pdo->prepare("
            INSERT INTO stock_history (product_id, type, quantity, reference, notes) 
            VALUES (?, 'out', ?, ?, 'Penjualan kasir')
        ");

        // 3. Proses setiap item
        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['qty'];

            // Insert detail
            $stmtDetail->execute([$txId, $item['id'], $item['qty'], $subtotal]);

            // Kurangi stok dengan validasi
            $stmtStock->execute([$item['qty'], $item['id'], $item['qty']]);
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Stok produk '{$item['name']}' tidak mencukupi!");
            }

            // Catat mutasi stok keluar
            $stmtHist->execute([$item['id'], $item['qty'], $invoice]);
        }
    }

    $pdo->commit();

    // Redirect dengan invoice untuk cetak struk
    header('Location: index.php?success=1&change=' . $changeAmount . '&invoice=' . urlencode($invoice));
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<script>alert('❌ Gagal: " . addslashes($e->getMessage()) . "');window.location.href='index.php';</script>");
}
