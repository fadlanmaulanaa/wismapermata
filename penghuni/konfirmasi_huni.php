<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni'], $depth . 'login.php');
$page_title = 'Konfirmasi Kelanjutan Kost';
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $pilihan = in_array($_POST['pilihan'] ?? '', ['lanjut','keluar']) ? $_POST['pilihan'] : null;

    if ($pilihan) {
        $k = $conn->query("SELECT * FROM konfirmasi_huni WHERE id=$id AND user_id=$uid AND status='menunggu'")->fetch_assoc();
        if ($k) {
            $conn->query("UPDATE konfirmasi_huni SET status='$pilihan', tanggal_respon=NOW() WHERE id=$id");

            $uname = getUser()['nama'];
            $label = $pilihan === 'lanjut' ? 'akan melanjutkan' : 'akan mengakhiri';
            $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
            while ($a = $admins->fetch_assoc()) {
                addNotif($conn, $a['id'], 'Konfirmasi Huni Diterima 📋',
                    "$uname $label masa kost untuk bulan {$k['periode_bulan']}.",
                    'info', 'laporan_huni.php');
            }
            $msg = 'success|Terima kasih, jawaban Anda sudah kami catat.';
        }
    }
}

$pending = $conn->query("SELECT * FROM konfirmasi_huni WHERE user_id=$uid AND status='menunggu' ORDER BY id DESC");
$riwayat = $conn->query("SELECT * FROM konfirmasi_huni WHERE user_id=$uid AND status != 'menunggu' ORDER BY id DESC LIMIT 10");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<?php if($pending->num_rows > 0): ?>
<?php while($p = $pending->fetch_assoc()): ?>
<div class="card mb-4 border-warning">
    <div class="card-header py-3 fw-700 bg-warning bg-opacity-25">
        <i class="bi bi-exclamation-circle me-2"></i>Konfirmasi Diperlukan
    </div>
    <div class="card-body">
        <p class="mb-3">
            Pembayaran sewa bulan <strong><?= $p['periode_bulan'] ?></strong> akan segera jatuh tempo.
            Apakah Anda akan <strong>melanjutkan</strong> masa kost, atau <strong>mengakhiri</strong> sewa di bulan ini?
        </p>
        <form method="POST" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" name="pilihan" value="lanjut" class="btn btn-success px-4" onclick="return confirm('Konfirmasi: Anda akan MELANJUTKAN kost?')">
                <i class="bi bi-check-circle me-1"></i>Lanjut Ngekost
            </button>
            <button type="submit" name="pilihan" value="keluar" class="btn btn-outline-danger px-4" onclick="return confirm('Konfirmasi: Anda akan KELUAR / tidak melanjutkan kost?')">
                <i class="bi bi-box-arrow-right me-1"></i>Tidak Lanjut / Keluar
            </button>
        </form>
    </div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="card mb-4">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
        Tidak ada konfirmasi yang perlu diisi saat ini.
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header py-3 fw-700"><i class="bi bi-clock-history me-2"></i>Riwayat Konfirmasi</div>
    <div class="card-body p-0">
        <?php if($riwayat->num_rows === 0): ?>
        <div class="text-center py-4 text-muted" style="font-size:.9rem">Belum ada riwayat.</div>
        <?php else: ?>
        <table class="table mb-0">
            <thead><tr><th>Periode</th><th>Jawaban</th><th>Tgl Respon</th></tr></thead>
            <tbody>
            <?php while($r = $riwayat->fetch_assoc()): ?>
            <tr>
                <td><?= $r['periode_bulan'] ?></td>
                <td>
                    <?php if($r['status'] === 'lanjut'): ?>
                    <span class="badge bg-success">Lanjut</span>
                    <?php else: ?>
                    <span class="badge bg-danger">Keluar</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.85rem"><?= $r['tanggal_respon'] ? date('d M Y H:i', strtotime($r['tanggal_respon'])) : '-' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>