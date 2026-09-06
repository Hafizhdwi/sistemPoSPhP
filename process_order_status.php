<?php
require_once 'config/database.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');

if (!$orderId || empty($newStatus)) {
    echo json_encode(['success' => false, 'error' => 'Data tidak valid']);
    exit;
}

// Validasi status yang diperbolehkan
$allowedStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Status tidak valid']);
    exit;
}

try {
    // Ambil status saat ini untuk validasi transisi
    $stmt = $pdo->prepare("SELECT status, order_type FROM transactions WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
        exit;
    }

    // Validasi transisi status
    $transitions = [
        'pending'   => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready'     => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    if (!in_array($newStatus, $transitions[$order['status']] ?? [])) {
        echo json_encode(['success' => false, 'error' => "Tidak bisa ubah dari '{$order['status']}' ke '{$newStatus}'"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    echo json_encode(['success' => true, 'message' => "Status diperbarui ke: $newStatus"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
