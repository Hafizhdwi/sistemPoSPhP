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
        if (strtolower($password) === strtolower($username)) throw new Exception('Password tidak boleh sama dengan username!');

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) throw new Exception('Username sudah digunakan!');

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$fullName, $username, $passwordHash, $role]);

        $newUserId = $pdo->lastInsertId();

        // ✅ Catat password awal ke history
        logPasswordChange(
            $pdo,
            $newUserId,
            $passwordHash,
            $_SESSION['user_id'] ?? null,
            $_SESSION['full_name'] ?? 'Unknown',
            'initial',
            'Password awal saat user baru dibuat'
        );

        // ✅ Log aktivitas user created
        logActivity(
            $pdo,
            'user_created',
            'user',
            $newUserId,
            "Admin {$_SESSION['full_name']} membuat user baru: {$fullName} (@{$username}) sebagai {$role}"
        );

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
        $password = $_POST['password'] ?? '';

        if (!$id || empty($fullName) || empty($username)) throw new Exception('Data tidak valid!');
        if (!in_array($role, ['admin', 'kasir'])) throw new Exception('Role tidak valid!');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) throw new Exception('Username hanya boleh huruf, angka, dan underscore!');

        // ✅ Ambil data lengkap user untuk log perubahan
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $currentUser = $stmt->fetch();
        if (!$currentUser) throw new Exception('User tidak ditemukan!');

        // Cek username unik (kecuali milik sendiri)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) throw new Exception('Username sudah digunakan oleh user lain!');

        // Proteksi admin terakhir - ubah role
        if ($currentUser['role'] === 'admin' && $role === 'kasir') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) throw new Exception('Tidak bisa mengubah admin terakhir menjadi kasir!');
        }

        // Proteksi admin terakhir - nonaktifkan
        if ($currentUser['role'] === 'admin' && $isActive === 0 && $currentUser['is_active'] === 1) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] === 0) throw new Exception('Tidak bisa menonaktifkan admin terakhir!');
        }

        // ✅ VALIDASI PASSWORD JIKA DIISI
        if (!empty($password)) {
            if (strlen($password) < 6) throw new Exception('Password minimal 6 karakter!');
            if (strtolower($password) === strtolower($username)) throw new Exception('Password tidak boleh sama dengan username!');
        }

        // ✅ UPDATE DENGAN ATAU TANPA PASSWORD
        if (!empty($password)) {
            $stmtOld = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldHash = $stmtOld->fetch()['password_hash'];

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, is_active = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$fullName, $username, $role, $isActive, $passwordHash, $id]);

            logPasswordChange(
                $pdo,
                $id,
                $oldHash,
                $_SESSION['user_id'],
                $_SESSION['full_name'],
                'custom',
                'Password diubah via form edit user'
            );

            // ✅ Log aktivitas password
            logActivity(
                $pdo,
                'user_password_changed',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} mengubah password user {$currentUser['full_name']} (@{$currentUser['username']}) via form edit"
            );
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$fullName, $username, $role, $isActive, $id]);
        }

        // ✅ LOG AKTIVITAS DETAIL PERUBAHAN FIELD
        if ($currentUser['full_name'] !== $fullName) {
            logActivity(
                $pdo,
                'user_fullname_updated',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} mengubah nama user dari '{$currentUser['full_name']}' menjadi '{$fullName}'"
            );
        }

        if ($currentUser['username'] !== $username) {
            logActivity(
                $pdo,
                'user_username_updated',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} mengubah username dari '{$currentUser['username']}' menjadi '{$username}'"
            );
        }

        if ($currentUser['role'] !== $role) {
            logActivity(
                $pdo,
                'user_role_updated',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} mengubah role user {$fullName} dari {$currentUser['role']} menjadi {$role}"
            );
        }

        if ((int)$currentUser['is_active'] !== (int)$isActive) {
            $oldStatus = $currentUser['is_active'] ? 'aktif' : 'nonaktif';
            $newStatus = $isActive ? 'aktif' : 'nonaktif';
            logActivity(
                $pdo,
                'user_status_updated',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} mengubah status user {$fullName} dari {$oldStatus} menjadi {$newStatus}"
            );
        }

        // Jika submit update tapi tidak ada perubahan
        if (
            $currentUser['full_name'] === $fullName &&
            $currentUser['username'] === $username &&
            $currentUser['role'] === $role &&
            (int)$currentUser['is_active'] === (int)$isActive &&
            empty($password)
        ) {
            logActivity(
                $pdo,
                'user_update_no_change',
                'user',
                $id,
                "Admin {$_SESSION['full_name']} menyimpan user {$fullName}, tetapi tidak ada perubahan data"
            );
        }

        // Update session jika edit diri sendiri
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

        $stmt = $pdo->prepare("SELECT full_name, username, role, is_active FROM users WHERE id = ?");
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

        // ✅ Log aktivitas delete
        logActivity(
            $pdo,
            'user_deleted',
            'user',
            $id,
            "Admin {$_SESSION['full_name']} menghapus user {$user['full_name']} (@{$user['username']}) dengan role {$user['role']}"
        );

        header('Location: users.php?msg=deleted');
        exit;
    }

    // ==================== RESET PASSWORD (Legacy) ====================
    if ($action === 'reset_password') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID tidak valid!');

        $stmtOld = $pdo->prepare("SELECT password_hash, username FROM users WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldUser = $stmtOld->fetch();
        if (!$oldUser) throw new Exception('User tidak ditemukan!');
        $oldHash = $oldUser['password_hash'];

        $defaultPassword = 'password123';
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $id]);

        logPasswordChange(
            $pdo,
            $id,
            $oldHash,
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            'default_reset',
            'Password direset via legacy reset'
        );

        // ✅ Log aktivitas
        logActivity(
            $pdo,
            'user_password_reset',
            'user',
            $id,
            "Admin {$_SESSION['full_name']} mereset password user {$oldUser['username']} ke password default"
        );

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: users.php?msg=password_reset');
        exit;
    }

    // ==================== CHANGE PASSWORD ====================
    if ($action === 'change_password') {
        $id = intval($_POST['id'] ?? 0);
        $passwordOption = $_POST['password_option'] ?? 'default';
        if (!$id) throw new Exception('ID tidak valid!');

        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch();
        if (!$userData) throw new Exception('User tidak ditemukan!');

        $oldHash = $userData['password_hash'];

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

        $method = ($passwordOption === 'custom') ? 'custom' : 'default_reset';
        $notes = ($passwordOption === 'custom')
            ? 'Password diubah via menu ganti password (custom)'
            : 'Password direset ke default via menu ganti password';

        logPasswordChange($pdo, $id, $oldHash, $_SESSION['user_id'], $_SESSION['full_name'], $method, $notes);

        // ✅ Log aktivitas
        $activityAction = ($passwordOption === 'custom') ? 'user_password_changed_custom' : 'user_password_reset_default';
        logActivity(
            $pdo,
            $activityAction,
            'user',
            $id,
            "Admin {$_SESSION['full_name']} " .
                (($passwordOption === 'custom')
                    ? "mengubah password user {$userData['username']} menggunakan password custom"
                    : "mereset password user {$userData['username']} ke password default")
        );

        if ($id == $_SESSION['user_id']) {
            session_unset();
            session_destroy();
            header('Location: login.php?msg=password_changed');
            exit;
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
            exit;
        }

        header('Location: users.php?msg=password_changed');
        exit;
    }

    // ==================== CHECK PASSWORD STATUS (AJAX) ====================
    if ($action === 'check_password_status') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID tidak valid!');

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) throw new Exception('User tidak ditemukan!');

        $isDefault = password_verify('password123', $user['password_hash']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_default' => $isDefault]);
        exit;
    }

    // ==================== TOGGLE STATUS (AJAX) ====================
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $isActive = intval($_POST['is_active'] ?? 0);
        if (!$id) throw new Exception('ID tidak valid!');
        if ($id == $_SESSION['user_id']) throw new Exception('Tidak bisa menonaktifkan akun sendiri!');

        $stmt = $pdo->prepare("SELECT full_name, username, role, is_active FROM users WHERE id = ?");
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

        // ✅ Log aktivitas toggle
        $newStatusText = $isActive ? 'aktif' : 'nonaktif';
        logActivity(
            $pdo,
            'user_status_toggled',
            'user',
            $id,
            "Admin {$_SESSION['full_name']} mengubah status user {$user['full_name']} (@{$user['username']}) menjadi {$newStatusText}"
        );

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
