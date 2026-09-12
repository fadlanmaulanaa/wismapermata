<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Pemesanan Kamar';

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];
    $uid    = $_SESSION['user_id'];

    if($action === 'setujui') {
        $p = $conn->query("SELECT * FROM pemesanan_kamar WHERE id=$id")->fetch_assoc();

        // Guard: tolak otomatis jika pemesan ternyata sudah punya kamar aktif
        // (mencegah race-condition atau pengesahan ganda oleh admin berbeda)
        $user_aktif = $conn->query("SELECT nomor_kamar, role FROM users WHERE id={$p['user_id']}")->fetch_assoc();
        if ($user_aktif && $user_aktif['role'] === 'penghuni' && !empty($user_aktif['nomor_kamar'])) {
            // Batalkan pemesanan ini dan kembalikan status kamar ke tersedia
            $conn->query("UPDATE pemesanan_kamar SET status='ditolak', diproses_oleh=$uid WHERE id=$id");
            $conn->query("UPDATE kamar SET status='tersedia' WHERE id={$p['kamar_id']}");
            addNotif($conn, $p['user_id'], 'Pemesanan Tidak Dapat Disetujui ⚠️',
                'Pemesanan kamar Anda tidak dapat disetujui karena Anda sudah memiliki kamar aktif (Kamar '.$user_aktif['nomor_kamar'].'). Satu akun hanya dapat menghuni satu kamar.',
                'warning', '');
            $msg = 'warning|Pemesanan ditolak otomatis: pemesan sudah memiliki kamar aktif (' . $user_aktif['nomor_kamar'] . ').';
        } else {
            $conn->query("UPDATE pemesanan_kamar SET status='disetujui',diproses_oleh=$uid,batas_bayar=DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id=$id");
            $conn->query("UPDATE kamar SET status='dipesan' WHERE id={$p['kamar_id']}");
            // Update role user menjadi penghuni dan assign nomor kamar
            $conn->query("UPDATE users SET role='penghuni',status='aktif',nomor_kamar=(SELECT nomor_kamar FROM kamar WHERE id={$p['kamar_id']}) WHERE id={$p['user_id']}");
            addNotif($conn, $p['user_id'], 'Pemesanan Disetujui ✅', 'Pemesanan kamar Anda telah disetujui. Silakan lakukan pembayaran sewa dalam 24 jam, jika tidak pemesanan akan otomatis dibatalkan.', 'success', 'pembayaran.php');
            $msg = 'success|Pemesanan disetujui. Penyewa punya waktu 24 jam untuk membayar.';
        } // end guard

    } elseif($action === 'tolak') {
        $p = $conn->query("SELECT * FROM pemesanan_kamar WHERE id=$id")->fetch_assoc();
        $alasan = $conn->real_escape_string($_POST['alasan'] ?? 'Tidak memenuhi persyaratan');
        $conn->query("UPDATE pemesanan_kamar SET status='ditolak',diproses_oleh=$uid WHERE id=$id");
        // Kamar dikunci ('dipesan') sejak pemesanan diajukan, jadi saat ditolak
        // harus dikembalikan ke 'tersedia' supaya bisa dipesan penghuni lain lagi.
        $conn->query("UPDATE kamar SET status='tersedia' WHERE id={$p['kamar_id']}");
        addNotif($conn, $p['user_id'], 'Pemesanan Ditolak ❌', 'Pemesanan kamar Anda ditolak. Alasan: '.$alasan, 'danger', '');
        $msg = 'danger|Pemesanan ditolak.';

    } elseif($action === 'selesai') {
        $p = $conn->query("SELECT * FROM pemesanan_kamar WHERE id=$id")->fetch_assoc();
        $conn->query("UPDATE pemesanan_kamar SET status='selesai' WHERE id=$id");
        $conn->query("UPDATE kamar SET status='terisi' WHERE id={$p['kamar_id']}");
        $msg = 'success|Status kamar diperbarui menjadi terisi.';
    }
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE pk.status='$filter'" : '';
$data   = $conn->query("SELECT pk.*,u.nama,u.nomor_telepon,k.nomor_kamar,k.tipe,k.harga_per_bulan
    FROM pemesanan_kamar pk JOIN users u ON u.id=pk.user_id JOIN kamar k ON k.id=pk.kamar_id
    $where ORDER BY pk.created_at DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-calendar-check me-2"></i>Daftar Pemesanan Kamar</span>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach([''=>'Semua','pending'=>'Pending','disetujui'=>'Disetujui','ditolak'=>'Ditolak','selesai'=>'Selesai'] as $v=>$l): ?>
            <a href="?status=<?= $v ?>" class="btn btn-sm <?= $filter===$v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Pemesan</th>
                    <th>Kamar</th>
                    <th>Tipe</th>
                    <th>Harga/Bln</th>
                    <th>Tgl Pesan</th>
                    <th>Tgl Mulai</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($r = $data->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <div class="fw-600"><?= htmlspecialchars($r['nama']) ?></div>
                    <div class="text-muted" style="font-size:.8rem"><?= $r['nomor_telepon'] ?></div>
                </td>
                <td><span class="badge bg-light text-dark fw-700"><?= $r['nomor_kamar'] ?></span></td>
                <td><span class="tipe-badge tipe-<?= strtolower($r['tipe']) ?>"><?= $r['tipe'] ?></span></td>
                <td class="fw-600 text-primary"><?= formatRupiah($r['harga_per_bulan']) ?></td>
                <td style="font-size:.82rem"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td style="font-size:.82rem"><?= $r['tanggal_mulai'] ? date('d M Y', strtotime($r['tanggal_mulai'])) : '-' ?></td>
                <td><?= statusBadge($r['status']) ?></td>
                <td>
                    <?php if($r['status'] === 'pending'): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Setujui pemesanan ini?')">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="action" value="setujui">
                        <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <button class="btn btn-sm btn-danger" onclick="tolakModal(<?= $r['id'] ?>)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <?php elseif($r['status'] === 'disetujui'): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Tandai kamar sebagai terisi?')">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="action" value="selesai">
                        <button class="btn btn-sm btn-primary">Terisi</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:.8rem">—</span>
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

<!-- Modal Tolak -->
<div class="modal fade" id="tolakModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
          <h5 class="modal-title">Tolak Pemesanan</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="tolak">
        <input type="hidden" name="id" id="tolak_id">
        <div class="modal-body">
            <label class="form-label fw-600">Alasan Penolakan</label>
            <textarea name="alasan" class="form-control" rows="3" placeholder="Isi alasan penolakan..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-danger">Tolak Pemesanan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include $depth . 'includes/footer.php'; ?>
<script>
function tolakModal(id) {
    document.getElementById('tolak_id').value = id;
    new bootstrap.Modal(document.getElementById('tolakModal')).show();
}
</script>