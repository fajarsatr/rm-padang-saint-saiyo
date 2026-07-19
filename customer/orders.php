<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_login();
if ($_SESSION['role'] !== 'customer') { header('Location: ' . base_url('index.php')); exit; }

$confirm_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_received') {
    $order_id = (int)$_POST['order_id'];
    // Hanya boleh menandai selesai kalau order itu miliknya sendiri, diantar, dan memang berstatus 'diantar'
    $stmt = $pdo->prepare("UPDATE orders SET status = 'selesai' WHERE id = ? AND user_id = ? AND fulfillment_type = 'delivery' AND status = 'diantar'");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $confirm_msg = 'Terima kasih! Pesanan ditandai selesai.';
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");

$labels = [
    'menunggu_pembayaran' => 'Menunggu Pembayaran',
    'dibayar' => 'Sudah Dibayar',
    'diproses' => 'Sedang Diproses',
    'diantar' => 'Sedang Diantar',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Saya — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<?php render_navbar(); ?>
<?php render_cart_drawer(); ?>
<section>
    <div class="container" style="max-width:760px">
        <div class="section-head" style="text-align:left;margin-bottom:24px">
            <span class="eyebrow">Riwayat</span>
            <h2>Pesanan Saya</h2>
        </div>
        <?php if (isset($_GET['paid'])): ?>
            <div class="alert alert-success">Pembayaran dikonfirmasi. Terima kasih, pesanan Anda sedang kami siapkan!</div>
        <?php endif; ?>
        <?php if ($confirm_msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($confirm_msg) ?></div>
        <?php endif; ?>

        <?php if (!$orders): ?>
            <div class="panel" style="text-align:center;color:#8a7761">Belum ada pesanan. <a href="<?= base_url('index.php') ?>#menu" style="color:var(--chili);font-weight:600">Pesan sekarang</a>.</div>
        <?php endif; ?>

        <?php foreach ($orders as $o): $items_stmt->execute([$o['id']]); $its = $items_stmt->fetchAll(); ?>
        <div class="panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div>
                    <strong class="mono"><?= htmlspecialchars($o['order_code']) ?></strong>
                    <div style="font-size:.78rem;color:#8a7761"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></div>
                </div>
                <span class="tag <?= $o['status'] ?>"><?= $labels[$o['status']] ?></span>
            </div>
            <div style="font-size:.82rem;color:#6b5544;margin-bottom:10px">
                <?php if ($o['fulfillment_type'] === 'delivery'): ?>
                    <strong style="color:var(--chili)">Diantar</strong> ke <?= htmlspecialchars($o['delivery_address']) ?>
                <?php else: ?>
                    <strong>Ambil sendiri</strong> di RM Padang Saint Saiyo
                <?php endif; ?>
            </div>
            <table>
                <tbody>
                <?php foreach ($its as $it): ?>
                    <tr><td><?= htmlspecialchars($it['product_name']) ?> &times; <?= $it['qty'] ?></td><td class="mono" style="text-align:right"><?= rupiah($it['subtotal']) ?></td></tr>
                <?php endforeach; ?>
                <?php if ($o['delivery_fee'] > 0): ?>
                    <tr><td style="color:#8a7761">Ongkos Kirim</td><td class="mono" style="text-align:right;color:#8a7761"><?= rupiah($o['delivery_fee']) ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div style="display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid #EEE0C4">
                <strong>Total</strong><strong class="mono" style="color:var(--maroon)"><?= rupiah($o['total_amount']) ?></strong>
            </div>
            <?php if ($o['fulfillment_type'] === 'delivery' && $o['status'] === 'diantar'): ?>
            <form method="post" style="margin-top:14px" onsubmit="return confirm('Pastikan pesanan sudah benar-benar Anda terima ya.');">
                <input type="hidden" name="action" value="confirm_received">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button type="submit" class="btn btn-maroon btn-sm" style="width:100%;justify-content:center">Sudah Diterima</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
</body>
</html>
