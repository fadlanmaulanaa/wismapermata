<?php
$user = getUser();
$depth = $depth ?? '../';
$notifs = isLoggedIn() ? getNotifs($conn, $_SESSION['user_id'], 5) : null;
?>
<div class="top-bar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fw-600 text-muted" style="font-size:.9rem"><?= $page_title ?? '' ?></span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Notifikasi -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <?php if($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem">
                    <?= $notif_count > 9 ? '9+' : $notif_count ?>
                </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong style="font-size:.9rem">Notifikasi</strong>
                    <?php if($notif_count > 0): ?>
                    <a href="<?= $depth ?>includes/mark_read.php" class="text-primary" style="font-size:.8rem">Tandai dibaca</a>
                    <?php endif; ?>
                </div>
                <?php if($notifs && $notifs->num_rows > 0):
                    while($n = $notifs->fetch_assoc()): ?>
                <a href="<?= $n['link'] ?: '#' ?>" class="text-decoration-none">
                    <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                        <div class="d-flex gap-2">
                            <i class="bi bi-<?= $n['tipe']=='success'?'check-circle text-success':($n['tipe']=='danger'?'x-circle text-danger':($n['tipe']=='warning'?'exclamation-circle text-warning':'info-circle text-primary')) ?> fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <div style="font-size:.85rem;font-weight:600;color:#1E293B"><?= htmlspecialchars($n['judul']) ?></div>
                                <div style="font-size:.8rem;color:#64748B"><?= htmlspecialchars($n['pesan']) ?></div>
                                <div style="font-size:.75rem;color:#94A3B8"><?= timeAgo($n['created_at']) ?></div>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endwhile; else: ?>
                <div class="text-center py-4 text-muted" style="font-size:.85rem">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    Tidak ada notifikasi
                </div>
                <?php endif; ?>
                <div class="text-center py-2 border-top">
                    <a href="<?= $depth ?><?= isAdmin() ? 'admin' : 'penghuni' ?>/notifikasi.php" style="font-size:.82rem">Lihat semua notifikasi</a>
                </div>
            </div>
        </div>

        <!-- User -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <div style="width:30px;height:30px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;font-weight:700">
                    <?= strtoupper(substr($user['nama'],0,1)) ?>
                </div>
                <span style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($user['nama']) ?></span>
                <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><span class="dropdown-item-text text-muted" style="font-size:.8rem"><?= ucfirst($user['role']) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <?php if($user['role'] === 'penghuni'): ?>
                <li><a class="dropdown-item" href="<?= $depth ?>penghuni/ganti_password.php"><i class="bi bi-shield-lock me-2"></i>Ganti Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="<?= $depth ?>logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>