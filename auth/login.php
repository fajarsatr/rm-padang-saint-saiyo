<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'customer' && isset($_SESSION['pending_product'])) {
            $pid = $_SESSION['pending_product'];
            unset($_SESSION['pending_product']);
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $_SESSION['cart'][$pid] = ['qty' => ($_SESSION['cart'][$pid]['qty'] ?? 0) + 1];
            header('Location: ' . base_url('customer/cart.php'));
            exit;
        }

        header('Location: ' . base_url($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
        exit;
    } else {
        $error = 'Email atau kata sandi salah. Coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>Selamat Datang Kembali</h2>
        <p class="sub">Masuk untuk melanjutkan pesanan Anda</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if (isset($_GET['next']) && $_GET['next'] === 'order'): ?>
            <div class="alert alert-success">Masuk dulu ya, nanti pesanan Anda otomatis masuk keranjang.</div>
        <?php endif; ?>
        <form method="post">
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required autofocus>
            </div>
            <div class="field">
                <label>Kata Sandi</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-maroon" style="width:100%;justify-content:center">Masuk</button>
        </form>
        <p class="auth-alt">Belum punya akun? <a href="<?= base_url('auth/register.php') ?>" style="color:var(--chili);font-weight:600">Daftar di sini</a></p>
        <p class="auth-alt" style="margin-top:4px"><a href="<?= base_url('index.php') ?>">&larr; Kembali ke beranda</a></p>
        <p class="auth-alt" style="margin-top:14px;font-size:.75rem;color:#a08a6e">Akun admin demo: admin@saintsaiyo.com / admin123</p>
    </div>
</div>
</body>
</html>
