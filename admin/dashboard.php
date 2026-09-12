<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');

$page_title = 'Dashboard Admin';

// Stats
$total_kamar    = $conn->query("SELECT COUNT(*) c FROM kamar")->fetch_assoc()['c'];
$tersedia       = $conn->query("SELECT COUNT(*) c FROM kamar WHERE status='tersedia'")->fetch_assoc()['c'];
$terisi         = $conn->query("SELECT COUNT(*) c FROM kamar WHERE status='terisi'")->fetch_assoc()['c'];
$total_penghuni = $conn->query("SELECT COUNT(*) c FROM users WHERE role='penghuni' AND status='aktif'")->fetch_assoc()['c'];
$pending_pesan  = $conn->query("SELECT COUNT(*) c FROM pemesanan_kamar WHERE status='pending'")->fetch_assoc()['c'];
$pending_bayar  = $conn->query("SELECT COUNT(*) c FROM pembayaran_sewa WHERE status='pending'")->fetch_assoc()['c'];
$aduan_open     = $conn->query("SELECT COUNT(*) c FROM laporan_aduan WHERE status IN ('pending','ditangani')")->fetch_assoc()['c'];
$pendapatan     = $conn->query("SELECT SUM(jumlah) s FROM pembayaran_sewa WHERE status='verified' AND MONTH(tanggal_bayar)=MONTH(NOW())")->fetch_assoc()['s'] ?? 0;

// Recent pemesanan
$rec_pesan = $conn->query("SELECT pk.*,u.nama,k.nomor_kamar,k.tipe FROM pemesanan_kamar pk
    JOIN users u ON u.id=pk.user_id JOIN kamar k ON k.id=pk.kamar_id
    ORDER BY pk.created_at DESC LIMIT 5");

// Recent aduan
$rec_aduan = $conn->query("SELECT la.*,u.nama FROM laporan_aduan la
    JOIN users u ON u.id=la.user_id ORDER BY la.tanggal_aduan DESC LIMIT 5");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>

<div class="main-content">

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php $stats = [
        ['Kamar Tersedia','bi-door-open',$tersedia,'/'.$total_kamar.' kamar','#E7F1EE','#0F6B5C'],
        ['Penghuni Aktif','bi-people',$total_penghuni,'orang','#EEF2E6','#3F7A3E'],
        ['Pendapatan Bulan Ini','bi-cash-coin',formatRupiah($pendapatan),'','#FAEEDE','#C2711E'],
        ['Aduan Aktif','bi-chat-square-warning',$aduan_open,'perlu ditangani','#F1EAF5','#6B3FA0'],
    ]; foreach($stats as $s): ?>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:<?= $s[4] ?>;color:<?= $s[5] ?>">
                    <i class="bi <?= $s[1] ?>"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:.82rem"><?= $s[0] ?></div>
                    <div class="fw-700" style="font-size:1.3rem"><?= $s[2] ?></div>
                    <?php if($s[3]): ?><div class="text-muted" style="font-size:.78rem"><?= $s[3] ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pending Alerts -->
<?php if($pending_pesan + $pending_bayar > 0): ?>
<div class="row g-3 mb-4">
    <?php if($pending_pesan > 0): ?>
    <div class="col-auto">
        <a href="pemesanan_kamar.php" class="btn btn-warning">
            <i class="bi bi-calendar-check me-2"></i><?= $pending_pesan ?> Pemesanan Kamar Pending
        </a>
    </div>
    <?php endif; ?>
    <?php if($pending_bayar > 0): ?>
    <div class="col-auto">
        <a href="pembayaran.php" class="btn btn-info text-white">
            <i class="bi bi-cash-coin me-2"></i><?= $pending_bayar ?> Pembayaran Perlu Verifikasi
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Status Kamar Grid -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span><i class="bi bi-door-open me-2"></i>Status Kamar (<?= $total_kamar ?> Kamar)</span>
        <a href="kamar.php" class="btn btn-sm btn-outline-primary">Kelola Kamar</a>
    </div>
    <div class="card-body">
        <div class="d-flex gap-3 mb-3 flex-wrap">
            <span class="badge bg-success p-2"><i class="bi bi-circle-fill me-1"></i>Tersedia: <?= $tersedia ?></span>
            <span class="badge bg-warning text-dark p-2"><i class="bi bi-circle-fill me-1"></i>Dipesan: <?= $conn->query("SELECT COUNT(*) c FROM kamar WHERE status='dipesan'")->fetch_assoc()['c'] ?></span>
            <span class="badge bg-danger p-2"><i class="bi bi-circle-fill me-1"></i>Terisi: <?= $terisi ?></span>
        </div>
        <div class="row g-2">
        <?php
        $all_kamar = $conn->query("SELECT nomor_kamar,tipe,status,harga_per_bulan FROM kamar ORDER BY lantai,nomor_kamar");
        while($k = $all_kamar->fetch_assoc()):
            $c = ['tersedia'=>'success','dipesan'=>'warning','terisi'=>'danger'];
        ?>
        <div class="col-lg-1 col-md-2 col-3">
            <div class="border rounded-2 p-2 text-center border-<?= $c[$k['status']] ?> bg-<?= $c[$k['status']] ?> bg-opacity-10">
                <div class="fw-700 text-<?= $c[$k['status']] ?>" style="font-size:.9rem"><?= $k['nomor_kamar'] ?></div>
                <div style="font-size:.65rem;color:#64748B"><?= $k['tipe'] ?></div>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Pemesanan -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between">
                <span><i class="bi bi-calendar-check me-2"></i>Pemesanan Kamar Terbaru</span>
                <a href="pemesanan_kamar.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Nama</th><th>Kamar</th><th>Status</th><th>Waktu</th></tr></thead>
                    <tbody>
                    <?php while($p = $rec_pesan->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= $p['nomor_kamar'] ?></span></td>
                        <td><?= statusBadge($p['status']) ?></td>
                        <td style="font-size:.8rem"><?= timeAgo($p['created_at']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Aduan -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between">
                <span><i class="bi bi-chat-square-warning me-2"></i>Aduan Terbaru</span>
                <a href="aduan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Nama</th><th>Kategori</th><th>Status</th><th>Waktu</th></tr></thead>
                    <tbody>
                    <?php while($a = $rec_aduan->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= ucfirst($a['kategori']) ?></span></td>
                        <td><?= statusBadge($a['status']) ?></td>
                        <td style="font-size:.8rem"><?= timeAgo($a['tanggal_aduan']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>

<?php include $depth . 'includes/footer.php'; ?>