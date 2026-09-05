<?php
require_once 'config/database.php';
requireLogin(); // ✅ Harus login untuk cetak struk

$invoice = $_GET['invoice'] ?? '';
if (empty($invoice)) die("No invoice tidak valid!");

// Ambil data transaksi header
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE invoice_number = ?");
$stmt->execute([$invoice]);
$transaction = $stmt->fetch();

if (!$transaction) die("Transaksi tidak ditemukan!");

// Ambil detail item
$stmtDetail = $pdo->prepare("
    SELECT td.*, p.name 
    FROM transaction_details td 
    JOIN products p ON td.product_id = p.id 
    WHERE td.transaction_id = ?
");
$stmtDetail->execute([$transaction['id']]);
$items = $stmtDetail->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Struk - <?= htmlspecialchars($invoice) ?></title>
    <style>
        /* STYLE TAMPILAN DI LAYAR & KERTAS */
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            background: #f0f0f0;
        }

        .receipt-box {
            width: 300px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .divider {
            border-top: 1px dashed #333;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        h2 {
            margin: 0 0 5px;
            font-size: 18px;
        }

        .small {
            font-size: 11px;
            color: #555;
        }

        /* STYLE KHUSUS SAAT DICETAK (PENTING!) */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .receipt-box {
                width: 100%;
                max-width: 80mm;
                box-shadow: none;
                padding: 5px;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 0;
                size: auto;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="receipt-box">
        <!-- HEADER TOKO -->
        <div class="text-center">
            <h2>🏪 MINI POS</h2>
            <p class="small" style="margin:0;">Jl. Teknologi No. 123, Jakarta</p>
            <p class="small" style="margin:0 0 10px;">Telp: (021) 1234-5678</p>
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
                <td class="text-right">Admin</td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- DAFTAR ITEM -->
        <table>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="2"><?= htmlspecialchars($item['name']) ?></td>
                </tr>
                <tr>
                    <td>&nbsp;&nbsp;<?= $item['quantity'] ?> x <?= formatRupiah($item['subtotal'] / $item['quantity']) ?></td>
                    <td class="text-right"><?= formatRupiah($item['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="divider"></div>

        <!-- TOTAL & PEMBAYARAN -->
        <table>
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
            <p style="margin:0;">✨ Terima kasih atas kunjungan Anda! ✨</p>
            <p style="margin:5px 0 0;">Barang yang dibeli tidak dapat ditukar</p>
        </div>
    </div>

    <!-- Tombol Kembali (Hanya muncul di layar, hilang saat print) -->
    <div class="no-print text-center" style="margin-top: 20px;">
        <button onclick="window.close()" style="padding: 10px 30px; cursor:pointer; font-size:14px;">
            ← Tutup / Kembali
        </button>
    </div>

</body>

</html>