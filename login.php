<?php
require_once 'config/database.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    $redirectUrl = ($_SESSION['role'] === 'admin') ? 'dashboard.php' : 'index.php';
    header('Location: ' . $redirectUrl);
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } elseif (loginUser($pdo, $username, $password)) {
        // ✅ Admin → Dashboard, Kasir → Kasir
        if (!empty($redirect)) {
            header('Location: ' . $redirect);
        } else {
            $redirectUrl = ($_SESSION['role'] === 'admin') ? 'dashboard.php' : 'index.php';
            header('Location: ' . $redirectUrl);
        }
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini PoS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div style="font-size:3rem; margin-bottom:10px;">🏪</div>
                <h2>Mini PoS</h2>
                <p class="mb-0 mt-2" style="opacity:0.7; font-size:0.9rem;">Sistem Point of Sale</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 rounded-3 py-2 mb-4">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Username</label>
                        <input type="text" name="username" class="form-control form-control-lg"
                            placeholder="Masukkan username" required autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg"
                            placeholder="Masukkan password" required>
                    </div>
                    <button type="submit" class="btn btn-login">
                        🔓 MASUK
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted">
                        Demo: <code>admin/admin123</code> atau <code>kasir1/kasir123</code>
                    </small>
                </div>
            </div>
        </div>
    </div>

</body>

</html>