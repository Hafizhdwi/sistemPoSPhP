<?php
require_once 'config/database.php';

// ✅ Log aktivitas sebelum logout
if (isLoggedIn()) {
    logActivity(
        $pdo,
        'logout',
        'user',
        $_SESSION['user_id'],
        "User {$_SESSION['full_name']} logout dari sistem"
    );
}

session_unset();
session_destroy();

header('Location: login.php?msg=logged_out');
exit;
