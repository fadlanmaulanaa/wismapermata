<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni','calon_penghuni'], $depth . 'login.php');
$page_title = 'Notifikasi';
$uid  = $_SESSION['user_id'];
// Mark all read
$conn->query("UPDATE notifikasi SET is_read=1 WHERE user_id=$uid");
$data = $conn->query("SELECT * FROM notifikasi WHERE user_id=$uid ORDER BY created_at DESC");
include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<div class="card">
    <div class="card-header py-3 fw-700"><i class="bi bi-bell me-2"></i>Semua Notifikasi</div>
    <div class="card-body p-0">
    <?php if($data->num_rows === 0): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>Tidak ada notifikasi.
    </div>
    <?php else: while($n = $data->fetch_assoc()):
        $icons = ['success'=>'check-circle-fill text-success','danger'=>'x-circle-fill text-danger','warning'=>'exclamation-circle-fill text-warning','info'=>'info-circle-fill text-primary'];
    ?>
    <div class="p-4 border-bottom d-flex gap-3">
        <i class="bi bi-<?= $icons[$n['tipe']] ?? 'info-circle-fill text-primary' ?> fs-4 flex-shrink-0 mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-700"><?= htmlspecialchars($n['judul']) ?></div>
            <div class="text-muted" style="font-size:.88rem"><?= htmlspecialchars($n['pesan']) ?></div>
            <div class="text-muted mt-1" style="font-size:.78rem"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
        </div>
    </div>
    <?php endwhile; endif; ?>
    </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
