<?php
// =====================================================
// Wisma Permata — Konfigurasi Database (CONTOH)
// =====================================================
// 1. Copy/rename file ini menjadi "db.php" di folder yang sama
// 2. Isi DB_USER, DB_PASS, DB_NAME sesuai database Anda
// 3. File "db.php" hasil rename TIDAK akan ikut ter-commit ke Git
//    (sudah didaftarkan di .gitignore) — supaya kredensial asli
//    Anda tidak pernah ter-upload ke repository publik.
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wisma_permata');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2 style="color:#dc3545;">❌ Koneksi Database Gagal</h2>
        <p>Pastikan MySQL aktif dan database <strong>wisma_permata</strong> sudah dibuat.</p>
        <p style="color:#666;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
