<?php
require_once 'config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
    $payAmount = floatval($_POST['pay_amount'] ?? 0);
    $kioskOrderId = $_POST['kiosk_order_id'] ?? null;

    if (empty($cartData)) {
        throw new Exception('Keranjang kosong!');
    }

    if ($payAmount < 0) {
        throw new Exception('Jumlah bayar tidak valid!');
    }

    // ✅ Ambil setting pajak
    $taxEnabled = getSetting($pdo, 'tax_enabled', '0');
    $taxRate = floatval(getSetting($pdo, 'tax_rate', '0'));

    $pdo->beginTransaction();

    // Hitung subtotal
    $subtotal = 0;
    foreach ($cartData as $item) {
        $subtotal += $item['price'] * $item['qty'];
    }

    // ✅ Hitung pajak
    $taxAmount = 0;
    if ($taxEnabled == '1' && $taxRate > 0) {
        $taxAmount = round($subtotal * $taxRate / 100, 0);
    }

    // ✅ Total akhir = subtotal + pajak
    $totalAmount = $subtotal + $taxAmount;

    // Validasi pembayaran
    if ($payAmount < $totalAmount) {
        throw new Exception('Uang pembayaran kurang! Total: ' . formatRupiah($totalAmount));
    }

    $changeAmount = $payAmount - $totalAmount;

    // Generate invoice number
    $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(100, 999);

    // ✅ AUTO-DETECT: Cek apakah kolom subtotal_amount dan tax_amount ada
    $hasSubtotalCol = false;
    $hasTaxCol = false;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
        $hasSubtotalCol = in_array('subtotal_amount', $cols);
        $hasTaxCol = in_array('tax_amount', $cols);
    } catch (Exception $e) {
        // Silent fail
    }

    // ✅ AUTO-MIGRATE: Jika kolom belum ada, tambahkan otomatis
    if (!$hasSubtotalCol) {
        try {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_amount");
            $hasSubtotalCol = true;
        } catch (Exception $e) {
            error_log('Auto-migrate subtotal_amount failed: ' . $e->getMessage());
        }
    }

    if (!$hasTaxCol) {
        try {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal_amount");
            $hasTaxCol = true;
        } catch (Exception $e) {
            error_log('Auto-migrate tax_amount failed: ' . $e->getMessage());
        }
    }

    // ✅ Gunakan query yang sesuai dengan struktur tabel
    if ($hasSubtotalCol && $hasTaxCol) {
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (invoice_number, total_amount, subtotal_amount, tax_amount, pay_amount, change_amount, order_type, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'kasir', 'completed')
        ");
        $stmt->execute([
            $invoiceNumber,
            $totalAmount,
            $subtotal,
            $taxAmount,
            $payAmount,
            $changeAmount
        ]);
    } else {
        // Fallback: query lama tanpa kolom pajak
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (invoice_number, total_amount, pay_amount, change_amount, order_type, status) 
            VALUES (?, ?, ?, ?, 'kasir', 'completed')
        ");
        $stmt->execute([
            $invoiceNumber,
            $totalAmount,
            $payAmount,
            $changeAmount
        ]);
    }

    $transactionId = $pdo->lastInsertId();

    // Simpan detail transaksi
    $stmtDetail = $pdo->prepare("
        INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) 
        VALUES (?, ?, ?, ?)
    ");

    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cartData as $item) {
        $itemSubtotal = $item['price'] * $item['qty'];
        
        $stmtDetail->execute([
            $transactionId,
            $item['id'],
            $item['qty'],
            $itemSubtotal
        ]);

        // Kurangi stok
        $stmtUpdateStock->execute([$item['qty'], $item['id']]);

        // Catat mutasi stok
        $stmtStock = $pdo->prepare("
            INSERT INTO stock_history (product_id, type, quantity, reference, notes) 
            VALUES (?, 'out', ?, ?, ?)
        ");
        $stmtStock->execute([
            $item['id'],
            $item['qty'],
            $invoiceNumber,
            'Penjualan via kasir'
        ]);
    }

    // ✅ Update status pesanan kiosk jika ada
    if ($kioskOrderId) {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
        $stmt->execute([$kioskOrderId]);
    }

    // Log aktivitas
    logActivity(
        $pdo,
        'transaction_created',
        'transaction',
        $transactionId,
        "Kasir {$_SESSION['full_name']} membuat transaksi {$invoiceNumber} senilai " . formatRupiah($totalAmount) . 
        ($taxAmount > 0 ? " (termasuk pajak " . formatRupiah($taxAmount) . ")" : "")
    );

    $pdo->commit();

    // Redirect ke kasir dengan pesan sukses
    header('Location: index.php?success=1&change=' . $changeAmount . '&invoice=' . urlencode($invoiceNumber));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<script>alert('❌ " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
?>