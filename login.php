<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Kalau sudah login, redirect sesuai role
if (isLoggedIn()) {
    $u = getUser();
    if (in_array($u['role'], ['pemilik', 'pegawai'])) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: penghuni/dashboard.php');
    }
    exit;
}

$error = '';
$show_resend = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];

    $r = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
    if ($r && $r->num_rows > 0) {
        $user = $r->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error       = 'Akun Anda belum diverifikasi. Silakan cek email Anda untuk link verifikasi.';
                $show_resend = true;
            } elseif ($user['status'] !== 'aktif') {
                $error = 'Akun Anda tidak aktif. Silakan hubungi pengelola Wisma Permata.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user;
                if (in_array($user['role'], ['pemilik', 'pegawai'])) {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: penghuni/dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Email atau password salah.';
        }
    } else {
        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Wisma Permata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
body { background: linear-gradient(135deg,#1E293B,#2563EB); min-height:100vh; display:flex; align-items:center; }
.login-card { background:#fff; border-radius:20px; padding:40px; box-shadow:0 20px 60px rgba(0,0,0,.3); max-width:440px; width:100%; }
.form-control { border-radius:10px; padding:12px 16px; border:1px solid #E2E8F0; }
.btn-primary { border-radius:10px; padding:12px; font-weight:700; background:#2563EB; border:none; }
.btn-primary:hover { background:#1d4ed8; }
.input-group-text { border-radius:10px 0 0 10px !important; background:#F8FAFC; border-color:#E2E8F0; }
.form-control { border-radius:0 10px 10px 0 !important; }
</style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <div style="width:60px;height:60px;background:#EFF6FF;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;">🏠</div>
            <h4 class="fw-800 mb-1">Masuk ke Wisma Permata</h4>
            <p class="text-muted" style="font-size:.9rem">Silakan masuk dengan akun Anda</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <?php if ($show_resend): ?>
                <div class="mt-2"><a href="resend_verifikasi.php" class="fw-600">Kirim ulang email verifikasi</a></div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-600">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()" style="border-radius:0 10px 10px 0 !important">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>

        <div class="text-center">
            <span class="text-muted" style="font-size:.9rem">Belum punya akun? </span>
            <a href="register.php" class="fw-600">Daftar di sini</a>
        </div>
        <hr>
        <div class="text-center">
            <a href="index.php" class="text-muted" style="font-size:.85rem">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
            </a>
        </div>

        
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const e = document.getElementById('eyeIcon');
    p.type = p.type === 'password' ? 'text' : 'password';
    e.className = p.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
</body>
</html>
