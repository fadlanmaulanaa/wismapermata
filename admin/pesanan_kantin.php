<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pegawai'], $depth . 'admin/dashboard.php'); // Khusus pegawai — pemilik tidak bisa akses halaman ini
$page_title = 'Pesanan Kantin';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)$_POST['id'];
    $act = $conn->real_escape_string($_POST['action']);
    $uid = $_SESSION['user_id'];
    $p   = $conn->query("SELECT * FROM pemesanan_kantin WHERE id=$id")->fetch_assoc();
    $conn->query("UPDATE pemesanan_kantin SET status='$act',diproses_oleh=$uid WHERE id=$id");
    $pesan = ['diproses'=>'Pesanan sedang disiapkan 🍽️','diantar'=>'Pesanan sedang diantar ke kamar Anda 🚶','selesai'=>'Pesanan telah selesai ✅','ditolak'=>'Pesanan ditolak ❌'];
    if (isset($pesan[$act])) {
        addNotif($conn, $p['user_id'], ucfirst($act), $pesan[$act], $act==='ditolak'?'danger':($act==='selesai'?'success':'info'), 'riwayat_kantin.php');
    }
    $msg = 'success|Status pesanan diperbarui.';
}
$data = $conn->query("SELECT pk.*,u.nama,u.nomor_kamar,
    GROUP_CONCAT(mk.nama_menu,' (x',dpk.jumlah,')' SEPARATOR ', ') AS items
    FROM pemesanan_kantin pk JOIN users u ON u.id=pk.user_id
    LEFT JOIN detail_pemesanan_kantin dpk ON dpk.pemesanan_id=pk.id
    LEFT JOIN menu_kantin mk ON mk.id=dpk.menu_id
    GROUP BY pk.id ORDER BY pk.tanggal_pesan DESC");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m)=explode('|',$msg,2); echo alert($t,$m); endif; ?>
<div class="card">
    <div class="card-header py-3"><i class="bi bi-bag-check me-2"></i>Daftar Pesanan Kantin</div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Penghuni</th><th>Kamar</th><th>Menu</th><th>Total</th><th>Bayar</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $no=1; while($r=$data->fetch_assoc()): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
            <td><span class="badge bg-light text-dark"><?= $r['nomor_kamar']??'-' ?></span></td>
            <td style="font-size:.82rem;max-width:200px"><?= htmlspecialchars($r['items']??'-') ?></td>
            <td class="fw-700 text-primary"><?= formatRupiah($r['total_harga']) ?></td>
            <td><span class="badge bg-<?= $r['metode_bayar']==='qris'?'info':'secondary' ?>"><?= strtoupper($r['metode_bayar']) ?></span></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td style="font-size:.8rem"><?= timeAgo($r['tanggal_pesan']) ?></td>
            <td>
                <?php if($r['status']==='pending'): ?>
                <form method="POST" class="d-inline"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="diproses"><button class="btn btn-sm btn-info text-white">Proses</button></form>
                <form method="POST" class="d-inline"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="ditolak"><button class="btn btn-sm btn-danger">Tolak</button></form>
                <?php elseif($r['status']==='diproses'): ?>
                <form method="POST" class="d-inline"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="diantar"><button class="btn btn-sm btn-warning">Antar</button></form>
                <?php elseif($r['status']==='diantar'): ?>
                <form method="POST" class="d-inline"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="selesai"><button class="btn btn-sm btn-success">Selesai</button></form>
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