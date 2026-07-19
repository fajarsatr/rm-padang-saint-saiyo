<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['role'] !== 'customer') {
    echo json_encode(['ok' => false, 'need_login' => true]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$pid = (int)($_POST['product_id'] ?? 0);

if ($action === 'inc' && $pid) {
    $_SESSION['cart'][$pid]['qty'] = ($_SESSION['cart'][$pid]['qty'] ?? 0) + 1;
} elseif ($action === 'dec' && $pid) {
    if (($_SESSION['cart'][$pid]['qty'] ?? 1) > 1) $_SESSION['cart'][$pid]['qty']--;
    else unset($_SESSION['cart'][$pid]);
} elseif ($action === 'remove' && $pid) {
    unset($_SESSION['cart'][$pid]);
}

$data = get_cart_items($pdo);

// Render potongan HTML daftar item, dipakai langsung oleh drawer di sisi JS
ob_start();
if (!$data['items']) {
    echo '<p class="cart-empty">Keranjang masih kosong. Yuk pilih menu di bawah.</p>';
} else {
    foreach ($data['items'] as $it) {
        ?>
        <div class="drawer-item" data-pid="<?= $it['id'] ?>">
            <div class="drawer-item-info">
                <strong><?= htmlspecialchars($it['name']) ?></strong>
                <span class="mono"><?= rupiah($it['price']) ?></span>
            </div>
            <div class="drawer-item-qty">
                <button type="button" class="qty-btn" data-act="dec" data-pid="<?= $it['id'] ?>">&minus;</button>
                <span><?= $it['qty'] ?></span>
                <button type="button" class="qty-btn" data-act="inc" data-pid="<?= $it['id'] ?>">+</button>
            </div>
            <span class="mono drawer-item-subtotal"><?= rupiah($it['subtotal']) ?></span>
            <button type="button" class="drawer-item-remove" data-act="remove" data-pid="<?= $it['id'] ?>" title="Hapus">&times;</button>
        </div>
        <?php
    }
}
$html = ob_get_clean();

echo json_encode([
    'ok' => true,
    'html' => $html,
    'count' => array_sum(array_column($_SESSION['cart'] ?? [], 'qty')),
    'total_formatted' => rupiah($data['total']),
    'has_items' => count($data['items']) > 0,
]);
