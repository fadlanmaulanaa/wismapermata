<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik','pegawai'], $depth . 'login.php');
$page_title = 'Tenggat Masa Sewa';
$uid = $_SESSION['user_id'];
$msg = '';

// ── Aksi: kirim peringatan manual ke satu penghuni ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'kirim_peringatan') {
        $target_uid   = (int)$_POST['target_uid'];
        $jatuh_tempo  = $conn->real_escape_string($_POST['jatuh_tempo']);
        $sisa_hari    = (int)$_POST['sisa_hari'];
        $nomor_kamar  = $conn->real_escape_string($_POST['nomor_kamar']);

        if ($sisa_hari <= 0) {
            $judul = 'Masa Sewa Telah Berakhir ⚠️';
            $pesan = "Masa sewa kamar $nomor_kamar Anda telah berakhir pada $jatuh_tempo. "
                   . "Segera hubungi pengelola untuk perpanjangan atau proses check-out.";
            $tipe  = 'danger';
        } elseif ($sisa_hari <= 3) {
            $judul = 'Masa Sewa Hampir Habis 🔴';
            $pesan = "Masa sewa kamar $nomor_kamar Anda tersisa $sisa_hari hari lagi (jatuh tempo: $jatuh_tempo). "
                   . "Segera lakukan pembayaran perpanjangan agar tidak terputus.";
            $tipe  = 'danger';
        } else {
            $judul = 'Pengingat Masa Sewa 🔔';
            $pesan = "Masa sewa kamar $nomor_kamar Anda akan berakhir dalam $sisa_hari hari "
                   . "(jatuh tempo: $jatuh_tempo). Jangan lupa perpanjang sebelum tenggat.";
            $tipe  = 'warning';
        }

        addNotif($conn, $target_uid, $judul, $pesan, $tipe, 'pembayaran.php');
        $msg = "success|Peringatan berhasil dikirim ke penghuni kamar $nomor_kamar.";
    }

    elseif ($_POST['action'] === 'kirim_semua') {
        // Kirim peringatan ke semua penghuni yang jatuh tempo ≤ N hari
        $batas_hari = max(1, (int)($_POST['batas_hari'] ?? 14));
        $kirim_count = 0;

        $semua = $conn->query("
            SELECT
                u.id AS user_id, u.nama, u.nomor_kamar,
                pk.tanggal_mulai AS tanggal_huni,
                DATE_ADD(pk.tanggal_mulai, INTERVAL agg.total_bulan MONTH) AS jatuh_tempo,
                DATEDIFF(
                    DATE_ADD(pk.tanggal_mulai, INTERVAL agg.total_bulan MONTH),
                    CURDATE()
                ) AS sisa_hari
            FROM users u
            JOIN kamar k ON k.nomor_kamar = u.nomor_kamar
            JOIN (
                SELECT user_id, kamar_id, MAX(tanggal_mulai) AS tanggal_mulai
                FROM pemesanan_kamar
                WHERE status = 'selesai' AND tanggal_mulai IS NOT NULL
                GROUP BY user_id, kamar_id
            ) pk ON pk.user_id = u.id AND pk.kamar_id = k.id
            JOIN (
                SELECT user_id, kamar_id,
                    SUM(jumlah_bulan) AS total_bulan,
                    MAX(tanggal_bayar) AS bayar_terakhir
                FROM pembayaran_sewa
                WHERE status = 'verified'
                GROUP BY user_id, kamar_id
            ) agg ON agg.user_id = u.id AND agg.kamar_id = k.id
            WHERE u.role='penghuni' AND u.status='aktif'
            HAVING jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL $batas_hari DAY)
        ");

        if ($semua && $semua->num_rows > 0) {
            while ($row = $semua->fetch_assoc()) {
                $sisa     = (int)$row['sisa_hari'];
                $jt       = date('d M Y', strtotime($row['jatuh_tempo']));
                $nokamar  = $conn->real_escape_string($row['nomor_kamar']);

                if ($sisa <= 0) {
                    $judul = 'Masa Sewa Telah Berakhir ⚠️';
                    $pesan = "Masa sewa kamar {$row['nomor_kamar']} Anda telah berakhir pada $jt. "
                           . "Segera hubungi pengelola.";
                    $tipe  = 'danger';
                } elseif ($sisa <= 3) {
                    $judul = 'Masa Sewa Hampir Habis 🔴';
                    $pesan = "Masa sewa kamar {$row['nomor_kamar']} Anda tersisa $sisa hari lagi (jatuh tempo: $jt). "
                           . "Segera lakukan pembayaran perpanjangan.";
                    $tipe  = 'danger';
                } else {
                    $judul = 'Pengingat Masa Sewa 🔔';
                    $pesan = "Masa sewa kamar {$row['nomor_kamar']} Anda akan berakhir dalam $sisa hari ($jt). "
                           . "Jangan lupa perpanjang sebelum tenggat.";
                    $tipe  = 'warning';
                }

                addNotif($conn, $row['user_id'], $judul, $pesan, $tipe, 'pembayaran.php');
                $kirim_count++;
            }
            $msg = "success|Peringatan berhasil dikirim ke $kirim_count penghuni.";
        } else {
            $msg = 'info|Tidak ada penghuni dengan jatuh tempo dalam $batas_hari hari ke depan.';
        }
    }
}

// ── Query utama: semua penghuni aktif beserta jatuh tempo sewa ──────────────
$data = $conn->query("
    SELECT
        u.id AS user_id,
        u.nama,
        u.nomor_kamar,
        u.nomor_telepon,
        pk.tanggal_mulai AS tanggal_huni,
        agg.total_bulan,
        agg.periode_terakhir AS periode,
        agg.bayar_terakhir,
        DATE_ADD(pk.tanggal_mulai, INTERVAL agg.total_bulan MONTH) AS jatuh_tempo,
        DATEDIFF(
            DATE_ADD(pk.tanggal_mulai, INTERVAL agg.total_bulan MONTH),
            CURDATE()
        ) AS sisa_hari
    FROM users u
    JOIN kamar k ON k.nomor_kamar = u.nomor_kamar
    JOIN (
        SELECT user_id, kamar_id, MAX(tanggal_mulai) AS tanggal_mulai
        FROM pemesanan_kamar
        WHERE status = 'selesai' AND tanggal_mulai IS NOT NULL
        GROUP BY user_id, kamar_id
    ) pk ON pk.user_id = u.id AND pk.kamar_id = k.id
    JOIN (
        SELECT user_id, kamar_id,
            SUM(jumlah_bulan) AS total_bulan,
            MAX(tanggal_bayar) AS bayar_terakhir,
            SUBSTRING_INDEX(GROUP_CONCAT(periode ORDER BY tanggal_bayar DESC), ',', 1) AS periode_terakhir
        FROM pembayaran_sewa
        WHERE status = 'verified'
        GROUP BY user_id, kamar_id
    ) agg ON agg.user_id = u.id AND agg.kamar_id = k.id
    WHERE u.role='penghuni' AND u.status='aktif'
    ORDER BY jatuh_tempo ASC
");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>

<div class="main-content">
<?php if ($msg): list($t,$m) = explode('|', $msg, 2); echo alert($t,$m); endif; ?>

<!-- Kirim ke semua -->
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-megaphone me-2"></i>Kirim Peringatan Massal</span>
    </div>
    <div class="card-body">
        <form method="POST" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="action" value="kirim_semua">
            <label class="fw-600 mb-0">Kirim ke penghuni dengan sisa sewa ≤</label>
            <select name="batas_hari" class="form-select" style="width:auto">
                <option value="3">3 hari</option>
                <option value="7" selected>7 hari</option>
                <option value="14">14 hari</option>
                <option value="30">30 hari</option>
            </select>
            <button type="submit" class="btn btn-warning fw-600"
                onclick="return confirm('Kirim notifikasi peringatan ke semua penghuni yang masuk kriteria?')">
                <i class="bi bi-send me-2"></i>Kirim Sekarang
            </button>
        </form>
    </div>
</div>

<!-- Tabel tenggat -->
<div class="card">
    <div class="card-header py-3">
        <i class="bi bi-calendar-x me-2"></i>Daftar Tenggat Masa Sewa Penghuni
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="masaSewaTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Penghuni</th>
                    <th>No. Kamar</th>
                    <th>Telepon</th>
                    <th>Periode</th>
                    <th>Tanggal Huni</th>
                    <th>Bayar Terakhir</th>
                    <th>Jatuh Tempo</th>
                    <th>Sisa Hari</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$data || $data->num_rows === 0): ?>
            <tr>
                <td colspan="11" class="text-center py-4 text-muted">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2"></i>
                    Tidak ada data penghuni aktif dengan riwayat pembayaran.
                </td>
            </tr>
            <?php else: $no = 1; while ($r = $data->fetch_assoc()):
                $sisa = (int)$r['sisa_hari'];
                // Tentukan warna baris dan label status
                if ($sisa <= 0) {
                    $row_class  = 'table-danger';
                    $status_html = '<span class="badge bg-danger">Sudah Berakhir</span>';
                } elseif ($sisa <= 3) {
                    $row_class  = 'table-danger';
                    $status_html = '<span class="badge bg-danger">Kritis (' . $sisa . ' hari)</span>';
                } elseif ($sisa <= 7) {
                    $row_class  = 'table-warning';
                    $status_html = '<span class="badge bg-warning text-dark">Segera (' . $sisa . ' hari)</span>';
                } elseif ($sisa <= 14) {
                    $row_class  = 'table-info';
                    $status_html = '<span class="badge bg-info text-white">Mendekati (' . $sisa . ' hari)</span>';
                } else {
                    $row_class  = '';
                    $status_html = '<span class="badge bg-success">Aman</span>';
                }
            ?>
            <tr class="<?= $row_class ?>">
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['nomor_kamar']) ?></span></td>
                <td style="font-size:.85rem"><?= htmlspecialchars($r['nomor_telepon']) ?></td>
                <td>
                    <span class="badge <?= $r['periode']==='tahunan' ? 'bg-primary' : 'bg-secondary' ?>">
                        <?= $r['periode'] === 'tahunan' ? 'Tahunan' : 'Bulanan' ?>
                    </span>
                </td>
                <td style="font-size:.85rem"><?= date('d M Y', strtotime($r['tanggal_huni'])) ?></td>
                <td style="font-size:.85rem"><?= date('d M Y', strtotime($r['bayar_terakhir'])) ?></td>
                <td class="fw-600" style="font-size:.9rem">
                    <?= date('d M Y', strtotime($r['jatuh_tempo'])) ?>
                </td>
                <td class="fw-700 <?= $sisa <= 3 ? 'text-danger' : ($sisa <= 7 ? 'text-warning' : '') ?>">
                    <?= $sisa <= 0 ? '<span class="text-danger">Lewat ' . abs($sisa) . ' hari</span>' : $sisa . ' hari' ?>
                </td>
                <td><?= $status_html ?></td>
                <td>
                    <!-- Tombol kirim peringatan per-orang -->
                    <button class="btn btn-sm btn-outline-warning fw-600"
                        onclick="konfirmasiKirim(
                            <?= $r['user_id'] ?>,
                            '<?= addslashes(htmlspecialchars($r['nama'])) ?>',
                            '<?= htmlspecialchars($r['nomor_kamar']) ?>',
                            '<?= date('d M Y', strtotime($r['jatuh_tempo'])) ?>',
                            <?= $sisa ?>
                        )">
                        <i class="bi bi-bell me-1"></i>Ingatkan
                    </button>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Keterangan warna -->
<div class="mt-3 d-flex gap-3 flex-wrap" style="font-size:.82rem">
    <span><span class="badge bg-danger">■</span> Kritis / Sudah Berakhir (≤ 3 hari)</span>
    <span><span class="badge bg-warning text-dark">■</span> Segera (4–7 hari)</span>
    <span><span class="badge bg-info text-white">■</span> Mendekati (8–14 hari)</span>
    <span><span class="badge bg-success">■</span> Aman (> 14 hari)</span>
</div>
</div>

<!-- Modal konfirmasi kirim per-orang -->
<div class="modal fade" id="kirimModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700">
                    <i class="bi bi-bell me-2"></i>Kirim Peringatan
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="kirim_peringatan">
                <input type="hidden" name="target_uid" id="modal_uid">
                <input type="hidden" name="jatuh_tempo" id="modal_jatuh_tempo">
                <input type="hidden" name="sisa_hari" id="modal_sisa">
                <input type="hidden" name="nomor_kamar" id="modal_kamar">
                <div class="modal-body">
                    <div class="p-3 rounded-3 mb-3" id="modal_preview"
                         style="background:#FFF3CD;border:1px solid #FFC107;font-size:.9rem">
                    </div>
                    <p class="text-muted mb-0" style="font-size:.85rem">
                        Pesan di atas akan dikirim sebagai notifikasi ke penghuni dan
                        muncul di halaman dashboard serta menu Notifikasi mereka.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-600">
                        <i class="bi bi-send me-2"></i>Kirim Peringatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $depth . 'includes/footer.php'; ?>
<script>
function konfirmasiKirim(uid, nama, kamar, jatuhTempo, sisaHari) {
    document.getElementById('modal_uid').value          = uid;
    document.getElementById('modal_jatuh_tempo').value  = jatuhTempo;
    document.getElementById('modal_sisa').value         = sisaHari;
    document.getElementById('modal_kamar').value        = kamar;

    let pesan, warna;
    if (sisaHari <= 0) {
        pesan  = `Masa sewa kamar <strong>${kamar}</strong> milik <strong>${nama}</strong> telah <strong>berakhir</strong> sejak ${jatuhTempo}.`;
        warna  = '#FDE8E8';
    } else if (sisaHari <= 3) {
        pesan  = `Masa sewa kamar <strong>${kamar}</strong> milik <strong>${nama}</strong> tersisa <strong>${sisaHari} hari</strong> lagi (jatuh tempo: ${jatuhTempo}).`;
        warna  = '#FDE8E8';
    } else {
        pesan  = `Masa sewa kamar <strong>${kamar}</strong> milik <strong>${nama}</strong> akan berakhir dalam <strong>${sisaHari} hari</strong> (jatuh tempo: ${jatuhTempo}).`;
        warna  = '#FFF3CD';
    }

    const preview = document.getElementById('modal_preview');
    preview.innerHTML = `<i class="bi bi-bell-fill me-2"></i>${pesan}`;
    preview.style.background = warna;
    preview.style.borderColor = sisaHari <= 3 ? '#f87171' : '#FFC107';

    new bootstrap.Modal(document.getElementById('kirimModal')).show();
}
</script>