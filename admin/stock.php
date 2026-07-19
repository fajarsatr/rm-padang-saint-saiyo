<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)$_POST['product_id'];
    $action = $_POST['action'];
    $qty = (int)$_POST['qty'];
    if ($qty > 0) {
        if ($action === 'add') {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$qty, $pid]);
            $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, note) VALUES (?, ?, 'restock_manual', 'Ditambahkan admin')")->execute([$pid, $qty]);
            $msg = 'Stok berhasil ditambahkan.';
        } elseif ($action === 'reduce') {
            $pdo->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?")->execute([$qty, $pid]);
            $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, note) VALUES (?, ?, 'koreksi_admin', 'Dikurangi admin')")->execute([$pid, -$qty]);
            $msg = 'Stok berhasil dikurangi.';
        }
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY category, name")->fetchAll();
$movements = $pdo->query("SELECT sm.*, p.name AS product_name FROM stock_movements sm JOIN products p ON p.id = sm.product_id ORDER BY sm.created_at DESC LIMIT 15")->fetchAll();
$reason_labels = ['restock_manual' => 'Tambah Stok Manual', 'penjualan' => 'Penjualan', 'koreksi_admin' => 'Koreksi Admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Stok — Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="dash">
    <?php render_admin_sidebar('stock'); ?>
    <div class="dash-main">
        <div class="dash-header"><h1>Kelola Stok</h1></div>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

        <div class="panel">
            <h2>Stok Saat Ini</h2>
            <table>
                <thead><tr><th>Menu</th><th>Kategori</th><th>Stok</th><th>Tambah / Kurangi</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= ucfirst($p['category']) ?></td>
                    <td><strong><?= $p['stock'] ?></strong></td>
                    <td>
                        <form method="post" class="form-inline">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <input type="number" name="qty" min="1" value="1" required>
                            <button class="btn btn-sm btn-maroon" name="action" value="add" type="submit">+ Tambah</button>
                            <button class="btn btn-sm btn-chili" name="action" value="reduce" type="submit">&minus; Kurangi</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>Riwayat Pergerakan Stok Terbaru</h2>
            <table>
                <thead><tr><th>Waktu</th><th>Menu</th><th>Perubahan</th><th>Alasan</th><th>Catatan</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $m): ?>
                <tr>
                    <td><?= date('d M, H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= htmlspecialchars($m['product_name']) ?></td>
                    <td class="mono" style="color:<?= $m['change_qty'] < 0 ? 'var(--chili)' : 'var(--sage)' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= $m['change_qty'] ?></td>
                    <td><?= $reason_labels[$m['reason']] ?? $m['reason'] ?></td>
                    <td><?= htmlspecialchars($m['note']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
