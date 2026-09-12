<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pegawai'], $depth . 'admin/dashboard.php'); // Khusus pegawai — pemilik tidak bisa akses halaman ini
$page_title = 'Menu Kantin';
$msg = '';

// Helper: proses upload foto menu, return nama file baru atau null kalau tidak ada upload
function prosesUploadFotoMenu($conn, $depth) {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) return null;
    $allowed_ext = ['jpg','jpeg','png','webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext) || $_FILES['foto']['size'] > $max_size) return null;

    $upload_dir = $depth . 'uploads/menu_kantin/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $filename)) {
        return $filename;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'];
    if ($act === 'tambah') {
        $nama = $conn->real_escape_string($_POST['nama_menu']);
        $kat  = $conn->real_escape_string($_POST['kategori']);
        $harga= (float)$_POST['harga'];
        $desk = $conn->real_escape_string($_POST['deskripsi'] ?? '');
        $foto = prosesUploadFotoMenu($conn, $depth);
        $foto_esc = $foto ? "'" . $conn->real_escape_string($foto) . "'" : 'NULL';
        $conn->query("INSERT INTO menu_kantin (nama_menu,kategori,harga,deskripsi,foto,tersedia) VALUES ('$nama','$kat',$harga,'$desk',$foto_esc,1)");
        $msg = 'success|Menu berhasil ditambahkan.';
    } elseif ($act === 'edit') {
        $id   = (int)$_POST['id'];
        $nama = $conn->real_escape_string($_POST['nama_menu']);
        $kat  = $conn->real_escape_string($_POST['kategori']);
        $harga= (float)$_POST['harga'];
        $desk = $conn->real_escape_string($_POST['deskripsi'] ?? '');
        $tsedia = (int)$_POST['tersedia'];

        $foto = prosesUploadFotoMenu($conn, $depth);
        if ($foto) {
            // Hapus foto lama dari disk sebelum ganti dengan yang baru
            $lama = $conn->query("SELECT foto FROM menu_kantin WHERE id=$id")->fetch_assoc();
            if ($lama && $lama['foto']) {
                $path_lama = $depth . 'uploads/menu_kantin/' . $lama['foto'];
                if (is_file($path_lama)) @unlink($path_lama);
            }
            $foto_esc = $conn->real_escape_string($foto);
            $conn->query("UPDATE menu_kantin SET nama_menu='$nama',kategori='$kat',harga=$harga,deskripsi='$desk',tersedia=$tsedia,foto='$foto_esc' WHERE id=$id");
        } else {
            $conn->query("UPDATE menu_kantin SET nama_menu='$nama',kategori='$kat',harga=$harga,deskripsi='$desk',tersedia=$tsedia WHERE id=$id");
        }
        $msg = 'success|Menu diperbarui.';
    } elseif ($act === 'hapus') {
        $id = (int)$_POST['id'];
        $f = $conn->query("SELECT foto FROM menu_kantin WHERE id=$id")->fetch_assoc();
        if ($f && $f['foto']) {
            $path = $depth . 'uploads/menu_kantin/' . $f['foto'];
            if (is_file($path)) @unlink($path);
        }
        $conn->query("DELETE FROM menu_kantin WHERE id=$id");
        $msg = 'success|Menu dihapus.';
    }
}
$data = $conn->query("SELECT * FROM menu_kantin ORDER BY kategori,nama_menu");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m)=explode('|',$msg,2); echo alert($t,$m); endif; ?>
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
        <i class="bi bi-plus-lg me-2"></i>Tambah Menu
    </button>
</div>
<div class="card">
    <div class="card-header py-3"><i class="bi bi-egg-fried me-2"></i>Daftar Menu Kantin</div>
    <div class="card-body p-0">
    <table class="table table-hover mb-0">
        <thead><tr><th>Foto</th><th>#</th><th>Nama Menu</th><th>Kategori</th><th>Harga</th><th>Tersedia</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php $no=1; while($r=$data->fetch_assoc()): ?>
        <tr>
            <td>
                <?php if($r['foto']): ?>
                <img src="../uploads/menu_kantin/<?= htmlspecialchars($r['foto']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light rounded-2" style="width:48px;height:48px">
                    <i class="bi bi-image text-muted"></i>
                </div>
                <?php endif; ?>
            </td>
            <td><?= $no++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['nama_menu']) ?></td>
            <td><span class="badge bg-secondary"><?= ucfirst($r['kategori']) ?></span></td>
            <td class="text-primary fw-700"><?= formatRupiah($r['harga']) ?></td>
            <td><?= $r['tersedia'] ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-danger">Tidak</span>' ?></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick='editMenu(<?= json_encode($r) ?>)'><i class="bi bi-pencil"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus menu ini?')">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-700">Tambah Menu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="tambah">
    <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-600">Nama Menu</label><input type="text" name="nama_menu" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-600">Kategori</label>
        <select name="kategori" class="form-select"><option value="makanan">Makanan</option><option value="minuman">Minuman</option><option value="snack">Snack</option></select></div>
        <div class="mb-3"><label class="form-label fw-600">Harga (Rp)</label><input type="number" name="harga" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-600">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"></textarea></div>
        <div class="mb-3">
            <label class="form-label fw-600">Foto Menu</label>
            <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="text-muted mt-1" style="font-size:.76rem">Opsional. JPG/PNG/WEBP, maks. 2MB.</div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-700">Edit Menu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="em_id">
    <div class="modal-body">
        <div class="mb-3" id="em_foto_preview_wrap">
            <label class="form-label fw-600">Foto Saat Ini</label>
            <div><img id="em_foto_preview" src="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;display:none"></div>
        </div>
        <div class="mb-3"><label class="form-label fw-600">Nama Menu</label><input type="text" name="nama_menu" id="em_nama" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-600">Kategori</label>
        <select name="kategori" id="em_kat" class="form-select"><option value="makanan">Makanan</option><option value="minuman">Minuman</option><option value="snack">Snack</option></select></div>
        <div class="mb-3"><label class="form-label fw-600">Harga (Rp)</label><input type="number" name="harga" id="em_harga" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-600">Deskripsi</label><textarea name="deskripsi" id="em_desk" class="form-control" rows="2"></textarea></div>
        <div class="mb-3">
            <label class="form-label fw-600">Ganti Foto</label>
            <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="text-muted mt-1" style="font-size:.76rem">Kosongkan kalau tidak mau ganti foto.</div>
        </div>
        <div class="mb-3"><label class="form-label fw-600">Tersedia</label>
        <select name="tersedia" id="em_tsedia" class="form-select"><option value="1">Ya</option><option value="0">Tidak</option></select></div>
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
function editMenu(r) {
    document.getElementById('em_id').value=r.id;
    document.getElementById('em_nama').value=r.nama_menu;
    document.getElementById('em_kat').value=r.kategori;
    document.getElementById('em_harga').value=r.harga;
    document.getElementById('em_desk').value=r.deskripsi||'';
    document.getElementById('em_tsedia').value=r.tersedia;

    const preview = document.getElementById('em_foto_preview');
    if (r.foto) {
        preview.src = '../uploads/menu_kantin/' + r.foto;
        preview.style.display = '';
    } else {
        preview.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>