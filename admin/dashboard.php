<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_admin();

$today = date('Y-m-d');
$omzet_today = $pdo->query("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE status != 'dibatalkan' AND DATE(created_at) = CURDATE()")->fetch()['v'];
$orders_today = $pdo->query("SELECT COUNT(*) v FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['v'];
$pending = $pdo->query("SELECT COUNT(*) v FROM orders WHERE status = 'menunggu_pembayaran'")->fetch()['v'];
$low_stock = $pdo->query("SELECT COUNT(*) v FROM products WHERE stock <= 5 AND is_active = 1")->fetch()['v'];

$recent = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
$labels = ['menunggu_pembayaran'=>'Menunggu Bayar','dibayar'=>'Dibayar','diproses'=>'Diproses','diantar'=>'Diantar','selesai'=>'Selesai','dibatalkan'=>'Batal'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="dash">
    <?php render_admin_sidebar('dashboard'); ?>
    <div class="dash-main">
        <div class="dash-header">
            <h1>Dashboard</h1>
            <span style="color:#8a7761;font-size:.9rem">Halo, <?= htmlspecialchars(current_user()['name']) ?></span>
        </div>
        <div class="kpi-row">
            <div class="kpi-card"><div class="label">Omzet Hari Ini</div><div class="value"><?= rupiah($omzet_today) ?></div></div>
            <div class="kpi-card"><div class="label">Pesanan Hari Ini</div><div class="value"><?= $orders_today ?></div></div>
            <div class="kpi-card"><div class="label">Menunggu Pembayaran</div><div class="value"><?= $pending ?></div></div>
            <div class="kpi-card"><div class="label">Stok Menipis (&le;5)</div><div class="value"><?= $low_stock ?></div></div>
        </div>
        <div class="panel">
            <h2>Pesanan Terbaru</h2>
            <table>
                <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $o): ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars($o['order_code']) ?></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td class="mono"><?= rupiah($o['total_amount']) ?></td>
                        <td><span class="tag <?= $o['status'] ?>"><?= $labels[$o['status']] ?></span></td>
                        <td><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
