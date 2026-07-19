<?php
require_once __DIR__ . '/../includes/functions.php';

$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
function respond($ok, $extra = []) {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['ok' => $ok], $extra));
        exit;
    }
}

$product_id = (int)($_POST['product_id'] ?? 0);
if (!$product_id) { respond(false); header('Location: ' . base_url('index.php')); exit; }

if (!is_logged_in()) {
    // Simpan niat pesan customer, lanjutkan setelah login
    $_SESSION['pending_product'] = $product_id;
    respond(false, ['need_login' => true, 'redirect' => base_url('auth/login.php?next=order')]);
    header('Location: ' . base_url('auth/login.php?next=order'));
    exit;
}

if ($_SESSION['role'] !== 'customer') {
    respond(false);
    header('Location: ' . base_url('index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product || $product['stock'] <= 0) {
    respond(false, ['message' => 'Stok menu ini sudah habis.']);
    header('Location: ' . base_url('index.php') . '#menu');
    exit;
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['qty']++;
} else {
    $_SESSION['cart'][$product_id] = ['qty' => 1];
}

if ($is_ajax) {
    respond(true, ['count' => array_sum(array_column($_SESSION['cart'], 'qty'))]);
}
header('Location: ' . base_url('customer/cart.php'));
exit;
