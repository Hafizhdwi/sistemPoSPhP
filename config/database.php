<?php
// ============================================
// config/database.php
// Koneksi Database + Helper + Auth Loader
// ============================================

// --- KONFIGURASI DATABASE ---
$host = 'localhost';
$db   = 'db_mini_pos';
$user = 'root';      // Default Laragon
$pass = '';          // Default Laragon kosong

// --- KONEKSI PDO ---
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("<div class='alert alert-danger m-3'>❌ Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// --- HELPER: FORMAT RUPIAH ---
function formatRupiah($number): string
{
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

// --- LOAD SISTEM AUTENTIKASI ---
// Memuat session_start(), requireLogin(), requireAdmin(), hasRole(), dll.
require_once __DIR__ . '/auth.php';
