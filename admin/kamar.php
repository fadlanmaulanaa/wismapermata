<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Data Kamar';

$msg = '';

// Handle actions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if($action === 'edit') {
        $id       = (int)$_POST['id'];
        $nomor    = $conn->real_escape_string($_POST['nomor_kamar']);
        $tipe     = $conn->real_escape_string($_POST['tipe']);
        $ukuran   = $conn->real_escape_string($_POST['ukuran']);
        $harga    = (float)$_POST['harga_per_bulan'];
        $lantai   = (int)$_POST['lantai'];
        $status   = $conn->real_escape_string($_POST['status']);
        $desk     = $conn->real_escape_string($_POST['deskripsi']);
        $fas      = $conn->real_escape_string($_POST['fasilitas']);
        $conn->query("UPDATE kamar SET nomor_kamar='$nomor',tipe='$tipe',ukuran='$ukuran',
            harga_per_bulan=$harga,lantai=$lantai,status='$status',
            deskripsi='$desk',fasilitas='$fas' WHERE id=$id");
        $msg = 'success|Data kamar berhasil diperbarui.';
    }

    elseif ($action === 'upload_foto') {
        $kamar_id = (int)$_POST['kamar_id'];
        $allowed_ext = ['jpg','jpeg','png','webp'];
        $max_size = 3 * 1024 * 1024; // 3MB
        $sukses = 0; $gagal = 0;

        if (!empty($_FILES['foto']['name'][0])) {
            $total_file = count($_FILES['foto']['name']);
            for ($i = 0; $i < $total_file; $i++) {
                if ($_FILES['foto']['error'][$i] !== UPLOAD_ERR_OK) { $gagal++; continue; }
                $ext = strtolower(pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext) || $_FILES['foto']['size'][$i] > $max_size) { $gagal++; continue; }

                $upload_dir = $depth . 'uploads/kamar/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = 'kamar_' . $kamar_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                if (move_uploaded_file($_FILES['foto']['tmp_name'][$i], $upload_dir . $filename)) {
                    $fn_esc = $conn->real_escape_string($filename);
                    $conn->query("INSERT INTO kamar_foto (kamar_id, filename) VALUES ($kamar_id, '$fn_esc')");
                    $sukses++;
                } else {
                    $gagal++;
                }
            }
        }
        $msg = $sukses > 0 ? "success|$sukses foto berhasil diunggah." . ($gagal ? " ($gagal gagal)" : '') : 'danger|Tidak ada foto yang berhasil diunggah.';
        header("Location: kamar.php?edit=$kamar_id");
        exit;
    }

    elseif ($action === 'hapus_foto') {
        $foto_id  = (int)$_POST['foto_id'];
        $kamar_id = (int)$_POST['kamar_id'];
        $f = $conn->query("SELECT filename FROM kamar_foto WHERE id=$foto_id")->fetch_assoc();
        if ($f) {
            $path = $depth . 'uploads/kamar/' . $f['filename'];
            if (is_file($path)) @unlink($path);
            $conn->query("DELETE FROM kamar_foto WHERE id=$foto_id");
        }
        header("Location: kamar.php?edit=$kamar_id");
        exit;
    }
}

$kamar = $conn->query("SELECT * FROM kamar ORDER BY lantai, nomor_kamar");

// Kumpulkan semua foto, dikelompokkan per kamar_id, untuk ditampilkan di modal edit
$foto_by_kamar = [];
$foto_q = $conn->query("SELECT * FROM kamar_foto ORDER BY kamar_id, urutan, id");
while ($f = $foto_q->fetch_assoc()) {
    $foto_by_kamar[$f['kamar_id']][] = $f;
}

// Salinan data semua kamar untuk JS (dipakai saat auto-buka modal setelah upload/hapus foto)
$semua_kamar = [];
$kamar_js = $conn->query("SELECT * FROM kamar ORDER BY lantai, nomor_kamar");
while ($row = $kamar_js->fetch_assoc()) {
    $semua_kamar[] = $row;
}

$reopen_kamar_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>

<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-door-open me-2"></i>Daftar 32 Kamar Wisma Permata</span>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:200px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="searchKamar" placeholder="Cari no. kamar..." oninput="filterTable()">
            </div>
            <select class="form-select form-select-sm" id="filterTipe" onchange="filterTable()">
                <option value="">Semua Tipe</option>
                <option value="Standar">Standar</option>
                <option value="Deluxe">Deluxe</option>
                <option value="Premium">Premium</option>
                <option value="VIP">VIP</option>
            </select>
            <select class="form-select form-select-sm" id="filterStatus" onchange="filterTable()">
                <option value="">Semua Status</option>
                <option value="tersedia">Tersedia</option>
                <option value="dipesan">Dipesan</option>
                <option value="terisi">Terisi</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="kamarTable">
            <thead>
                <tr>
                    <th>No. Kamar</th><th>Tipe</th><th>Ukuran</th><th>Lantai</th>
                    <th>Harga/Bulan</th><th>Fasilitas</th><th>Foto</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($k = $kamar->fetch_assoc()): ?>
            <tr data-tipe="<?= $k['tipe'] ?>" data-status="<?= $k['status'] ?>">
                <td><strong><?= $k['nomor_kamar'] ?></strong></td>
                <td><span class="tipe-badge tipe-<?= strtolower($k['tipe']) ?>"><?= $k['tipe'] ?></span></td>
                <td><?= $k['ukuran'] ?></td>
                <td>Lantai <?= $k['lantai'] ?></td>
                <td class="fw-600 text-primary"><?= formatRupiah($k['harga_per_bulan']) ?></td>
                <td style="font-size:.8rem;max-width:200px"><?= htmlspecialchars(substr($k['fasilitas'],0,60)) ?>...</td>
                <td>
                    <span class="badge <?= !empty($foto_by_kamar[$k['id']]) ? 'bg-success' : 'bg-secondary' ?>">
                        <?= count($foto_by_kamar[$k['id']] ?? []) ?> foto
                    </span>
                </td>
                <td><?= statusBadge($k['status']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary"
                        onclick='editKamar(<?= json_encode($k) ?>)'>
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

<!-- Modal Edit Kamar -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-700">Edit Data Kamar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- FORM DATA KAMAR -->
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="row g-3 mb-2">
                <div class="col-md-3">
                    <label class="form-label fw-600">No. Kamar</label>
                    <input type="text" name="nomor_kamar" id="edit_nomor" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-600">Tipe</label>
                    <select name="tipe" id="edit_tipe" class="form-select">
                        <option value="Standar">Standar</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Premium">Premium</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-600">Ukuran</label>
                    <input type="text" name="ukuran" id="edit_ukuran" class="form-control" placeholder="3x3 m">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-600">Lantai</label>
                    <select name="lantai" id="edit_lantai" class="form-select">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600">Harga/Bulan (Rp)</label>
                    <input type="number" name="harga_per_bulan" id="edit_harga" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="tersedia">Tersedia</option>
                        <option value="dipesan">Dipesan</option>
                        <option value="terisi">Terisi</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-600">Fasilitas</label>
                    <input type="text" name="fasilitas" id="edit_fasilitas" class="form-control"
                           placeholder="Kasur, lemari, AC, ...">
                </div>
                <div class="col-12">
                    <label class="form-label fw-600">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-2"></i>Simpan Data Kamar</button>
            </div>
        </form>

        <hr class="my-4">

        <!-- GALERI FOTO KAMAR -->
        <label class="form-label fw-600 mb-2">Foto Kondisi Kamar</label>
        <div class="row g-2 mb-3" id="fotoGallery"><!-- diisi via JS --></div>

        <!-- FORM UPLOAD FOTO (form terpisah, bukan nested di dalam form edit) -->
        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="action" value="upload_foto">
            <input type="hidden" name="kamar_id" id="foto_kamar_id">
            <input type="file" name="foto[]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap"><i class="bi bi-upload me-1"></i>Unggah</button>
        </form>
        <div class="text-muted mt-1" style="font-size:.76rem">JPG/PNG/WEBP, maks. 3MB per file. Bisa pilih beberapa foto sekaligus.</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Form tersembunyi untuk hapus foto (di-submit lewat JS agar bisa dipicu dari tombol di dalam galeri) -->
<form method="POST" id="hapusFotoForm" class="d-none">
    <input type="hidden" name="action" value="hapus_foto">
    <input type="hidden" name="foto_id" id="hapus_foto_id">
    <input type="hidden" name="kamar_id" id="hapus_foto_kamar_id">
</form>

<?php include $depth . 'includes/footer.php'; ?>
<script>
const fotoByKamar = <?= json_encode($foto_by_kamar) ?>;
const reopenKamarId = <?= (int)$reopen_kamar_id ?>;
const semuaKamar = <?= json_encode($semua_kamar) ?>;

function renderGallery(kamarId) {
    const gallery = document.getElementById('fotoGallery');
    const fotos = fotoByKamar[kamarId] || [];
    if (fotos.length === 0) {
        gallery.innerHTML = '<div class="col-12 text-muted text-center py-3" style="font-size:.85rem"><i class="bi bi-image fs-3 d-block mb-1"></i>Belum ada foto untuk kamar ini.</div>';
        return;
    }
    gallery.innerHTML = fotos.map(f => `
        <div class="col-4 col-md-3">
            <div class="position-relative">
                <img src="../uploads/kamar/${f.filename}" class="w-100 rounded-2 border" style="height:90px;object-fit:cover">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0"
                        style="width:22px;height:22px;line-height:1;font-size:.7rem"
                        onclick="hapusFoto(${f.id}, ${kamarId})" title="Hapus foto">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function hapusFoto(fotoId, kamarId) {
    if (!confirm('Hapus foto ini?')) return;
    document.getElementById('hapus_foto_id').value = fotoId;
    document.getElementById('hapus_foto_kamar_id').value = kamarId;
    document.getElementById('hapusFotoForm').submit();
}

function editKamar(k) {
    document.getElementById('edit_id').value = k.id;
    document.getElementById('edit_nomor').value = k.nomor_kamar;
    document.getElementById('edit_tipe').value = k.tipe;
    document.getElementById('edit_ukuran').value = k.ukuran;
    document.getElementById('edit_lantai').value = k.lantai;
    document.getElementById('edit_harga').value = k.harga_per_bulan;
    document.getElementById('edit_status').value = k.status;
    document.getElementById('edit_fasilitas').value = k.fasilitas;
    document.getElementById('edit_deskripsi').value = k.deskripsi;
    document.getElementById('foto_kamar_id').value = k.id;
    renderGallery(k.id);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function filterTable() {
    const tipe = document.getElementById('filterTipe').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('searchKamar').value.trim().toLowerCase();
    document.querySelectorAll('#kamarTable tbody tr').forEach(row => {
        const mt = !tipe || row.dataset.tipe === tipe;
        const ms = !status || row.dataset.status === status;
        const nomorKamar = row.querySelector('td strong')?.textContent.toLowerCase() || '';
        const mc = !search || nomorKamar.includes(search);
        row.style.display = (mt && ms && mc) ? '' : 'none';
    });
}

// Setelah upload/hapus foto, halaman redirect balik ke sini dengan ?edit=ID — buka lagi modalnya otomatis
if (reopenKamarId > 0) {
    const target = semuaKamar.find(k => parseInt(k.id) === reopenKamarId);
    if (target) editKamar(target);
}
</script>