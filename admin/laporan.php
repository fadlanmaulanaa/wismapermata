<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik'], $depth . 'login.php');
$page_title = 'Laporan & Rekap';
$bulan = $_GET['bulan'] ?? date('Y-m');
$bln   = $conn->real_escape_string($bulan);
$total_bayar = $conn->query("SELECT SUM(jumlah) s FROM pembayaran_sewa WHERE status='verified' AND bulan_bayar='$bln'")->fetch_assoc()['s'] ?? 0;
$total_aduan = $conn->query("SELECT COUNT(*) c FROM laporan_aduan WHERE DATE_FORMAT(tanggal_aduan,'%Y-%m')='$bln'")->fetch_assoc()['c'];
$penghuni_aktif = $conn->query("SELECT COUNT(*) c FROM users WHERE role='penghuni' AND status='aktif'")->fetch_assoc()['c'];
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<div class="d-flex align-items-center gap-3 mb-4">
    <label class="fw-600">Filter Bulan:</label>
    <form method="GET" class="d-flex gap-2">
        <input type="month" name="bulan" class="form-control" value="<?= $bulan ?>" style="width:auto">
        <button class="btn btn-primary">Tampilkan</button>
    </form>
</div>
<div class="row g-3 mb-4">
    <?php $stats=[
        ['Pendapatan Sewa','bi-cash-coin',formatRupiah($total_bayar),'Bulan ini','#E7F1EE','#0F6B5C'],
        ['Aduan Masuk','bi-chat-square-warning',$total_aduan,'Bulan ini','#F1EAF5','#6B3FA0'],
        ['Penghuni Aktif','bi-people',$penghuni_aktif,'Orang','#EEF2E6','#3F7A3E'],
    ]; foreach($stats as $s): ?>
    <div class="col-md-4 col-6">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:<?=$s[4]?>;color:<?=$s[5]?>"><i class="bi <?=$s[1]?>"></i></div>
                <div>
                    <div class="text-muted" style="font-size:.82rem"><?=$s[0]?></div>
                    <div class="fw-700" style="font-size:1.1rem"><?=$s[2]?></div>
                    <div class="text-muted" style="font-size:.78rem"><?=$s[3]?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header py-3 fw-700">Pembayaran Sewa Bulan <?= $bulan ?></div>
    <div class="card-body p-0">
    <table class="table mb-0">
        <thead><tr><th>Nama</th><th>Kamar</th><th>Jumlah</th><th>Status</th></tr></thead>
        <tbody>
        <?php $q=$conn->query("SELECT ps.*,u.nama,k.nomor_kamar FROM pembayaran_sewa ps JOIN users u ON u.id=ps.user_id JOIN kamar k ON k.id=ps.kamar_id WHERE ps.bulan_bayar='$bln' ORDER BY ps.tanggal_bayar DESC");
        while($r=$q->fetch_assoc()): ?>
        <tr><td><?=htmlspecialchars($r['nama'])?></td><td><?=$r['nomor_kamar']?></td><td class="text-primary fw-600"><?=formatRupiah($r['jumlah'])?></td><td><?=statusBadge($r['status'])?></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>