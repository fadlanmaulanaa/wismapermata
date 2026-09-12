<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Data Penghuni';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_role') {
    $id   = (int)$_POST['id'];
    $role = $conn->real_escape_string($_POST['role']);
    $nokamar = $conn->real_escape_string($_POST['nomor_kamar'] ?? '');
    $conn->query("UPDATE users SET role='$role',nomor_kamar='$nokamar' WHERE id=$id");
    if($role==='penghuni' && $nokamar) {
        $conn->query("UPDATE kamar SET status='terisi' WHERE nomor_kamar='$nokamar'");
    }
    $msg = 'success|Data pengguna diperbarui.';
}
$data = $conn->query("SELECT * FROM users WHERE role IN ('penghuni','calon_penghuni') ORDER BY created_at DESC");
$kamar_list = $conn->query("SELECT nomor_kamar FROM kamar ORDER BY lantai,nomor_kamar");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m)=explode('|',$msg,2); echo alert($t,$m); endif; ?>
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-people me-2"></i>Data Penghuni & Calon Penghuni</span>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:220px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="searchPenghuni" placeholder="Cari nama / email..." oninput="filterPenghuni()">
            </div>
            <select class="form-select form-select-sm" id="filterRole" onchange="filterPenghuni()" style="width:auto">
                <option value="">Semua Role</option>
                <option value="penghuni">Penghuni</option>
                <option value="calon_penghuni">Calon Penghuni</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0" id="penghuniTable">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>KTP</th><th>Kamar</th><th>Role</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $no=1; $km=[]; while($k=$kamar_list->fetch_assoc()) $km[]=$k['nomor_kamar'];
        $data->data_seek(0); while($r=$data->fetch_assoc()): ?>
        <tr data-role="<?= $r['role'] ?>" data-search="<?= strtolower(htmlspecialchars($r['nama'].' '.$r['email'])) ?>">
            <td><?= $no++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
            <td style="font-size:.85rem"><?= htmlspecialchars($r['email']) ?></td>
            <td style="font-size:.85rem"><?= $r['nomor_telepon'] ?></td>
            <td style="font-size:.82rem"><?= $r['nomor_ktp'] ?></td>
            <td><?= $r['nomor_kamar'] ? '<span class="badge bg-light text-dark">'.$r['nomor_kamar'].'</span>' : '-' ?></td>
            <td><?= $r['role']==='penghuni' ? '<span class="badge bg-success">Penghuni</span>' : '<span class="badge bg-secondary">Calon</span>' ?></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($r) ?>)'>
                    <i class="bi bi-pencil"></i>
                </button>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title fw-700">Edit Pengguna</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <input type="hidden" name="action" value="update_role">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-600">Nama</label>
                <input type="text" id="edit_nama" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Role</label>
                <select name="role" id="edit_role" class="form-select">
                    <option value="calon_penghuni">Calon Penghuni</option>
                    <option value="penghuni">Penghuni</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Nomor Kamar</label>
                <select name="nomor_kamar" id="edit_kamar" class="form-select">
                    <option value="">-- Belum Ada Kamar --</option>
                    <?php foreach($km as $k): ?>
                    <option value="<?= $k ?>"><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
function editUser(r) {
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_nama').value = r.nama;
    document.getElementById('edit_role').value = r.role;
    document.getElementById('edit_kamar').value = r.nomor_kamar || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function filterPenghuni() {
    const role = document.getElementById('filterRole').value;
    const search = document.getElementById('searchPenghuni').value.trim().toLowerCase();
    document.querySelectorAll('#penghuniTable tbody tr').forEach(row => {
        const mr = !role || row.dataset.role === role;
        const ms = !search || row.dataset.search.includes(search);
        row.style.display = (mr && ms) ? '' : 'none';
    });
}
</script>