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

    // ✅ JIKA INI PEMBAYARAN KIOSK:
    // Update transaksi yang sudah ada, bukan buat baru
    if ($kioskOrderId > 0) {
        $stmtUpdate = $pdo->prepare("
            UPDATE transactions 
            SET status = 'completed', 
                pay_amount = ?, 
                change_amount = ?, 
                invoice_number = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmtUpdate->execute([$payAmount, $changeAmount, $invoice, $kioskOrderId]);

        if ($stmtUpdate->rowCount() === 0) {
            throw new Exception('Pesanan tidak ditemukan atau sudah diproses!');
        }

        $txId = $kioskOrderId;

        // ✅ Stok sudah dikurangi saat order kiosk dibuat,
        // jadi TIDAK perlu kurangi stok lagi di sini.
        // Hanya catat riwayat pembayaran.
        $stmtHist = $pdo->prepare("
            INSERT INTO stock_history (product_id, type, quantity, reference, notes) 
            VALUES (?, 'adjustment', 0, ?, 'Pembayaran kiosk dikonfirmasi')
        ");
        // Catat untuk setiap produk di transaksi ini
        $stmtItems = $pdo->prepare("SELECT product_id FROM transaction_details WHERE transaction_id = ?");
        $stmtItems->execute([$kioskOrderId]);
        foreach ($stmtItems->fetchAll() as $item) {
            $stmtHist->execute([$item['product_id'], $invoice]);
        }
    }
    // ✅ TRANSAKSI KASIR BIASA:
    else {
        // 1. Insert ke tabel transactions
        $stmt = $pdo->prepare("INSERT INTO transactions 
            (invoice_number, total_amount, pay_amount, change_amount, order_type, status) 
            VALUES (?, ?, ?, ?, 'kasir', 'completed')");
        $stmt->execute([$invoice, $totalAmount, $payAmount, $changeAmount]);
        $txId = $pdo->lastInsertId();

        // 2. Insert detail + kurangi stok + catat mutasi
        $stmtDetail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
        $stmtStock  = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmtHist   = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, 'out', ?, ?, 'Penjualan kasir')");

        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['qty'];
            $stmtDetail->execute([$txId, $item['id'], $item['qty'], $subtotal]);

            $stmtStock->execute([$item['qty'], $item['id'], $item['qty']]);
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Stok produk '{$item['name']}' tidak mencukupi!");
            }

            $stmtHist->execute([$item['id'], $item['qty'], $invoice]);
        }
    }

    $pdo->commit();

    // Redirect dengan invoice untuk cetak struk
    header('Location: index.php?success=1&change=' . $changeAmount . '&invoice=' . urlencode($invoice));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("<script>alert('❌ Gagal: " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
