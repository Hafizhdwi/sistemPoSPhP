<?php
require_once 'config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirectUrl = ($_SESSION['role'] === 'admin') ? 'dashboard.php' : 'index.php';
    header('Location: ' . $redirectUrl);
    exit;
}

$error = '';
$infoMsg = '';
$redirect = $_GET['redirect'] ?? '';

// ✅ HANDLE PESAN DARI REDIRECT (misal: setelah ganti password)
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'password_changed':
            $infoMsg = '🔑 Password berhasil diubah! Silakan login dengan password baru.';
            break;
        case 'logged_out':
            $infoMsg = '👋 Anda telah berhasil logout.';
            break;
        case 'session_expired':
            $infoMsg = '⏰ Sesi Anda telah berakhir. Silakan login kembali.';
            break;
        case 'access_denied':
            $error = '⛔ Akses ditolak. Anda tidak memiliki izin untuk halaman tersebut.';
            break;
    }
}

// ✅ HELPER: Log activity (aman, tidak error jika fungsi belum ada)
function safeLogActivity($pdo, $action, $entityType = null, $entityId = null, $description = '')
{
    // Cek apakah fungsi logActivity sudah didefinisikan
    if (function_exists('logActivity')) {
        try {
            logActivity($pdo, $action, $entityType, $entityId, $description);
        } catch (Exception $e) {
            // Silent fail - jangan ganggu proses login jika logging error
            error_log('Login logging failed: ' . $e->getMessage());
        }
        return;
    }

    // Fallback: coba insert langsung ke tabel activity_logs jika ada
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (action, entity_type, entity_id, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
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
        // Tabel activity_logs belum ada, silent fail
        error_log('Activity log skipped (table not exist): ' . $e->getMessage());
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        // ✅ Ambil user dulu untuk cek password manual
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmtUser->execute([$username]);
        $user = $stmtUser->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // ✅ AUTO-REHASH jika hash lama perlu diupgrade
            // (misal PHP update versi, cost factor berubah, dll)
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                try {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtRehash = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmtRehash->execute([$newHash, $user['id']]);
                } catch (Exception $e) {
                    error_log('Auto rehash failed: ' . $e->getMessage());
                }
            }

            // ✅ Set session manual (karena kita sudah punya data user)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // ✅ Catat login berhasil
            safeLogActivity(
                $pdo,
                'login_success',
                'user',
                (int)$user['id'],
                "User {$user['full_name']} login sebagai {$user['role']}"
            );

            if (!empty($redirect)) {
                header('Location: ' . $redirect);
            } else {
                $redirectUrl = ($user['role'] === 'admin') ? 'dashboard.php' : 'index.php';
                header('Location: ' . $redirectUrl);
            }
            exit;
        } else {
            $error = 'Username atau password salah!';

            // ✅ Catat login gagal
            safeLogActivity(
                $pdo,
                'login_failed',
                null,
                null,
                "Percobaan login gagal untuk username: {$username}"
            );
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Password input with eye icon */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            z-index: 5;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Info alert styling */
        .alert-info-custom {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1e40af;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Shake animation untuk error */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-8px);
            }

            40%,
            80% {
                transform: translateX(8px);
            }
        }

        .shake {
            animation: shake 0.4s ease;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card" id="loginCard">
            <div class="login-header">
                <div style="font-size:3rem; margin-bottom:10px;">🏪</div>
                <h2>Mini PoS</h2>
                <p class="mb-0 mt-2" style="opacity:0.7; font-size:0.9rem;">Sistem Point of Sale</p>
            </div>
            <div class="login-body">
                <!-- ✅ INFO MESSAGE (untuk password changed, logout, dll) -->
                <?php if ($infoMsg): ?>
                    <div class="alert-info-custom">
                        <i class="bi bi-info-circle-fill"></i>
                        <span><?= htmlspecialchars($infoMsg) ?></span>
                    </div>
                <?php endif; ?>

                <!-- ERROR MESSAGE -->
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 rounded-3 py-2 mb-4">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">
                            <i class="bi bi-person-fill me-1"></i>Username
                        </label>
                        <input type="text" name="username" id="usernameInput" class="form-control form-control-lg"
                            placeholder="Masukkan username" required autofocus autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">
                            <i class="bi bi-lock-fill me-1"></i>Password
                        </label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="passwordInput" class="form-control form-control-lg"
                                placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login" id="btnLogin">
                        <span id="btnText">🔓 MASUK</span>
                        <span id="btnLoading" style="display:none;">
                            <span class="spinner-border spinner-border-sm me-2"></span>Memproses...
                        </span>
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted">
                        Demo: <code>admin/admin123</code> atau <code>kasir1/kasir123</code>
                    </small>
                </div>

                <!-- ✅ Keyboard shortcut info -->
                <div class="mt-3 text-center">
                    <small class="text-muted" style="font-size:0.7rem;">
                        💡 Tekan <kbd>Enter</kbd> untuk login cepat
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==================== PASSWORD VISIBILITY TOGGLE ====================
        function togglePassword(btn) {
            const input = document.getElementById('passwordInput');
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // ==================== FORM SUBMIT HANDLING ====================
        const loginForm = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        const loginCard = document.getElementById('loginCard');

        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('usernameInput').value.trim();
            const password = document.getElementById('passwordInput').value;

            // Validasi client-side
            if (!username || !password) {
                e.preventDefault();
                loginCard.classList.add('shake');
                setTimeout(() => loginCard.classList.remove('shake'), 400);
                return false;
            }

            // Tampilkan loading state
            btnLogin.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
        });

        // ==================== AUTO-FOCUS LOGIC ====================
        const usernameInput = document.getElementById('usernameInput');
        const passwordInput = document.getElementById('passwordInput');

        // Jika username sudah terisi, fokus ke password
        if (usernameInput.value.trim() !== '') {
            passwordInput.focus();
        }

        // Enter key di username → pindah ke password
        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                passwordInput.focus();
            }
        });
    </script>
</body>

</html>