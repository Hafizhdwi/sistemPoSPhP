<?php
require_once 'config/database.php';
requireLogin();

// Ambil semua produk untuk search
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$allProducts = $stmt->fetchAll();
$products = array_filter($allProducts, fn($p) => $p['stock'] > 0);

// ✅ Load pesanan kiosk ke cart jika ada parameter process_order
$processOrder = null;
$processItems = [];
if (isset($_GET['process_order'])) {
    $orderId = intval($_GET['process_order']);
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status IN ('pending','preparing','ready')");
    $stmt->execute([$orderId]);
    $processOrder = $stmt->fetch();

    if ($processOrder) {
        $stmtItems = $pdo->prepare("
            SELECT td.product_id, td.quantity, p.name, p.price 
            FROM transaction_details td 
            JOIN products p ON td.product_id = p.id 
            WHERE td.transaction_id = ?
        ");
        $stmtItems->execute([$orderId]);
        $processItems = $stmtItems->fetchAll();
    }
}

// ✅ Ambil pesanan kiosk yang masih aktif (pending, preparing, ready)
$pendingOrders = $pdo->query("
    SELECT t.*, COUNT(td.id) as item_count 
    FROM transactions t 
    LEFT JOIN transaction_details td ON t.id = td.transaction_id 
    WHERE t.status IN ('pending', 'preparing', 'ready') AND t.order_type = 'kiosk'
    GROUP BY t.id 
    ORDER BY t.transaction_date ASC
")->fetchAll();

// Alert sukses
$successMsg = '';
$invoiceNumber = '';
if (isset($_GET['success'])) {
    $change = isset($_GET['change']) ? floatval($_GET['change']) : 0;
    $successMsg = "Transaksi Berhasil! Kembalian: " . formatRupiah($change);
    $invoiceNumber = $_GET['invoice'] ?? '';
}

// ✅ Ambil setting pajak untuk JavaScript
$taxEnabled = getSetting($pdo, 'tax_enabled', '0') == '1';
$taxRate = floatval(getSetting($pdo, 'tax_rate', '0'));
$taxLabel = getSetting($pdo, 'tax_label', 'Pajak');

// ✅ Data User untuk Avatar
$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';

// ✅ Ambil data transaksi per hari untuk kalender (3 bulan terakhir)
$calendarData = [];
try {
    $stmt = $pdo->query("
        SELECT 
            DATE(transaction_date) as date,
            COUNT(*) as tx_count,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM transactions
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY DATE(transaction_date)
        ORDER BY date DESC
    ");
    foreach ($stmt->fetchAll() as $row) {
        $calendarData[$row['date']] = [
            'count' => (int)$row['tx_count'],
            'revenue' => (float)$row['total_revenue'],
            'completed' => (int)$row['completed_count']
        ];
    }
} catch (Exception $e) {
    $calendarData = [];
}

// ✅ Stats hari ini
$todayStats = $calendarData[date('Y-m-d')] ?? ['count' => 0, 'revenue' => 0, 'completed' => 0];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Search */
        .search-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .search-wrapper input {
            padding-left: 48px;
            border-radius: 14px;
            border: 2px solid var(--border-color);
            font-size: 1rem;
            transition: all 0.2s;
        }

        .search-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
            padding: 4px 8px;
            border-radius: 50%;
        }

        .search-clear:hover {
            background: #f3f4f6;
            color: var(--text-main);
        }

        .shortcut-hint {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: #f3f4f6;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: monospace;
            pointer-events: none;
        }

        .product-card.out-of-stock {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(0.5);
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .no-results.show {
            display: block;
        }

        .pending-panel {
            border-left: 5px solid #e63946 !important;
        }

        @keyframes slideIn {
            from {
                transform: translateX(150%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ============================================
           CALENDAR PANEL - MODERN DESIGN
           ============================================ */
        .calendar-card {
            position: sticky;
            top: 30px;
            border-radius: 20px;
            border: none;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 20px 20px;
            position: relative;
            overflow: hidden;
        }

        .calendar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .calendar-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            position: relative;
        }

        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
            backdrop-filter: blur(10px);
        }

        .calendar-nav-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }

        .calendar-month-year {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .calendar-today-box {
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 12px 16px;
            border: 1px solid rgba(255,255,255,0.25);
            position: relative;
        }

        .today-day {
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 4px;
        }

        .today-info {
            font-size: 0.7rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-body {
            padding: 20px;
            background: white;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-bottom: 10px;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 0;
        }

        .calendar-weekday.weekend {
            color: #ef4444;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            position: relative;
            background: transparent;
            color: #374151;
            border: 1.5px solid transparent;
        }

        .calendar-day:hover:not(.empty):not(.other-month) {
            background: #f3f4f6;
            border-color: #e5e7eb;
            transform: scale(1.05);
        }

        .calendar-day.other-month {
            color: #d1d5db;
            cursor: default;
        }

        .calendar-day.today {
            background: var(--primary);
            color: white;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .calendar-day.today:hover {
            background: var(--primary-hover);
            color: white;
        }

        .calendar-day.selected:not(.today) {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }

        .calendar-day.has-transactions::after {
            content: '';
            position: absolute;
            bottom: 4px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #10b981;
        }

        .calendar-day.today.has-transactions::after {
            background: white;
        }

        .calendar-day.has-many-transactions::after {
            width: 6px;
            height: 6px;
            background: #f59e0b;
        }

        .calendar-day.empty {
            cursor: default;
        }

        /* Calendar Stats Panel */
        .calendar-stats {
            padding: 16px 20px;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }

        .calendar-stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .calendar-stats-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-stats-date {
            font-size: 0.7rem;
            color: #9ca3af;
            font-weight: 600;
        }

        .calendar-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 1px solid #f3f4f6;
            transition: all 0.15s;
        }

        .calendar-stat-row:hover {
            border-color: #e5e7eb;
            transform: translateX(2px);
        }

        .calendar-stat-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 600;
        }

        .calendar-stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .calendar-stat-icon.blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .calendar-stat-icon.green {
            background: #dcfce7;
            color: #16a34a;
        }

        .calendar-stat-icon.orange {
            background: #fed7aa;
            color: #c2410c;
        }

        .calendar-stat-value {
            font-size: 0.9rem;
            font-weight: 800;
            color: #1f2937;
        }

        .calendar-no-data {
            text-align: center;
            padding: 16px;
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .calendar-no-data .icon {
            font-size: 2rem;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        .calendar-legend {
            display: flex;
            gap: 12px;
            padding: 12px 20px;
            background: white;
            border-top: 1px solid #f3f4f6;
            flex-wrap: wrap;
        }

        .calendar-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: #6b7280;
        }

        .calendar-legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .calendar-legend-dot.today {
            background: var(--primary);
        }

        .calendar-legend-dot.has-tx {
            background: #10b981;
        }

        .calendar-legend-dot.many-tx {
            background: #f59e0b;
        }

        /* ============================================
           FLOATING CART BUTTON
           ============================================ */
        .floating-cart-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1030;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: visible;
        }

        .floating-cart-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 32px rgba(79, 70, 229, 0.5);
        }

        .floating-cart-btn:active {
            transform: scale(0.95);
        }

        .floating-cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 800;
            min-width: 22px;
            height: 22px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .floating-cart-btn.empty {
            opacity: 0.7;
        }

        .floating-cart-btn.has-items {
            animation: cartPulse 0.5s ease;
        }

        @keyframes cartPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* ============================================
           OFFCANVAS CART
           ============================================ */
        .offcanvas.offcanvas-end {
            width: 420px;
            max-width: 90vw;
        }

        @media (max-width: 575.98px) {
            .offcanvas.offcanvas-end {
                width: 100vw;
                max-width: 100vw;
            }
        }

        .cart-offcanvas-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 20px 24px;
            border-bottom: none;
        }

        .cart-offcanvas-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn-close-offcanvas {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-close-offcanvas:hover {
            background: rgba(255,255,255,0.3);
        }

        .cart-offcanvas-body {
            padding: 0;
            display: flex;
            flex-direction: column;
            height: calc(100% - 76px);
        }

        .cart-items-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
        }

        .cart-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid #f3f4f6;
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-price {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }

        .cart-qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.15s;
        }

        .cart-qty-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .cart-qty-value {
            font-weight: 700;
            min-width: 24px;
            text-align: center;
        }

        .cart-item-subtotal {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
            text-align: right;
        }

        .cart-item-remove {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            font-size: 1rem;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.15s;
        }

        .cart-item-remove:hover {
            background: #fee2e2;
        }

        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .cart-empty .icon {
            font-size: 4rem;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            background: white;
        }

        /* ============================================
           TOAST NOTIFIKASI SUKSES - UPGRADED
           ============================================ */
        .success-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            min-width: 380px;
            max-width: 480px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0,0,0,0.05);
            z-index: 9999;
            overflow: hidden;
            animation: toastSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-origin: top right;
        }

        .success-toast.hiding {
            animation: toastSlideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes toastSlideIn {
            0% {
                opacity: 0;
                transform: translateX(120%) scale(0.8);
            }
            60% {
                transform: translateX(-8px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes toastSlideOut {
            0% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateX(120%) scale(0.9);
            }
        }

        .toast-content {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px 20px;
            position: relative;
        }

        /* Icon check dengan animasi draw */
        .toast-icon-wrapper {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            animation: iconPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s backwards;
        }

        @keyframes iconPop {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }
            100% {
                transform: scale(1) rotate(0);
                opacity: 1;
            }
        }

        .toast-icon-wrapper::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            animation: iconRipple 1.5s ease-out infinite;
        }

        @keyframes iconRipple {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            100% {
                transform: scale(1.8);
                opacity: 0;
            }
        }

        .toast-icon-wrapper svg {
            width: 22px;
            height: 22px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .toast-icon-wrapper svg path {
            stroke-dasharray: 30;
            stroke-dashoffset: 30;
            animation: checkDraw 0.5s ease 0.6s forwards;
        }

        @keyframes checkDraw {
            to { stroke-dashoffset: 0; }
        }

        .toast-body {
            flex: 1;
            min-width: 0;
        }

        .toast-title {
            font-weight: 800;
            font-size: 1rem;
            color: #065f46;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: titleFadeIn 0.4s ease 0.4s backwards;
        }

        @keyframes titleFadeIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-message {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
            animation: messageFadeIn 0.4s ease 0.5s backwards;
        }

        @keyframes messageFadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-invoice-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            color: #15803d;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            font-family: monospace;
            margin-top: 6px;
            border: 1px solid #bbf7d0;
        }

        .toast-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #f3f4f6;
            border: none;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 1rem;
            line-height: 1;
        }

        .toast-close:hover {
            background: #e5e7eb;
            color: #1f2937;
            transform: rotate(90deg);
        }

        .toast-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .toast-action-btn {
            flex: 1;
            padding: 8px 12px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .toast-action-btn.primary {
            background: #10b981;
            color: white;
        }

        .toast-action-btn.primary:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .toast-action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .toast-action-btn.secondary:hover {
            background: #e5e7eb;
        }

        /* Progress bar countdown */
        .toast-progress {
            height: 4px;
            background: #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .toast-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            width: 100%;
            animation: progressCountdown 5s linear forwards;
            transform-origin: left;
        }

        @keyframes progressCountdown {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        .success-toast:hover .toast-progress-bar {
            animation-play-state: paused;
        }

        /* Confetti particles */
        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9998;
            overflow: hidden;
        }

        .confetti {
            position: absolute;
            width: 8px;
            height: 8px;
            opacity: 0;
            animation: confettiFall 3s ease-out forwards;
        }

        @keyframes confettiFall {
            0% {
                opacity: 1;
                transform: translateY(-100vh) rotate(0deg);
            }
            100% {
                opacity: 0;
                transform: translateY(100vh) rotate(720deg);
            }
        }

        /* Responsive Calendar */
        @media (max-width: 991.98px) {
            .calendar-card {
                position: static;
                margin-top: 20px;
            }
            
            .floating-cart-btn {
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                font-size: 1.3rem;
            }
        }

        /* Responsive Toast */
        @media (max-width: 575.98px) {
            .success-toast {
                top: 12px;
                right: 12px;
                left: 12px;
                min-width: auto;
                max-width: none;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand">🏪 Mini PoS</div>
        
        <!-- USER INFO MODERN DENGAN DROPDOWN -->
        <div class="user-info-wrapper" id="userWrapper">
            <div class="user-info" onclick="toggleUserDropdown(event)">
                <div class="user-avatar <?= $role ?>"><?= $initials ?></div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <span class="user-role-badge <?= $role ?>"><?= $roleIcon ?> <?= ucfirst($role) ?></span>
                </div>
                <span class="user-dropdown-icon">▼</span>
            </div>
            
            <div class="user-dropdown">
                <div class="dropdown-header">
                    <div class="label">Login sebagai</div>
                    <div class="value">@<?= htmlspecialchars($_SESSION['username']) ?></div>
                </div>
                
                <a href="profile.php">
                    <span class="dropdown-icon">👤</span> Edit Profil
                </a>
                
                <?php if ($role === 'admin'): ?>
                    <a href="settings.php">
                        <span class="dropdown-icon">⚙️</span> Pengaturan Toko
                    </a>
                    <a href="users.php?edit=<?= $_SESSION['user_id'] ?>">
                        <span class="dropdown-icon">🔑</span> Ganti Password
                    </a>
                    <div class="divider"></div>
                <?php endif; ?>
                
                <a href="logout.php" class="danger">
                    <span class="dropdown-icon">🚪</span> Logout
                </a>
            </div>
        </div>
        
        <nav>
            <?php if (hasRole('admin')): ?>
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="users.php" class="nav-link">👥 User</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link active">🛒 Kasir</a>
            <?php if (hasRole('admin')): ?>
                <a href="products.php" class="nav-link">📦 Produk</a>
            <?php endif; ?>
            <a href="kitchen.php" class="nav-link">🍳 Dapur</a>
            <?php if (hasRole('admin')): ?>
                <a href="settings.php" class="nav-link">⚙️ Pengaturan</a>
            <?php endif; ?>
            <a href="history.php" class="nav-link">📜 Riwayat</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link logout">🚪 Logout</a>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <!-- MOBILE HEADER -->
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <!-- ✅ TOAST NOTIFIKASI SUKSES (UPGRADED) -->
        <?php if ($successMsg): ?>
            <div class="success-toast" id="successToast" data-autohide="5000">
                <div class="toast-content">
                    <div class="toast-icon-wrapper">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="toast-body">
                        <div class="toast-title">
                            Transaksi Berhasil!
                        </div>
                        <p class="toast-message"><?= $successMsg ?></p>
                        
                        <?php if ($invoiceNumber): ?>
                            <div class="toast-invoice-badge">
                                🧾 <?= htmlspecialchars($invoiceNumber) ?>
                            </div>
                            <div class="toast-actions">
                                <a href="receipt.php?invoice=<?= urlencode($invoiceNumber) ?>"
                                    target="_blank"
                                    class="toast-action-btn primary">
                                    🖨️ Cetak Struk
                                </a>
                                <button type="button" class="toast-action-btn secondary" onclick="hideSuccessToast()">
                                    Tutup
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="toast-close" onclick="hideSuccessToast()" aria-label="Tutup">✕</button>
                </div>
                <div class="toast-progress">
                    <div class="toast-progress-bar" id="toastProgressBar"></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ALERT PROSES PESANAN KIOSK -->
        <?php if ($processOrder): ?>
            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>🖥️ <strong>Memproses Pesanan Kiosk</strong> — <?= htmlspecialchars($processOrder['customer_name']) ?> | Meja <?= htmlspecialchars($processOrder['table_number']) ?> | <code><?= $processOrder['invoice_number'] ?></code></span>
                <a href="index.php" class="btn btn-sm btn-outline-secondary fw-bold">✕ Batal</a>
            </div>
        <?php endif; ?>

        <!-- PANEL PESANAN KIOSK AKTIF -->
        <?php if (!empty($pendingOrders)): ?>
            <div class="card border-0 shadow-sm mb-4 pending-panel">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold m-0 text-danger">🔔 Pesanan Kiosk Aktif (<?= count($pendingOrders) ?>)</h5>
                        <span class="badge bg-danger px-3 py-2">Perlu Diproses</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Antrian</th>
                                    <th>Pelanggan</th>
                                    <th>Meja</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark fw-bold px-3 py-2">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['table_number']) ?></td>
                                        <td>
                                            <?php
                                            $badge = match ($order['status']) {
                                                'pending' => '<span class="badge bg-danger">PENDING</span>',
                                                'preparing' => '<span class="badge bg-warning text-dark">DIMASAK</span>',
                                                'ready' => '<span class="badge bg-success">SIAP</span>',
                                                default => '<span class="badge bg-secondary">-</span>'
                                            };
                                            echo $badge;
                                            ?>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $order['item_count'] ?> item</span></td>
                                        <td class="fw-bold text-primary"><?= formatRupiah($order['total_amount']) ?></td>
                                        <td class="text-center">
                                            <?php if ($order['status'] === 'ready'): ?>
                                                <a href="index.php?process_order=<?= $order['id'] ?>"
                                                    class="btn btn-sm btn-success fw-bold px-4 py-2">
                                                    💰 Bayar
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">
                                                    <?= $order['status'] === 'pending' ? '⏳ Menunggu' : '🔥 Dimasak' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3 g-lg-4">
            <!-- PRODUCT GRID -->
            <div class="col-12 col-lg-8 col-xl-8">
                <!-- SEARCH BAR -->
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="productSearch" class="form-control form-control-lg"
                        placeholder="Cari produk... (Ctrl+K)" autocomplete="off">
                    <span class="shortcut-hint d-none d-md-inline">Ctrl+K</span>
                    <button class="search-clear" id="searchClear" onclick="clearSearch()">✕</button>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0">Pilih Produk</h4>
                    <span class="badge bg-light text-dark border px-3 py-2 d-none d-sm-inline" id="productCount">
                        <?= count($products) ?> produk
                    </span>
                </div>

                <div class="no-results" id="noResults">
                    <div style="font-size:3rem;">🔍</div>
                    <h5 class="fw-bold mt-3">Produk tidak ditemukan</h5>
                    <p class="mb-0">Coba kata kunci lain atau cek ejaan</p>
                </div>

                <div class="row g-2 g-md-3" id="productGrid">
                    <?php foreach ($allProducts as $p): ?>
                        <div class="col-6 col-md-4 col-xl-3 product-col"
                            data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                            <div class="product-card <?= $p['stock'] <= 0 ? 'out-of-stock' : '' ?>"
                                onclick="<?= $p['stock'] > 0 ? "addToCart({$p['id']}, '" . addslashes($p['name']) . "', {$p['price']})" : '' ?>">
                                <div class="icon">☕</div>
                                <div class="name text-truncate"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="stock">Stok: <?= $p['stock'] ?></div>
                                <div class="price"><?= formatRupiah($p['price']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ✅ CALENDAR PANEL -->
            <div class="col-12 col-lg-4 col-xl-4">
                <div class="calendar-card">
                    <!-- Calendar Header dengan Gradient -->
                    <div class="calendar-header">
                        <div class="calendar-header-top">
                            <button class="calendar-nav-btn" onclick="changeMonth(-1)" title="Bulan Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="calendar-month-year" id="calendarMonthYear">-</div>
                            <button class="calendar-nav-btn" onclick="changeMonth(1)" title="Bulan Berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <div class="calendar-today-box">
                            <div class="today-day" id="todayDay"><?= date('d') ?></div>
                            <div class="today-info" id="todayInfo"><?= date('l, F Y') ?></div>
                        </div>
                    </div>

                    <!-- Calendar Body -->
                    <div class="calendar-body">
                        <div class="calendar-weekdays">
                            <div class="calendar-weekday weekend">Min</div>
                            <div class="calendar-weekday">Sen</div>
                            <div class="calendar-weekday">Sel</div>
                            <div class="calendar-weekday">Rab</div>
                            <div class="calendar-weekday">Kam</div>
                            <div class="calendar-weekday">Jum</div>
                            <div class="calendar-weekday weekend">Sab</div>
                        </div>
                        <div class="calendar-days" id="calendarDays">
                            <!-- Days akan di-render oleh JS -->
                        </div>
                    </div>

                    <!-- Calendar Stats (untuk tanggal yang dipilih) -->
                    <div class="calendar-stats" id="calendarStats">
                        <div class="calendar-stats-header">
                            <div class="calendar-stats-title">Statistik</div>
                            <div class="calendar-stats-date" id="statsDate">Hari Ini</div>
                        </div>
                        <div id="statsContent">
                            <div class="calendar-stat-row">
                                <div class="calendar-stat-label">
                                    <div class="calendar-stat-icon blue">📦</div>
                                    <span>Total Transaksi</span>
                                </div>
                                <div class="calendar-stat-value" id="statTxCount"><?= $todayStats['count'] ?></div>
                            </div>
                            <div class="calendar-stat-row">
                                <div class="calendar-stat-label">
                                    <div class="calendar-stat-icon green">💰</div>
                                    <span>Pendapatan</span>
                                </div>
                                <div class="calendar-stat-value" id="statRevenue"><?= formatRupiah($todayStats['revenue']) ?></div>
                            </div>
                            <div class="calendar-stat-row">
                                <div class="calendar-stat-label">
                                    <div class="calendar-stat-icon orange">✅</div>
                                    <span>Selesai</span>
                                </div>
                                <div class="calendar-stat-value" id="statCompleted"><?= $todayStats['completed'] ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="calendar-legend">
                        <div class="calendar-legend-item">
                            <div class="calendar-legend-dot today"></div>
                            <span>Hari Ini</span>
                        </div>
                        <div class="calendar-legend-item">
                            <div class="calendar-legend-dot has-tx"></div>
                            <span>Ada Transaksi</span>
                        </div>
                        <div class="calendar-legend-item">
                            <div class="calendar-legend-dot many-tx"></div>
                            <span>Ramai (>5)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ OFFCANVAS CART (Slide dari kanan) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="cart-offcanvas-header">
            <h5 class="cart-offcanvas-title">
                🛒 Keranjang
                <span class="badge bg-white text-primary" id="offcanvas-cart-count">0 item</span>
            </h5>
            <button type="button" class="btn-close-offcanvas" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="cart-offcanvas-body">
            <div class="cart-items-list" id="cart-items-list">
                <div class="cart-empty">
                    <div class="icon">🛒</div>
                    <div class="fw-semibold mb-1">Keranjang Kosong</div>
                    <div class="small">Klik produk untuk menambahkan</div>
                </div>
            </div>
            <div class="cart-footer" id="cart-footer-offcanvas" style="display:none;">
                <div id="cart-summary-offcanvas"></div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-semibold">Total</span>
                    <h3 class="fw-bold text-dark m-0" id="grand-total-offcanvas">Rp 0</h3>
                </div>

                <form id="checkout-form" action="process_sale.php" method="POST">
                    <input type="hidden" name="cart_data" id="cart-data">
                    <?php if ($processOrder): ?>
                        <input type="hidden" name="kiosk_order_id" value="<?= $processOrder['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Uang Diterima</label>
                        <input type="number" name="pay_amount" class="form-control form-control-lg fw-bold" required min="0" step="any" placeholder="0">
                    </div>
                    <button type="submit" class="btn btn-pay w-100" id="btn-checkout" disabled>
                        💰 BAYAR SEKARANG
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ✅ FLOATING CART BUTTON -->
    <button class="floating-cart-btn empty" id="floatingCartBtn" 
            data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" 
            title="Lihat Keranjang">
        🛒
        <span class="floating-cart-badge" id="floatingCartBadge" style="display:none;">0</span>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==================== SIDEBAR TOGGLE ====================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        // ==================== USER DROPDOWN ====================
        function toggleUserDropdown(event) {
            event.stopPropagation();
            const wrapper = document.getElementById('userWrapper');
            if (wrapper) wrapper.classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('userWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const wrapper = document.getElementById('userWrapper');
                if (wrapper) wrapper.classList.remove('open');
            }
        });

        // ==================== SEARCH ====================
        const searchInput = document.getElementById('productSearch');
        const searchClear = document.getElementById('searchClear');
        const productCols = document.querySelectorAll('.product-col');
        const noResults = document.getElementById('noResults');
        const productCount = document.getElementById('productCount');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;
            searchClear.style.display = query.length > 0 ? 'block' : 'none';
            productCols.forEach(col => {
                const name = col.getAttribute('data-name');
                const match = name.includes(query);
                col.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            noResults.classList.toggle('show', visibleCount === 0 && query.length > 0);
            productCount.textContent = visibleCount + ' produk';
        });

        function clearSearch() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                clearSearch();
                searchInput.blur();
            }
        });

        // ==================== CALENDAR LOGIC ====================
        const CALENDAR_DATA = <?= json_encode($calendarData) ?>;
        const MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                           'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const DAYS_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        
        let currentCalendarDate = new Date();
        let selectedDate = new Date();
        
        function formatCurrency(amount) {
            return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
        }

        function renderCalendar() {
            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth();
            
            document.getElementById('calendarMonthYear').textContent = 
                MONTHS_ID[month] + ' ' + year;
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            const container = document.getElementById('calendarDays');
            container.innerHTML = '';
            
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + 
                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                String(today.getDate()).padStart(2, '0');
            
            // Days from previous month
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const div = document.createElement('div');
                div.className = 'calendar-day other-month';
                div.textContent = day;
                container.appendChild(div);
            }
            
            // Days of current month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = year + '-' + 
                    String(month + 1).padStart(2, '0') + '-' + 
                    String(day).padStart(2, '0');
                const div = document.createElement('div');
                div.className = 'calendar-day';
                div.textContent = day;
                
                const dayData = CALENDAR_DATA[dateStr];
                
                if (dayData && dayData.count > 0) {
                    div.classList.add('has-transactions');
                    if (dayData.count > 5) {
                        div.classList.add('has-many-transactions');
                    }
                }
                
                if (dateStr === todayStr) {
                    div.classList.add('today');
                }
                
                const selectedStr = selectedDate.getFullYear() + '-' + 
                    String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(selectedDate.getDate()).padStart(2, '0');
                if (dateStr === selectedStr && dateStr !== todayStr) {
                    div.classList.add('selected');
                }
                
                div.onclick = () => selectDate(new Date(year, month, day));
                container.appendChild(div);
            }
            
            // Fill remaining cells
            const totalCells = container.children.length;
            const remaining = (7 - (totalCells % 7)) % 7;
            for (let i = 1; i <= remaining; i++) {
                const div = document.createElement('div');
                div.className = 'calendar-day other-month';
                div.textContent = i;
                container.appendChild(div);
            }
        }

        function selectDate(date) {
            selectedDate = date;
            renderCalendar();
            
            const dateStr = date.getFullYear() + '-' + 
                String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                String(date.getDate()).padStart(2, '0');
            
            const today = new Date();
            const isToday = dateStr === (today.getFullYear() + '-' + 
                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                String(today.getDate()).padStart(2, '0'));
            
            document.getElementById('statsDate').textContent = isToday ? 'Hari Ini' : 
                DAYS_ID[date.getDay()] + ', ' + date.getDate() + ' ' + MONTHS_ID[date.getMonth()];
            
            const data = CALENDAR_DATA[dateStr];
            
            if (data && data.count > 0) {
                document.getElementById('statsContent').innerHTML = `
                    <div class="calendar-stat-row">
                        <div class="calendar-stat-label">
                            <div class="calendar-stat-icon blue">📦</div>
                            <span>Total Transaksi</span>
                        </div>
                        <div class="calendar-stat-value">${data.count}</div>
                    </div>
                    <div class="calendar-stat-row">
                        <div class="calendar-stat-label">
                            <div class="calendar-stat-icon green">💰</div>
                            <span>Pendapatan</span>
                        </div>
                        <div class="calendar-stat-value">${formatCurrency(data.revenue)}</div>
                    </div>
                    <div class="calendar-stat-row">
                        <div class="calendar-stat-label">
                            <div class="calendar-stat-icon orange">✅</div>
                            <span>Selesai</span>
                        </div>
                        <div class="calendar-stat-value">${data.completed}</div>
                    </div>
                `;
            } else {
                document.getElementById('statsContent').innerHTML = `
                    <div class="calendar-no-data">
                        <div class="icon">📭</div>
                        <div>Tidak ada transaksi</div>
                    </div>
                `;
            }
        }

        function changeMonth(delta) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
            renderCalendar();
        }

        // Initial render
        renderCalendar();
        selectDate(new Date());

        // ==================== CART LOGIC ====================
        let cart = [];
        
        const TAX_ENABLED = <?= $taxEnabled ? 'true' : 'false' ?>;
        const TAX_RATE = <?= $taxRate ?>;
        const TAX_LABEL = '<?= addslashes($taxLabel) ?>';

        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) existing.qty++;
            else cart.push({ id, name, price, qty: 1 });
            
            renderCart();
            
            // Auto-open offcanvas saat pertama kali tambah
            if (cart.length === 1) {
                const offcanvas = new bootstrap.Offcanvas(document.getElementById('cartOffcanvas'));
                offcanvas.show();
            }
        }

        function updateQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            item.qty += delta;
            if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            renderCart();
        }

        function renderCart() {
            const listContainer = document.getElementById('cart-items-list');
            const summaryContainer = document.getElementById('cart-summary-offcanvas');
            const footer = document.getElementById('cart-footer-offcanvas');
            const floatingBtn = document.getElementById('floatingCartBtn');
            const floatingBadge = document.getElementById('floatingCartBadge');
            
            summaryContainer.innerHTML = '';
            let subtotal = 0;
            const totalItems = cart.reduce((a, b) => a + b.qty, 0);

            // Update floating button
            if (totalItems > 0) {
                floatingBtn.classList.remove('empty');
                floatingBtn.classList.add('has-items');
                floatingBadge.textContent = totalItems;
                floatingBadge.style.display = 'flex';
                setTimeout(() => floatingBtn.classList.remove('has-items'), 500);
            } else {
                floatingBtn.classList.add('empty');
                floatingBtn.classList.remove('has-items');
                floatingBadge.style.display = 'none';
            }

            if (cart.length === 0) {
                listContainer.innerHTML = `
                    <div class="cart-empty">
                        <div class="icon">🛒</div>
                        <div class="fw-semibold mb-1">Keranjang Kosong</div>
                        <div class="small">Klik produk untuk menambahkan</div>
                    </div>
                `;
                document.getElementById('grand-total-offcanvas').innerText = 'Rp 0';
                document.getElementById('offcanvas-cart-count').innerText = '0 item';
                document.getElementById('cart-data').value = '[]';
                document.getElementById('btn-checkout').disabled = true;
                footer.style.display = 'none';
                return;
            }

            footer.style.display = 'block';
            let html = '';
            cart.forEach(item => {
                const sub = item.price * item.qty;
                subtotal += sub;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">${formatCurrency(item.price)} × ${item.qty}</div>
                            <div class="cart-item-controls">
                                <div class="cart-qty-btn" onclick="updateQty(${item.id}, -1)">−</div>
                                <div class="cart-qty-value">${item.qty}</div>
                                <div class="cart-qty-btn" onclick="updateQty(${item.id}, 1)">+</div>
                            </div>
                        </div>
                        <div>
                            <div class="cart-item-subtotal">${formatCurrency(sub)}</div>
                            <button class="cart-item-remove" onclick="removeFromCart(${item.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            listContainer.innerHTML = html;

            // Hitung pajak
            let taxAmount = 0;
            if (TAX_ENABLED && TAX_RATE > 0) {
                taxAmount = Math.round(subtotal * TAX_RATE / 100);
                summaryContainer.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Subtotal</span>
                        <span class="small fw-semibold">${formatCurrency(subtotal)}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">${TAX_LABEL}</span>
                        <span class="small fw-semibold">${formatCurrency(taxAmount)}</span>
                    </div>
                `;
            }

            const grandTotal = subtotal + taxAmount;
            document.getElementById('grand-total-offcanvas').innerText = formatCurrency(grandTotal);
            document.getElementById('offcanvas-cart-count').innerText = totalItems + ' item';
            document.getElementById('cart-data').value = JSON.stringify(cart);
            document.getElementById('btn-checkout').disabled = false;
        }

        // ==================== AUTO-LOAD CART DARI PESANAN KIOSK ====================
        <?php if ($processOrder && !empty($processItems)): ?>
            (function() {
                <?php foreach ($processItems as $item): ?>
                    cart.push({
                        id: <?= $item['product_id'] ?>,
                        name: '<?= addslashes($item['name']) ?>',
                        price: <?= $item['price'] ?>,
                        qty: <?= $item['quantity'] ?>
                    });
                <?php endforeach; ?>
                renderCart();
                
                // Auto-open offcanvas
                setTimeout(() => {
                    const offcanvas = new bootstrap.Offcanvas(document.getElementById('cartOffcanvas'));
                    offcanvas.show();
                    setTimeout(() => {
                        document.querySelector('[name="pay_amount"]').focus();
                    }, 400);
                }, 300);
            })();
        <?php else: ?>
            renderCart();
        <?php endif; ?>

        // ==================== AUTO-POLLING PESANAN KIOSK BARU ====================
        let knownPendingIds = <?= json_encode(array_column($pendingOrders, 'id')) ?>;

        setInterval(function() {
            fetch('index.php?ajax_pending=1')
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPanel = doc.querySelector('.pending-panel');
                    const existingPanel = document.querySelector('.pending-panel');

                    const newIds = [];
                    if (newPanel) {
                        newPanel.querySelectorAll('a[href*="process_order"]').forEach(link => {
                            const id = parseInt(link.getAttribute('href').split('=')[1]);
                            newIds.push(id);
                        });
                    }

                    const hasNew = newIds.some(id => !knownPendingIds.includes(id));
                    if (hasNew) {
                        playCashierAlert();
                        showCashierToast();
                    }

                    if (newPanel && !existingPanel) {
                        const mobileHeader = document.querySelector('.mobile-header');
                        if (mobileHeader) mobileHeader.insertAdjacentElement('afterend', newPanel);
                    } else if (newPanel && existingPanel) {
                        existingPanel.outerHTML = newPanel.outerHTML;
                    } else if (!newPanel && existingPanel) {
                        existingPanel.remove();
                    }

                    knownPendingIds = newIds;
                })
                .catch(() => {});
        }, 5000);

        function playCashierAlert() {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 600;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.8);
            } catch (e) {}
        }

        function showCashierToast() {
            let toast = document.getElementById('cashierToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'cashierToast';
                toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#e63946;color:white;padding:16px 24px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 30px rgba(230,57,70,0.4);animation:slideIn 0.3s ease;';
                document.body.appendChild(toast);
            }
            toast.textContent = '🔔 Pesanan kiosk baru masuk!';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4000);
        }

        // ==================== SUCCESS TOAST AUTO-HIDE ====================
        (function() {
            const toast = document.getElementById('successToast');
            if (!toast) return;
            
            const autoHideDelay = parseInt(toast.dataset.autohide) || 5000;
            let hideTimeout;
            let isHidden = false;
            
            // Play success sound
            function playSuccessSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // "Ka-ching" sound: 2 quick tones
                    const playTone = (freq, startTime, duration) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.frequency.value = freq;
                        osc.type = 'sine';
                        gain.gain.setValueAtTime(0.2, ctx.currentTime + startTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + startTime + duration);
                        osc.start(ctx.currentTime + startTime);
                        osc.stop(ctx.currentTime + startTime + duration);
                    };
                    
                    playTone(1200, 0, 0.1);      // First tone
                    playTone(1600, 0.12, 0.15);   // Second tone (higher)
                    playTone(2000, 0.28, 0.2);    // Third tone (highest)
                } catch (e) {}
            }
            
            // Mini confetti effect
            function spawnConfetti() {
                const container = document.createElement('div');
                container.className = 'confetti-container';
                document.body.appendChild(container);
                
                const colors = ['#10b981', '#f59e0b', '#3b82f6', '#ec4899', '#8b5cf6'];
                const shapes = ['square', 'circle'];
                
                for (let i = 0; i < 30; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    const color = colors[Math.floor(Math.random() * colors.length)];
                    const shape = shapes[Math.floor(Math.random() * shapes.length)];
                    const left = Math.random() * 100;
                    const delay = Math.random() * 0.5;
                    const size = 6 + Math.random() * 6;
                    
                    confetti.style.cssText = `
                        left: ${left}%;
                        width: ${size}px;
                        height: ${size}px;
                        background: ${color};
                        border-radius: ${shape === 'circle' ? '50%' : '2px'};
                        animation-delay: ${delay}s;
                        animation-duration: ${2 + Math.random() * 1.5}s;
                    `;
                    container.appendChild(confetti);
                }
                
                setTimeout(() => container.remove(), 4000);
            }
            
            function hideToast() {
                if (isHidden) return;
                isHidden = true;
                toast.classList.add('hiding');
                setTimeout(() => {
                    toast.remove();
                }, 400);
            }
            
            // Expose globally for close button
            window.hideSuccessToast = hideToast;
            
            // Pause on hover
            toast.addEventListener('mouseenter', () => {
                clearTimeout(hideTimeout);
            });
            
            toast.addEventListener('mouseleave', () => {
                // Resume countdown
                hideTimeout = setTimeout(hideToast, 2000);
            });
            
            // Trigger effects
            playSuccessSound();
            setTimeout(spawnConfetti, 300);
            
            // Auto-hide timer
            hideTimeout = setTimeout(hideToast, autoHideDelay);
        })();
    </script>
</body>

</html>