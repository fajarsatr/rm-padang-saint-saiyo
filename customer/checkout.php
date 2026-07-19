<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_login();
if ($_SESSION['role'] !== 'customer') { header('Location: ' . base_url('index.php')); exit; }

// Konfirmasi "sudah bayar" -> finalisasi order, kurangi stok, catat pergerakan stok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_paid') {
    $order_id = (int)$_POST['order_id'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'dibayar' WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);

        $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items->execute([$order_id]);
        foreach ($items->fetchAll() as $it) {
            $pdo->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?")
                ->execute([$it['qty'], $it['product_id']]);
            $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, note) VALUES (?, ?, 'penjualan', ?)")
                ->execute([$it['product_id'], -$it['qty'], 'Order ' . $order_id]);
        }
        $pdo->commit();
        unset($_SESSION['cart'], $_SESSION['current_order_id'], $_SESSION['current_order_code']);
        header('Location: ' . base_url('customer/orders.php?paid=1'));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Gagal memproses pembayaran: ' . $e->getMessage());
    }
}

// Sudah ada order tertunda dari sesi checkout sebelumnya -> langsung tampilkan QRIS-nya lagi
if (isset($_SESSION['current_order_id'])) {
    $order_id = $_SESSION['current_order_id'];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    if (!$order) { unset($_SESSION['current_order_id'], $_SESSION['current_order_code']); header('Location: ' . base_url('customer/cart.php')); exit; }
    $total = $order['total_amount'];
    $fulfillment_type = $order['fulfillment_type'];
    $delivery_address = $order['delivery_address'];
    goto render_qris;
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: ' . base_url('customer/cart.php')); exit; }

$ids = array_keys($cart);
$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$subtotal = 0;
$line_items = [];
foreach ($products as $p) {
    $qty = min($cart[$p['id']]['qty'], $p['stock']);
    if ($qty <= 0) continue;
    $item_subtotal = $qty * $p['price'];
    $subtotal += $item_subtotal;
    $line_items[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $item_subtotal];
}
if (!$line_items) { header('Location: ' . base_url('customer/cart.php')); exit; }

// Tahap 1: pembeli memilih ambil sendiri / diantar (+ alamat kalau diantar), lalu order baru dibuat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'choose_fulfillment') {
    $fulfillment_type = ($_POST['fulfillment_type'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $fulfillment_error = '';
    if ($fulfillment_type === 'delivery' && $delivery_address === '') {
        $fulfillment_error = 'Alamat pengantaran wajib diisi kalau memilih diantar.';
    } else {
        $delivery_fee = $fulfillment_type === 'delivery' ? DELIVERY_FEE : 0;
        $total = $subtotal + $delivery_fee;
        $code = generate_order_code();
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_code, fulfillment_type, delivery_address, delivery_fee, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 'qris', 'menunggu_pembayaran')");
        $stmt->execute([$_SESSION['user_id'], $code, $fulfillment_type, $fulfillment_type === 'delivery' ? $delivery_address : null, $delivery_fee, $total]);
        $order_id = $pdo->lastInsertId();
        foreach ($line_items as $li) {
            $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, cost_price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$order_id, $li['product']['id'], $li['product']['name'], $li['product']['price'], $li['product']['cost_price'], $li['qty'], $li['subtotal']]);
        }
        $_SESSION['current_order_id'] = $order_id;
        $_SESSION['current_order_code'] = $code;
        goto render_qris;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pilih Metode Pesanan — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<?php render_navbar(); ?>
<?php render_cart_drawer(); ?>
<section>
    <div class="container" style="max-width:460px">
        <div class="section-head" style="text-align:left;margin-bottom:20px">
            <span class="eyebrow">Sebelum Bayar</span>
            <h2>Pesanan Ini Mau Diambil atau Diantar?</h2>
        </div>
        <?php if (!empty($fulfillment_error)): ?><div class="alert alert-error"><?= htmlspecialchars($fulfillment_error) ?></div><?php endif; ?>
        <form method="post" class="panel" id="fulfillForm">
            <input type="hidden" name="action" value="choose_fulfillment">
            <label class="fulfill-option">
                <input type="radio" name="fulfillment_type" value="pickup" <?= (($_POST['fulfillment_type'] ?? 'pickup') === 'pickup') ? 'checked' : '' ?>>
                <div>
                    <strong>Ambil Sendiri di Tempat</strong>
                    <span>Gratis — datang ke RM Padang Saint Saiyo setelah pesanan siap.</span>
                </div>
            </label>
            <label class="fulfill-option">
                <input type="radio" name="fulfillment_type" value="delivery" <?= (($_POST['fulfillment_type'] ?? '') === 'delivery') ? 'checked' : '' ?>>
                <div>
                    <strong>Diantar ke Alamat Saya</strong>
                    <span>Ongkos kirim flat <?= rupiah(DELIVERY_FEE) ?> untuk area sekitar kampus.</span>
                </div>
            </label>
            <div class="field" id="addressField" style="display:none">
                <label>Alamat Pengantaran</label>
                <textarea name="delivery_address" rows="3" placeholder="Nama gedung/kos, jalan, patokan..."><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
            </div>
            <div class="fulfill-summary">
                <div><span>Subtotal</span><span class="mono"><?= rupiah($subtotal) ?></span></div>
                <div id="feeRow" style="display:none"><span>Ongkos Kirim</span><span class="mono"><?= rupiah(DELIVERY_FEE) ?></span></div>
            </div>
            <button type="submit" class="btn btn-chili" style="width:100%;justify-content:center">Lanjut ke Pembayaran QRIS</button>
        </form>
        <a href="<?= base_url('customer/cart.php') ?>" class="auth-alt" style="display:block;margin-top:12px">&larr; Kembali ke keranjang</a>
    </div>
</section>
<?php render_footer(); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const radios = document.querySelectorAll('input[name="fulfillment_type"]');
    const addressField = document.getElementById('addressField');
    const feeRow = document.getElementById('feeRow');
    const addressInput = addressField.querySelector('textarea');
    function sync(){
        const isDelivery = document.querySelector('input[name="fulfillment_type"]:checked').value === 'delivery';
        addressField.style.display = isDelivery ? 'block' : 'none';
        feeRow.style.display = isDelivery ? 'flex' : 'none';
        addressInput.required = isDelivery;
    }
    radios.forEach(r => r.addEventListener('change', sync));
    sync();
});
</script>
</body>
</html>
<?php
exit;

render_qris:
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran QRIS — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<?php render_navbar(); ?>
<?php render_cart_drawer(); ?>
<section>
    <div class="container" style="max-width:420px;text-align:center">
        <div class="panel qris-card">
            <div class="qris-header">
                <span class="qris-badge">QRIS</span>
                <span class="qris-header-sub">Quick Response Code Indonesian Standard</span>
            </div>
            <div class="qris-merchant">
                <strong>RM PADANG SAINT SAIYO</strong>
                <span>NMID: ID10<?= str_pad(abs(crc32($_SESSION['current_order_code'])) % 100000000, 8, '0', STR_PAD_LEFT) ?></span>
                <span>Kode Pesanan: <?= htmlspecialchars($_SESSION['current_order_code']) ?></span>
                <span><?= $fulfillment_type === 'delivery' ? 'Diantar ke: ' . htmlspecialchars($delivery_address) : 'Ambil sendiri di tempat' ?></span>
            </div>
            <canvas id="qris" width="260" height="260" class="qris-canvas"></canvas>
            <p style="color:#6b5544;font-size:.82rem;margin:14px 0 2px">Nominal</p>
            <p class="mono" style="font-size:1.7rem;color:var(--maroon);font-weight:700;margin:0 0 6px"><?= rupiah($total) ?></p>
            <div class="qris-accepted">
                <span>Berlaku di semua e-wallet &amp; m-banking</span>
            </div>
            <p style="font-size:.74rem;color:#8a7761;margin-top:14px;border-top:1px dashed #D9C6A8;padding-top:10px">Ini adalah simulasi tampilan QRIS untuk keperluan demo/tugas — bukan QRIS resmi terbitan bank/PJSP berizin Bank Indonesia.</p>
            <form method="post" style="margin-top:16px">
                <input type="hidden" name="action" value="confirm_paid">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" class="btn btn-chili" style="width:100%;justify-content:center">Saya Sudah Bayar</button>
            </form>
            <a href="<?= base_url('customer/cart.php') ?>" class="auth-alt" style="display:block;margin-top:12px">&larr; Kembali ke keranjang</a>
        </div>
    </div>
</section>
<?php render_footer(); ?>
<script>
// Menggambar pola ala-QR yang lebih akurat secara struktur (finder + timing + alignment)
// untuk keperluan simulasi tampilan — BUKAN generator QRIS/QR resmi yang bisa dipindai bank.
(function(){
    const cv = document.getElementById('qris'), ctx = cv.getContext('2d');
    const seedStr = <?= json_encode($_SESSION['current_order_code'] . '-' . $total) ?>;
    let seed = 0; for (const c of seedStr) seed = (seed * 31 + c.charCodeAt(0)) >>> 0;
    function rnd(){ seed = (seed * 1103515245 + 12345) >>> 0; return (seed >>> 8) / 16777216; }

    const N = 29, quiet = 1, cell = cv.width / (N + quiet * 2);
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cv.width, cv.height);
    ctx.fillStyle = '#1a1210';

    function isFinderZone(x, y) {
        return (x < 8 && y < 8) || (x > N - 9 && y < 8) || (x < 8 && y > N - 9);
    }
    function isTimingZone(x, y) {
        return (y === 6 && x >= 8 && x <= N - 9) || (x === 6 && y >= 8 && y <= N - 9);
    }
    function isAlignmentZone(x, y) {
        const ax = N - 9, ay = N - 9;
        return x >= ax - 2 && x <= ax + 2 && y >= ay - 2 && y <= ay + 2;
    }

    for (let y = 0; y < N; y++) {
        for (let x = 0; x < N; x++) {
            if (isFinderZone(x, y) || isAlignmentZone(x, y)) continue;
            if (isTimingZone(x, y)) {
                if ((x + y) % 2 === 0) ctx.fillRect((x + quiet) * cell, (y + quiet) * cell, cell, cell);
                continue;
            }
            if (rnd() > 0.52) ctx.fillRect((x + quiet) * cell, (y + quiet) * cell, cell, cell);
        }
    }

    function finder(gx, gy) {
        const px = (gx + quiet) * cell, py = (gy + quiet) * cell;
        ctx.fillStyle = '#1a1210'; ctx.fillRect(px, py, cell * 7, cell * 7);
        ctx.fillStyle = '#fff'; ctx.fillRect(px + cell, py + cell, cell * 5, cell * 5);
        ctx.fillStyle = '#A8321A'; ctx.fillRect(px + cell * 2, py + cell * 2, cell * 3, cell * 3);
    }
    finder(0, 0); finder(N - 7, 0); finder(0, N - 7);

    const ax = (N - 9 - 2 + quiet) * cell, ay = (N - 9 - 2 + quiet) * cell;
    ctx.fillStyle = '#1a1210'; ctx.fillRect(ax, ay, cell * 5, cell * 5);
    ctx.fillStyle = '#fff'; ctx.fillRect(ax + cell, ay + cell, cell * 3, cell * 3);
    ctx.fillStyle = '#1a1210'; ctx.fillRect(ax + cell * 2, ay + cell * 2, cell, cell);
})();
</script>
</body>
</html>
