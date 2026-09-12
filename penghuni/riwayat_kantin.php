<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni'], $depth . 'login.php');
$page_title = 'Riwayat Pesanan Kantin';
$uid  = $_SESSION['user_id'];
$data = $conn->query("SELECT pk.*,GROUP_CONCAT(mk.nama_menu ORDER BY mk.nama_menu SEPARATOR ', ') AS menu_list
    FROM pemesanan_kantin pk
    LEFT JOIN detail_pemesanan_kantin dpk ON dpk.pemesanan_id=pk.id
    LEFT JOIN menu_kantin mk ON mk.id=dpk.menu_id
    WHERE pk.user_id=$uid GROUP BY pk.id ORDER BY pk.tanggal_pesan DESC");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<div class="card">
    <div class="card-header py-3 fw-700"><i class="bi bi-receipt me-2"></i>Riwayat Pesanan Kantin</div>
    <div class="card-body p-0">
    <?php if($data->num_rows === 0): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-bag-x fs-1 d-block mb-2"></i>
        Belum ada riwayat pesanan.
    </div>
    <?php else: while($r = $data->fetch_assoc()): ?>
    <div class="p-4 border-bottom">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="fw-700">#<?= $r['id'] ?> &nbsp;·&nbsp; <?= date('d M Y H:i', strtotime($r['tanggal_pesan'])) ?></div>
                <div class="text-muted mt-1" style="font-size:.88rem"><?= htmlspecialchars($r['menu_list'] ?? '-') ?></div>
                <div class="mt-1"><span class="badge bg-light text-dark"><?= $r['metode_bayar'] === 'qris' ? '📱 QRIS' : '💵 Cash' ?></span></div>
            </div>
            <div class="text-end">
                <?= statusBadge($r['status']) ?>
                <div class="fw-700 text-primary mt-1"><?= formatRupiah($r['total_harga']) ?></div>
            </div>
        </div>
        <!-- Progress bar status -->
        <div class="mt-3 d-flex gap-2 align-items-center" style="font-size:.78rem">
            <?php $steps = ['pending'=>'Menunggu','diproses'=>'Diproses','diantar'=>'Diantar','selesai'=>'Selesai'];
            $reached = false;
            foreach($steps as $s=>$l):
                $active = $r['status'] === $s;
                if($active) $reached = true;
                $done = !$reached && !$active;
            ?>
            <span class="<?= $done || $active ? 'text-success fw-600' : 'text-muted' ?>">
                <i class="bi bi-<?= $done || $active ? 'check-circle-fill' : 'circle' ?> me-1"></i><?= $l ?>
            </span>
            <?php if($s !== 'selesai'): ?><i class="bi bi-arrow-right text-muted"></i><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endwhile; endif; ?>
    </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>