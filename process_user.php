<?php
require_once 'config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$action = $_POST['action'] ?? '';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

try {
    // ==================== CREATE USER ====================
    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'kasir';

        if (empty($fullName) || empty($username) || empty($password)) throw new Exception('Semua field wajib diisi!');
        if (strlen($password) < 6) throw new Exception('Password minimal 6 karakter!');
        if (!in_array($role, ['admin', 'kasir'])) throw new Exception('Role tidak valid!');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) throw new Exception('Username hanya boleh huruf, angka, dan underscore!');

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) throw new Exception('Username sudah digunakan!');

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$fullName, $username, $passwordHash, $role]);

        header('Location: users.php?msg=added');
        exit;
    }

    // ==================== UPDATE USER ====================
    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'kasir';
        $isActive = intval($_POST['is_active'] ?? 1);

        if (!$id || empty($fullName) || empty($username)) throw new Exception('Data tidak valid!');
        if (!in_array($role, ['admin', 'kasir'])) throw new Exception('Role tidak valid!');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) throw new Exception('Username hanya boleh huruf, angka, dan underscore!');

        $stmt = $pdo->prepare("SELECT role, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $currentUser = $stmt->fetch();
        if (!$currentUser) throw new Exception('User tidak ditemukan!');

        // Cek username unik (kecuali milik sendiri)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) throw new Exception('Username sudah digunakan oleh user lain!');

        // Proteksi admin terakhir
        if ($currentUser['role'] === 'admin' && $role === 'kasir') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) throw new Exception('Tidak bisa mengubah admin terakhir menjadi kasir!');
        }

        if ($currentUser['role'] === 'admin' && $isActive === 0 && $currentUser['is_active'] === 1) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) throw new Exception('Tidak bisa menonaktifkan admin terakhir!');
        }

        // ✅ UPDATE TERMASUK USERNAME
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$fullName, $username, $role, $isActive, $id]);

        // Jika username sendiri diubah, update session
        if ($id == $_SESSION['user_id']) {
            $_SESSION['full_name'] = $fullName;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
        }

        header('Location: users.php?msg=updated');
        exit;
    }

    // ==================== DELETE USER ====================
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID tidak valid!');
        if ($id == $_SESSION['user_id']) {
            header('Location: users.php?msg=cannot_delete_self');
            exit;
        }

        $stmt = $pdo->prepare("SELECT role, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) throw new Exception('User tidak ditemukan!');

        if ($user['role'] === 'admin' && $user['is_active'] === 1) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) {
                header('Location: users.php?msg=cannot_delete_last_admin');
                exit;
            }
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: users.php?msg=deleted');
        exit;
    }

    // ==================== CHANGE PASSWORD ====================
    if ($action === 'change_password') {
        $id = intval($_POST['id'] ?? 0);
        $passwordOption = $_POST['password_option'] ?? 'default';
        if (!$id) throw new Exception('ID tidak valid!');

        // ✅ ADMIN BISA GANTI PASSWORD DIRI SENDIRI
        // Hanya blokir jika bukan admin yang mencoba ganti password sendiri via menu ini
        // Tapi karena halaman ini sudah requireAdmin(), maka admin BOLEH ganti password sendiri

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch();
        if (!$userData) throw new Exception('User tidak ditemukan!');

        if ($passwordOption === 'custom') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if (empty($newPassword)) throw new Exception('Password baru wajib diisi!');
            if (strlen($newPassword) < 6) throw new Exception('Password minimal 6 karakter!');
            if ($newPassword !== $confirmPassword) throw new Exception('Password dan konfirmasi tidak sama!');
            if (strtolower($newPassword) === strtolower($userData['username'])) throw new Exception('Password tidak boleh sama dengan username!');
        } else {
            $newPassword = 'password123';
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $id]);

        // Jika ganti password sendiri, logout agar login ulang dengan password baru
        if ($id == $_SESSION['user_id']) {
            session_unset();
            session_destroy();
            header('Location: login.php?msg=password_changed');
            exit;
        }

        header('Location: users.php?msg=password_changed');
        exit;
    }

    // ==================== TOGGLE STATUS (AJAX) ====================
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $isActive = intval($_POST['is_active'] ?? 0);
        if (!$id) throw new Exception('ID tidak valid!');
        if ($id == $_SESSION['user_id']) throw new Exception('Tidak bisa menonaktifkan akun sendiri!');

        $stmt = $pdo->prepare("SELECT role, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) throw new Exception('User tidak ditemukan!');

        if ($isActive === 0 && $user['role'] === 'admin' && $user['is_active'] === 1) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) throw new Exception('Tidak bisa menonaktifkan admin terakhir!');
        }

        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$isActive, $id]);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: users.php?msg=toggled');
        exit;
    }

    throw new Exception('Aksi tidak dikenali!');
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    die("<script>alert('❌ " . addslashes($e->getMessage()) . "');window.history.back();</script>");
}
