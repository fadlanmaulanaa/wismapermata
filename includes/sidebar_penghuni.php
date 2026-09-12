<?php
$current = basename($_SERVER['PHP_SELF']);
$depth = $depth ?? '../';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-house-heart-fill text-primary"></i>
        Wisma Permata
    </div>
    <nav class="py-3">
        <div class="nav-label">Menu</div>
        <a href="<?= $depth ?>penghuni/dashboard.php" class="nav-link <?= $current=='dashboard.php'?'active':'' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="<?= $depth ?>penghuni/kamar.php" class="nav-link <?= $current=='kamar.php'?'active':'' ?>">
            <i class="bi bi-door-open"></i> Info Kamar Saya
        </a>
        <a href="<?= $depth ?>penghuni/pemesanan.php" class="nav-link <?= $current=='pemesanan.php'?'active':'' ?>">
            <i class="bi bi-calendar-check"></i> Pemesanan Kamar
        </a>
        <a href="<?= $depth ?>penghuni/pembayaran.php" class="nav-link <?= $current=='pembayaran.php'?'active':'' ?>">
            <i class="bi bi-cash-coin"></i> Pembayaran Sewa
        </a>
        <a href="<?= $depth ?>penghuni/konfirmasi_huni.php" class="nav-link <?= $current=='konfirmasi_huni.php'?'active':'' ?>">
            <i class="bi bi-clipboard-check"></i> Konfirmasi Huni
        </a>

        <div class="nav-label">Kantin</div>
        <a href="<?= $depth ?>penghuni/kantin.php" class="nav-link <?= $current=='kantin.php'?'active':'' ?>">
            <i class="bi bi-egg-fried"></i> Pesan Makanan
        </a>
        <a href="<?= $depth ?>penghuni/riwayat_kantin.php" class="nav-link <?= $current=='riwayat_kantin.php'?'active':'' ?>">
            <i class="bi bi-receipt"></i> Riwayat Pesanan
        </a>

        <div class="nav-label">Aduan</div>
        <a href="<?= $depth ?>penghuni/aduan.php" class="nav-link <?= $current=='aduan.php'?'active':'' ?>">
            <i class="bi bi-chat-square-warning"></i> Laporan Aduan
        </a>

        <div class="nav-label">Akun</div>
        <a href="<?= $depth ?>penghuni/notifikasi.php" class="nav-link <?= $current=='notifikasi.php'?'active':'' ?>">
            <i class="bi bi-bell"></i> Notifikasi
            <?php if($notif_count > 0): ?>
                <span class="badge bg-danger ms-auto"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
    </nav>
</div>