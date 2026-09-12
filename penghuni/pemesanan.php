<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni','calon_penghuni'], $depth . 'login.php');
$page_title = 'Pemesanan Kamar';
$uid  = $_SESSION['user_id'];
$user = getUser();
$msg  = '';

// Cek apakah user sudah menjadi penghuni aktif dengan kamar —
// jika ya, blokir semua akses pemesanan (1 akun = 1 kamar).
$sudah_punya_kamar = ($user['role'] === 'penghuni' && !empty($user['nomor_kamar']));

// Ambil info masa sewa aktif (untuk ditampilkan di samping form jika sudah punya kamar)
$masa_sewa_info = $sudah_punya_kamar ? getJatuhTempoSewa($conn, $uid) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kamar_id  = (int)$_POST['kamar_id'];
    $tgl_mulai = $conn->real_escape_string($_POST['tanggal_mulai']);
    $catatan   = $conn->real_escape_string($_POST['catatan'] ?? '');

    // Blokir server-side: penghuni aktif tidak bisa pesan kamar lagi
    if ($sudah_punya_kamar) {
        $msg = 'danger|Anda sudah memiliki kamar aktif. Satu akun hanya dapat menghuni satu kamar.';
    // Cek sudah ada pending/disetujui
    } elseif ($conn->query("SELECT id FROM pemesanan_kamar WHERE user_id=$uid AND status IN ('pending','disetujui')")->num_rows > 0) {
        $msg = 'warning|Anda sudah memiliki pemesanan yang sedang diproses.';
    } else {
        // Klaim kamar secara atomik: UPDATE ini hanya berhasil kalau kamar MASIH 'tersedia'
        // pada saat query dieksekusi, sehingga dua pemesan yang submit nyaris bersamaan
        // tidak bisa sama-sama lolos mengklaim kamar yang sama.
        $conn->begin_transaction();
        $conn->query("UPDATE kamar SET status='dipesan' WHERE id=$kamar_id AND status='tersedia'");

        if ($conn->affected_rows === 1) {
            $ok = $conn->query("INSERT INTO pemesanan_kamar (user_id,kamar_id,tanggal_mulai,catatan,status)
                          VALUES ($uid,$kamar_id,'$tgl_mulai','$catatan','pending')");
            if ($ok) {
                $conn->commit();
                // Notif ke admin
                $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
                $uname  = getUser()['nama'];
                while ($a = $admins->fetch_assoc()) {
                    addNotif($conn, $a['id'], 'Pemesanan Kamar Baru 🏠', "$uname mengajukan pemesanan kamar.", 'info', 'pemesanan_kamar.php');
                }
                addNotif($conn, $uid, 'Pemesanan Terkirim ✅', 'Pemesanan kamar Anda sedang menunggu persetujuan pengelola.', 'success', 'pemesanan.php');
                $msg = 'success|Pemesanan berhasil dikirim! Tunggu konfirmasi dari pengelola.';
            } else {
                $conn->rollback();
                $msg = 'danger|Terjadi kesalahan saat menyimpan pemesanan. Silakan coba lagi.';
            }
        } else {
            $conn->rollback();
            $msg = 'danger|Kamar tidak tersedia atau baru saja dipesan orang lain. Silakan pilih kamar lain.';
        }
    }
}

$kamar_tersedia = $conn->query("SELECT * FROM kamar WHERE status='tersedia' ORDER BY lantai, nomor_kamar");
$riwayat        = $conn->query("SELECT pk.*,k.nomor_kamar,k.tipe,k.harga_per_bulan FROM pemesanan_kamar pk
    JOIN kamar k ON k.id=pk.kamar_id WHERE pk.user_id=$uid ORDER BY pk.created_at DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="row g-4">
  <!-- Form Pesan -->
  <div class="col-lg-5">
    <div class="card">
        <div class="card-header py-3"><i class="bi bi-calendar-plus me-2"></i>Pesan Kamar</div>
        <div class="card-body">
        <?php if ($sudah_punya_kamar): ?>
            <!-- Blokir tampilan form jika sudah punya kamar aktif -->
            <div class="text-center py-4">
                <i class="bi bi-house-check-fill fs-1 text-success d-block mb-3"></i>
                <h6 class="fw-700">Anda sudah memiliki kamar</h6>
                <p class="text-muted" style="font-size:.9rem">
                    Anda saat ini menghuni <strong>Kamar <?= htmlspecialchars($user['nomor_kamar']) ?></strong>.<br>
                    Satu akun hanya dapat menghuni satu kamar dalam satu waktu.
                </p>
                <a href="kamar.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-door-open me-1"></i>Lihat Info Kamar Saya
                </a>
            </div>

            <?php if ($masa_sewa_info): ?>
            <?php
                $sisa   = $masa_sewa_info['sisa_hari'];
                $tgl_jt = date('d M Y', strtotime($masa_sewa_info['jatuh_tempo']));
                if ($sisa <= 0) {
                    $alert_type = 'danger';
                    $alert_icon = 'bi-exclamation-octagon-fill';
                    $alert_judul = 'Masa Sewa Telah Berakhir!';
                    $alert_msg   = "Masa sewa Anda telah berakhir sejak <strong>$tgl_jt</strong>. Segera hubungi pengelola untuk perpanjangan.";
                } elseif ($sisa <= 3) {
                    $alert_type = 'danger';
                    $alert_icon = 'bi-alarm-fill';
                    $alert_judul = "Sisa $sisa Hari Lagi!";
                    $alert_msg   = "Masa sewa Anda akan berakhir pada <strong>$tgl_jt</strong>. Segera bayar perpanjangan.";
                } elseif ($sisa <= 7) {
                    $alert_type = 'warning';
                    $alert_icon = 'bi-bell-fill';
                    $alert_judul = "Sisa $sisa Hari";
                    $alert_msg   = "Masa sewa Anda akan berakhir pada <strong>$tgl_jt</strong>. Jangan lupa perpanjang sebelum tenggat.";
                } else {
                    $alert_type = 'info';
                    $alert_icon = 'bi-info-circle-fill';
                    $alert_judul = "Masa Sewa Aktif";
                    $alert_msg   = "Masa sewa Anda berlaku hingga <strong>$tgl_jt</strong> (sisa $sisa hari).";
                }
            ?>
            <hr class="my-3">
            <div class="alert alert-<?= $alert_type ?> d-flex align-items-start gap-2 mb-3" style="border-radius:10px">
                <i class="bi <?= $alert_icon ?> fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <div class="fw-700" style="font-size:.9rem"><?= $alert_judul ?></div>
                    <div style="font-size:.85rem"><?= $alert_msg ?></div>
                </div>
            </div>
            <div class="d-grid">
                <a href="pembayaran.php" class="btn btn-<?= $sisa <= 7 ? ($sisa <= 3 ? 'danger' : 'warning') : 'outline-primary' ?> btn-sm">
                    <i class="bi bi-cash-coin me-1"></i>
                    <?= $sisa <= 0 ? 'Bayar Perpanjangan' : 'Perpanjang Sewa' ?>
                </a>
            </div>
            <?php endif; ?>

        <?php elseif ($kamar_tersedia->num_rows === 0): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-door-closed fs-1 d-block mb-2"></i>
                Semua kamar sedang tidak tersedia.
            </div>
        <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-600">Pilih Kamar <span class="text-danger">*</span></label>
                <select name="kamar_id" class="form-select" required onchange="updateHarga(this)">
                    <option value="">-- Pilih Kamar --</option>
                    <?php while($k = $kamar_tersedia->fetch_assoc()): ?>
                    <option value="<?= $k['id'] ?>" data-harga="<?= $k['harga_per_bulan'] ?>">
                        <?= $k['nomor_kamar'] ?> — <?= $k['tipe'] ?> | <?= $k['ukuran'] ?> | Lantai <?= $k['lantai'] ?> | <?= formatRupiah($k['harga_per_bulan']) ?>/bln
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3 p-3 bg-light rounded-3 d-none" id="infoHarga">
                <div class="fw-600">Harga Sewa: <span class="text-primary" id="hargaText">-</span>/bulan</div>
                <div class="text-muted" style="font-size:.82rem">Pembayaran melalui transfer bank</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Tanggal Mulai Huni <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Catatan (opsional)</label>
                <textarea name="catatan" class="form-control" rows="3" placeholder="Kebutuhan khusus, dll..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i>Kirim Pemesanan
            </button>
        </form>
        <?php endif; ?>
        </div>
    </div>
  </div>

  <!-- Riwayat -->
  <div class="col-lg-7">
    <div class="card">
        <div class="card-header py-3"><i class="bi bi-clock-history me-2"></i>Riwayat Pemesanan</div>
        <div class="card-body p-0">
        <?php if ($riwayat->num_rows === 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Belum ada riwayat pemesanan.
            </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Kamar</th><th>Tipe</th><th>Harga/Bln</th><th>Tgl Mulai</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r = $riwayat->fetch_assoc()): ?>
            <tr>
                <td class="fw-700"><?= $r['nomor_kamar'] ?></td>
                <td><?= $r['tipe'] ?></td>
                <td class="text-primary fw-600"><?= formatRupiah($r['harga_per_bulan']) ?></td>
                <td style="font-size:.82rem"><?= $r['tanggal_mulai'] ? date('d M Y', strtotime($r['tanggal_mulai'])) : '-' ?></td>
                <td>
                    <?= statusBadge($r['status']) ?>
                    <?php if($r['status'] === 'ditolak' && strpos($r['catatan'] ?? '', 'Dibatalkan otomatis') !== false): ?>
                    <div class="text-muted" style="font-size:.72rem">⏰ Lewat batas waktu bayar</div>
                    <?php endif; ?>
                </td>
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
<script>
function updateHarga(sel) {
    const harga = sel.options[sel.selectedIndex].dataset.harga;
    const info  = document.getElementById('infoHarga');
    if (harga) {
        document.getElementById('hargaText').textContent = 'Rp ' + parseInt(harga).toLocaleString('id-ID');
        info.classList.remove('d-none');
    } else {
        info.classList.add('d-none');
    }
}
</script>