<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['pemilik'], $depth . 'login.php');
$page_title = 'Laporan Kelanjutan Huni';

$filter_periode = $_GET['periode'] ?? '';
$where = $filter_periode ? "WHERE kh.periode_bulan='" . $conn->real_escape_string($filter_periode) . "'" : '';

$data = $conn->query("
    SELECT kh.*, u.nama, u.nomor_kamar, u.nomor_telepon,
        (SELECT ps.tanggal_bayar FROM pembayaran_sewa ps
         WHERE ps.user_id = kh.user_id AND ps.kamar_id = kh.kamar_id AND ps.status='verified'
         ORDER BY ps.tanggal_bayar DESC LIMIT 1) AS tanggal_bayar_terakhir
    FROM konfirmasi_huni kh
    JOIN users u ON u.id = kh.user_id
    $where
    ORDER BY kh.periode_bulan DESC, u.nama ASC
");

$daftar_periode = $conn->query("SELECT DISTINCT periode_bulan FROM konfirmasi_huni ORDER BY periode_bulan DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_admin.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="fw-600 mb-0">Filter Periode:</label>
        <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
            <option value="">Semua Periode</option>
            <?php $daftar_periode->data_seek(0); while($dp = $daftar_periode->fetch_assoc()): ?>
            <option value="<?= $dp['periode_bulan'] ?>" <?= $filter_periode === $dp['periode_bulan'] ? 'selected' : '' ?>>
                <?= $dp['periode_bulan'] ?>
            </option>
            <?php endwhile; ?>
        </select>
    </form>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-2"></i>Cetak / Print
    </button>
</div>

<div class="card">
    <div class="card-header py-3 fw-700 d-none d-print-block">
        Laporan Kelanjutan Huni — Wisma Permata
        <?php if($filter_periode): ?> (Periode <?= $filter_periode ?>)<?php endif; ?>
    </div>
    <div class="card-header py-3 fw-700 no-print">
        <i class="bi bi-file-earmark-text me-2"></i>Laporan Kelanjutan Huni Penghuni
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="laporanTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Penghuni</th>
                    <th>No. Kamar</th>
                    <th>No. Telepon</th>
                    <th>Tanggal Bayar Terakhir</th>
                    <th>Periode Dikonfirmasi</th>
                    <th>Status Kelanjutan</th>
                    <th>Tgl Respon</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($r = $data->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="fw-600"><?= htmlspecialchars($r['nama']) ?></td>
                <td><span class="badge bg-light text-dark"><?= $r['nomor_kamar'] ?></span></td>
                <td style="font-size:.85rem"><?= $r['nomor_telepon'] ?></td>
                <td style="font-size:.85rem"><?= $r['tanggal_bayar_terakhir'] ? date('d M Y', strtotime($r['tanggal_bayar_terakhir'])) : '-' ?></td>
                <td><?= $r['periode_bulan'] ?></td>
                <td>
                    <?php if($r['status'] === 'lanjut'): ?>
                    <span class="badge bg-success">Lanjut Ngekost</span>
                    <?php elseif($r['status'] === 'keluar'): ?>
                    <span class="badge bg-danger">Keluar</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Menunggu Respon</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.85rem"><?= $r['tanggal_respon'] ? date('d M Y H:i', strtotime($r['tanggal_respon'])) : '-' ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($no === 1): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data konfirmasi huni.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

<style>
@media print {
    .sidebar, .top-bar, .no-print, footer { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { background: #fff !important; }
}
</style>

<?php include $depth . 'includes/footer.php'; ?>