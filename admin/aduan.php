<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Laporan Aduan';

$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];
    $uid    = $_SESSION['user_id'];
    $a      = $conn->query("SELECT * FROM laporan_aduan WHERE id=$id")->fetch_assoc();

    if($action === 'tangani') {
        $catatan  = $conn->real_escape_string($_POST['catatan'] ?? '');
        $teknisi  = $conn->real_escape_string($_POST['teknisi'] ?? '');
        $conn->query("UPDATE laporan_aduan SET status='ditangani',catatan_pengelola='$catatan',teknisi='$teknisi',ditangani_oleh=$uid WHERE id=$id");
        addNotif($conn, $a['user_id'], 'Aduan Sedang Ditangani 🔧', 'Aduan Anda "'.$a['judul'].'" sedang dalam proses penanganan.', 'info', 'aduan.php');
        $msg = 'success|Aduan ditandai sedang ditangani.';
    } elseif($action === 'selesai') {
        $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');
        $conn->query("UPDATE laporan_aduan SET status='selesai',catatan_pengelola='$catatan',tanggal_selesai=NOW() WHERE id=$id");
        addNotif($conn, $a['user_id'], 'Aduan Selesai Ditangani ✅', 'Aduan Anda "'.$a['judul'].'" telah selesai ditangani.', 'success', 'aduan.php');
        $msg = 'success|Aduan ditandai selesai.';
    } elseif($action === 'tolak') {
        $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');
        $conn->query("UPDATE laporan_aduan SET status='ditolak',catatan_pengelola='$catatan' WHERE id=$id");
        addNotif($conn, $a['user_id'], 'Aduan Ditolak ❌', 'Aduan Anda "'.$a['judul'].'" tidak dapat diproses.', 'danger', 'aduan.php');
        $msg = 'danger|Aduan ditolak.';
    }
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE la.status='$filter'" : '';
$data   = $conn->query("SELECT la.*,u.nama,u.nomor_kamar FROM laporan_aduan la
    JOIN users u ON u.id=la.user_id $where ORDER BY la.tanggal_aduan DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-chat-square-warning me-2"></i>Laporan Aduan Penghuni</span>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach([''=>'Semua','pending'=>'Pending','ditangani'=>'Ditangani','selesai'=>'Selesai','ditolak'=>'Ditolak'] as $v=>$l): ?>
            <a href="?status=<?= $v ?>" class="btn btn-sm <?= $filter===$v ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Penghuni</th><th>Kamar</th><th>Kategori</th><th>Judul Aduan</th><th>Status</th><th>Teknisi</th><th>Tgl Aduan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $no=1; while($r = $data->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
                <td><span class="badge bg-light text-dark"><?= $r['nomor_kamar'] ?: '-' ?></span></td>
                <td><span class="badge bg-secondary"><?= ucfirst($r['kategori']) ?></span></td>
                <td>
                    <div class="fw-600" style="max-width:180px"><?= htmlspecialchars($r['judul']) ?>
                        <?php if(!empty($r['foto'])): ?><i class="bi bi-camera-fill text-primary ms-1" style="font-size:.75rem" title="Ada foto bukti"></i><?php endif; ?>
                    </div>
                    <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars(substr($r['deskripsi'],0,50)) ?>...</div>
                </td>
                <td><?= statusBadge($r['status']) ?></td>
                <td style="font-size:.82rem"><?= $r['teknisi'] ?: '-' ?></td>
                <td style="font-size:.82rem"><?= date('d M Y', strtotime($r['tanggal_aduan'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick='detailAduan(<?= json_encode($r) ?>)'>
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if($r['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-warning" onclick="aksModal(<?= $r['id'] ?>,'tangani')">Tangani</button>
                    <?php elseif($r['status'] === 'ditangani'): ?>
                    <button class="btn btn-sm btn-success" onclick="aksModal(<?= $r['id'] ?>,'selesai')">Selesai</button>
                    <button class="btn btn-sm btn-danger" onclick="aksModal(<?= $r['id'] ?>,'tolak')">Tolak</button>
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

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detail Aduan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="detailBody"></div>
    </div>
  </div>
</div>

<!-- Modal Aksi -->
<div class="modal fade" id="aksModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="aksTitle">Aksi Aduan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <input type="hidden" name="id" id="aks_id">
        <input type="hidden" name="action" id="aks_action">
        <div class="modal-body">
            <div id="teknisiDiv" class="mb-3 d-none">
                <label class="form-label fw-600">Nama Teknisi (jika ada kerusakan)</label>
                <input type="text" name="teknisi" class="form-control" placeholder="Nama teknisi yang ditugaskan">
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Catatan Pengelola</label>
                <textarea name="catatan" class="form-control" rows="3" placeholder="Isi catatan penanganan..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-primary" id="aksBtnText">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include $depth . 'includes/footer.php'; ?>
<script>
function detailAduan(r) {
    document.getElementById('detailBody').innerHTML = `
        <table class="table">
            <tr><th width="150">Penghuni</th><td>${r.nama}</td></tr>
            <tr><th>Kategori</th><td>${r.kategori}</td></tr>
            <tr><th>Judul</th><td><strong>${r.judul}</strong></td></tr>
            <tr><th>Deskripsi</th><td>${r.deskripsi}</td></tr>
            <tr><th>Foto Bukti</th><td>${r.foto ? '<a href="../uploads/aduan/'+r.foto+'" target="_blank"><img src="../uploads/aduan/'+r.foto+'" style="max-width:220px;max-height:180px;object-fit:cover;border-radius:8px" class="border"></a>' : '<span class="text-muted">Tidak ada foto</span>'}</td></tr>
            <tr><th>Status</th><td>${r.status}</td></tr>
            <tr><th>Teknisi</th><td>${r.teknisi || '-'}</td></tr>
            <tr><th>Catatan Pengelola</th><td>${r.catatan_pengelola || '-'}</td></tr>
            <tr><th>Tgl Aduan</th><td>${r.tanggal_aduan}</td></tr>
            <tr><th>Tgl Selesai</th><td>${r.tanggal_selesai || '-'}</td></tr>
        </table>`;
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}
function aksModal(id, action) {
    document.getElementById('aks_id').value = id;
    document.getElementById('aks_action').value = action;
    const titles = {tangani:'Tangani Aduan',selesai:'Tandai Selesai',tolak:'Tolak Aduan'};
    document.getElementById('aksTitle').textContent = titles[action];
    document.getElementById('teknisiDiv').classList.toggle('d-none', action !== 'tangani');
    new bootstrap.Modal(document.getElementById('aksModal')).show();
}
</script>
