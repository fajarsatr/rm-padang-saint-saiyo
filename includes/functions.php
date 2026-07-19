<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

define('DELIVERY_FEE', 10000); // ongkir flat untuk opsi antar, berlaku sekitar area kampus

function rupiah($n) {
    return 'Rp' . number_format((float)$n, 0, ',', '.');
}

function is_logged_in() {
    if (!isset($_SESSION['user_id'])) return false;
    global $pdo;
    // Cek sekali per sesi (bukan tiap panggilan) supaya tidak query berulang-ulang
    if (!isset($_SESSION['_uid_verified'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            // User lama sudah tidak ada di DB (biasanya karena schema.sql baru saja di-reimport) -> paksa logout
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['_uid_verified'] = true;
    }
    return true;
}

function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

function current_user() {
    return is_logged_in() ? [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['role'],
    ] : null;
}

function require_login($redirect_to = '') {
    if (!is_logged_in()) {
        $target = $redirect_to ? '?next=' . urlencode($redirect_to) : '';
        header('Location: /rmpadang/auth/login.php' . $target);
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: /rmpadang/auth/login.php');
        exit;
    }
}

function cart_count() {
    return isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
}

// Ambil isi keranjang dari session + data produk terbaru (harga, stok) dari DB
function get_cart_items($pdo) {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return ['items' => [], 'total' => 0];
    $ids = array_keys($cart);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
    $stmt->execute($ids);
    $items = [];
    $total = 0;
    foreach ($stmt->fetchAll() as $p) {
        $qty = min($cart[$p['id']]['qty'], $p['stock']);
        if ($qty <= 0) continue;
        $subtotal = $qty * $p['price'];
        $total += $subtotal;
        $items[] = array_merge($p, ['qty' => $qty, 'subtotal' => $subtotal]);
    }
    return ['items' => $items, 'total' => $total];
}

function generate_order_code() {
    return 'SSY' . date('ymd') . rand(100, 999);
}

function asset_url($path) {
    $full = __DIR__ . '/../' . ltrim($path, '/');
    $v = file_exists($full) ? filemtime($full) : time();
    return base_url($path) . '?v=' . $v;
}

function base_url($path = '') {
    return '/rmpadang/' . ltrim($path, '/');
}
