<?php
$depth = '../';
require_once $depth . 'config/functions.php';
requireRole(['penghuni'], $depth . 'login.php'); // HANYA role 'penghuni' yang boleh akses & pesan
$page_title = 'Pesan Makanan Kantin';
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items  = $_POST['items'] ?? [];
    $jumlah = $_POST['jumlah'] ?? [];
    $metode = $conn->real_escape_string($_POST['metode_bayar'] ?? 'cash');
    $catatan= $conn->real_escape_string($_POST['catatan'] ?? '');

    $valid = false;
    $total = 0;
    $detail = [];

    foreach ($items as $menu_id) {
        $menu_id = (int)$menu_id;
        $qty     = max(1, (int)($jumlah[$menu_id] ?? 1));
        $m = $conn->query("SELECT * FROM menu_kantin WHERE id=$menu_id AND tersedia=1")->fetch_assoc();
        if ($m) {
            $sub   = $m['harga'] * $qty;
            $total += $sub;
            $detail[] = ['menu_id'=>$menu_id,'jumlah'=>$qty,'harga'=>$m['harga'],'sub'=>$sub];
            $valid = true;
        }
    }

    if (!$valid) {
        $msg = 'warning|Pilih minimal 1 menu.';
    } else {
        $conn->query("INSERT INTO pemesanan_kantin (user_id,total_harga,metode_bayar,catatan,status)
                      VALUES ($uid,$total,'$metode','$catatan','pending')");
        $pid = $conn->insert_id;
        generateKode($conn, 'pemesanan_kantin', 'kode_pesanan', 'PSN', $pid, 4);
        foreach ($detail as $d) {
            $conn->query("INSERT INTO detail_pemesanan_kantin (pemesanan_id,menu_id,jumlah,harga_satuan,subtotal)
                          VALUES ($pid,{$d['menu_id']},{$d['jumlah']},{$d['harga']},{$d['sub']})");
        }
        // Notif admin
        $admins = $conn->query("SELECT id FROM users WHERE role IN ('pemilik','pegawai') AND status='aktif'");
        $uname  = getUser()['nama'];
        while ($a = $admins->fetch_assoc()) {
            addNotif($conn, $a['id'], 'Pesanan Kantin Baru 🍽️', "$uname memesan " . count($detail) . " item. Total: " . formatRupiah($total), 'info', 'pesanan_kantin.php');
        }
        addNotif($conn, $uid, 'Pesanan Dikirim ✅', 'Pesanan kantin Anda sedang diproses oleh pegawai.', 'success', 'riwayat_kantin.php');
        $msg = 'success|Pesanan berhasil dikirim! Total: ' . formatRupiah($total) . '. Pesanan akan segera disiapkan.';
    }
}

$menu_makanan = $conn->query("SELECT * FROM menu_kantin WHERE kategori='makanan' AND tersedia=1");
$menu_minuman = $conn->query("SELECT * FROM menu_kantin WHERE kategori='minuman' AND tersedia=1");
$menu_snack   = $conn->query("SELECT * FROM menu_kantin WHERE kategori='snack' AND tersedia=1");

include $depth . 'includes/header.php';
include $depth . 'includes/sidebar_penghuni.php';
include $depth . 'includes/topbar.php';
?>
<div class="main-content">
<?php if($msg): list($t,$m) = explode('|',$msg,2); echo alert($t,$m); endif; ?>

<div class="row g-4">
  <!-- Menu -->
  <div class="col-lg-8">
    <form method="POST" id="orderForm">
    <?php
    $sections = [
        ['🍽️ Makanan', $menu_makanan],
        ['🥤 Minuman', $menu_minuman],
        ['🍟 Snack', $menu_snack],
    ];
    foreach ($sections as [$title, $menu]):
        if (!$menu || $menu->num_rows === 0) continue;
    ?>
    <div class="card mb-4">
        <div class="card-header py-3 fw-700"><?= $title ?></div>
        <div class="card-body">
        <div class="row g-3">
        <?php while($m = $menu->fetch_assoc()): ?>
        <div class="col-md-4 col-6">
            <div class="border rounded-3 p-3 menu-item" id="item-<?= $m['id'] ?>">
                <?php if(!empty($m['foto'])): ?>
                <div style="width:100%;height:110px;background:#F1F5F9;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden" class="mb-1">
                    <img src="../uploads/menu_kantin/<?= htmlspecialchars($m['foto']) ?>"
                         style="max-width:100%;max-height:100%;object-fit:contain">
                </div>
                <?php else: ?>
                <div style="font-size:2.2rem;text-align:center">
                    <?= $title[0] === '🍽' ? '🍽️' : ($title[0] === '🥤' ? '🥤' : '🍟') ?>
                </div>
                <?php endif; ?>
                <div class="fw-600 mt-2 text-center" style="font-size:.9rem"><?= htmlspecialchars($m['nama_menu']) ?></div>
                <div class="text-primary fw-700 text-center mb-2"><?= formatRupiah($m['harga']) ?></div>
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="kurang(<?= $m['id'] ?>)">−</button>
                    <input type="number" name="jumlah[<?= $m['id'] ?>]" id="qty-<?= $m['id'] ?>"
                           value="0" min="0" max="10" style="width:44px;text-align:center" class="form-control form-control-sm p-1"
                           onchange="updateCart(<?= $m['id'] ?>,<?= $m['harga'] ?>)">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambah(<?= $m['id'] ?>)">+</button>
                </div>
                <input type="checkbox" name="items[]" id="chk-<?= $m['id'] ?>" value="<?= $m['id'] ?>" class="d-none">
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Metode & Catatan -->
    <div class="card">
        <div class="card-header py-3 fw-700">💳 Pembayaran & Catatan</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-600">Metode Pembayaran</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="metode_bayar" value="cash" id="cash" checked>
                        <label class="form-check-label fw-500" for="cash">💵 Cash saat pengantaran</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="metode_bayar" value="qris" id="qris">
                        <label class="form-check-label fw-500" for="qris">📱 QRIS</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600">Catatan (opsional)</label>
                <textarea name="catatan" class="form-control" rows="2" placeholder="Tidak pedas, extra sambal, dll..."></textarea>
            </div>
        </div>
    </div>
    </form>
  </div>

  <!-- Ringkasan -->
  <div class="col-lg-4">
    <div class="card" style="position:sticky;top:80px">
        <div class="card-header py-3 fw-700">🛒 Ringkasan Pesanan</div>
        <div class="card-body">
            <div id="cartItems"><p class="text-muted text-center py-3" style="font-size:.9rem">Belum ada item dipilih</p></div>
            <hr>
            <div class="d-flex justify-content-between fw-700 mb-4">
                <span>Total</span>
                <span class="text-primary" id="totalText">Rp 0</span>
            </div>
            <button type="submit" form="orderForm" class="btn btn-primary w-100 btn-lg" id="btnOrder" disabled>
                <i class="bi bi-send me-2"></i>Pesan Sekarang
            </button>
            <p class="text-muted text-center mt-2" style="font-size:.8rem">Pesanan diantar ke kamar Anda</p>
        </div>
    </div>
  </div>
</div>
</div>
<?php include $depth . 'includes/footer.php'; ?>
<script>
let cart = {};
function tambah(id) {
    const qty = document.getElementById('qty-'+id);
    qty.value = Math.min(10, parseInt(qty.value||0)+1);
    qty.dispatchEvent(new Event('change'));
}
function kurang(id) {
    const qty = document.getElementById('qty-'+id);
    qty.value = Math.max(0, parseInt(qty.value||0)-1);
    qty.dispatchEvent(new Event('change'));
}
function updateCart(id, harga) {
    const qty = parseInt(document.getElementById('qty-'+id).value) || 0;
    const chk = document.getElementById('chk-'+id);
    const item = document.getElementById('item-'+id);
    if (qty > 0) {
        cart[id] = {qty, harga};
        chk.checked = true;
        item.style.borderColor = '#2563EB';
        item.style.background  = '#EFF6FF';
    } else {
        delete cart[id];
        chk.checked = false;
        item.style.borderColor = '';
        item.style.background  = '';
    }
    renderCart();
}
function renderCart() {
    let html = '', total = 0;
    const keys = Object.keys(cart);
    if (keys.length === 0) {
        html = '<p class="text-muted text-center py-3" style="font-size:.9rem">Belum ada item dipilih</p>';
    } else {
        keys.forEach(id => {
            const {qty, harga} = cart[id];
            const sub = qty * harga;
            total += sub;
            const name = document.querySelector('#item-'+id+' .fw-600')?.textContent || '-';
            html += `<div class="d-flex justify-content-between mb-2" style="font-size:.88rem">
                <span>${name} x${qty}</span>
                <span class="fw-600">Rp ${sub.toLocaleString('id-ID')}</span>
            </div>`;
        });
    }
    document.getElementById('cartItems').innerHTML = html;
    document.getElementById('totalText').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('btnOrder').disabled = keys.length === 0;
}
</script>