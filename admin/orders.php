<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    // Ambil status & metode saat ini dulu, supaya tidak bisa loncat tahap (mis. dibayar -> selesai langsung)
    $cur_stmt = $pdo->prepare("SELECT status, fulfillment_type FROM orders WHERE id = ?");
    $cur_stmt->execute([$order_id]);
    $current = $cur_stmt->fetch();
    if ($current) {
        $valid_next = [
            'dibayar' => ['diproses', 'dibatalkan'],
            'diproses' => $current['fulfillment_type'] === 'delivery' ? ['diantar', 'dibatalkan'] : ['selesai', 'dibatalkan'],
            'diantar' => ['selesai', 'dibatalkan'],
        ];
        $allowed = $valid_next[$current['status']] ?? [];
        if (in_array($new_status, $allowed)) {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        }
    }
}

$orders = $pdo->query("SELECT o.*, u.name AS customer_name, u.phone FROM orders o JOIN users u ON u.id = o.user_id ORDER BY FIELD(o.status,'dibayar','diproses','diantar','menunggu_pembayaran','selesai','dibatalkan'), o.created_at DESC")->fetchAll();
$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$labels = ['menunggu_pembayaran'=>'Menunggu Bayar','dibayar'=>'Dibayar','diproses'=>'Diproses','diantar'=>'Diantar','selesai'=>'Selesai','dibatalkan'=>'Batal'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk — Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="dash">
    <?php render_admin_sidebar('orders'); ?>
    <div class="dash-main">
        <div class="dash-header"><h1>Pesanan Masuk</h1></div>
        <div class="panel">
            <table>
                <thead><tr><th>Kode</th><th>Pelanggan</th><th>Item</th><th>Metode</th><th>Total</th><th>Status</th><th>Ubah Status</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): $items_stmt->execute([$o['id']]); $its = $items_stmt->fetchAll(); ?>
                <tr>
                    <td class="mono"><?= htmlspecialchars($o['order_code']) ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?><?= $o['phone'] ? '<br><span style="color:#8a7761;font-size:.78rem">'.htmlspecialchars($o['phone']).'</span>' : '' ?></td>
                    <td style="font-size:.82rem"><?php foreach ($its as $it): ?><?= htmlspecialchars($it['product_name']) ?>&times;<?= $it['qty'] ?><br><?php endforeach; ?></td>
                    <td style="font-size:.8rem">
                        <?php if ($o['fulfillment_type'] === 'delivery'): ?>
                            <strong style="color:var(--chili)">Antar</strong><br>
                            <span style="color:#8a7761"><?= htmlspecialchars($o['delivery_address']) ?></span>
                        <?php else: ?>
                            <strong>Ambil Sendiri</strong>
                        <?php endif; ?>
                    </td>
                    <td class="mono"><?= rupiah($o['total_amount']) ?></td>
                    <td><span class="tag <?= $o['status'] ?>"><?= $labels[$o['status']] ?></span></td>
                    <td>
                        <?php if (in_array($o['status'], ['dibayar', 'diproses', 'diantar'])): ?>
                        <form method="post" class="form-inline">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="status" style="padding:6px 8px;border-radius:6px;border:1.5px solid #d8c6a5">
                                <?php if ($o['status'] === 'dibayar'): ?>
                                    <option value="diproses">Proses Pesanan</option>
                                <?php elseif ($o['status'] === 'diproses' && $o['fulfillment_type'] === 'delivery'): ?>
                                    <option value="diantar">Kirim / Sedang Diantar</option>
                                <?php elseif ($o['status'] === 'diproses'): ?>
                                    <option value="selesai">Tandai Diambil / Selesai</option>
                                <?php elseif ($o['status'] === 'diantar'): ?>
                                    <option value="selesai">Tandai Selesai (fallback bila pembeli belum konfirmasi)</option>
                                <?php endif; ?>
                                <option value="dibatalkan">Batalkan</option>
                            </select>
                            <button class="btn btn-sm btn-maroon" type="submit">Simpan</button>
                        </form>
                        <?php else: ?>
                            <span style="color:#8a7761;font-size:.8rem">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
