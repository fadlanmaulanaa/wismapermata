<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik'], $depth . 'login.php');
$page_title = 'Kelola Pengguna';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['id'];
    $role   = $conn->real_escape_string($_POST['role']);
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE users SET role='$role',status='$status' WHERE id=$id");
    $msg = 'success|Data pengguna diperbarui.';
}
$data = $conn->query("SELECT * FROM users ORDER BY role,created_at DESC");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m)=explode('|',$msg,2); echo alert($t,$m); endif; ?>
<div class="card">
    <div class="card-header py-3 fw-700"><i class="bi bi-person-gear me-2"></i>Kelola Semua Pengguna</div>
    <div class="card-body p-0">
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Kamar</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $no=1; while($r=$data->fetch_assoc()): ?>
        <tr>
            <td><?=$no++?></td>
            <td class="fw-600"><?=htmlspecialchars($r['nama'])?></td>
            <td style="font-size:.85rem"><?=htmlspecialchars($r['email'])?></td>
            <td style="font-size:.85rem"><?=$r['nomor_telepon']?></td>
            <td><?=$r['nomor_kamar']?:'<span class="text-muted">-</span>'?></td>
            <td><span class="badge bg-<?=['pemilik'=>'danger','pegawai'=>'warning','penghuni'=>'success','calon_penghuni'=>'secondary'][$r['role']]??'secondary' ?>"><?=ucfirst(str_replace('_',' ',$r['role']))?></span></td>
            <td><?=$r['status']==='aktif'?'<span class="badge bg-success">Aktif</span>':'<span class="badge bg-secondary">Nonaktif</span>'?></td>
            <td><button class="btn btn-sm btn-outline-primary" onclick='editU(<?=json_encode($r)?>)'><i class="bi bi-pencil"></i></button></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-700">Edit Pengguna</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST"><input type="hidden" name="id" id="eu_id">
    <div class="modal-body">
        <p class="fw-600" id="eu_nama"></p>
        <div class="mb-3"><label class="form-label fw-600">Role</label>
        <select name="role" id="eu_role" class="form-select">
            <option value="calon_penghuni">Calon Penghuni</option>
            <option value="penghuni">Penghuni</option>
            <option value="pegawai">Pegawai</option>
            <option value="pemilik">Pemilik</option>
        </select></div>
        <div class="mb-3"><label class="form-label fw-600">Status</label>
        <select name="status" id="eu_status" class="form-select">
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
            <option value="pending">Pending</option>
        </select></div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
function editU(r){
    document.getElementById('eu_id').value=r.id;
    document.getElementById('eu_nama').textContent=r.nama+' ('+r.email+')';
    document.getElementById('eu_role').value=r.role;
    document.getElementById('eu_status').value=r.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
