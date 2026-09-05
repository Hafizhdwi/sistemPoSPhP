<?php
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$cart = json_decode($_POST['cart_data'] ?? '[]', true);
$customerName = trim($_POST['customer_name'] ?? '');
$tableNumber = trim($_POST['table_number'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'kasir');

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Pesanan kosong!']);
    exit;
}
if (empty($customerName) || empty($tableNumber)) {
    echo json_encode(['success' => false, 'error' => 'Nama dan meja wajib diisi!']);
    exit;
}

$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$invoice = 'KSK-' . date('YmdHis');

// ✅ Tentukan status berdasarkan metode bayar
// Semua metode tetap pending karena verifikasi dilakukan kasir
$status = 'pending';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO transactions 
        (invoice_number, total_amount, pay_amount, change_amount, order_type, status, customer_name, table_number) 
        VALUES (?, ?, 0, 0, 'kiosk', ?, ?, ?)");
    $stmt->execute([$invoice, $status, $customerName, $tableNumber]);
    $txId = $pdo->lastInsertId();

    // Simpan payment method di notes stock_history sebagai referensi
    $stmtDetail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmtHist = $pdo->prepare("INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES (?, 'out', ?, ?, ?)");

    $payNotes = "Pesanan kiosk - Bayar: " . strtoupper($paymentMethod);

    foreach ($cart as $item) {
        $subtotal = $item['price'] * $item['qty'];
        $stmtDetail->execute([$txId, $item['id'], $item['qty'], $subtotal]);
        $stmtStock->execute([$item['qty'], $item['id'], $item['qty']]);
        if ($stmtStock->rowCount() === 0) throw new Exception("Stok '{$item['name']}' habis!");
        $stmtHist->execute([$item['id'], $item['qty'], $invoice, $payNotes]);
    }

    $pdo->commit();

    $stmtQ = $pdo->prepare("SELECT COUNT(*) as q FROM transactions WHERE DATE(transaction_date) = CURDATE() AND id <= ?");
    $stmtQ->execute([$txId]);
    $queue = str_pad($stmtQ->fetch()['q'], 3, '0', STR_PAD_LEFT);

    echo json_encode(['success' => true, 'queue' => $queue, 'invoice' => $invoice, 'payment_method' => $paymentMethod]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
