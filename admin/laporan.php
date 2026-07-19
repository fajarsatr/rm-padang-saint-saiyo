<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_admin();

$period = $_GET['periode'] ?? '7hari';
$days = $period === '30hari' ? 30 : ($period === 'hari' ? 1 : 7);

$sql = "SELECT COALESCE(SUM(oi.subtotal),0) omzet, COALESCE(SUM(oi.cost_price*oi.qty),0) modal, COALESCE(SUM(oi.qty),0) terjual
        FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE o.status IN ('dibayar','diproses','selesai') AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$days]);
$sum = $stmt->fetch();
$omzet = $sum['omzet']; $modal = $sum['modal']; $untung = $omzet - $modal;
$margin = $omzet > 0 ? round($untung / $omzet * 100, 1) : 0;

$per_item = $pdo->prepare("SELECT oi.product_name, SUM(oi.qty) qty, SUM(oi.subtotal) omzet, SUM(oi.cost_price*oi.qty) modal
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status IN ('dibayar','diproses','selesai') AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY oi.product_id, oi.product_name ORDER BY omzet DESC");
$per_item->execute([$days]);
$rows = $per_item->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Keuntungan — Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="dash">
    <?php render_admin_sidebar('laporan'); ?>
    <div class="dash-main">
        <div class="dash-header">
            <h1>Laporan Keuntungan</h1>
            <div class="form-inline">
                <a href="?periode=hari" class="btn btn-sm <?= $period==='hari'?'btn-maroon':'btn-outline' ?>" style="color:<?= $period==='hari'?'#fff':'var(--maroon)' ?>;border-color:var(--maroon)">Hari Ini</a>
                <a href="?periode=7hari" class="btn btn-sm <?= $period==='7hari'?'btn-maroon':'btn-outline' ?>" style="color:<?= $period==='7hari'?'#fff':'var(--maroon)' ?>;border-color:var(--maroon)">7 Hari</a>
                <a href="?periode=30hari" class="btn btn-sm <?= $period==='30hari'?'btn-maroon':'btn-outline' ?>" style="color:<?= $period==='30hari'?'#fff':'var(--maroon)' ?>;border-color:var(--maroon)">30 Hari</a>
            </div>
        </div>
        <div class="kpi-row">
            <div class="kpi-card"><div class="label">Omzet</div><div class="value"><?= rupiah($omzet) ?></div></div>
            <div class="kpi-card"><div class="label">Modal</div><div class="value"><?= rupiah($modal) ?></div></div>
            <div class="kpi-card"><div class="label">Keuntungan</div><div class="value" style="color:var(--sage)"><?= rupiah($untung) ?></div></div>
            <div class="kpi-card"><div class="label">Margin</div><div class="value"><?= $margin ?>%</div></div>
        </div>
        <div class="panel">
            <h2>Rincian per Menu</h2>
            <table>
                <thead><tr><th>Menu</th><th>Terjual</th><th>Omzet</th><th>Modal</th><th>Untung</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): $u = $r['omzet'] - $r['modal']; ?>
                <tr>
                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                    <td><?= $r['qty'] ?></td>
                    <td class="mono"><?= rupiah($r['omzet']) ?></td>
                    <td class="mono"><?= rupiah($r['modal']) ?></td>
                    <td class="mono" style="color:var(--sage)"><?= rupiah($u) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="5" style="text-align:center;color:#8a7761">Belum ada transaksi pada periode ini.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
