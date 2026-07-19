<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_login();
if ($_SESSION['role'] !== 'customer') { header('Location: ' . base_url('index.php')); exit; }

// Aksi ubah qty / hapus item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
    } elseif ($action === 'inc') {
        $_SESSION['cart'][$pid]['qty']++;
    } elseif ($action === 'dec') {
        if (($_SESSION['cart'][$pid]['qty'] ?? 1) > 1) $_SESSION['cart'][$pid]['qty']--;
        else unset($_SESSION['cart'][$pid]);
    }
    header('Location: ' . base_url('customer/cart.php'));
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if ($cart) {
    $ids = array_keys($cart);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $qty = min($cart[$p['id']]['qty'], $p['stock']); // batasi qty maksimal sesuai stok
        $subtotal = $qty * $p['price'];
        $total += $subtotal;
        $items[] = array_merge($p, ['qty' => $qty, 'subtotal' => $subtotal]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<?php render_navbar(); ?>
<?php render_cart_drawer(); ?>
<section>
    <div class="container" style="max-width:760px">
        <div class="section-head" style="text-align:left;margin-bottom:24px">
            <span class="eyebrow">Keranjang Anda</span>
            <h2>Periksa Pesanan Sebelum Bayar</h2>
        </div>

        <?php if (!$items): ?>
            <div class="panel" style="text-align:center;color:#8a7761">
                Keranjang masih kosong. <a href="<?= base_url('index.php') ?>#menu" style="color:var(--chili);font-weight:600">Yuk pilih menu</a>.
            </div>
        <?php else: ?>
            <div class="panel">
                <table>
                    <thead><tr><th>Menu</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['name']) ?></td>
                            <td class="mono"><?= rupiah($it['price']) ?></td>
                            <td>
                                <div class="form-inline">
                                    <form method="post"><input type="hidden" name="product_id" value="<?= $it['id'] ?>"><input type="hidden" name="action" value="dec">
                                        <button class="btn btn-outline btn-sm" style="color:var(--maroon);border-color:var(--maroon)" type="submit">&minus;</button></form>
                                    <span><?= $it['qty'] ?></span>
                                    <form method="post"><input type="hidden" name="product_id" value="<?= $it['id'] ?>"><input type="hidden" name="action" value="inc">
                                        <button class="btn btn-outline btn-sm" style="color:var(--maroon);border-color:var(--maroon)" type="submit">+</button></form>
                                </div>
                            </td>
                            <td class="mono"><?= rupiah($it['subtotal']) ?></td>
                            <td>
                                <form method="post"><input type="hidden" name="product_id" value="<?= $it['id'] ?>"><input type="hidden" name="action" value="remove">
                                    <button class="btn btn-sm" style="color:var(--chili)" type="submit">Hapus</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:16px;border-top:2px solid #EEE0C4">
                    <strong style="font-size:1.1rem">Total</strong>
                    <strong class="mono" style="font-size:1.2rem;color:var(--maroon)"><?= rupiah($total) ?></strong>
                </div>
            </div>
            <a href="<?= base_url('customer/checkout.php') ?>" class="btn btn-chili" style="width:100%;justify-content:center">Lanjut ke Pembayaran QRIS</a>
        <?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
</body>
</html>
