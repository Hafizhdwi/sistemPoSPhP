<?php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name ASC");
$products = $stmt->fetchAll();

// Siapkan data produk untuk JavaScript
$productsData = [];
foreach ($products as $p) {
    $category = 'snack';
    if (preg_match('/kopi|teh|air|minum|jus|soda/i', $p['name'])) {
        $category = 'minuman';
    } elseif (preg_match('/nasi|mie|ayam|goreng|bakso|soto|rendang/i', $p['name'])) {
        $category = 'makanan';
    }
    
    $productsData[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (float)$p['price'],
        'stock' => (int)$p['stock'],
        'category' => $category
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Order Here - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kiosk.css">
</head>
<body>

    <?php include 'components/kiosk/header.php'; ?>
    <?php include 'components/kiosk/product-grid.php'; ?>
    <?php include 'components/kiosk/floating-cart.php'; ?>
    <?php include 'components/kiosk/order-modal.php'; ?>
    <?php include 'components/kiosk/confirm-modal.php'; ?>
    <?php include 'components/kiosk/success-screen.php'; ?>

    <script>
        window.KIOSK_CONFIG = {
            products: <?= json_encode($productsData) ?>
        };
    </script>
    <script src="assets/js/kiosk.js"></script>
</body>
</html>