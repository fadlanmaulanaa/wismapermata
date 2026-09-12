<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni','calon_penghuni'], $depth . 'login.php');
$page_title = 'Info Kamar';
$uid  = $_SESSION['user_id'];
$user = getUser();
$kamar_info = null;
if ($user['nomor_kamar']) {
    $kamar_info = $conn->query("SELECT * FROM kamar WHERE nomor_kamar='" . $conn->real_escape_string($user['nomor_kamar']) . "'")->fetch_assoc();
}
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($kamar_info): ?>
<div class="card">
    <div class="card-header py-3 fw-700"><i class="bi bi-door-open me-2"></i>Detail Kamar Saya</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <table class="table">
                    <tr><th width="150">No. Kamar</th><td><strong class="text-primary fs-4"><?= $kamar_info['nomor_kamar'] ?></strong></td></tr>
                    <tr><th>Tipe</th><td><span class="tipe-badge tipe-<?= strtolower($kamar_info['tipe']) ?>"><?= $kamar_info['tipe'] ?></span></td></tr>
                    <tr><th>Ukuran</th><td><?= $kamar_info['ukuran'] ?></td></tr>
                    <tr><th>Lantai</th><td><?= $kamar_info['lantai'] ?></td></tr>
                    <tr><th>Harga/Bulan</th><td><strong class="text-primary"><?= formatRupiah($kamar_info['harga_per_bulan']) ?></strong></td></tr>
                    <tr><th>Status</th><td><?= statusBadge($kamar_info['status']) ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="fw-600 mb-2">Fasilitas:</div>
                <div class="p-3 bg-light rounded-3"><?= htmlspecialchars($kamar_info['fasilitas']) ?></div>
                <div class="fw-600 mb-2 mt-3">Deskripsi:</div>
                <div class="p-3 bg-light rounded-3"><?= htmlspecialchars($kamar_info['deskripsi']) ?></div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card border-warning">
    <div class="card-body text-center py-5">
        <i class="bi bi-house-slash fs-1 text-warning d-block mb-3"></i>
        <h5>Anda belum memiliki kamar aktif</h5>
        <p class="text-muted">Pesan kamar terlebih dahulu untuk dapat melihat detail kamar Anda.</p>
        <a href="pemesanan.php" class="btn btn-primary">Pesan Kamar Sekarang</a>
    </div>
</div>
<?php endif; ?>
</div>
<?php include $depth . 'includes/footer.php'; ?>
