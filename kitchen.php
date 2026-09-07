<?php
require_once 'config/database.php';
requireLogin();

$orders = $pdo->query("
    SELECT t.*, GROUP_CONCAT(CONCAT(td.quantity, 'x ', p.name) SEPARATOR ', ') as items
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN products p ON td.product_id = p.id
    WHERE t.status IN ('pending', 'preparing', 'ready')
    GROUP BY t.id
    ORDER BY t.transaction_date ASC
")->fetchAll();

$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';

$orderIds = array_column($orders, 'id');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍳 Kitchen Display - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kitchen.css">
</head>
<body>

    <?php include 'components/kitchen/header.php'; ?>
    <?php include 'components/kitchen/toast-notification.php'; ?>
    <?php include 'components/kitchen/order-grid.php'; ?>

    <script>
        window.KITCHEN_CONFIG = {
            orderIds: <?= json_encode($orderIds) ?>
        };
    </script>
    <script src="assets/js/kitchen.js"></script>
</body>
</html>