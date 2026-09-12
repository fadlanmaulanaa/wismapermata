<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($conn->real_escape_string($_POST['email']));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    } else {
        $r = $conn->query("SELECT id, nama, status FROM users WHERE email='$email' LIMIT 1");

        if ($r && $r->num_rows > 0) {
            $u = $r->fetch_assoc();

            if ($u['status'] === 'aktif') {
                $error = 'Akun ini sudah terverifikasi. Silakan langsung login.';
            } else {
                $uid           = (int)$u['id'];
                $token         = bin2hex(random_bytes(32));
                $token_expired = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $conn->query("UPDATE users SET verification_token='$token', token_expired_at='$token_expired' WHERE id=$uid");
                sendVerificationEmail($email, $u['nama'], $token);

                $success = 'Email verifikasi baru sudah dikirim ke ' . htmlspecialchars($email) . '. Silakan cek inbox atau folder spam Anda.';
            }
        } else {
            // Pesan sengaja dibuat sama baik email terdaftar atau tidak,
            // supaya orang lain tidak bisa menebak-nebak email yang terdaftar.
            $success = 'Jika email tersebut terdaftar dan belum diverifikasi, tautan verifikasi baru sudah dikirim.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kirim Ulang Verifikasi - Wisma Permata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
body { background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); min-height: 100vh; display:flex; align-items:center; }
.resend-card {
    background: #fff; border-radius: 18px; padding: 40px;
    box-shadow: 0 25px 60px rgba(0,0,0,.35);
    max-width: 440px; width: 100%;
}
.form-label { font-weight: 600; font-size: .88rem; color: #374151; }
.form-control {
    border-radius: 10px; padding: 11px 14px;
    border: 1.5px solid #E2E8F0; font-size: .9rem;
}
.form-control:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.btn-main {
    background: linear-gradient(135deg, #2563EB, #7c3aed);
    border: none; border-radius: 10px; padding: 13px;
    font-weight: 700; font-size: 1rem; color: #fff;
    width: 100%;
}
.btn-main:hover { opacity: .92; color: #fff; }
.input-icon { position: relative; }
.input-icon .bi { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.input-icon input { padding-left: 40px !important; }
</style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-center">
    <div class="resend-card">

      <div class="text-center mb-4">
        <div style="width:60px;height:60px;background:#EFF6FF;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.8rem;">📧</div>
        <h4 class="fw-800 mb-1" style="color:#1E293B">Kirim Ulang Verifikasi</h4>
        <p class="text-muted mb-0" style="font-size:.88rem">Masukkan email yang Anda daftarkan</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:12px;font-size:.88rem">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:12px;font-size:.88rem">
        <i class="bi bi-check-circle-fill flex-shrink-0"></i><span><?= $success ?></span>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <div class="input-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>
        <button type="submit" class="btn-main">
          <i class="bi bi-send me-2"></i>Kirim Ulang Link Verifikasi
        </button>
      </form>

      <div class="text-center mt-4">
        <a href="login.php" class="fw-700" style="color:#2563EB;font-size:.88rem">Kembali ke Login</a>
      </div>

    </div>
  </div>
</div>
</body>
</html>
