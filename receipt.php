<?php
require_once 'config/database.php';
requireLogin();

$invoice = $_GET['invoice'] ?? '';
if (empty($invoice)) die("No invoice tidak valid!");

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE invoice_number = ?");
$stmt->execute([$invoice]);
$transaction = $stmt->fetch();

if (!$transaction) die("Transaksi tidak ditemukan!");

$stmtDetail = $pdo->prepare("
    SELECT td.*, p.name 
    FROM transaction_details td 
    JOIN products p ON td.product_id = p.id 
    WHERE td.transaction_id = ?
");
$stmtDetail->execute([$transaction['id']]);
$items = $stmtDetail->fetchAll();

// ✅ Ambil Store Settings
$showLogo = getSetting($pdo, 'receipt_show_logo', '1');
$logo = getSetting($pdo, 'store_logo', '');
$storeName = getSetting($pdo, 'store_name', 'Mini PoS');
$storeAddress = getSetting($pdo, 'store_address', '');
$storePhone = getSetting($pdo, 'store_phone', '');
$receiptHeader = getSetting($pdo, 'receipt_header', '');
$receiptFooter = getSetting($pdo, 'receipt_footer', '');
$showAddress = getSetting($pdo, 'receipt_show_address', '1');
$taxLabel = getSetting($pdo, 'tax_label', 'Pajak');

// ✅ Ambil data pajak dari database (bukan hitung ulang)
$taxAmount = floatval($transaction['tax_amount'] ?? 0);
$subtotal = floatval($transaction['subtotal_amount'] ?? 0);

// Fallback untuk data lama: hitung dari items jika kolom belum diisi
if ($subtotal == 0) {
    $subtotal = array_sum(array_column($items, 'subtotal'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk - <?= htmlspecialchars($invoice) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0; padding: 20px; background: #f0f0f0;
        }
        .receipt-box {
            width: 300px; margin: 0 auto; background: #fff;
            padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider { border-top: 1px dashed #333; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        td { padding: 2px 0; vertical-align: top; }
        h2 { margin: 0 0 5px; font-size: 18px; }
        .small { font-size: 11px; color: #555; }
        .logo-img { max-height: 50px; margin-bottom: 8px; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .receipt-box { width: 100%; max-width: 80mm; box-shadow: none; padding: 5px; margin: 0; }
            .no-print { display: none !important; }
            @page { margin: 0; size: auto; }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="receipt-box">
        <!-- HEADER TOKO -->
        <div class="text-center">
            <?php if ($showLogo == '1' && !empty($logo)): ?>
                <img src="<?= htmlspecialchars($logo) ?>" class="logo-img" alt="Logo">
            <?php endif; ?>
            
            <h2><?= htmlspecialchars($storeName) ?></h2>
            
            <?php if ($showAddress == '1'): ?>
                <p class="small" style="margin:0;"><?= htmlspecialchars($storeAddress) ?></p>
                <p class="small" style="margin:0 0 10px;">Telp: <?= htmlspecialchars($storePhone) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($receiptHeader)): ?>
                <p class="small" style="margin:0 0 5px; opacity:0.8;"><?= htmlspecialchars($receiptHeader) ?></p>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <!-- INFO TRANSAKSI -->
        <table>
            <tr>
                <td>No</td>
                <td class="text-right"><?= htmlspecialchars($transaction['invoice_number']) ?></td>
            </tr>
            <tr>
                <td>Tgl</td>
                <td class="text-right"><?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="text-right"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></td>
            </tr>
            <?php if ($transaction['customer_name']): ?>
            <tr>
                <td>Pelanggan</td>
                <td class="text-right"><?= htmlspecialchars($transaction['customer_name']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($transaction['table_number']): ?>
            <tr>
                <td>Meja</td>
                <td class="text-right"><?= htmlspecialchars($transaction['table_number']) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="divider"></div>

        <!-- DAFTAR ITEM -->
        <table>
            <?php foreach ($items as $item): 
                $pricePerUnit = $item['quantity'] > 0 ? $item['subtotal'] / $item['quantity'] : 0;
            ?>
                <tr>
                    <td colspan="2"><?= htmlspecialchars($item['name']) ?></td>
                </tr>
                <tr>
                    <td>&nbsp;&nbsp;<?= $item['quantity'] ?> x <?= formatRupiah($pricePerUnit) ?></td>
                    <td class="text-right"><?= formatRupiah($item['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="divider"></div>

        <!-- TOTAL & PEMBAYARAN -->
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-right"><?= formatRupiah($subtotal) ?></td>
            </tr>
            <?php if ($taxAmount > 0): ?>
            <tr>
                <td><?= htmlspecialchars($taxLabel) ?></td>
                <td class="text-right"><?= formatRupiah($taxAmount) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong><?= formatRupiah($transaction['total_amount']) ?></strong></td>
            </tr>
            <tr>
                <td>Tunai</td>
                <td class="text-right"><?= formatRupiah($transaction['pay_amount']) ?></td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td class="text-right"><?= formatRupiah($transaction['change_amount']) ?></td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- FOOTER -->
        <div class="text-center small" style="margin-top:10px;">
            <?php if (!empty($receiptFooter)): ?>
                <p style="margin:0;"><?= htmlspecialchars($receiptFooter) ?></p>
            <?php else: ?>
                <p style="margin:0;">✨ Terima kasih atas kunjungan Anda! ✨</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="no-print text-center" style="margin-top: 20px;">
        <button onclick="window.close()" style="padding: 10px 30px; cursor:pointer; font-size:14px;">
            ← Tutup / Kembali
        </button>
    </div>

</body>
</html>