<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni'], $depth . 'login.php'); // HANYA role 'penghuni' yang boleh akses
$page_title = 'Ganti Password';
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama  = $_POST['password_lama'] ?? '';
    $password_baru  = $_POST['password_baru'] ?? '';
    $password_ulang = $_POST['password_ulang'] ?? '';

    $user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

    if (!$user || !password_verify($password_lama, $user['password'])) {
        $msg = 'danger|Password lama yang Anda masukkan salah.';
    } elseif (strlen($password_baru) < 6) {
        $msg = 'danger|Password baru minimal 6 karakter.';
    } elseif ($password_baru !== $password_ulang) {
        $msg = 'danger|Konfirmasi password baru tidak cocok.';
    } elseif (password_verify($password_baru, $user['password'])) {
        $msg = 'warning|Password baru tidak boleh sama dengan password lama.';
    } else {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='" . $conn->real_escape_string($hash) . "' WHERE id=$uid");

        // Sinkronkan sesi supaya tidak perlu logout-login lagi
        $_SESSION['user']['password'] = $hash;

        addNotif($conn, $uid, 'Password Diperbarui 🔒', 'Password akun Anda berhasil diubah.', 'success', '');

        $msg = 'success|Password berhasil diubah.';
    }
}

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="card">
        <div class="card-header py-3 fw-700"><i class="bi bi-shield-lock me-2"></i>Ganti Password</div>
        <div class="card-body">
        <form method="POST" id="gantiPasswordForm">
            <div class="mb-3">
                <label class="form-label fw-600">Password Saat Ini <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password_lama" id="password_lama" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_lama','eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Password Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password_baru" id="password_baru" class="form-control" minlength="6" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_baru','eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
                <div class="text-muted mt-1" style="font-size:.78rem">Minimal 6 karakter.</div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password_ulang" id="password_ulang" class="form-control" minlength="6" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_ulang','eye3')">
                        <i class="bi bi-eye" id="eye3"></i>
                    </button>
                </div>
                <div id="matchInfo" class="mt-1" style="font-size:.78rem"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-circle me-2"></i>Simpan Password Baru
            </button>
        </form>
        </div>
    </div>
  </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
function togglePwd(inputId, iconId) {
    const p = document.getElementById(inputId);
    const e = document.getElementById(iconId);
    p.type = p.type === 'password' ? 'text' : 'password';
    e.className = p.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}

const baru  = document.getElementById('password_baru');
const ulang = document.getElementById('password_ulang');
const info  = document.getElementById('matchInfo');

function cekCocok() {
    if (!ulang.value) { info.textContent = ''; return; }
    if (baru.value === ulang.value) {
        info.textContent = 'Password cocok ✓';
        info.className = 'mt-1 text-success';
    } else {
        info.textContent = 'Password belum sama';
        info.className = 'mt-1 text-danger';
    }
}
baru.addEventListener('input', cekCocok);
ulang.addEventListener('input', cekCocok);
</script>