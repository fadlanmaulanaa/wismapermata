<?php
session_start();
require_once 'config/db.php';
require_once 'config/functions.php';

if(isLoggedIn()) {
    $u = getUser();
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'penghuni/dashboard.php'));
    exit;
}

$kamar = $conn->query("SELECT * FROM kamar ORDER BY lantai, nomor_kamar");
$total = $conn->query("SELECT COUNT(*) c FROM kamar")->fetch_assoc()['c'];
$tersedia = $conn->query("SELECT COUNT(*) c FROM kamar WHERE status='tersedia'")->fetch_assoc()['c'];

// Foto kondisi kamar, dikelompokkan per kamar_id, untuk modal detail
$foto_by_kamar = [];
$foto_q = $conn->query("SELECT * FROM kamar_foto ORDER BY kamar_id, urutan, id");
while ($f = $foto_q->fetch_assoc()) {
    $foto_by_kamar[$f['kamar_id']][] = $f;
}

$page_title = 'Wisma Permata - Kost Nyaman & Terjangkau';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
:root { --primary:#2563EB; --dark:#1E293B; --border:#E2E8F0; }
body { background:#F8FAFC; }
h1, h2, h3 { letter-spacing: -.01em; }
.navbar { background:#fff!important; border-bottom:1px solid var(--border); box-shadow:0 1px 8px rgba(0,0,0,.05); }
.hero { background:linear-gradient(135deg,#1E293B 0%,#1d4ed8 100%); color:#fff; padding:90px 0 70px; }
.hero h1 { font-size:3rem; font-weight:800; }
.hero p { font-size:1.15rem; opacity:.85; }
.feature-icon { width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.6rem; }
.kamar-card { background:#fff;border-radius:16px;border:1.5px solid var(--border);padding:20px;transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;height:100%;cursor:pointer; }
.kamar-card:hover { border-color:#2563EB;box-shadow:0 8px 26px rgba(37,99,235,.14);transform:translateY(-3px); }
.kamar-card.terisi { border-color:#fca5a5;background:#fff5f5; }
.kamar-card.dipesan { border-color:#fcd34d;background:#fffbeb; }
.room-num { font-size:1.8rem;font-weight:700;color:#2563EB; }
.tipe-badge { font-size:.75rem;font-weight:600;padding:3px 10px;border-radius:6px; }
.tipe-standar { background:#EFF6FF;color:#1d4ed8; }
.tipe-deluxe  { background:#F0FDF4;color:#15803d; }
.tipe-premium { background:#FFF7ED;color:#c2410c; }
.tipe-vip     { background:#FDF4FF;color:#7e22ce; }
.stat-num { font-size:2.4rem;font-weight:800;color:#2563EB; }
.section-title { font-size:1.8rem;font-weight:700; }
footer { background:#1E293B;color:rgba(255,255,255,.7); }
.filter-btn { border-radius:8px;font-size:.85rem;transition:.15s; }
.filter-btn.active { background:#2563EB;color:#fff;border-color:#2563EB; }
.btn { border-radius:10px; transition: transform .1s ease, box-shadow .15s ease; }
.btn:active { transform: scale(.98); }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(37,99,235,.25); }

/* CARA MENDAFTAR — kartu langkah bernomor */
.step-row { align-items: stretch; }
.step-col { display: flex; }
.step-card {
    background:#F8FAFC; border:1.5px solid var(--border); border-radius:14px;
    padding:22px 16px; text-align:center; position:relative; width:100%;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.step-card:hover { transform:translateY(-3px); border-color:#2563EB; box-shadow:0 10px 26px rgba(37,99,235,.12); background:#fff; }
.step-num {
    position:absolute; top:-10px; left:50%; transform:translateX(-50%);
    width:26px; height:26px; border-radius:50%; background:#2563EB; color:#fff;
    font-size:.78rem; font-weight:700; display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 6px rgba(37,99,235,.35);
}
.step-arrow { padding: 0 2px; }
@media(max-width: 991px){
    .step-col { flex: 0 0 50%; max-width: 50%; margin-bottom: 12px; }
}

/* MODAL DETAIL KAMAR — foto carousel gaya Instagram: rasio tetap, tidak dipotong/zoom */
#dk_carousel .dk-photo-frame {
    aspect-ratio: 1 / 1;
    background: #F1F5F9;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
#dk_carousel .dk-photo-frame img {
    width: 100%; height: 100%; object-fit: contain;
}
#dk_carousel_dots { margin-bottom: 8px; }
#dk_carousel_dots [data-bs-target] {
    width: 7px; height: 7px; border-radius: 50%; background: rgba(37,99,235,.4); opacity: 1;
}
#dk_carousel_dots .active { background: #2563EB; }
@media(min-width: 768px){
    #dk_carousel .dk-photo-frame { aspect-ratio: 4 / 3; }
}

/* LOKASI — peta interaktif Google Maps, dibungkus rapi & responsif */
.map-embed-wrapper {
    position: relative; width: 100%; aspect-ratio: 4 / 3;
    border-radius: 16px; overflow: hidden; border: 1.5px solid var(--border);
    box-shadow: 0 1px 3px rgba(15,23,42,.05);
}
.map-embed-wrapper iframe { position: absolute; inset: 0; }
@media(min-width: 768px){
    .map-embed-wrapper { aspect-ratio: 16 / 7; }
}

/* TATA TERTIB — panel section & pop-up, menyesuaikan gaya kartu di atas */
.rules-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0; }
.rules-panel-group ul { font-size:.9rem; color:#374151; }
.rules-panel-group li { margin-bottom:8px; line-height:1.55; }
.rules-panel-group li:last-child { margin-bottom:0; }

.rules-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,.75);
    z-index: 2100; display: none; align-items: center; justify-content: center; padding: 20px;
}
.rules-overlay.show { display: flex; }
.rules-modal {
    background:#fff; border-radius:18px; max-width:560px; width:100%;
    max-height:88vh; display:flex; flex-direction:column;
    box-shadow:0 25px 70px rgba(0,0,0,.35); position:relative;
}
.rules-modal-close {
    position:absolute; top:14px; right:16px; border:none; background:none;
    font-size:1.7rem; line-height:1; color:#94a3b8; cursor:pointer; z-index:2;
}
.rules-modal-close:hover { color:#374151; }
.rules-modal-head { padding:28px 30px 16px; border-bottom:1px solid var(--border); }
.rules-modal-head p.eyebrow { color:#2563EB; font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
.rules-modal-body { padding:20px 30px; overflow-y:auto; }
.rules-modal-group { margin-bottom:20px; }
.rules-modal-group:last-child { margin-bottom:0; }
.rules-modal-group-label { font-weight:700; font-size:.92rem; display:flex; align-items:center; gap:8px; margin-bottom:8px; color:#1E293B; }
.rules-modal-group ul { margin:0; padding-left:20px; font-size:.88rem; color:#374151; }
.rules-modal-group li { margin-bottom:6px; line-height:1.5; }
.rules-modal-group li:last-child { margin-bottom:0; }
.rules-modal-footer {
    padding:16px 30px 24px; border-top:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
}
.rules-check { font-size:.82rem; color:#6B7280; display:flex; align-items:center; gap:8px; cursor:pointer; }
@media(max-width:576px){ .rules-modal-footer{ flex-direction:column; align-items:stretch; } .rules-modal-footer .btn{ width:100%; } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand fw-800 text-primary" href="index.php">
        <i class="bi bi-house-heart-fill me-2"></i>Wisma Permata
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link fw-500" href="#caradaftar">Cara Daftar</a></li>
            <li class="nav-item"><a class="nav-link fw-500" href="#kamar">Kamar</a></li>
            <li class="nav-item"><a class="nav-link fw-500" href="#fasilitas">Fasilitas</a></li>
            <li class="nav-item"><a class="nav-link fw-500" href="#tatatertib">Tata Tertib</a></li>
            <li class="nav-item"><a class="nav-link fw-500" href="#lokasi">Lokasi</a></li>
        </ul>
        <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-primary btn-sm px-4">Masuk</a>
            <a href="register.php" class="btn btn-primary btn-sm px-4">Daftar</a>
        </div>
    </div>
  </div>
</nav>

<!-- POP-UP TATA TERTIB -->
<div class="rules-overlay" id="rulesOverlay">
  <div class="rules-modal">
    <button type="button" class="rules-modal-close" id="rulesCloseBtn" aria-label="Tutup">&times;</button>
    <div class="rules-modal-head">
        <p class="eyebrow mb-1">Wajib Dibaca</p>
        <h3 class="fw-800 mb-1">Tata Tertib Wisma Permata</h3>
        <p class="text-muted mb-0" style="font-size:.9rem">Berlaku untuk seluruh penghuni, demi kenyamanan bersama.</p>
    </div>
    <div class="rules-modal-body">
        <div class="rules-modal-group">
            <div class="rules-modal-group-label"><i class="bi bi-shield-lock text-primary"></i> Keamanan & Akses</div>
            <ul>
                <li>Parkir motor <strong>khusus penghuni</strong> di area yang sudah disediakan.</li>
                <li>Selalu <strong>kunci &amp; tutup pintu depan</strong> setiap keluar-masuk, terutama malam hari.</li>
                <li>Tamu lawan jenis wajib pulang <strong>maksimal jam 22.00 WIB</strong>; dilarang menginap tanpa izin pengelola.</li>
                <li>Wajib lapor ke pengelola apabila ada tamu yang menginap.</li>
            </ul>
        </div>
        <div class="rules-modal-group">
            <div class="rules-modal-group-label"><i class="bi bi-people text-primary"></i> Ketertiban & Sosial</div>
            <ul>
                <li>Jaga ketenangan lingkungan, hindari kebisingan di atas jam 22.00 WIB.</li>
                <li>Dilarang membawa/mengonsumsi minuman keras, narkoba, dan barang terlarang lainnya.</li>
                <li>Dilarang merokok di dalam kamar dan area yang bukan diperuntukkan.</li>
            </ul>
        </div>
        <div class="rules-modal-group">
            <div class="rules-modal-group-label"><i class="bi bi-droplet-half text-primary"></i> Kebersihan & Fasilitas</div>
            <ul>
                <li>Jaga kebersihan kamar, kamar mandi, dan area bersama setiap saat.</li>
                <li>Dilarang membuang sampah sembarangan, buang pada tempat yang disediakan.</li>
                <li>Gunakan dapur/kompor umum secara teratur dan bergiliran; bersihkan setelah dipakai.</li>
                <li>Matikan lampu, kipas, dan peralatan listrik saat tidak digunakan.</li>
            </ul>
        </div>
        <div class="rules-modal-group">
            <div class="rules-modal-group-label"><i class="bi bi-file-earmark-text text-primary"></i> Administrasi</div>
            <ul>
                <li>Pembayaran sewa wajib tepat waktu sesuai periode yang dipilih (bulanan/tahunan).</li>
                <li>Barang pribadi menjadi tanggung jawab masing-masing penghuni.</li>
                <li>Kerusakan fasilitas akibat kelalaian penghuni menjadi tanggungan penghuni bersangkutan.</li>
            </ul>
        </div>
    </div>
    <div class="rules-modal-footer">
        <label class="rules-check">
            <input type="checkbox" id="rulesDontShow"> Jangan tampilkan lagi di perangkat ini
        </label>
        <button type="button" class="btn btn-primary px-4" id="rulesAgreeBtn">Saya Mengerti</button>
    </div>
  </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('rulesOverlay');
    var closeBtn = document.getElementById('rulesCloseBtn');
    var agreeBtn = document.getElementById('rulesAgreeBtn');
    var dontShow = document.getElementById('rulesDontShow');
    var KEY = 'wisma_permata_rules_seen';

    if (!localStorage.getItem(KEY)) {
        overlay.classList.add('show');
    }
    function closeRules() {
        if (dontShow.checked) localStorage.setItem(KEY, '1');
        overlay.classList.remove('show');
    }
    closeBtn.addEventListener('click', closeRules);
    agreeBtn.addEventListener('click', closeRules);
})();
</script>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <p class="text-warning fw-600 mb-2"><i class="bi bi-star-fill me-1"></i> Kost Terbaik di Kota Bandung</p>
        <h1 class="mb-4">Selamat Datang di<br><span class="text-warning">Wisma Permata</span></h1>
        <p class="mb-5">Kost nyaman, aman, dan terjangkau dengan berbagai pilihan tipe kamar. Dilengkapi layanan pengelolaan dan aduan yang terintegrasi.</p>
        <div class="d-flex gap-3 flex-wrap">
            <a href="register.php" class="btn btn-warning btn-lg px-5 fw-700">Daftar Sekarang</a>
            <a href="#kamar" class="btn btn-outline-light btn-lg px-5">Lihat Kamar</a>
        </div>
      </div>
      <div class="col-lg-5 text-center mt-5 mt-lg-0">
        <div class="row g-3">
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-4 text-center">
                <div class="stat-num" style="color:#FCD34D"><?= $tersedia ?></div>
                <div style="font-size:.9rem">Kamar Tersedia</div>
            </div></div>
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-4 text-center">
                <div class="stat-num" style="color:#FCD34D"><?= $total ?></div>
                <div style="font-size:.9rem">Total Kamar</div>
            </div></div>
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-4 text-center">
                <div class="stat-num" style="color:#FCD34D">4</div>
                <div style="font-size:.9rem">Tipe Kamar</div>
            </div></div>
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-4 text-center">
                <div class="stat-num" style="color:#FCD34D">24</div>
                <div style="font-size:.9rem">Jam Layanan</div>
            </div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CARA MENDAFTAR -->
<section id="caradaftar" class="py-5" style="background:#fff">
  <div class="container">
    <div class="text-center mb-5">
        <h2 class="section-title">Cara Mendaftar Kost</h2>
        <p class="text-muted">Prosesnya mudah dan bisa dipantau langsung dari akun Anda</p>
    </div>
    <div class="row g-3 step-row">
        <?php $steps = [
            ['bi-person-plus','1','Daftar Akun','Isi data diri dan nomor KTP untuk membuat akun di sistem.'],
            ['bi-door-open','2','Pilih & Ajukan Kamar','Jelajahi kamar yang tersedia, lalu ajukan pemesanan sesuai kebutuhan.'],
            ['bi-hourglass-split','3','Tunggu Persetujuan','Pengelola meninjau pengajuan Anda dan memberikan persetujuan.'],
            ['bi-cash-coin','4','Bayar Sewa (24 Jam)','Setelah disetujui, selesaikan pembayaran dalam waktu 24 jam.'],
            ['bi-check-circle','5','Resmi Jadi Penghuni','Pembayaran diverifikasi, kamar aktif — Anda siap menghuni!'],
        ]; foreach($steps as $i => $s): ?>
        <div class="col-md-4 col-lg step-col">
            <div class="step-card h-100">
                <div class="step-num"><?= $s[1] ?></div>
                <div class="feature-icon mx-auto mb-2" style="background:#EFF6FF;color:#2563EB">
                    <i class="bi <?= $s[0] ?>"></i>
                </div>
                <div class="fw-700 mb-1"><?= $s[2] ?></div>
                <div class="text-muted" style="font-size:.85rem"><?= $s[3] ?></div>
            </div>
        </div>
        <?php if($i < count($steps) - 1): ?>
        <div class="col-auto d-none d-lg-flex align-items-center step-arrow">
            <i class="bi bi-arrow-right fs-4 text-primary opacity-50"></i>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="register.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-person-plus me-2"></i>Mulai Daftar Sekarang
        </a>
    </div>
  </div>
</section>

<!-- FASILITAS -->
<section id="fasilitas" class="py-5">
  <div class="container">
    <div class="text-center mb-5">
        <h2 class="section-title">Fasilitas Wisma Permata</h2>
        <p class="text-muted">Semua yang Anda butuhkan tersedia di sini</p>
    </div>
    <div class="row g-4">
        <?php $fasilitas = [
            ['bi-wifi','WiFi Gratis','Internet cepat untuk semua kamar tanpa batas','#EFF6FF','#2563EB'],
            ['bi-shield-check','Keamanan 24 Jam','CCTV dan petugas keamanan siaga sepanjang waktu','#F0FDF4','#15803d'],
            ['bi-tools','Perawatan Berkala','Tim teknisi siap menangani keluhan dan perbaikan fasilitas','#FFF7ED','#c2410c'],
            ['bi-droplet','Air Bersih','Suplai air bersih yang memadai setiap saat','#EFF6FF','#0369a1'],
            ['bi-p-square','Parkir Luas','Area parkir motor yang luas dan nyaman bagi penghuni','#F0FDF4','#15803d'],
            ['bi-phone','Sistem Digital','Kelola kost dan aduan melalui sistem terintegrasi','#FDF4FF','#7e22ce'],
        ]; foreach($fasilitas as $f): ?>
        <div class="col-md-4 col-6">
            <div class="d-flex gap-3 p-4 bg-white rounded-3 border h-100">
                <div class="feature-icon flex-shrink-0" style="background:<?= $f[3] ?>;color:<?= $f[4] ?>">
                    <i class="bi <?= $f[0] ?>"></i>
                </div>
                <div>
                    <div class="fw-700 mb-1"><?= $f[1] ?></div>
                    <div class="text-muted" style="font-size:.85rem"><?= $f[2] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TATA TERTIB — selalu tampil di halaman, bukan cuma pop-up -->
<section id="tatatertib" class="py-5">
  <div class="container">
    <div class="text-center mb-5">
        <h2 class="section-title">Tata Tertib Wisma Permata</h2>
        <p class="text-muted">Berlaku untuk seluruh penghuni, demi kenyamanan dan keamanan bersama</p>
    </div>
    <div class="row g-4">
        <?php $tatib = [
            ['bi-shield-lock','Keamanan & Akses','#EFF6FF','#2563EB', [
                'Parkir motor <strong>khusus penghuni</strong> di area yang sudah disediakan.',
                'Selalu <strong>kunci &amp; tutup pintu depan</strong> setiap keluar-masuk, terutama malam hari.',
                'Tamu lawan jenis wajib pulang <strong>maksimal jam 22.00 WIB</strong>; dilarang menginap tanpa izin pengelola.',
                'Wajib lapor ke pengelola apabila ada tamu yang menginap.',
            ]],
            ['bi-people','Ketertiban & Sosial','#F0FDF4','#15803d', [
                'Jaga ketenangan lingkungan — hindari kebisingan di atas jam 22.00 WIB.',
                'Dilarang membawa/mengonsumsi minuman keras, narkoba, dan barang terlarang lainnya.',
                'Dilarang merokok di dalam kamar dan area yang bukan diperuntukkan.',
            ]],
            ['bi-droplet-half','Kebersihan & Fasilitas','#FFF7ED','#c2410c', [
                'Jaga kebersihan kamar, kamar mandi, dan area bersama setiap saat.',
                'Dilarang membuang sampah sembarangan — buang pada tempat yang disediakan.',
                'Gunakan dapur/kompor umum secara teratur dan bergiliran; bersihkan setelah dipakai.',
                'Matikan lampu, kipas, dan peralatan listrik saat tidak digunakan.',
            ]],
            ['bi-file-earmark-text','Administrasi','#FDF4FF','#7e22ce', [
                'Pembayaran sewa wajib tepat waktu sesuai periode yang dipilih (bulanan/tahunan).',
                'Barang pribadi menjadi tanggung jawab masing-masing penghuni.',
                'Kerusakan fasilitas akibat kelalaian penghuni menjadi tanggungan penghuni bersangkutan.',
            ]],
        ]; foreach($tatib as $t): ?>
        <div class="col-md-6">
            <div class="p-4 bg-white rounded-3 border h-100 rules-panel-group">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rules-icon" style="background:<?= $t[2] ?>;color:<?= $t[3] ?>">
                        <i class="bi <?= $t[0] ?>"></i>
                    </div>
                    <div class="fw-700"><?= $t[1] ?></div>
                </div>
                <ul class="mb-0 ps-3">
                    <?php foreach($t[4] as $item): ?>
                    <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- KAMAR -->
<section id="kamar" class="py-5" style="background:#fff">
  <div class="container">
    <div class="text-center mb-4">
        <h2 class="section-title">Pilihan Kamar</h2>
        <p class="text-muted">32 kamar dengan berbagai tipe sesuai kebutuhan dan anggaran Anda</p>
    </div>

    <!-- Filter -->
    <div class="d-flex gap-2 flex-wrap justify-content-center mb-4" id="filterBtns">
        <button class="btn btn-outline-secondary filter-btn active" onclick="filterKamar('all',this)">Semua</button>
        <button class="btn btn-outline-secondary filter-btn" onclick="filterKamar('tersedia',this)">Tersedia</button>
        <button class="btn btn-outline-secondary filter-btn" onclick="filterKamar('Standar',this)">Standar</button>
        <button class="btn btn-outline-secondary filter-btn" onclick="filterKamar('Deluxe',this)">Deluxe</button>
        <button class="btn btn-outline-secondary filter-btn" onclick="filterKamar('Premium',this)">Premium</button>
        <button class="btn btn-outline-secondary filter-btn" onclick="filterKamar('VIP',this)">VIP</button>
    </div>

    <div class="row g-3" id="kamarGrid">
    <?php $kamar->data_seek(0); while($k = $kamar->fetch_assoc()): ?>
    <div class="col-lg-3 col-md-4 col-6 kamar-item"
         data-status="<?= $k['status'] ?>" data-tipe="<?= $k['tipe'] ?>">
        <div class="kamar-card <?= $k['status'] ?>" role="button" onclick='detailKamar(<?= json_encode($k) ?>)'>
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="room-num"><?= $k['nomor_kamar'] ?></div>
                <?php
                $bs = ['tersedia'=>'success','dipesan'=>'warning','terisi'=>'danger'];
                $bl = ['tersedia'=>'Tersedia','dipesan'=>'Dipesan','terisi'=>'Terisi'];
                echo "<span class='badge bg-{$bs[$k['status']]}'>{$bl[$k['status']]}</span>";
                ?>
            </div>
            <div class="mb-2 d-flex align-items-center gap-2">
                <span class="tipe-badge tipe-<?= strtolower($k['tipe']) ?>"><?= $k['tipe'] ?></span>
                <?php if(!empty($foto_by_kamar[$k['id']])): ?>
                <span class="text-muted" style="font-size:.76rem"><i class="bi bi-camera me-1"></i><?= count($foto_by_kamar[$k['id']]) ?></span>
                <?php endif; ?>
            </div>
            <div class="text-muted mb-1" style="font-size:.8rem">
                <i class="bi bi-rulers me-1"></i><?= $k['ukuran'] ?>
                &nbsp;|&nbsp; <i class="bi bi-building me-1"></i>Lantai <?= $k['lantai'] ?>
            </div>
            <div class="fw-700 text-primary mb-3" style="font-size:1rem">
                <?= formatRupiah($k['harga_per_bulan']) ?>/bln
            </div>
            <?php if($k['status'] === 'tersedia'): ?>
            <a href="login.php?redirect=pesan&kamar=<?= $k['id'] ?>" class="btn btn-primary btn-sm w-100" onclick="event.stopPropagation()">
                <i class="bi bi-calendar-plus me-1"></i>Pesan Kamar
            </a>
            <?php else: ?>
            <button class="btn btn-secondary btn-sm w-100" onclick="event.stopPropagation()" disabled>
                <i class="bi bi-x-circle me-1"></i>Tidak Tersedia
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- MODAL DETAIL KAMAR -->
<div class="modal fade" id="detailKamarModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px;overflow:hidden">
      <div class="modal-header">
        <div>
            <h5 class="modal-title fw-800 mb-0" id="dk_nomor">-</h5>
            <div class="text-muted" style="font-size:.85rem" id="dk_subjudul">-</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Carousel foto -->
        <div id="dk_carousel" class="carousel slide" data-bs-ride="false" data-bs-touch="true">
            <div class="carousel-inner" id="dk_carousel_inner">
                <!-- diisi via JS -->
            </div>
            <div class="carousel-indicators" id="dk_carousel_dots" style="bottom:10px"><!-- diisi via JS --></div>
            <button class="carousel-control-prev" type="button" data-bs-target="#dk_carousel" data-bs-slide="prev" id="dk_prev_btn">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#dk_carousel" data-bs-slide="next" id="dk_next_btn">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>

        <div class="p-4">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="text-muted" style="font-size:.78rem">Status</div>
                    <div class="fw-700" id="dk_status">-</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted" style="font-size:.78rem">Ukuran</div>
                    <div class="fw-700" id="dk_ukuran">-</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted" style="font-size:.78rem">Lantai</div>
                    <div class="fw-700" id="dk_lantai">-</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted" style="font-size:.78rem">Harga/Bulan</div>
                    <div class="fw-700 text-primary" id="dk_harga">-</div>
                </div>
            </div>
            <div class="mb-3">
                <div class="fw-700 mb-1">Fasilitas</div>
                <div class="text-muted" id="dk_fasilitas" style="font-size:.9rem">-</div>
            </div>
            <div>
                <div class="fw-700 mb-1">Deskripsi</div>
                <div class="text-muted" id="dk_deskripsi" style="font-size:.9rem">-</div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
        <a href="#" id="dk_btn_pesan" class="btn btn-primary px-4">
            <i class="bi bi-calendar-plus me-1"></i>Pesan Kamar Ini
        </a>
      </div>
    </div>
  </div>
</div>

<!-- LOKASI -->
<section id="lokasi" class="py-5">
  <div class="container">
    <div class="text-center mb-5">
        <h2 class="section-title">Lokasi Strategis</h2>
        <p class="text-muted">Mudah dijangkau, ada di jantung Kota Bandung</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="map-embed-wrapper mb-3">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15844.457694573637!2d107.6096949!3d-6.876892150000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e7936e7e8079%3A0xd48bb590f212dcda!2sWisma%20Permata!5e0!3m2!1sen!2sid!4v1785316157699!5m2!1sen!2sid"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 bg-white rounded-3 border">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-primary fs-5"></i>
                <div>
                    <div class="fw-700" style="color:#1E293B">Wisma Permata</div>
                    <div class="text-muted" style="font-size:.85rem">Bandung, Jawa Barat</div>
                </div>
            </div>
            <a href="https://maps.app.goo.gl/tPto66MFrwGmBRsN8" target="_blank" rel="noopener" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-map me-1"></i>Buka di Google Maps
            </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-5" style="background:linear-gradient(135deg,#2563EB,#7c3aed);color:#fff">
  <div class="container text-center">
    <h2 class="fw-800 mb-3">Tertarik Tinggal di Wisma Permata?</h2>
    <p class="mb-4 opacity-75">Daftar sekarang dan pesan kamar impianmu sebelum kehabisan!</p>
    <a href="register.php" class="btn btn-warning btn-lg px-5 fw-700 me-3">Daftar Gratis</a>
    <a href="login.php" class="btn btn-outline-light btn-lg px-5">Sudah Punya Akun?</a>
  </div>
</section>

<!-- FOOTER -->
<footer class="py-4">
  <div class="container text-center">
    <p class="mb-1 fw-600 text-white"><i class="bi bi-house-heart-fill me-2 text-primary"></i>Wisma Permata</p>
    <p style="font-size:.85rem">© 2026 Wisma Permata. Semua hak cipta dilindungi.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const fotoByKamar = <?= json_encode($foto_by_kamar) ?>;
const statusLabel = {tersedia:'Tersedia', dipesan:'Dipesan', terisi:'Terisi'};

function detailKamar(k) {
    document.getElementById('dk_nomor').textContent = 'Kamar ' + k.nomor_kamar;
    document.getElementById('dk_subjudul').textContent = k.tipe + ' · Lantai ' + k.lantai;
    document.getElementById('dk_status').textContent = statusLabel[k.status] || k.status;
    document.getElementById('dk_ukuran').textContent = k.ukuran || '-';
    document.getElementById('dk_lantai').textContent = 'Lantai ' + k.lantai;
    document.getElementById('dk_harga').textContent = 'Rp ' + parseInt(k.harga_per_bulan).toLocaleString('id-ID') + '/bln';
    document.getElementById('dk_fasilitas').textContent = k.fasilitas || '-';
    document.getElementById('dk_deskripsi').textContent = k.deskripsi || '-';

    const btnPesan = document.getElementById('dk_btn_pesan');
    if (k.status === 'tersedia') {
        btnPesan.classList.remove('disabled');
        btnPesan.href = 'login.php?redirect=pesan&kamar=' + k.id;
        btnPesan.innerHTML = '<i class="bi bi-calendar-plus me-1"></i>Pesan Kamar Ini';
    } else {
        btnPesan.classList.add('disabled');
        btnPesan.href = '#';
        btnPesan.innerHTML = '<i class="bi bi-x-circle me-1"></i>Tidak Tersedia';
    }

    // Render carousel foto
    const fotos = fotoByKamar[k.id] || [];
    const carouselEl = document.getElementById('dk_carousel');
    const inner = document.getElementById('dk_carousel_inner');
    const dots  = document.getElementById('dk_carousel_dots');
    const prevBtn = document.getElementById('dk_prev_btn');
    const nextBtn = document.getElementById('dk_next_btn');

    const oldInstance = bootstrap.Carousel.getInstance(carouselEl);
    if (oldInstance) oldInstance.dispose();

    if (fotos.length === 0) {
        inner.innerHTML = `
            <div class="carousel-item active">
                <div class="dk-photo-frame">
                    <div class="text-center text-muted">
                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                        Belum ada foto untuk kamar ini
                    </div>
                </div>
            </div>`;
        dots.innerHTML = '';
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        inner.innerHTML = fotos.map((f, i) => `
            <div class="carousel-item ${i === 0 ? 'active' : ''}">
                <div class="dk-photo-frame">
                    <img src="uploads/kamar/${f.filename}" loading="lazy">
                </div>
            </div>
        `).join('');

        if (fotos.length > 1) {
            dots.innerHTML = fotos.map((f, i) => `
                <button type="button" data-bs-target="#dk_carousel" data-bs-slide-to="${i}" class="${i === 0 ? 'active' : ''}" aria-label="Foto ${i + 1}"></button>
            `).join('');
            prevBtn.style.display = '';
            nextBtn.style.display = '';
        } else {
            dots.innerHTML = '';
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        }
    }

    new bootstrap.Carousel(carouselEl, { ride: false, touch: true });

    new bootstrap.Modal(document.getElementById('detailKamarModal')).show();
}

function filterKamar(val, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.kamar-item').forEach(item => {
        if(val === 'all') { item.style.display = ''; return; }
        if(val === 'tersedia') {
            item.style.display = item.dataset.status === 'tersedia' ? '' : 'none';
        } else {
            item.style.display = item.dataset.tipe === val ? '' : 'none';
        }
    });
}
</script>
</body>
</html>