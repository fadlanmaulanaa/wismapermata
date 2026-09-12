<?php
$current = basename($_SERVER['PHP_SELF']);
$user = getUser();
$depth = $depth ?? '../';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-house-heart-fill text-primary"></i>
        Wisma Permata
    </div>
    <nav class="py-3">
        <div class="nav-label">Menu Utama</div>
        <a href="<?= $depth ?>admin/dashboard.php" class="nav-link <?= $current=='dashboard.php'?'active':'' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="<?= $depth ?>admin/kamar.php" class="nav-link <?= $current=='kamar.php'?'active':'' ?>">
            <i class="bi bi-door-open"></i> Data Kamar
        </a>
        <a href="<?= $depth ?>admin/penghuni.php" class="nav-link <?= $current=='penghuni.php'?'active':'' ?>">
            <i class="bi bi-people"></i> Data Penghuni
        </a>
        <a href="<?= $depth ?>admin/pemesanan_kamar.php" class="nav-link <?= $current=='pemesanan_kamar.php'?'active':'' ?>">
            <i class="bi bi-calendar-check"></i> Pemesanan Kamar
        </a>

        <div class="nav-label">Keuangan</div>
        <a href="<?= $depth ?>admin/pembayaran.php" class="nav-link <?= $current=='pembayaran.php'?'active':'' ?>">
            <i class="bi bi-cash-coin"></i> Pembayaran Sewa
        </a>
        <a href="<?= $depth ?>admin/masa_sewa.php" class="nav-link <?= $current=='masa_sewa.php'?'active':'' ?>">
            <i class="bi bi-calendar-x"></i> Tenggat Masa Sewa
            <?php
            // Badge merah otomatis jika ada penghuni dengan sisa sewa <= 7 hari
            $__q = $conn->query("
                SELECT COUNT(*) c FROM (
                    SELECT u.id,
                        CASE WHEN ps.periode='tahunan'
                            THEN DATE_ADD(MAX(ps.tanggal_bayar), INTERVAL 12 MONTH)
                            ELSE DATE_ADD(MAX(ps.tanggal_bayar), INTERVAL  1 MONTH)
                        END AS jatuh_tempo
                    FROM users u
                    JOIN kamar k ON k.nomor_kamar = u.nomor_kamar
                    JOIN pembayaran_sewa ps ON ps.user_id = u.id AND ps.kamar_id = k.id AND ps.status='verified'
                    WHERE u.role='penghuni' AND u.status='aktif'
                    GROUP BY u.id, ps.periode
                ) t WHERE jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ");
            $__cnt = $__q ? (int)$__q->fetch_assoc()['c'] : 0;
            if ($__cnt > 0): ?>
                <span class="badge bg-danger ms-auto"><?= $__cnt ?></span>
            <?php endif; ?>
        </a>

        <?php if(!isPemilik()): ?>
        <div class="nav-label">Kantin</div>
        <a href="<?= $depth ?>admin/menu_kantin.php" class="nav-link <?= $current=='menu_kantin.php'?'active':'' ?>">
            <i class="bi bi-egg-fried"></i> Menu Kantin
        </a>
        <a href="<?= $depth ?>admin/pesanan_kantin.php" class="nav-link <?= $current=='pesanan_kantin.php'?'active':'' ?>">
            <i class="bi bi-bag-check"></i> Pesanan Kantin
        </a>
        <?php endif; ?>

        <div class="nav-label">Aduan</div>
        <a href="<?= $depth ?>admin/aduan.php" class="nav-link <?= $current=='aduan.php'?'active':'' ?>">
            <i class="bi bi-chat-square-warning"></i> Laporan Aduan
        </a>

        <?php if(isPemilik()): ?>
        <div class="nav-label">Laporan</div>
        <a href="<?= $depth ?>admin/laporan.php" class="nav-link <?= $current=='laporan.php'?'active':'' ?>">
            <i class="bi bi-bar-chart"></i> Laporan & Rekap
        </a>
        <a href="<?= $depth ?>admin/laporan_huni.php" class="nav-link <?= $current=='laporan_huni.php'?'active':'' ?>">
            <i class="bi bi-file-earmark-text"></i> Laporan Kelanjutan Huni
        </a>
        <a href="<?= $depth ?>admin/users.php" class="nav-link <?= $current=='users.php'?'active':'' ?>">
            <i class="bi bi-person-gear"></i> Kelola Pengguna
        </a>
        <?php endif; ?>

        <div class="nav-label">Akun</div>
        <a href="<?= $depth ?>admin/notifikasi.php" class="nav-link <?= $current=='notifikasi.php'?'active':'' ?>">
            <i class="bi bi-bell"></i> Notifikasi
            <?php if($notif_count > 0): ?>
                <span class="badge bg-danger ms-auto"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
    </nav>
</div>