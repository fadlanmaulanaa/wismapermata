<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Pembayaran Sewa';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)$_POST['id'];
    $act = $_POST['action'];
    $uid = $_SESSION['user_id'];
    $p   = $conn->query("SELECT * FROM pembayaran_sewa WHERE id=$id")->fetch_assoc();
    if ($act === 'verify') {
        $conn->query("UPDATE pembayaran_sewa SET status='verified',diverifikasi_oleh=$uid WHERE id=$id");

        // Pembayaran terverifikasi = penyewa resmi mulai menghuni:
        // kamar langsung jadi 'terisi', dan pemesanan yang tadinya 'disetujui' ditandai 'selesai'.
        $conn->query("UPDATE kamar SET status='terisi' WHERE id={$p['kamar_id']}");
        $conn->query("UPDATE pemesanan_kamar SET status='selesai'
            WHERE user_id={$p['user_id']} AND kamar_id={$p['kamar_id']} AND status='disetujui'");

        addNotif($conn, $p['user_id'], 'Pembayaran Terverifikasi ✅', 'Pembayaran sewa bulan '.$p['bulan_bayar'].' telah dikonfirmasi. Kamar Anda kini berstatus aktif dihuni.', 'success', 'pembayaran.php');
        $msg = 'success|Pembayaran diverifikasi. Kamar otomatis berstatus Terisi.';
    } elseif ($act === 'reject') {
        $conn->query("UPDATE pembayaran_sewa SET status='ditolak' WHERE id=$id");
        addNotif($conn, $p['user_id'], 'Pembayaran Ditolak ❌', 'Pembayaran sewa bulan '.$p['bulan_bayar'].' ditolak. Silakan kirim ulang.', 'danger', 'pembayaran.php');
        $msg = 'danger|Pembayaran ditolak.';
    }
}
$data = $conn->query("SELECT ps.*,u.nama,u.nomor_kamar,k.tipe FROM pembayaran_sewa ps
    JOIN users u ON u.id=ps.user_id JOIN kamar k ON k.id=ps.kamar_id ORDER BY ps.created_at DESC");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m)=explode('|',$msg,2); echo alert($t,$m); endif; ?>
<div class="card">
    <div class="card-header py-3"><i class="bi bi-cash-coin me-2"></i>Daftar Pembayaran Sewa</div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Nama</th><th>Kamar</th><th>Cakupan Bulan</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th>Tgl</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $no=1; while($r=$data->fetch_assoc()): ?>
        <?php
            $jb = max(1, (int)($r['jumlah_bulan'] ?? 1));
            $bulan_akhir_r = date('Y-m', strtotime($r['bulan_bayar'] . '-01 +' . ($jb - 1) . ' months'));
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
            <td><span class="badge bg-light text-dark"><?= $r['nomor_kamar'] ?></span></td>
            <td>
                <div><?= namaBulanIndo($r['bulan_bayar']) ?><?= $jb > 1 ? ' s/d ' . namaBulanIndo($bulan_akhir_r) : '' ?></div>
                <span class="badge <?= $jb === 1 ? 'bg-light text-dark' : ($jb >= 12 ? 'bg-primary' : 'bg-info text-dark') ?>" style="font-size:.72rem">
                    <?= $jb ?> Bulan<?= $jb === 12 ? ' (1 Tahun)' : '' ?>
                </span>
            </td>
            <td class="fw-700 text-primary"><?= formatRupiah($r['jumlah']) ?></td>
            <td>
                <?php if(!empty($r['bukti_bayar'])): ?>
                <a href="<?= $depth ?>uploads/bukti_bayar/<?= urlencode($r['bukti_bayar']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-image"></i> Lihat
                </a>
                <?php else: ?>
                <span class="text-muted" style="font-size:.8rem">-</span>
                <?php endif; ?>
            </td>
            <td><?= statusBadge($r['status']) ?></td>
            <td style="font-size:.82rem"><?= date('d M Y',strtotime($r['tanggal_bayar'])) ?></td>
            <td>
                <?php if($r['status']==='pending'): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="verify">
                    <button class="btn btn-sm btn-success" onclick="return confirm('Verifikasi pembayaran ini?')"><i class="bi bi-check-lg"></i></button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-sm btn-danger" onclick="return confirm('Tolak pembayaran ini?')"><i class="bi bi-x-lg"></i></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>