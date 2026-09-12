<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni'], $depth . 'login.php');
$page_title = 'Pembayaran Sewa';
$uid  = $_SESSION['user_id'];
$user = getUser();
$msg  = '';

// Info kamar (dipindah ke atas supaya bisa dipakai untuk validasi POST juga)
$kamar_info = null;
if ($user['nomor_kamar']) {
    $kamar_info = $conn->query("SELECT * FROM kamar WHERE nomor_kamar='" . $conn->real_escape_string($user['nomor_kamar']) . "'")->fetch_assoc();
}

// Bulan mulai yang tersedia untuk pembayaran berikutnya (otomatis lompati
// bulan yang sudah lunas/menunggu verifikasi) — ini SELALU bulan berikutnya
// yang berurutan, tidak bisa dipilih bebas/lompat.
$bulan_tersedia = $kamar_info ? getBulanBayarTersedia($conn, $uid, $kamar_info['id']) : ['next_bulan' => date('Y-m'), 'options' => [date('Y-m')]];
$next_bulan = $bulan_tersedia['next_bulan'];

// Berapa bulan sekaligus penghuni boleh pilih untuk dibayar (1 s.d. 12 bulan)
$MAKS_BULAN_SEKALIGUS = 12;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kamar_id     = (int)$_POST['kamar_id'];
    $bulan        = $conn->real_escape_string($_POST['bulan_bayar']);
    $jumlah_bulan = (int)($_POST['jumlah_bulan'] ?? 1);
    $bukti_bayar  = '';
    $upload_error = '';

    // Pastikan kamar_id yang dikirim benar-benar kamar milik penghuni ini
    // (jangan percaya mentah dari form/devtools)
    if (!$kamar_info || $kamar_id !== (int)$kamar_info['id']) {
        $upload_error = 'Data kamar tidak valid. Silakan muat ulang halaman.';
    }
    // Bulan mulai HARUS PERSIS bulan berikutnya yang tersedia — tidak boleh
    // mundur (sudah lunas) ATAUPUN maju/lompat. Ini yang membuat pembayaran
    // selalu berurutan (jangan percaya mentah dari form/devtools).
    elseif (!preg_match('/^\d{4}-\d{2}$/', $bulan) || $bulan !== $next_bulan) {
        $upload_error = 'Bulan pembayaran harus berurutan. Silakan muat ulang halaman untuk memperbarui bulan yang tersedia.';
    }
    // Jumlah bulan sekaligus dibatasi wajar (1—12), boleh bayar beberapa
    // bulan sekaligus (mis. 2 atau 3 bulan) tapi tetap dari bulan berurutan
    // yang sama di atas (jangan percaya mentah dari form/devtools).
    elseif ($jumlah_bulan < 1 || $jumlah_bulan > $MAKS_BULAN_SEKALIGUS) {
        $upload_error = 'Jumlah bulan tidak valid.';
    }

    // Jumlah dihitung ulang di server dari harga kamar × jumlah bulan (bukan
    // dipercaya mentah dari input), supaya tidak bisa diakali lewat devtools.
    $k = $conn->query("SELECT harga_per_bulan FROM kamar WHERE id=$kamar_id")->fetch_assoc();
    $harga_bulan = $k ? (float)$k['harga_per_bulan'] : 0;
    $jumlah = $harga_bulan * $jumlah_bulan;

    // Label 'periode' otomatis mengikuti jumlah bulan — hanya untuk
    // keperluan tampilan badge (Bulanan/Tahunan), tidak dipakai lagi untuk
    // hitung tenggat/masa sewa (itu sekarang murni dari jumlah_bulan).
    $periode = ($jumlah_bulan >= 12) ? 'tahunan' : 'bulanan';

    // Validasi & proses upload bukti pembayaran
    if (!$upload_error) {
        if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext      = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($ext, $allowed_ext)) {
                $upload_error = 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.';
            } elseif ($_FILES['bukti_bayar']['size'] > $max_size) {
                $upload_error = 'Ukuran file maksimal 2MB.';
            } else {
                $upload_dir = $depth . 'uploads/bukti_bayar/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = 'bukti_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $upload_dir . $filename)) {
                    $bukti_bayar = $filename;
                } else {
                    $upload_error = 'Gagal mengunggah file. Silakan coba lagi.';
                }
            }
        } else {
            $upload_error = 'Bukti pembayaran wajib diunggah.';
        }
    }

    if ($upload_error) {
        $msg = 'danger|' . $upload_error;
    } else {
        $bukti_esc   = $conn->real_escape_string($bukti_bayar);
        $periode_esc = $conn->real_escape_string($periode);
        $conn->query("INSERT INTO pembayaran_sewa (user_id,kamar_id,jumlah,bulan_bayar,metode,periode,jumlah_bulan,bukti_bayar,status)
                      VALUES ($uid,$kamar_id,$jumlah,'$bulan','transfer','$periode_esc',$jumlah_bulan,'$bukti_esc','pending')");
        generateKode($conn, 'pembayaran_sewa', 'kode_pembayaran', 'PBS', $conn->insert_id, 4);

        $bulan_akhir = date('Y-m', strtotime("$bulan-01 +" . ($jumlah_bulan - 1) . " months"));
        $label_cakupan = ($jumlah_bulan === 1)
            ? namaBulanIndo($bulan)
            : namaBulanIndo($bulan) . ' s/d ' . namaBulanIndo($bulan_akhir) . " ($jumlah_bulan bulan)";

        $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
        $uname  = $user['nama'];
        while ($a = $admins->fetch_assoc()) {
            addNotif($conn, $a['id'], 'Bukti Pembayaran Masuk 💰', "$uname mengajukan pembayaran sewa untuk $label_cakupan senilai " . formatRupiah($jumlah), 'info', 'pembayaran.php');
        }
        addNotif($conn, $uid, 'Pembayaran Dikirim ✅', 'Bukti pembayaran sedang diverifikasi oleh pengelola.', 'success', 'pembayaran.php');
        $msg = 'success|Pembayaran berhasil dikirim. Menunggu verifikasi pengelola.';

        // Refresh bulan berikutnya yang tersedia, supaya form yang tampil
        // setelah ini sudah tidak menawarkan bulan yang baru saja diajukan
        $bulan_tersedia = getBulanBayarTersedia($conn, $uid, $kamar_info['id']);
        $next_bulan     = $bulan_tersedia['next_bulan'];
    }
}

// Batas waktu pembayaran (24 jam sejak disetujui) — ditampilkan sebagai banner peringatan
$batas_bayar = getBatasBayarAktif($conn, $uid);

$riwayat = $conn->query("SELECT ps.*,k.nomor_kamar FROM pembayaran_sewa ps
    JOIN kamar k ON k.id=ps.kamar_id WHERE ps.user_id=$uid ORDER BY ps.created_at DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>
<?php if($batas_bayar): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-hourglass-split fs-5"></i>
    <div>
        Batas waktu pembayaran: <strong><?= date('d M Y H:i', strtotime($batas_bayar)) ?></strong>.
        Jika lewat waktu ini tanpa pembayaran, pemesanan akan <strong>otomatis dibatalkan</strong> dan kamar dilepas kembali.
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Form -->
  <div class="col-lg-5">
    <div class="card mb-4">
        <div class="card-header py-3 fw-700"><i class="bi bi-info-circle me-2"></i>Info Rekening Tujuan</div>
        <div class="card-body">
            <div class="p-3 bg-primary bg-opacity-10 rounded-3">
                <div class="fw-700 mb-1 text-primary">Bank BCA</div>
                <div class="fw-700" style="font-size:1.3rem">1234567890</div>
                <div class="text-muted">a.n. Wisma Permata</div>
            </div>
        </div>
    </div>

    <?php if($kamar_info): ?>
    <div class="card">
        <div class="card-header py-3 fw-700"><i class="bi bi-cash-coin me-2"></i>Kirim Bukti Pembayaran</div>
        <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="kamar_id" value="<?= $kamar_info['id'] ?>">
            <div class="mb-3">
                <label class="form-label fw-600">Kamar</label>
                <input type="text" class="form-control" value="<?= $kamar_info['nomor_kamar'] ?> — <?= $kamar_info['tipe'] ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label fw-600">Mulai Bulan</label>
                <input type="text" class="form-control fw-700" value="<?= namaBulanIndo($next_bulan) ?>" readonly>
                <input type="hidden" name="bulan_bayar" value="<?= $next_bulan ?>">
                <div class="text-muted mt-1" style="font-size:.78rem">
                    Otomatis melanjutkan dari bulan setelah pembayaran terakhir Anda — tidak bisa lompat bulan.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-600">Bayar Berapa Bulan?</label>
                <select name="jumlah_bulan" id="jumlahBulan" class="form-select" onchange="updateRingkasan()">
                    <?php for ($n = 1; $n <= $MAKS_BULAN_SEKALIGUS; $n++): ?>
                    <option value="<?= $n ?>"><?= $n ?> Bulan<?= $n === 12 ? ' (1 Tahun)' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-600">Cakupan Pembayaran</label>
                <input type="text" id="cakupanDisplay" class="form-control" readonly value="<?= namaBulanIndo($next_bulan) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-600">Jumlah Dibayar</label>
                <input type="text" id="jumlahDisplay" class="form-control fw-700 text-primary" value="<?= formatRupiah($kamar_info['harga_per_bulan']) ?>" readonly>
                <div class="text-muted mt-1" style="font-size:.78rem">Dihitung otomatis: harga kamar × jumlah bulan.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-600">Bukti Transfer <span class="text-danger">*</span></label>
                <label for="buktiInput" class="upload-drop d-block text-center" id="uploadDrop">
                    <i class="bi bi-cloud-arrow-up fs-3 text-primary d-block mb-1"></i>
                    <span class="fw-600" id="uploadLabel">Klik untuk pilih file</span>
                    <div class="text-muted mt-1" style="font-size:.78rem">JPG, PNG, atau PDF — maks. 2MB</div>
                </label>
                <input type="file" name="bukti_bayar" id="buktiInput" accept=".jpg,.jpeg,.png,.pdf"
                       class="d-none" required onchange="showFileName(this)">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i>Konfirmasi Pembayaran
            </button>
        </form>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-warning">
        <div class="card-body text-center py-4">
            <i class="bi bi-exclamation-triangle fs-1 text-warning d-block mb-2"></i>
            <h6>Belum ada kamar aktif</h6>
            <p class="text-muted" style="font-size:.9rem">Pesan kamar terlebih dahulu untuk dapat melakukan pembayaran.</p>
            <a href="pemesanan.php" class="btn btn-primary btn-sm">Pesan Kamar</a>
        </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Riwayat -->
  <div class="col-lg-7">
    <div class="card">
        <div class="card-header py-3 fw-700"><i class="bi bi-receipt me-2"></i>Riwayat Pembayaran</div>
        <div class="card-body p-0">
        <?php if($riwayat->num_rows === 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-receipt fs-1 d-block mb-2"></i>
            Belum ada riwayat pembayaran.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Kamar</th><th>Cakupan Bulan</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th>Tgl Bayar</th></tr></thead>
            <tbody>
            <?php while($r = $riwayat->fetch_assoc()): ?>
            <?php
                $jb = max(1, (int)($r['jumlah_bulan'] ?? 1));
                $bulan_akhir_r = date('Y-m', strtotime($r['bulan_bayar'] . '-01 +' . ($jb - 1) . ' months'));
            ?>
            <tr>
                <td class="fw-700"><?= $r['nomor_kamar'] ?></td>
                <td>
                    <div><?= namaBulanIndo($r['bulan_bayar']) ?><?= $jb > 1 ? ' s/d ' . namaBulanIndo($bulan_akhir_r) : '' ?></div>
                    <span class="badge <?= $jb === 1 ? 'bg-light text-dark' : ($jb >= 12 ? 'bg-primary' : 'bg-info text-dark') ?>" style="font-size:.72rem">
                        <?= $jb ?> Bulan<?= $jb === 12 ? ' (1 Tahun)' : '' ?>
                    </span>
                </td>
                <td class="text-primary fw-700"><?= formatRupiah($r['jumlah']) ?></td>
                <td>
                    <?php if(!empty($r['bukti_bayar'])): ?>
                    <a href="<?= $depth ?>uploads/bukti_bayar/<?= urlencode($r['bukti_bayar']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-image"></i> Lihat
                    </a>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:.8rem">-</span>
                    <?php endif; ?>
                </td>
                <td><?= statusBadge($r['status']) ?></td>
                <td style="font-size:.82rem"><?= date('d M Y', strtotime($r['tanggal_bayar'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        </div>
    </div>
  </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<style>
.upload-drop {
    border: 1.5px dashed #E2E8F0; border-radius: 12px;
    padding: 22px 16px; cursor: pointer;
    background: #F8FAFC; transition: border-color .15s ease, background .15s ease;
}
.upload-drop:hover { border-color: #2563EB; background: #EFF6FF; }
.upload-drop.has-file { border-color: #15803d; background: #F0FDF4; }
</style>
<script>
const hargaPerBulan = <?= (int)($kamar_info['harga_per_bulan'] ?? 0) ?>;
const bulanMulai     = <?= json_encode($next_bulan) ?>;
const namaBulanJS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function formatBulanJS(ym) {
    const [y, m] = ym.split('-').map(Number);
    return namaBulanJS[m - 1] + ' ' + y;
}

function tambahBulanJS(ym, n) {
    let [y, m] = ym.split('-').map(Number);
    m += n;
    y += Math.floor((m - 1) / 12);
    m = ((m - 1) % 12) + 1;
    return y + '-' + String(m).padStart(2, '0');
}

function updateRingkasan() {
    const n = parseInt(document.getElementById('jumlahBulan').value, 10) || 1;

    // Jumlah dibayar
    document.getElementById('jumlahDisplay').value = 'Rp ' + (hargaPerBulan * n).toLocaleString('id-ID');

    // Cakupan bulan
    const cakupan = document.getElementById('cakupanDisplay');
    if (n === 1) {
        cakupan.value = formatBulanJS(bulanMulai);
    } else {
        const akhir = tambahBulanJS(bulanMulai, n - 1);
        cakupan.value = formatBulanJS(bulanMulai) + ' s/d ' + formatBulanJS(akhir) + ' (' + n + ' bulan)';
    }
}

document.addEventListener('DOMContentLoaded', updateRingkasan);

function showFileName(input) {
    const label = document.getElementById('uploadLabel');
    const drop  = document.getElementById('uploadDrop');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        drop.classList.add('has-file');
    } else {
        label.textContent = 'Klik untuk pilih file';
        drop.classList.remove('has-file');
    }
}
</script>
