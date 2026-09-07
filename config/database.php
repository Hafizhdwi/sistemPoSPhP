<?php
// ============================================
// config/database.php
// Koneksi Database + Helper + Auth + Logging + Settings + Auto Fix Password
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

// ==================== ✅ AUTO FIX: REHASH PASSWORD OTOMATIS ====================
/**
 * Fungsi ini otomatis dijalankan setiap kali aplikasi load.
 * 
 * Cara kerja:
 * 1. Cek apakah user default (admin, kasir1) ada di database
 * 2. Coba password_verify dengan password default (admin123, kasir123)
 * 3. Jika GAGAL → hash tidak kompatibel dengan PHP ini → auto rehash
 * 4. Jika SUKSES → hash OK, skip
 * 
 * Default credentials yang di-fix:
 * - admin / admin123
 * - kasir1 / kasir123
 */
if (!function_exists('autoFixUserPasswords')) {
    function autoFixUserPasswords($pdo) {
        $defaultCredentials = [
            'admin'  => 'admin123',
            'kasir1' => 'kasir123',
        ];

        try {
            foreach ($defaultCredentials as $username => $plainPassword) {
                $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if (!$user) continue;

                // Cek apakah hash saat ini bisa memverifikasi password default
                // Jika GAGAL → berarti hash tidak kompatibel, rehash otomatis
                if (!password_verify($plainPassword, $user['password_hash'])) {
                    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmtUpdate->execute([$newHash, $user['id']]);
                    
                    // Log untuk debugging (silent)
                    error_log("Auto-fix: Rehashed password for user '{$username}'");
                }
            }
        } catch (Exception $e) {
            // Silent fail - jangan ganggu aplikasi kalau auto-fix error
            // (misal tabel users belum dibuat, dll)
            error_log('Auto-fix password failed: ' . $e->getMessage());
        }
    }
}

// Jalankan auto-fix (hanya sekali per request, cepat karena query kecil)
autoFixUserPasswords($pdo);

// ==================== HELPER: ACTIVITY LOG ====================
if (!function_exists('logActivity')) {
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
            error_log('Activity log failed: ' . $e->getMessage());
        }
    }
}

// ==================== HELPER: PASSWORD HISTORY ====================
if (!function_exists('logPasswordChange')) {
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

// ==================== HELPER: STORE SETTINGS ====================
if (!function_exists('getSetting')) {
    /**
     * Ambil nilai setting dari database dengan caching
     */
    function getSetting($pdo, $key, $default = '') {
        static $cache = [];
        
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM store_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            $value = $row ? $row['setting_value'] : $default;
            $cache[$key] = $value;
            return $value;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('getAllSettings')) {
    /**
     * Ambil semua settings sebagai array
     */
    function getAllSettings($pdo) {
        $settings = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings");
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {}
        return $settings;
    }
}

if (!function_exists('updateSetting')) {
    /**
     * Update satu setting
     */
    function updateSetting($pdo, $key, $value) {
        try {
            $stmt = $pdo->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
            return true;
        } catch (Exception $e) {
            error_log('Update setting failed: ' . $e->getMessage());
            return false;
        }
    }
}

// ==================== LOAD AUTH ====================
require_once __DIR__ . '/auth.php';
?>