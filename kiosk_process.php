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

// Validasi input
if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Pesanan kosong!']);
    exit;
}
if (empty($customerName) || empty($tableNumber)) {
    echo json_encode(['success' => false, 'error' => 'Nama dan meja wajib diisi!']);
    exit;
}

// Validasi item cart
foreach ($cart as $item) {
    if (!isset($item['id']) || !isset($item['qty']) || $item['qty'] < 1) {
        echo json_encode(['success' => false, 'error' => 'Data item tidak valid!']);
        exit;
    }
}

$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$invoice = 'KSK-' . date('YmdHis');
$status = 'pending';

try {
    $pdo->beginTransaction();

    // ✅ FIXED: Jumlah placeholder dan execute SAMA (5 parameter)
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
            (invoice_number, total_amount, pay_amount, change_amount, order_type, status, customer_name, table_number) 
        VALUES 
            (?, ?, 0, 0, 'kiosk', ?, ?, ?)
    ");
    $stmt->execute([
        $invoice,       // ? 1: invoice_number
        $totalAmount,   // ? 2: total_amount
        $status,        // ? 3: status
        $customerName,  // ? 4: customer_name
        $tableNumber    // ? 5: table_number
    ]);
    $txId = $pdo->lastInsertId();

    // Insert detail + kurangi stok + catat mutasi
    $stmtDetail = $pdo->prepare("
        INSERT INTO transaction_details (transaction_id, product_id, quantity, subtotal) 
        VALUES (?, ?, ?, ?)
    ");
    $stmtStock = $pdo->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
    ");
    $stmtHist = $pdo->prepare("
        INSERT INTO stock_history (product_id, type, quantity, reference, notes) 
        VALUES (?, 'out', ?, ?, ?)
    ");

    $payNotes = "Pesanan kiosk - Bayar: " . strtoupper($paymentMethod);

    foreach ($cart as $item) {
        $productId = intval($item['id']);
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $subtotal = $price * $qty;

        // Insert detail
        $stmtDetail->execute([$txId, $productId, $qty, $subtotal]);

        // Kurangi stok
        $stmtStock->execute([$qty, $productId, $qty]);
        if ($stmtStock->rowCount() === 0) {
            throw new Exception("Stok produk ID {$productId} tidak mencukupi!");
        }

        // Catat mutasi stok keluar
        $stmtHist->execute([$productId, $qty, $invoice, $payNotes]);
    }

    $pdo->commit();

    // Hitung nomor antrian hari ini
    $stmtQ = $pdo->prepare("
        SELECT COUNT(*) as q 
        FROM transactions 
        WHERE DATE(transaction_date) = CURDATE() AND id <= ?
    ");
    $stmtQ->execute([$txId]);
    $queue = str_pad($stmtQ->fetch()['q'], 3, '0', STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'queue' => $queue,
        'invoice' => $invoice,
        'payment_method' => $paymentMethod
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
