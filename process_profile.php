<?php
require_once 'config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    // Ambil data user saat ini
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        throw new Exception('User tidak ditemukan!');
    }

    // ==================== UPDATE PROFIL ====================
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if (empty($fullName) || empty($username)) {
            throw new Exception('Nama dan username wajib diisi!');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Username hanya boleh huruf, angka, dan underscore!');
        }

        // Cek username sudah dipakai user lain
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Username sudah digunakan oleh user lain!');
        }

        // Cek apakah ada perubahan
        $hasChange = ($currentUser['full_name'] !== $fullName) || ($currentUser['username'] !== $username);

        if (!$hasChange) {
            header('Location: profile.php?msg=profile_updated');
            exit;
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
        $stmt->execute([$fullName, $username, $userId]);

        // Update session
        $_SESSION['full_name'] = $fullName;
        $_SESSION['username'] = $username;

        // Log aktivitas
        logActivity(
            $pdo,
            'profile_updated',
            'user',
            $userId,
            "User {$fullName} memperbarui profilnya"
        );

        header('Location: profile.php?msg=profile_updated');
        exit;
    }

    // ==================== GANTI PASSWORD ====================
    if ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Semua field password wajib diisi!');
        }

        // Verifikasi password lama
        if (!password_verify($oldPassword, $currentUser['password_hash'])) {
            throw new Exception('Password lama salah!');
        }

        // Validasi password baru
        if (strlen($newPassword) < 6) {
            throw new Exception('Password baru minimal 6 karakter!');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Password baru dan konfirmasi tidak sama!');
        }

        if (strtolower($newPassword) === strtolower($currentUser['username'])) {
            throw new Exception('Password tidak boleh sama dengan username!');
        }

        if ($newPassword === $oldPassword) {
            throw new Exception('Password baru tidak boleh sama dengan password lama!');
        }

        $oldHash = $currentUser['password_hash'];
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        // Catat ke password_history
        logPasswordChange(
            $pdo,
            $userId,
            $oldHash,
            $userId,
            $currentUser['full_name'],
            'custom',
            'User mengubah password sendiri via halaman profil'
        );

        // Log aktivitas
        logActivity(
            $pdo,
            'password_changed_self',
            'user',
            $userId,
            "User {$currentUser['full_name']} mengubah passwordnya sendiri"
        );

        // Logout agar login ulang dengan password baru
        session_unset();
        session_destroy();

        header('Location: login.php?msg=password_changed');
        exit;
    }

    throw new Exception('Aksi tidak dikenali!');

} catch (Exception $e) {
    die("<script>alert('❌ " . addslashes($e->getMessage()) . "');window.location.href='profile.php';</script>");
}
?>