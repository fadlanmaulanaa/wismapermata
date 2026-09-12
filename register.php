<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($conn->real_escape_string($_POST['nama']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $telp  = trim($conn->real_escape_string($_POST['nomor_telepon']));
    $ktp   = trim($conn->real_escape_string($_POST['nomor_ktp']));
    $pass  = $_POST['password'];
    $pass2 = $_POST['password2'];

    // Validasi
    if (empty($nama) || empty($email) || empty($telp) || empty($ktp) || empty($pass)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($pass !== $pass2) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($ktp) !== 16 || !ctype_digit($ktp)) {
        $error = 'Nomor KTP harus 16 digit angka.';
    } else {
        // Cek email duplikat
        $cek = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            // Token verifikasi email, berlaku 24 jam
            $token         = bin2hex(random_bytes(32));
            $token_expired = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Status = pending sampai email diverifikasi (BELUM bisa login)
            $ok = $conn->query("INSERT INTO users
                                (nama, email, password, nomor_telepon, nomor_ktp, role, status, verification_token, token_expired_at)
                                VALUES ('$nama', '$email', '$hash', '$telp', '$ktp', 'calon_penghuni', 'pending', '$token', '$token_expired')");

            if ($ok) {
                $new_id = $conn->insert_id;
                generateKode($conn, 'users', 'kode_user', 'PGH', $new_id, 4);

                $mail_sent = sendVerificationEmail($email, $nama, $token);
                if ($mail_sent) {
                    $success = $nama;
                } else {
                    $error = 'Akun Anda berhasil dibuat, tetapi email verifikasi gagal dikirim saat ini. Silakan buka halaman "Kirim Ulang Verifikasi" dalam beberapa saat, atau hubungi admin.';
                }
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Akun - Wisma Permata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
body { background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); min-height: 100vh; padding: 40px 0; }
.reg-card {
    background: #fff; border-radius: 18px; padding: 40px;
    box-shadow: 0 25px 60px rgba(0,0,0,.35);
    max-width: 520px; width: 100%;
}
.form-label { font-weight: 600; font-size: .88rem; color: #374151; }
.form-control, .form-select {
    border-radius: 10px; padding: 11px 14px;
    border: 1.5px solid #E2E8F0; font-size: .9rem;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.form-control:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.btn-daftar {
    background: linear-gradient(135deg, #2563EB, #7c3aed);
    border: none; border-radius: 10px; padding: 13px;
    font-weight: 700; font-size: 1rem; color: #fff;
    width: 100%; transition: opacity .2s ease, box-shadow .15s ease;
}
.btn-daftar:hover { opacity: .92; color: #fff; box-shadow: 0 6px 18px rgba(37,99,235,.3); }
.step-badge {
    width: 28px; height: 28px; border-radius: 50%;
    background: #EFF6FF; color: #2563EB;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700; flex-shrink: 0;
}
.input-icon { position: relative; }
.input-icon .bi {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 1rem; pointer-events: none;
}
.input-icon input { padding-left: 40px !important; }
</style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-center">
    <div class="reg-card">

      <!-- Header -->
      <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,#EFF6FF,#dbeafe);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:2rem;">📝</div>
        <h4 class="fw-800 mb-1" style="color:#1E293B">Buat Akun Baru</h4>
        <p class="text-muted mb-0" style="font-size:.88rem">Daftarkan diri Anda di Wisma Permata</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:12px;font-size:.88rem">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <!-- SUCCESS STATE -->
      <div class="text-center py-3">
        <div style="font-size:4rem">📧</div>
        <h5 class="fw-700 mt-3 mb-2">Cek Email Anda!</h5>
        <p class="text-muted mb-1" style="font-size:.9rem">
            Halo, <strong><?= htmlspecialchars($success) ?></strong>!<br>
            Kami telah mengirim link verifikasi ke email Anda.
        </p>
        <div class="p-3 bg-light rounded-3 text-start mt-3 mb-4" style="font-size:.84rem">
            <div class="fw-600 mb-2 text-primary">📋 Langkah Selanjutnya:</div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-success">✓</span>
                <span>Data tersimpan di database</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark">⏳</span>
                <span>Buka email Anda &amp; klik tombol <strong>Verifikasi Akun Saya</strong></span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-secondary">🔒</span>
                <span>Login baru bisa dilakukan <strong>setelah</strong> email terverifikasi</span>
            </div>
        </div>
        <p class="text-muted mb-3" style="font-size:.8rem">
            Tidak menerima email? Cek folder spam, atau
            <a href="resend_verifikasi.php">kirim ulang link verifikasi</a>.
        </p>
        <a href="login.php" class="btn btn-daftar mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ke Halaman Login
        </a>
        <div><a href="index.php" class="text-muted" style="font-size:.85rem"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a></div>
      </div>

      <?php else: ?>
      <!-- FORM -->
      <form method="POST" novalidate>
        <div class="row g-3">

          <!-- Nama -->
          <div class="col-12">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <div class="input-icon">
              <i class="bi bi-person"></i>
              <input type="text" name="nama" class="form-control"
                     placeholder="Sesuai KTP" required
                     value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>
          </div>

          <!-- Email -->
          <div class="col-12">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <div class="input-icon">
              <i class="bi bi-envelope"></i>
              <input type="email" name="email" class="form-control"
                     placeholder="email@contoh.com" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <!-- Telepon -->
          <div class="col-md-6">
            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
            <div class="input-icon">
              <i class="bi bi-phone"></i>
              <input type="tel" name="nomor_telepon" class="form-control"
                     placeholder="08xxxxxxxxxx" required
                     value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>">
            </div>
          </div>

          <!-- KTP -->
          <div class="col-md-6">
            <label class="form-label">Nomor KTP (NIK) <span class="text-danger">*</span></label>
            <div class="input-icon">
              <i class="bi bi-credit-card-2-front"></i>
              <input type="text" name="nomor_ktp" class="form-control"
                     placeholder="16 digit NIK" required maxlength="16" pattern="\d{16}"
                     value="<?= htmlspecialchars($_POST['nomor_ktp'] ?? '') ?>"
                     oninput="this.value=this.value.replace(/\D/,'')">
            </div>
          </div>

          <!-- Password -->
          <div class="col-12">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="pwd1" class="form-control"
                     placeholder="Minimal 6 karakter" required minlength="6"
                     style="border-radius:10px 0 0 10px">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pwd1','eye1')"
                      style="border-radius:0 10px 10px 0">
                  <i class="bi bi-eye" id="eye1"></i>
              </button>
            </div>
          </div>

          <!-- Konfirmasi Password -->
          <div class="col-12">
            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password2" id="pwd2" class="form-control"
                     placeholder="Ulangi password" required
                     style="border-radius:10px 0 0 10px">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pwd2','eye2')"
                      style="border-radius:0 10px 10px 0">
                  <i class="bi bi-eye" id="eye2"></i>
              </button>
            </div>
          </div>

          <!-- Info -->
          <div class="col-12">
            <div class="p-3 rounded-3" style="background:#F0FDF4;border:1px solid #86efac;font-size:.82rem">
              <div class="fw-600 text-success mb-2"><i class="bi bi-info-circle me-1"></i>Informasi Pendaftaran</div>
              <div class="d-flex gap-2 mb-1"><span>✅</span><span>Data langsung tersimpan di database</span></div>
              <div class="d-flex gap-2 mb-1"><span>📧</span><span>Link verifikasi akan dikirim ke email Anda</span></div>
              <div class="d-flex gap-2"><span>⏳</span><span>Login &amp; akses pemesanan kamar aktif setelah email diverifikasi</span></div>
            </div>
          </div>

          <!-- Submit -->
          <div class="col-12 mt-2">
            <button type="submit" class="btn-daftar">
              <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
            </button>
          </div>

        </div>
      </form>

      <div class="text-center mt-4">
        <span class="text-muted" style="font-size:.88rem">Sudah punya akun? </span>
        <a href="login.php" class="fw-700" style="color:#2563EB">Masuk di sini</a>
      </div>
      <div class="text-center mt-2">
        <a href="index.php" class="text-muted" style="font-size:.82rem">
          <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
        </a>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(inputId, iconId) {
    const p = document.getElementById(inputId);
    const e = document.getElementById(iconId);
    p.type = p.type === 'password' ? 'text' : 'password';
    e.className = p.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Real-time password match check
document.getElementById('pwd2')?.addEventListener('input', function() {
    const p1 = document.getElementById('pwd1').value;
    const match = this.value === p1;
    this.style.borderColor = this.value ? (match ? '#22c55e' : '#ef4444') : '';
});

// KTP hanya angka
document.querySelector('[name="nomor_ktp"]')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
    const ok = this.value.length === 16;
    this.style.borderColor = this.value.length > 0 ? (ok ? '#22c55e' : '#ef4444') : '';
});
</script>
</body>
</html>