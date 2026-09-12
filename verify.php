<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$status = 'invalid'; // invalid | success | expired | already
$nama   = '';

$token = $_GET['token'] ?? '';

if ($token !== '') {
    $token_esc = $conn->real_escape_string($token);
    $r = $conn->query("SELECT id, nama, status, token_expired_at FROM users WHERE verification_token='$token_esc' LIMIT 1");

    if ($r && $r->num_rows > 0) {
        $u    = $r->fetch_assoc();
        $nama = $u['nama'];

        if ($u['status'] === 'aktif') {
            $status = 'already';
        } elseif (!$u['token_expired_at'] || strtotime($u['token_expired_at']) < time()) {
            $status = 'expired';
        } else {
            $uid = (int)$u['id'];
            $conn->query("UPDATE users SET status='aktif', verification_token=NULL, token_expired_at=NULL WHERE id=$uid");

            // Notifikasi ke admin/pemilik/pegawai BARU dikirim sekarang,
            // setelah email benar-benar terverifikasi (bukan saat daftar)
            $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
            while ($a = $admins->fetch_assoc()) {
                addNotif($conn, $a['id'],
                    'Calon Penghuni Baru 👤',
                    htmlspecialchars($nama) . " telah mendaftar dan memverifikasi emailnya. Silakan tinjau dan berikan akses.",
                    'info',
                    'penghuni.php'
                );
            }

            $status = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verifikasi Akun - Wisma Permata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
body { background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); min-height: 100vh; display:flex; align-items:center; }
.verify-card {
    background: #fff; border-radius: 18px; padding: 44px;
    box-shadow: 0 25px 60px rgba(0,0,0,.35);
    max-width: 460px; width: 100%; text-align: center;
}
.btn-main {
    background: linear-gradient(135deg, #2563EB, #7c3aed);
    border: none; border-radius: 10px; padding: 13px 24px;
    font-weight: 700; font-size: .95rem; color: #fff;
    transition: opacity .2s ease;
}
.btn-main:hover { opacity: .92; color: #fff; }
</style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-center">
    <div class="verify-card">

      <?php if ($status === 'success'): ?>
        <div style="font-size:4rem">✅</div>
        <h4 class="fw-800 mt-3 mb-2" style="color:#1E293B">Verifikasi Berhasil!</h4>
        <p class="text-muted mb-4" style="font-size:.9rem">
            Halo <strong><?= htmlspecialchars($nama) ?></strong>, email Anda sudah terverifikasi.
            Akun Anda sekarang aktif dan siap digunakan.
        </p>
        <a href="login.php" class="btn-main"><i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang</a>

      <?php elseif ($status === 'already'): ?>
        <div style="font-size:4rem">ℹ️</div>
        <h4 class="fw-800 mt-3 mb-2" style="color:#1E293B">Akun Sudah Terverifikasi</h4>
        <p class="text-muted mb-4" style="font-size:.9rem">
            Akun ini sudah pernah diverifikasi sebelumnya. Silakan langsung login.
        </p>
        <a href="login.php" class="btn-main"><i class="bi bi-box-arrow-in-right me-2"></i>Ke Halaman Login</a>

      <?php elseif ($status === 'expired'): ?>
        <div style="font-size:4rem">⏰</div>
        <h4 class="fw-800 mt-3 mb-2" style="color:#1E293B">Link Sudah Kedaluwarsa</h4>
        <p class="text-muted mb-4" style="font-size:.9rem">
            Link verifikasi ini sudah lewat 24 jam masa berlakunya. Silakan minta link verifikasi baru.
        </p>
        <a href="resend_verifikasi.php" class="btn-main"><i class="bi bi-envelope me-2"></i>Kirim Ulang Verifikasi</a>

      <?php else: ?>
        <div style="font-size:4rem">❌</div>
        <h4 class="fw-800 mt-3 mb-2" style="color:#1E293B">Link Tidak Valid</h4>
        <p class="text-muted mb-4" style="font-size:.9rem">
            Link verifikasi ini tidak ditemukan atau sudah pernah dipakai.
        </p>
        <a href="resend_verifikasi.php" class="btn-main"><i class="bi bi-envelope me-2"></i>Kirim Ulang Verifikasi</a>
      <?php endif; ?>

      <div class="mt-3">
        <a href="index.php" class="text-muted" style="font-size:.85rem">
          <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
        </a>
      </div>

    </div>
  </div>
</div>
</body>
</html>
