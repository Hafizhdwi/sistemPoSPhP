<?php
require_once 'config/database.php';
requireAdmin();

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $perPage));

if ($page > $totalPages && $totalPages > 0) {
    header("Location: users.php?page=$totalPages");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY role ASC, full_name ASC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

$logPerPage = 5;
$logPage = max(1, intval($_GET['log_page'] ?? 1));
$logOffset = ($logPage - 1) * $logPerPage;

$totalLogs = 0;
try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM activity_logs al WHERE (al.entity_type IS NULL OR al.entity_type NOT IN ('transaction','transaction_detail','stock','stock_history')) AND al.action NOT LIKE 'transaction_%' AND al.action NOT LIKE 'stock_%'");
    $totalLogs = (int)$countStmt->fetchColumn();
} catch (Exception $e) {}

$logTotalPages = max(1, ceil($totalLogs / $logPerPage));

$activityLogs = [];
try {
    $stmtLogs = $pdo->prepare("SELECT al.action, al.entity_type, al.entity_id, al.description, al.ip_address, al.user_agent, al.created_at, u.full_name FROM activity_logs al LEFT JOIN users u ON al.entity_id = u.id AND al.entity_type = 'user' WHERE (al.entity_type IS NULL OR al.entity_type NOT IN ('transaction','transaction_detail','stock','stock_history')) AND al.action NOT LIKE 'transaction_%' AND al.action NOT LIKE 'stock_%' ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmtLogs->execute([$logPerPage, $logOffset]);
    $activityLogs = $stmtLogs->fetchAll();
} catch (Exception $e) {}

$passwordHistory = [];
if ($editUser) {
    try {
        $stmtHist = $pdo->prepare("SELECT ph.*, u.full_name as changer_name FROM password_history ph LEFT JOIN users u ON ph.changed_by_user_id = u.id WHERE ph.user_id = ? ORDER BY ph.created_at DESC LIMIT 5");
        $stmtHist->execute([$editUser['id']]);
        $passwordHistory = $stmtHist->fetchAll();
    } catch (Exception $e) {}
}

$msg = $_GET['msg'] ?? '';
$initials = strtoupper(substr($_SESSION['full_name'], 0, 2));
$role = $_SESSION['role'];
$roleIcon = $role === 'admin' ? '🛡️' : '🛒';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/css/users.css">
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="mobile-header">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">☰</button>
            <span class="brand-mobile">🏪 Mini PoS</span>
            <span style="width:30px;"></span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0">👥 Manajemen User</h4>
                <small class="text-muted">Kelola akun admin dan kasir</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2"><?= $totalUsers ?> user terdaftar</span>
        </div>

        <?php
        $alerts = [
            'added' => ['success', '✅ User berhasil ditambahkan!'],
            'updated' => ['success', '✅ User berhasil diperbarui!'],
            'deleted' => ['warning', '🗑️ User berhasil dihapus!'],
            'toggled' => ['info', '🔄 Status user berhasil diubah!'],
            'password_reset' => ['success', '🔑 Password berhasil direset ke default!'],
            'password_changed' => ['success', '🔑 Password berhasil diubah!'],
            'cannot_delete_self' => ['danger', '❌ Tidak bisa menghapus akun sendiri!'],
            'cannot_delete_last_admin' => ['danger', '❌ Tidak bisa menghapus admin terakhir!'],
        ];
        if (isset($alerts[$msg])): ?>
            <div class="alert alert-<?= $alerts[$msg][0] ?> shadow-sm fade show"><?= $alerts[$msg][1] ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- KOLOM KIRI -->
            <div class="col-12 col-lg-4">
                <?php include 'components/users/form-user.php'; ?>

                <?php if ($editUser): ?>
                    <?php include 'components/users/quick-actions.php'; ?>
                    <?php include 'components/users/password-management.php'; ?>
                <?php endif; ?>
            </div>

            <!-- KOLOM KANAN -->
            <div class="col-12 col-lg-8">
                <?php include 'components/users/user-table.php'; ?>
                <?php include 'components/users/info-card.php'; ?>
                <?php include 'components/users/log-activity.php'; ?>
            </div>
        </div>
    </div>

    <?php include 'components/users/modal-change-password.php'; ?>
    <?php include 'components/users/modal-delete-user.php'; ?>
    <?php include 'components/users/modal-confirm.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/users.js"></script>
</body>
</html>