<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user']);
}

function getUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin($redirect = '/index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireRole($roles, $redirect = '/index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
    $user = getUser();
    if (!in_array($user['role'], (array)$roles)) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Cek apakah user saat ini sudah berstatus penghuni aktif dengan kamar.
 * Dipakai sebagai guard di halaman pemesanan untuk memastikan
 * 1 akun hanya bisa menghuni 1 kamar dalam satu waktu.
 */
function sudahPunyaKamar() {
    $user = getUser();
    return $user
        && $user['role'] === 'penghuni'
        && !empty($user['nomor_kamar']);
}

function isAdmin() {
    $user = getUser();
    return $user && in_array($user['role'], ['pemilik', 'pegawai']);
}

function isPemilik() {
    $user = getUser();
    return $user && $user['role'] === 'pemilik';
}

function formatRupiah($number) {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function getNotifCount($conn, $user_id) {
    $r = $conn->query("SELECT COUNT(*) as c FROM notifikasi WHERE user_id=" . (int)$user_id . " AND is_read=0");
    return ($r && $r->num_rows > 0) ? (int)$r->fetch_assoc()['c'] : 0;
}

function getNotifs($conn, $user_id, $limit = 5) {
    return $conn->query("SELECT * FROM notifikasi WHERE user_id=" . (int)$user_id . " ORDER BY created_at DESC LIMIT " . (int)$limit);
}

function addNotif($conn, $user_id, $judul, $pesan, $tipe = 'info', $link = '') {
    $user_id = (int)$user_id;
    $judul   = $conn->real_escape_string($judul);
    $pesan   = $conn->real_escape_string($pesan);
    $tipe    = $conn->real_escape_string($tipe);
    $link    = $conn->real_escape_string($link);
    $conn->query("INSERT INTO notifikasi (user_id, judul, pesan, tipe, link) VALUES ($user_id, '$judul', '$pesan', '$tipe', '$link')");
}

function statusBadge($status) {
    $map = [
        'tersedia'  => '<span class="badge bg-success">Tersedia</span>',
        'dipesan'   => '<span class="badge bg-warning text-dark">Dipesan</span>',
        'terisi'    => '<span class="badge bg-danger">Terisi</span>',
        'pending'   => '<span class="badge bg-secondary">Menunggu</span>',
        'disetujui' => '<span class="badge bg-success">Disetujui</span>',
        'ditolak'   => '<span class="badge bg-danger">Ditolak</span>',
        'selesai'   => '<span class="badge bg-primary">Selesai</span>',
        'diproses'  => '<span class="badge bg-info text-white">Diproses</span>',
        'diantar'   => '<span class="badge bg-warning text-dark">Diantar</span>',
        'ditangani' => '<span class="badge bg-info text-white">Ditangani</span>',
        'verified'  => '<span class="badge bg-success">Terverifikasi</span>',
    ];
    return $map[$status] ?? "<span class='badge bg-secondary'>" . htmlspecialchars($status) . "</span>";
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'Baru saja';
    if ($diff < 3600)  return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return date('d M Y', strtotime($datetime));
}

/**
 * Ubah 'Y-m' (mis. '2026-09') jadi label bulan Indonesia (mis. 'September 2026').
 */
function namaBulanIndo($ym) {
    $bulan = [
        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
        7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
    ];
    [$y, $m] = explode('-', $ym);
    return $bulan[(int)$m] . ' ' . $y;
}

/**
 * Hitung daftar bulan ('Y-m') yang MASIH BISA dipilih untuk pembayaran sewa
 * seorang penghuni pada kamar tertentu — otomatis melompati bulan-bulan yang
 * sudah tercakup pembayaran (verified ATAU masih pending verifikasi), termasuk
 * bulan-bulan yang tercakup pembayaran tahunan (12 bulan ke depan dari bulan
 * yang dipilih). Tidak peduli pergantian tahun — Y-m otomatis lanjut ke tahun
 * berikutnya.
 *
 * Return: ['next_bulan' => 'Y-m', 'options' => ['Y-m', 'Y-m', ...]]
 */
function getBulanBayarTersedia($conn, $uid, $kamar_id, $jumlah_opsi = 24) {
    $uid      = (int)$uid;
    $kamar_id = (int)$kamar_id;

    $occupied = [];
    $q = $conn->query("SELECT bulan_bayar, jumlah_bulan FROM pembayaran_sewa
        WHERE user_id=$uid AND kamar_id=$kamar_id AND status IN ('pending','verified')
          AND bulan_bayar IS NOT NULL AND bulan_bayar != ''");
    while ($b = $q->fetch_assoc()) {
        $n = max(1, (int)$b['jumlah_bulan']);
        for ($i = 0; $i < $n; $i++) {
            $occupied[] = date('Y-m', strtotime($b['bulan_bayar'] . '-01 +' . $i . ' months'));
        }
    }

    if ($occupied) {
        sort($occupied);
        $last       = end($occupied);
        $next_bulan = date('Y-m', strtotime($last . '-01 +1 month'));
    } else {
        // Belum pernah bayar sama sekali -> mulai dari bulan tanggal huni
        // (kalau ada), atau bulan berjalan sebagai fallback.
        $tanggal_huni = getTanggalHuniAktif($conn, $uid, $kamar_id);
        $next_bulan   = $tanggal_huni ? date('Y-m', strtotime($tanggal_huni)) : date('Y-m');
    }

    $options = [];
    for ($i = 0; $i < $jumlah_opsi; $i++) {
        $options[] = date('Y-m', strtotime($next_bulan . '-01 +' . $i . ' months'));
    }

    return ['next_bulan' => $next_bulan, 'options' => $options];
}

function alert($type, $msg) {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
        $msg
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

/**
 * Kirim email berisi link verifikasi akun ke penghuni yang baru mendaftar.
 * Memakai fungsi mail() bawaan PHP (umumnya aktif di hosting cPanel).
 * Jika hosting Anda memblokir mail(), pertimbangkan beralih ke SMTP
 * (PHPMailer) — beri tahu saya jika ingin dibantu setup itu.
 *
 * Return true jika mail() berhasil DITERUSKAN ke MTA server (bukan jaminan
 * sampai ke inbox penerima).
 */
function sendVerificationEmail($to_email, $nama, $token) {
    $link = SITE_URL . '/verify.php?token=' . urlencode($token);

    $subject = 'Verifikasi Akun Wisma Permata';
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:28px;border:1px solid #e2e8f0;border-radius:14px;'>
        <h2 style='color:#2563EB;margin-top:0'>Verifikasi Akun Anda</h2>
        <p>Halo <strong>" . htmlspecialchars($nama) . "</strong>,</p>
        <p>Terima kasih telah mendaftar di <strong>Wisma Permata</strong>. Sebelum bisa login, silakan verifikasi email Anda dengan klik tombol di bawah ini:</p>
        <p style='text-align:center;margin:28px 0;'>
            <a href='" . htmlspecialchars($link) . "' style='background:#2563EB;color:#fff;text-decoration:none;padding:13px 30px;border-radius:9px;font-weight:700;display:inline-block;'>Verifikasi Akun Saya</a>
        </p>
        <p style='font-size:.85rem;color:#64748b;'>Atau salin tautan berikut ke browser Anda:<br>" . htmlspecialchars($link) . "</p>
        <p style='font-size:.85rem;color:#64748b;'>Tautan ini berlaku selama <strong>24 jam</strong>. Jika Anda tidak merasa mendaftar, abaikan saja email ini.</p>
    </div>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";

    return @mail($to_email, $subject, $body, $headers);
}

/**
 * Buat & simpan kode formatted (mis. 'KMR001', 'PGH0001') untuk satu baris
 * yang baru saja di-INSERT, dipanggil dari PHP (BUKAN dari trigger MySQL).
 */
function generateKode($conn, $table, $kolom, $prefix, $id, $padding = 4) {
    $id = (int)$id;
    $kode = $prefix . str_pad($id, $padding, '0', STR_PAD_LEFT);
    try {
        $kode_esc = $conn->real_escape_string($kode);
        $conn->query("UPDATE `$table` SET `$kolom`='$kode_esc' WHERE id=$id");
    } catch (mysqli_sql_exception $e) {
        error_log("generateKode() gagal — tabel: $table, kolom: $kolom, id: $id — " . $e->getMessage());
    }
    return $kode;
}

/**
 * Ambil batas waktu pembayaran (deadline) untuk booking aktif seorang penyewa
 * yang sudah disetujui admin tapi belum ada pembayaran sama sekali diajukan.
 */
/**
 * Ambil tanggal mulai huni (tanggal_mulai) yang aktif untuk seorang penghuni
 * di kamar tertentu. Ini adalah ANCHOR/patokan resmi untuk menghitung
 * jatuh tempo masa sewa — bukan tanggal pembayaran dilakukan.
 * Diambil dari pemesanan_kamar berstatus 'selesai' (booking yang sudah
 * diproses jadi huni aktif). Jika ada beberapa, dipakai yang paling baru.
 */
function getTanggalHuniAktif($conn, $uid, $kamar_id) {
    $uid      = (int)$uid;
    $kamar_id = (int)$kamar_id;
    $r = $conn->query("
        SELECT MAX(tanggal_mulai) AS tanggal_mulai
        FROM pemesanan_kamar
        WHERE user_id=$uid AND kamar_id=$kamar_id
          AND status='selesai' AND tanggal_mulai IS NOT NULL
    ")->fetch_assoc();
    return ($r && $r['tanggal_mulai']) ? $r['tanggal_mulai'] : null;
}

/**
 * Ambil tanggal jatuh tempo sewa aktif seorang penghuni beserta sisa harinya.
 * Dipakai di dashboard penghuni untuk menampilkan banner pengingat otomatis
 * (tanpa harus admin kirim manual).
 *
 * PATOKAN: jatuh_tempo = tanggal_mulai huni (bukan tanggal_bayar) + jumlah
 * bulan yang sudah dibayar (verified). Ini membuat tanggal jatuh tempo tetap
 * konsisten mengikuti tanggal huni asli, tidak ikut geser walau penghuni
 * bayar lebih awal/telat dari jadwalnya.
 *
 * Return array ['jatuh_tempo' => 'Y-m-d', 'sisa_hari' => int, 'periode' => str,
 * 'tanggal_huni' => 'Y-m-d'] atau null jika tidak ada data huni/pembayaran verified.
 */
function getJatuhTempoSewa($conn, $uid) {
    $uid = (int)$uid;

    $u = $conn->query("SELECT nomor_kamar FROM users WHERE id=$uid")->fetch_assoc();
    if (!$u || !$u['nomor_kamar']) return null;

    $k = $conn->query("SELECT id FROM kamar WHERE nomor_kamar='" . $conn->real_escape_string($u['nomor_kamar']) . "'")->fetch_assoc();
    if (!$k) return null;
    $kamar_id = (int)$k['id'];

    $tanggal_huni = getTanggalHuniAktif($conn, $uid, $kamar_id);
    if (!$tanggal_huni) return null;

    $agg = $conn->query("
        SELECT
            SUM(jumlah_bulan) AS total_bulan,
            SUBSTRING_INDEX(GROUP_CONCAT(periode ORDER BY tanggal_bayar DESC SEPARATOR ','), ',', 1) AS periode_terakhir
        FROM pembayaran_sewa
        WHERE user_id=$uid AND kamar_id=$kamar_id AND status='verified'
    ")->fetch_assoc();

    if (!$agg || !$agg['total_bulan']) return null;

    $total_bulan = (int)$agg['total_bulan'];
    $jatuh_tempo = date('Y-m-d', strtotime("$tanggal_huni +$total_bulan months"));
    $sisa = (int)floor((strtotime($jatuh_tempo) - strtotime(date('Y-m-d'))) / 86400);

    return [
        'jatuh_tempo'  => $jatuh_tempo,
        'sisa_hari'    => $sisa,
        'periode'      => $agg['periode_terakhir'],
        'tanggal_huni' => $tanggal_huni,
    ];
}

function getBatasBayarAktif($conn, $uid) {
    $uid = (int)$uid;
    $u = $conn->query("SELECT nomor_kamar FROM users WHERE id=$uid")->fetch_assoc();
    if (!$u || !$u['nomor_kamar']) return null;

    $k = $conn->query("SELECT id FROM kamar WHERE nomor_kamar='" . $conn->real_escape_string($u['nomor_kamar']) . "'")->fetch_assoc();
    if (!$k) return null;
    $kamar_id = (int)$k['id'];

    $p = $conn->query("SELECT batas_bayar FROM pemesanan_kamar
        WHERE user_id=$uid AND kamar_id=$kamar_id AND status='disetujui'
        ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if (!$p || !$p['batas_bayar']) return null;

    $sudah_bayar = $conn->query("SELECT id FROM pembayaran_sewa WHERE user_id=$uid AND kamar_id=$kamar_id")->num_rows > 0;
    if ($sudah_bayar) return null;

    return $p['batas_bayar'];
}

/**
 * Batalkan otomatis pemesanan kamar yang statusnya 'disetujui' tapi sudah
 * lewat batas_bayar (24 jam sejak disetujui admin) dan belum ada pembayaran
 * SAMA SEKALI yang diajukan untuk kamar tersebut.
 *
 * PERUBAHAN: role user hanya diturunkan ke 'calon_penghuni' jika nomor_kamar
 * mereka kosong — mencegah penghapusan status penghuni aktif di kamar lain
 * akibat pemesanan lama yang expire.
 */
function cancelExpiredBookings($conn) {
    $expired = $conn->query("
        SELECT pk.id, pk.user_id, pk.kamar_id
        FROM pemesanan_kamar pk
        WHERE pk.status = 'disetujui'
          AND pk.batas_bayar IS NOT NULL
          AND pk.batas_bayar < NOW()
          AND NOT EXISTS (
              SELECT 1 FROM pembayaran_sewa ps
              WHERE ps.user_id = pk.user_id AND ps.kamar_id = pk.kamar_id
          )
    ");
    if (!$expired || $expired->num_rows === 0) return;

    while ($row = $expired->fetch_assoc()) {
        $id       = (int)$row['id'];
        $user_id  = (int)$row['user_id'];
        $kamar_id = (int)$row['kamar_id'];

        $conn->query("UPDATE pemesanan_kamar SET status='ditolak',
            catatan = CONCAT(COALESCE(catatan,''), ' [Dibatalkan otomatis: lewat batas waktu pembayaran 24 jam]')
            WHERE id=$id");

        $conn->query("UPDATE kamar SET status='tersedia' WHERE id=$kamar_id");

        // Hanya turunkan role jika user belum menghuni kamar lain yang aktif
        // (guard: 1 akun = 1 kamar — jangan hapus status penghuni yang sudah valid)
        $cek_kamar = $conn->query("SELECT nomor_kamar FROM users WHERE id=$user_id")->fetch_assoc();
        if (empty($cek_kamar['nomor_kamar'])) {
            $conn->query("UPDATE users SET role='calon_penghuni', nomor_kamar=NULL WHERE id=$user_id");
        }

        addNotif($conn, $user_id, 'Pemesanan Dibatalkan Otomatis ⏰',
            'Pemesanan kamar Anda dibatalkan karena tidak ada pembayaran dalam 24 jam sejak disetujui. Silakan ajukan pemesanan ulang jika masih berminat.',
            'danger', 'pemesanan.php');

        $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
        while ($a = $admins->fetch_assoc()) {
            addNotif($conn, $a['id'], 'Pemesanan Kadaluarsa ⏰',
                'Sebuah pemesanan kamar dibatalkan otomatis karena penyewa tidak membayar dalam 24 jam sejak disetujui.',
                'warning', 'pemesanan_kamar.php');
        }
    }
}

/**
 * Kirim notifikasi konfirmasi kelanjutan huni (lanjut/keluar) ke setiap
 * penghuni aktif, H-7 sebelum jatuh tempo pembayaran sewa bulan berikutnya.
 */
function checkKonfirmasiHuni($conn) {
    $penghuni = $conn->query("
        SELECT u.id AS user_id, k.id AS kamar_id
        FROM users u
        JOIN kamar k ON k.nomor_kamar = u.nomor_kamar
        WHERE u.role = 'penghuni' AND u.status = 'aktif'
    ");
    if (!$penghuni) return;

    while ($p = $penghuni->fetch_assoc()) {
        $user_id  = (int)$p['user_id'];
        $kamar_id = (int)$p['kamar_id'];

        $tanggal_huni = getTanggalHuniAktif($conn, $user_id, $kamar_id);
        if (!$tanggal_huni) continue;

        $agg = $conn->query("SELECT SUM(jumlah_bulan) AS total_bulan
            FROM pembayaran_sewa
            WHERE user_id=$user_id AND kamar_id=$kamar_id AND status='verified'")->fetch_assoc();
        if (!$agg || !$agg['total_bulan']) continue;

        $total_bulan   = (int)$agg['total_bulan'];
        $jatuh_tempo   = date('Y-m-d', strtotime("$tanggal_huni +$total_bulan months"));
        $h7            = date('Y-m-d', strtotime($jatuh_tempo . ' -7 days'));
        $today         = date('Y-m-d');
        $periode_bulan = date('Y-m', strtotime($jatuh_tempo));

        if ($today >= $h7 && $today < $jatuh_tempo) {
            $sudah_ada = $conn->query("SELECT id FROM konfirmasi_huni
                WHERE user_id=$user_id AND kamar_id=$kamar_id AND periode_bulan='$periode_bulan'");
            if ($sudah_ada->num_rows === 0) {
                $conn->query("INSERT INTO konfirmasi_huni (user_id, kamar_id, periode_bulan)
                    VALUES ($user_id, $kamar_id, '$periode_bulan')");
                addNotif($conn, $user_id, 'Konfirmasi Kelanjutan Kost 📋',
                    "Pembayaran sewa bulan $periode_bulan akan jatuh tempo 7 hari lagi. Mohon konfirmasi apakah Anda akan melanjutkan atau mengakhiri masa kost.",
                    'warning', 'konfirmasi_huni.php');
            }
        }
    }
}

/**
 * Proses OTOMATIS penghuni yang masa sewanya berakhir tanpa perpanjangan.
 */
function checkMasaSewaBerakhir($conn) {
    $penghuni = $conn->query("
        SELECT u.id AS user_id, u.nomor_kamar, k.id AS kamar_id
        FROM users u
        JOIN kamar k ON k.nomor_kamar = u.nomor_kamar
        WHERE u.role = 'penghuni' AND u.status = 'aktif'
    ");
    if (!$penghuni) return;

    while ($p = $penghuni->fetch_assoc()) {
        $user_id     = (int)$p['user_id'];
        $kamar_id    = (int)$p['kamar_id'];
        $nomor_kamar = $p['nomor_kamar'];

        $tanggal_huni = getTanggalHuniAktif($conn, $user_id, $kamar_id);
        if (!$tanggal_huni) continue;

        $agg = $conn->query("SELECT SUM(jumlah_bulan) AS total_bulan
            FROM pembayaran_sewa
            WHERE user_id=$user_id AND kamar_id=$kamar_id AND status='verified'")->fetch_assoc();
        if (!$agg || !$agg['total_bulan']) continue;

        $total_bulan = (int)$agg['total_bulan'];
        $jatuh_tempo = date('Y-m-d', strtotime("$tanggal_huni +$total_bulan months"));
        $today       = date('Y-m-d');

        // total_bulan sudah menjumlahkan SEMUA pembayaran verified, jadi jika
        // hari ini masih < jatuh_tempo, berarti sudah ada pembayaran yang
        // memperpanjang masa sewa — tidak perlu cek "lanjut" terpisah lagi.
        if ($today < $jatuh_tempo) continue;

        $conn->query("UPDATE kamar SET status='tersedia' WHERE id=$kamar_id");
        $conn->query("UPDATE users SET status='nonaktif', role='calon_penghuni', nomor_kamar=NULL WHERE id=$user_id");

        $conn->query("UPDATE konfirmasi_huni SET status='keluar', tanggal_respon=NOW()
            WHERE user_id=$user_id AND kamar_id=$kamar_id AND status='menunggu'");

        addNotif($conn, $user_id, 'Masa Sewa Berakhir 🚪',
            'Masa sewa kamar Anda telah berakhir dan tidak diperpanjang. Akun Anda kini berstatus nonaktif. Hubungi pengelola apabila ingin menyewa kembali.',
            'danger', '');

        $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
        while ($a = $admins->fetch_assoc()) {
            addNotif($conn, $a['id'], 'Penghuni Keluar Otomatis 🚪',
                "Masa sewa kamar $nomor_kamar telah berakhir tanpa perpanjangan. Kamar otomatis kembali berstatus 'Tersedia' dan akun penyewa dinonaktifkan.",
                'warning', 'penghuni.php');
        }
    }
}

// Jalankan pengecekan setiap kali file ini di-load (setiap request ke aplikasi)
if (isset($conn) && $conn instanceof mysqli) {
    cancelExpiredBookings($conn);
    checkKonfirmasiHuni($conn);
    checkMasaSewaBerakhir($conn);
}