<?php
// config/auth.php
session_start();

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek role user saat ini
 */
function hasRole(string $role): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === $role;
}

/**
 * Proteksi halaman - redirect ke login jika belum login
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['PHP_SELF']));
        exit;
    }
}

/**
 * Proteksi halaman khusus admin
 */
function requireAdmin(): void
{
    requireLogin();
    if (!hasRole('admin')) {
        die('<div style="padding:40px;text-align:center;font-family:sans-serif;">
            <h1>⛔ Akses Ditolak</h1>
            <p>Halaman ini hanya untuk Admin.</p>
            <a href="index.php">← Kembali ke Kasir</a>
        </div>');
    }
}

/**
 * Login user
 */
function loginUser(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

/**
 * Logout user
 */
function logoutUser(): void
{
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
