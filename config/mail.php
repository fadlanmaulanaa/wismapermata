<?php
// ============================================================
// KONFIGURASI EMAIL — sesuaikan dengan domain/hosting Anda
// ============================================================

// Alamat pengirim email verifikasi. Idealnya pakai email dengan
// domain yang sama dengan hosting Anda (mis. noreply@wismapermata.com)
// agar tidak masuk folder spam.
define('MAIL_FROM_EMAIL', 'noreply@wismapermata.com');
define('MAIL_FROM_NAME', 'Wisma Permata');

// Deteksi otomatis alamat situs (protokol + domain + folder instalasi)
// supaya link verifikasi di email selalu valid, baik di localhost
// maupun setelah di-upload ke hosting.
if (!defined('SITE_URL')) {
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    define('SITE_URL', $protocol . '://' . $host . $base_path);
}
