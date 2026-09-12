<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni','calon_penghuni'], $depth . 'login.php');
$page_title = 'Laporan Aduan';
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $judul    = $conn->real_escape_string($_POST['judul']);
    $desk     = $conn->real_escape_string($_POST['deskripsi']);

    // Upload foto bukti kerusakan (opsional)
    $foto = null;
    $upload_error = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg','jpeg','png','webp'];
        $max_size = 3 * 1024 * 1024; // 3MB
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $upload_error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > $max_size) {
            $upload_error = 'Ukuran foto maksimal 3MB.';
        } else {
            $upload_dir = $depth . 'uploads/aduan/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'aduan_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $filename)) {
                $foto = $filename;
            } else {
                $upload_error = 'Gagal mengunggah foto. Aduan tetap dikirim tanpa foto.';
            }
        }
    }

    if ($upload_error) {
        // Foto gagal, tapi aduan tetap boleh dikirim tanpa foto (foto sifatnya opsional)
        $foto_esc = 'NULL';
    } else {
        $foto_esc = $foto ? "'" . $conn->real_escape_string($foto) . "'" : 'NULL';
    }

    $conn->query("INSERT INTO laporan_aduan (user_id,kategori,judul,deskripsi,foto,status)
                  VALUES ($uid,'$kategori','$judul','$desk',$foto_esc,'pending')");
    generateKode($conn, 'laporan_aduan', 'kode_aduan', 'ADU', $conn->insert_id, 4);
    $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
    $uname  = getUser()['nama'];
    while ($a = $admins->fetch_assoc()) {
        addNotif($conn, $a['id'], 'Aduan Baru ⚠️', "$uname mengajukan aduan: $judul", 'warning', 'aduan.php');
    }
    addNotif($conn, $uid, 'Aduan Terkirim 📝', 'Aduan Anda sedang ditinjau oleh pengelola.', 'info', 'aduan.php');

    $msg = $upload_error ? "warning|Aduan terkirim, tapi $upload_error" : 'success|Aduan berhasil dikirim! Kami akan segera menanganinya.';
}

$data = $conn->query("SELECT * FROM laporan_aduan WHERE user_id=$uid ORDER BY tanggal_aduan DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="row g-4">
  <!-- Form -->
  <div class="col-lg-4">
    <div class="card">
        <div class="card-header py-3 fw-700"><i class="bi bi-chat-square-warning me-2"></i>Kirim Aduan</div>
        <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-600">Kategori <span class="text-danger">*</span></label>
                <select name="kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="fasilitas">🔧 Fasilitas</option>
                    <option value="keamanan">🔒 Keamanan</option>
                    <option value="kebersihan">🧹 Kebersihan</option>
                    <option value="lainnya">📋 Lainnya</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Judul Aduan <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" placeholder="Ringkasan masalah" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Deskripsi Lengkap <span class="text-danger">*</span></label>
                <textarea name="deskripsi" class="form-control" rows="5" placeholder="Jelaskan masalah secara detail..." required></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Foto Bukti Kerusakan</label>
                <label for="fotoInput" class="d-block text-center" id="fotoDrop" style="border:1.5px dashed #E2E8F0;border-radius:12px;padding:18px;cursor:pointer;background:#F8FAFC;transition:.15s">
                    <i class="bi bi-camera fs-4 text-primary d-block mb-1"></i>
                    <span class="fw-600" id="fotoLabel" style="font-size:.88rem">Klik untuk pilih foto (opsional)</span>
                    <div class="text-muted mt-1" style="font-size:.76rem">JPG, PNG, atau WEBP — maks. 3MB</div>
                </label>
                <input type="file" name="foto" id="fotoInput" accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="showFotoName(this)">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i>Kirim Aduan
            </button>
        </form>
        </div>
    </div>
  </div>

  <!-- Riwayat -->
  <div class="col-lg-8">
    <div class="card">
        <div class="card-header py-3 fw-700"><i class="bi bi-clock-history me-2"></i>Riwayat Aduan Saya</div>
        <div class="card-body p-0">
        <?php if($data->num_rows === 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat-square fs-1 d-block mb-2"></i>
            Belum ada aduan yang diajukan.
        </div>
        <?php else: while($r = $data->fetch_assoc()): ?>
        <div class="p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="badge bg-secondary me-2"><?= ucfirst($r['kategori']) ?></span>
                    <strong><?= htmlspecialchars($r['judul']) ?></strong>
                </div>
                <?= statusBadge($r['status']) ?>
            </div>
            <p class="text-muted mb-2" style="font-size:.88rem"><?= htmlspecialchars($r['deskripsi']) ?></p>

            <?php if(!empty($r['foto'])): ?>
            <a href="<?= $depth ?>uploads/aduan/<?= urlencode($r['foto']) ?>" target="_blank">
                <img src="<?= $depth ?>uploads/aduan/<?= htmlspecialchars($r['foto']) ?>"
                     style="max-width:160px;max-height:120px;object-fit:cover;border-radius:8px" class="border mb-2">
            </a>
            <?php endif; ?>

            <!-- Timeline status -->
            <div class="d-flex gap-3 align-items-center mt-3" style="font-size:.8rem">
                <span class="<?= in_array($r['status'],['pending','ditangani','selesai']) ? 'text-success' : 'text-muted' ?>">
                    <i class="bi bi-check-circle-fill me-1"></i>Terkirim
                </span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="<?= in_array($r['status'],['ditangani','selesai']) ? 'text-info' : 'text-muted' ?>">
                    <i class="bi bi-gear-fill me-1"></i>Ditangani
                </span>
                <i class="bi bi-arrow-right text-muted"></i>
                <span class="<?= $r['status'] === 'selesai' ? 'text-success fw-600' : 'text-muted' ?>">
                    <i class="bi bi-check-all me-1"></i>Selesai
                </span>
            </div>

            <?php if($r['catatan_pengelola']): ?>
            <div class="mt-3 p-3 bg-light rounded-3">
                <div class="fw-600" style="font-size:.82rem">Catatan Pengelola:</div>
                <div style="font-size:.85rem"><?= htmlspecialchars($r['catatan_pengelola']) ?></div>
                <?php if($r['teknisi']): ?>
                <div class="mt-1 text-muted" style="font-size:.8rem"><i class="bi bi-person-gear me-1"></i>Teknisi: <?= htmlspecialchars($r['teknisi']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="text-muted mt-2" style="font-size:.78rem">
                <i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($r['tanggal_aduan'])) ?>
                <?php if($r['tanggal_selesai']): ?>
                &nbsp;·&nbsp;<i class="bi bi-check-circle me-1"></i>Selesai: <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; endif; ?>
        </div>
    </div>
  </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
function showFotoName(input) {
    const label = document.getElementById('fotoLabel');
    const drop  = document.getElementById('fotoDrop');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        drop.style.borderColor = '#15803d';
        drop.style.background  = '#F0FDF4';
    } else {
        label.textContent = 'Klik untuk pilih foto (opsional)';
        drop.style.borderColor = '#E2E8F0';
        drop.style.background  = '#F8FAFC';
    }
}
</script>