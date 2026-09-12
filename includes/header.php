<?php
require_once __DIR__ . '/../config/functions.php';
$user = getUser();
$notif_count = isLoggedIn() ? getNotifCount($conn, $_SESSION['user_id']) : 0;
$depth = $depth ?? '../';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $page_title ?? 'Wisma Permata' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563EB;
    --primary-dark: #1d4ed8;
    --secondary: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --dark: #1E293B;
    --light: #F8FAFC;
    --border: #E2E8F0;
    --sidebar-w: 260px;
}
* { font-family: 'Plus Jakarta Sans', sans-serif; }
body { background: var(--light); color: var(--dark); }

/* NAVBAR */
.navbar-brand { font-weight: 700; font-size: 1.3rem; }
.navbar { background: #fff; border-bottom: 1px solid var(--border); box-shadow: 0 1px 8px rgba(0,0,0,.06); }

/* SIDEBAR */
.sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: var(--dark);
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    overflow-y: auto;
    transition: .3s;
}
.sidebar-brand {
    padding: 20px 24px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.1);
    display: flex; align-items: center; gap: 10px;
}
.sidebar .nav-link {
    color: rgba(255,255,255,.7);
    padding: 10px 20px;
    border-radius: 8px;
    margin: 2px 10px;
    display: flex; align-items: center; gap: 10px;
    font-size: .9rem; font-weight: 500;
    transition: .2s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background: rgba(255,255,255,.12);
    color: #fff;
}
.sidebar .nav-label {
    font-size: .7rem; font-weight: 700;
    color: rgba(255,255,255,.4);
    padding: 12px 24px 4px;
    text-transform: uppercase; letter-spacing: 1px;
}
.main-content { margin-left: var(--sidebar-w); padding: 24px; }
.top-bar {
    background: #fff; border-bottom: 1px solid var(--border);
    padding: 12px 24px;
    margin-left: var(--sidebar-w);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 99;
}

/* CARDS */
.stat-card {
    background: #fff; border-radius: 14px;
    padding: 22px; border: 1px solid var(--border);
    transition: .2s;
}
.stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.stat-icon {
    width: 52px; height: 52px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
}
.card { border: 1px solid var(--border); border-radius: 14px; }
.card-header { background: #fff; border-bottom: 1px solid var(--border); font-weight: 600; }

/* KAMAR CARD */
.kamar-card {
    background: #fff; border-radius: 14px;
    border: 2px solid var(--border);
    padding: 20px; transition: .2s; cursor: pointer;
}
.kamar-card:hover { border-color: var(--primary); box-shadow: 0 4px 20px rgba(37,99,235,.1); }
.kamar-card.terisi { border-color: #fca5a5; background: #fff5f5; }
.kamar-card.dipesan { border-color: #fcd34d; background: #fffbeb; }
.kamar-card.tersedia { border-color: #6ee7b7; }
.room-num { font-size: 1.6rem; font-weight: 700; color: var(--primary); }

/* NOTIF */
.notif-dot {
    width: 8px; height: 8px; background: var(--danger);
    border-radius: 50%; display: inline-block; margin-left: 4px;
}
.notif-item { padding: 10px 16px; border-bottom: 1px solid var(--border); }
.notif-item:hover { background: var(--light); }
.notif-item.unread { background: #EFF6FF; }

/* BADGE STATUS */
.badge { font-weight: 500; font-size: .78rem; }

/* FORM */
.form-control, .form-select {
    border-radius: 8px; border: 1px solid var(--border);
    padding: 10px 14px; font-size: .9rem;
}
.btn { border-radius: 8px; font-weight: 500; }
.btn-primary { background: var(--primary); border-color: var(--primary); }
.btn-primary:hover { background: var(--primary-dark); }

/* TABLE */
.table th { font-weight: 600; font-size: .85rem; color: #64748B; text-transform: uppercase; letter-spacing: .5px; }
.table td { vertical-align: middle; font-size: .9rem; }

/* PUBLIC NAVBAR */
.public-nav { background: #fff; border-bottom: 1px solid var(--border); }
.hero { background: linear-gradient(135deg, #1E293B 0%, #2563EB 100%); color: #fff; padding: 80px 0; }

/* RESPONSIVE */
@media(max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.show { transform: translateX(0); }
    .main-content, .top-bar { margin-left: 0; }
}

.tipe-standar { background: #EFF6FF; color: #1d4ed8; }
.tipe-deluxe  { background: #F0FDF4; color: #15803d; }
.tipe-premium { background: #FFF7ED; color: #c2410c; }
.tipe-vip     { background: #FDF4FF; color: #7e22ce; }
</style>
</head>
<body>
