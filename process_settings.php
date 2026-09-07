<?php
require_once 'config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$activeTab = $_POST['active_tab'] ?? 'general';

try {
    $pdo->beginTransaction();

    // Daftar key yang boleh di-update per tab
    $allowedKeys = [
        'general' => ['store_name', 'store_address', 'store_phone', 'store_email'],
        'tax'     => ['tax_enabled', 'tax_rate', 'tax_label'],
        'receipt' => ['receipt_header', 'receipt_footer', 'receipt_show_logo', 'receipt_show_address'],
    ];

    $keys = $allowedKeys[$activeTab] ?? [];

    foreach ($keys as $key) {
        // Handle checkbox (jika tidak dicentang, value = 0)
        if (in_array($key, ['tax_enabled', 'receipt_show_logo', 'receipt_show_address'])) {
            $value = isset($_POST[$key]) ? '1' : '0';
        } else {
            $value = trim($_POST[$key] ?? '');
        }

        // Validasi
        if ($key === 'tax_rate') {
            $value = max(0, min(100, floatval($value)));
        }

        updateSetting($pdo, $key, $value);
    }

    // ✅ HANDLE LOGO UPLOAD
    if ($activeTab === 'general') {
        // Hapus logo
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            // Hapus file lama
            $oldLogo = getSetting($pdo, 'store_logo');
            if ($oldLogo && file_exists(__DIR__ . '/' . $oldLogo)) {
                @unlink(__DIR__ . '/' . $oldLogo);
            }
            updateSetting($pdo, 'store_logo', '');
        }

        // Upload logo baru
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['store_logo'];
            
            // Validasi ukuran (maks 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran logo maksimal 2MB!');
            }

            // Validasi tipe file
            $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Format file tidak didukung! Gunakan PNG, JPG, atau SVG.');
            }

            // Buat folder uploads jika belum ada
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate nama file unik
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            // Hapus logo lama
            $oldLogo = getSetting($pdo, 'store_logo');
            if ($oldLogo && file_exists(__DIR__ . '/' . $oldLogo)) {
                @unlink(__DIR__ . '/' . $oldLogo);
            }

            // Pindahkan file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                updateSetting($pdo, 'store_logo', 'uploads/' . $filename);
            } else {
                throw new Exception('Gagal mengupload logo!');
            }
        }
    }

    $pdo->commit();

    // Log aktivitas
    logActivity(
        $pdo,
        'settings_updated',
        'system',
        null,
        "Admin {$_SESSION['full_name']} memperbarui pengaturan toko (tab: {$activeTab})"
    );

    header('Location: settings.php?tab=' . $activeTab . '&msg=saved');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    die("<script>alert('❌ " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
?>