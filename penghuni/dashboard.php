<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni', 'calon_penghuni'], $depth . 'login.php');

$page_title = 'Dashboard Penghuni';
$uid  = $_SESSION['user_id'];
$user = getUser();

// Data penghuni
$kamar_info = null;
if ($user['nomor_kamar']) {
    $kamar_info = $conn->query("SELECT * FROM kamar WHERE nomor_kamar='" . $conn->real_escape_string($user['nomor_kamar']) . "'")->fetch_assoc();
}

$total_aduan   = $conn->query("SELECT COUNT(*) c FROM laporan_aduan WHERE user_id=$uid")->fetch_assoc()['c'];
$aduan_open    = $conn->query("SELECT COUNT(*) c FROM laporan_aduan WHERE user_id=$uid AND status IN ('pending','ditangani')")->fetch_assoc()['c'];
$pending_bayar = $conn->query("SELECT COUNT(*) c FROM pembayaran_sewa WHERE user_id=$uid AND status='pending'")->fetch_assoc()['c'];

// Cek jatuh tempo sewa untuk banner pengingat otomatis di dashboard
$jatuh_tempo_info = ($user['role'] === 'penghuni' && $user['nomor_kamar'])
    ? getJatuhTempoSewa($conn, $uid)
    : null;

// Batas waktu pembayaran booking yang baru disetujui — dipakai untuk pop-up pengingat
$batas_bayar_aktif = getBatasBayarAktif($conn, $uid);

// Konfirmasi kelanjutan huni (H-7) yang masih menunggu jawaban — cuma dicek kalau
// TIDAK ada pop-up batas bayar aktif, supaya dua pop-up tidak numpuk sekaligus.
// Kalau ada dua-duanya, pop-up bayar diutamakan; ini akan muncul di kunjungan berikutnya.
$konfirmasi_huni_aktif = null;
if (!$batas_bayar_aktif) {
    $konfirmasi_huni_aktif = $conn->query("SELECT * FROM konfirmasi_huni
        WHERE user_id=$uid AND status='menunggu' ORDER BY id DESC LIMIT 1")->fetch_assoc();
}

// Notifikasi terbaru
$notifs = $conn->query("SELECT * FROM notifikasi WHERE user_id=$uid AND is_read=0 ORDER BY created_at DESC LIMIT 5");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">

<?php if ($batas_bayar_aktif): ?>
<div class="pay-overlay" id="payOverlay">
  <div class="pay-card">
    <button type="button" class="pay-close" onclick="document.getElementById('payOverlay').style.display='none'" aria-label="Tutup">&times;</button>
    <div class="pay-icon"><i class="bi bi-hourglass-split"></i></div>
    <h4 class="fw-700 mt-3 mb-2">Pemesanan Anda Sudah Disetujui!</h4>
    <p class="text-muted mb-3" id="payMsg">
        Selesaikan pembayaran sewa sebelum waktu habis, atau pemesanan akan
        <strong>dibatalkan otomatis</strong> dan kamar dilepas kembali ke penyewa lain.
    </p>
    <div class="pay-countdown" id="payCountdown">--:--:--</div>
    <div class="text-muted mb-4" style="font-size:.82rem">
        Batas waktu: <?= date('d M Y H:i', strtotime($batas_bayar_aktif)) ?>
    </div>
    <a href="pembayaran.php" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-cash-coin me-2"></i>Bayar Sekarang
    </a>
    <div class="mt-3">
        <button type="button" class="btn btn-link text-muted" style="font-size:.85rem"
                onclick="document.getElementById('payOverlay').style.display='none'">Nanti saja</button>
    </div>
  </div>
</div>
<style>
.pay-overlay {
    position: fixed; inset: 0; background: rgba(11,17,32,.82);
    z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.pay-card {
    background: #fff; border-radius: 20px; padding: 44px 40px; max-width: 460px; width: 100%;
    text-align: center; position: relative; box-shadow: 0 30px 80px rgba(0,0,0,.4);
}
.pay-close {
    position: absolute; top: 14px; right: 18px; border: none; background: none;
    font-size: 1.7rem; line-height: 1; color: #94a3b8; cursor: pointer;
}
.pay-close:hover { color: #374151; }
.pay-icon {
    width: 64px; height: 64px; background: #FEF3C7; color: #D97706; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto;
}
.pay-countdown {
    font-variant-numeric: tabular-nums; font-size: 2.5rem; font-weight: 700;
    color: #2563EB; letter-spacing: .04em; margin: 6px 0 4px;
}
</style>
<script>
(function () {
    <?php $dt = strtotime($batas_bayar_aktif); ?>
    // Dikirim sebagai angka mentah (bukan string ISO dengan offset zona waktu)
    // supaya JavaScript membangun objek Date-nya sebagai waktu LOKAL browser,
    // persis sama dengan waktu yang tersimpan di database — tidak ada
    // konversi zona waktu yang bisa meleset.
    var deadline = new Date(
        <?= (int)date('Y', $dt) ?>,
        <?= (int)date('n', $dt) - 1 ?>,
        <?= (int)date('j', $dt) ?>,
        <?= (int)date('G', $dt) ?>,
        <?= (int)date('i', $dt) ?>,
        <?= (int)date('s', $dt) ?>
    ).getTime();

    var el  = document.getElementById('payCountdown');
    var msg = document.getElementById('payMsg');
    var timer = setInterval(tick, 1000);
    tick();

    function tick() {
        var now  = new Date().getTime();
        var diff = deadline - now;

        if (diff <= 0) {
            el.textContent = '00:00:00';
            msg.innerHTML = 'Waktu habis. Memuat ulang halaman untuk memperbarui status...';
            clearInterval(timer);
            setTimeout(function () { location.reload(); }, 2000);
            return;
        }

        var h = Math.floor(diff / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        el.textContent =
            String(h).padStart(2, '0') + ':' +
            String(m).padStart(2, '0') + ':' +
            String(s).padStart(2, '0');
    }
})();
</script>
<?php endif; ?>

<?php if ($konfirmasi_huni_aktif): ?>
<div class="pay-overlay" id="huniOverlay">
  <div class="pay-card">
    <button type="button" class="pay-close" onclick="document.getElementById('huniOverlay').style.display='none'" aria-label="Tutup">&times;</button>
    <div class="pay-icon" style="background:#EFF6FF;color:#2563EB"><i class="bi bi-clipboard-check"></i></div>
    <h4 class="fw-700 mt-3 mb-2">Konfirmasi Kelanjutan Kost</h4>
    <p class="text-muted mb-4">
        Pembayaran sewa bulan <strong><?= $konfirmasi_huni_aktif['periode_bulan'] ?></strong> akan jatuh tempo dalam waktu dekat (H-7).
        Mohon konfirmasi apakah Anda akan <strong>melanjutkan</strong> atau <strong>mengakhiri</strong> masa kost sebelum jatuh tempo.
    </p>
    <a href="konfirmasi_huni.php" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-clipboard-check me-2"></i>Konfirmasi Sekarang
    </a>
    <div class="mt-3">
        <button type="button" class="btn btn-link text-muted" style="font-size:.85rem"
                onclick="document.getElementById('huniOverlay').style.display='none'">Nanti saja</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
// Banner inline pengingat jatuh tempo sewa (muncul otomatis tanpa perlu admin kirim)
if ($jatuh_tempo_info):
    $sisa   = $jatuh_tempo_info['sisa_hari'];
    $tgl_jt = date('d M Y', strtotime($jatuh_tempo_info['jatuh_tempo']));
    if ($sisa <= 0):
?>
<div class="alert alert-danger d-flex align-items-start gap-3 mb-4" style="border-radius:12px">
    <i class="bi bi-exclamation-octagon-fill fs-4 flex-shrink-0 mt-1"></i>
    <div>
        <div class="fw-700">Masa Sewa Telah Berakhir!</div>
        <div style="font-size:.9rem">
            Masa sewa kamar Anda telah berakhir sejak <strong><?= $tgl_jt ?></strong>.
            Segera hubungi pengelola untuk perpanjangan atau proses check-out agar akun Anda tidak dinonaktifkan.
        </div>
        <a href="pembayaran.php" class="btn btn-danger btn-sm mt-2">
            <i class="bi bi-cash-coin me-1"></i>Bayar Perpanjangan
        </a>
    </div>
</div>
<?php elseif ($sisa <= 3): ?>
<div class="alert alert-danger d-flex align-items-start gap-3 mb-4" style="border-radius:12px">
    <i class="bi bi-alarm-fill fs-4 flex-shrink-0 mt-1"></i>
    <div>
        <div class="fw-700">Masa Sewa Hampir Habis! Tersisa <?= $sisa ?> Hari</div>
        <div style="font-size:.9rem">
            Masa sewa kamar Anda akan berakhir pada <strong><?= $tgl_jt ?></strong>.
            Segera lakukan pembayaran perpanjangan agar kamar tidak dilepas.
        </div>
        <a href="pembayaran.php" class="btn btn-danger btn-sm mt-2">
            <i class="bi bi-cash-coin me-1"></i>Bayar Sekarang
        </a>
    </div>
</div>
<?php elseif ($sisa <= 7): ?>
<div class="alert alert-warning d-flex align-items-start gap-3 mb-4" style="border-radius:12px">
    <i class="bi bi-bell-fill fs-4 flex-shrink-0 mt-1"></i>
    <div>
        <div class="fw-700">Pengingat: Masa Sewa Tersisa <?= $sisa ?> Hari</div>
        <div style="font-size:.9rem">
            Masa sewa kamar Anda akan berakhir pada <strong><?= $tgl_jt ?></strong>.
            Jangan lupa perpanjang sebelum tenggat.
        </div>
        <a href="pembayaran.php" class="btn btn-warning btn-sm mt-2">
            <i class="bi bi-cash-coin me-1"></i>Perpanjang Sewa
        </a>
    </div>
</div>
<?php elseif ($sisa <= 14): ?>
<div class="alert alert-info d-flex align-items-start gap-3 mb-4" style="border-radius:12px">
    <i class="bi bi-info-circle-fill fs-4 flex-shrink-0 mt-1"></i>
    <div>
        <div class="fw-700">Info: Masa Sewa Tersisa <?= $sisa ?> Hari</div>
        <div style="font-size:.9rem">
            Masa sewa kamar Anda akan berakhir pada <strong><?= $tgl_jt ?></strong>.
        </div>
    </div>
</div>
<?php endif; endif; ?>

<!-- Greeting -->
<div class="p-4 mb-4 rounded-3" style="background:linear-gradient(135deg,#2563EB,#7c3aed);color:#fff">
    <div class="d-flex align-items-center gap-3">
        <div style="width:56px;height:56px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700">
            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
        </div>
        <div>
            <h5 class="mb-1 fw-700">Selamat datang, <?= htmlspecialchars($user['nama']) ?>! 👋</h5>
            <p class="mb-0 opacity-75" style="font-size:.9rem">
                <?= $user['role'] === 'penghuni' ? 'Penghuni Kamar ' . ($user['nomor_kamar'] ?? '-') : 'Calon Penghuni' ?>
            </p>
        </div>
    </div>
</div>

<!-- Notifikasi belum dibaca -->
<?php if ($notifs && $notifs->num_rows > 0): ?>
<div class="mb-4">
    <?php while ($n = $notifs->fetch_assoc()):
        $cls = ['success'=>'success','danger'=>'danger','warning'=>'warning','info'=>'info'][$n['tipe']] ?? 'info'; ?>
    <div class="alert alert-<?= $cls ?> d-flex align-items-start gap-2 mb-2">
        <i class="bi bi-bell-fill mt-1"></i>
        <div>
            <strong><?= htmlspecialchars($n['judul']) ?></strong><br>
            <span style="font-size:.88rem"><?= htmlspecialchars($n['pesan']) ?></span>
        </div>
    </div>
    <?php endwhile; ?>
    <a href="../includes/mark_read.php" class="btn btn-sm btn-outline-secondary">Tandai semua dibaca</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-6">
        <div class="stat-card text-center">
            <div style="font-size:2rem">🏠</div>
            <div class="fw-700 mt-1"><?= $user['nomor_kamar'] ?: 'Belum ada' ?></div>
            <div class="text-muted" style="font-size:.82rem">Kamar Saya</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="stat-card text-center">
            <div style="font-size:2rem">📋</div>
            <div class="fw-700 mt-1"><?= $total_aduan ?></div>
            <div class="text-muted" style="font-size:.82rem">Total Aduan</div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="stat-card text-center">
            <div style="font-size:2rem">💰</div>
            <div class="fw-700 mt-1 <?= $pending_bayar > 0 ? 'text-warning' : 'text-success' ?>"><?= $pending_bayar > 0 ? $pending_bayar.' Pending' : 'Lunas' ?></div>
            <div class="text-muted" style="font-size:.82rem">Status Bayar</div>
        </div>
    </div>
</div>

<!-- Info Kamar -->
<?php if ($kamar_info): ?>
<div class="card mb-4">
    <div class="card-header py-3"><i class="bi bi-door-open me-2"></i>Informasi Kamar Saya</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="text-muted" style="font-size:.8rem">Nomor Kamar</div>
                <div class="fw-700 text-primary fs-4"><?= $kamar_info['nomor_kamar'] ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted" style="font-size:.8rem">Tipe</div>
                <div class="fw-600"><?= $kamar_info['tipe'] ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted" style="font-size:.8rem">Ukuran</div>
                <div class="fw-600"><?= $kamar_info['ukuran'] ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted" style="font-size:.8rem">Harga/Bulan</div>
                <div class="fw-700 text-primary"><?= formatRupiah($kamar_info['harga_per_bulan']) ?></div>
            </div>
            <div class="col-12">
                <div class="text-muted" style="font-size:.8rem">Fasilitas</div>
                <div><?= htmlspecialchars($kamar_info['fasilitas']) ?></div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card mb-4 border-warning">
    <div class="card-body text-center py-4">
        <div style="font-size:3rem">🏠</div>
        <h6 class="mt-2">Anda belum memiliki kamar</h6>
        <p class="text-muted" style="font-size:.9rem">Pesan kamar sekarang untuk mendapatkan fasilitas lengkap Wisma Permata</p>
        <a href="pemesanan.php" class="btn btn-primary">Pesan Kamar</a>
    </div>
</div>
<?php endif; ?>

<!-- Quick Links -->
<div class="row g-3">
    <?php $links = [
        ['pemesanan.php','bi-calendar-plus','Pesan Kamar','Lihat dan pesan kamar tersedia','primary'],
        ['pembayaran.php','bi-cash-coin','Pembayaran Sewa','Bayar sewa dan lihat riwayat','success'],
        ['aduan.php','bi-chat-square-warning','Laporan Aduan','Sampaikan keluhan Anda','danger'],
    ];
    foreach($links as $l): ?>
    <div class="col-md-4 col-6">
        <a href="<?= $l[0] ?>" class="text-decoration-none">
            <div class="stat-card text-center h-100">
                <div class="feature-icon mx-auto mb-2" style="background:var(--bs-<?= $l[4] ?>-bg, #EFF6FF)">
                    <i class="bi <?= $l[1] ?> text-<?= $l[4] ?>"></i>
                </div>
                <div class="fw-700"><?= $l[2] ?></div>
                <div class="text-muted" style="font-size:.78rem"><?= $l[3] ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

</div>
<?php include $depth . 'includes/footer.php'; ?>