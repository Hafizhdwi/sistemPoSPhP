<?php
// ============================================
// config/database.php
// Koneksi Database + Helper + Auth + Logging
// ============================================

$host = 'localhost';
$db   = 'db_mini_pos';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("<div class='alert alert-danger m-3'>❌ Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// ==================== HELPER: FORMAT RUPIAH ====================
function formatRupiah($number): string
{
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

// ==================== HELPER: ACTIVITY LOG ====================
if (!function_exists('logActivity')) {
    /**
     * Mencatat aktivitas ke tabel activity_logs
     * Silent fail - tidak menghentikan sistem jika logging gagal
     * 
     * @param PDO $pdo Koneksi database
     * @param string $action Nama aksi (login_success, user_created, dll)
     * @param string|null $entityType Tipe entity (user, product, transaction)
     * @param int|null $entityId ID entity terkait
     * @param string $description Deskripsi aktivitas
     */
    function logActivity($pdo, $action, $entityType = null, $entityId = null, $description = '')
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs 
                (action, entity_type, entity_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $action,
                $entityType,
                $entityId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255)
            ]);
        } catch (Exception $e) {
            // Jangan hentikan sistem kalau logging gagal
            error_log('Activity log failed: ' . $e->getMessage());
        }
    }
}

// ==================== HELPER: PASSWORD HISTORY LOG ====================
if (!function_exists('logPasswordChange')) {
    /**
     * Mencatat perubahan password ke tabel password_history
     * Silent fail - tidak menghentikan sistem jika logging gagal
     */
    function logPasswordChange($pdo, $userId, $oldHash, $changedByUserId, $changedByName, $method, $notes = null)
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO password_history (user_id, password_hash, changed_by_user_id, changed_by_name, change_method, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $oldHash, $changedByUserId, $changedByName, $method, $notes]);
        } catch (Exception $e) {
            error_log('Password history log failed: ' . $e->getMessage());
        }
    }
}

// ==================== LOAD SISTEM AUTENTIKASI ====================
require_once __DIR__ . '/auth.php';
