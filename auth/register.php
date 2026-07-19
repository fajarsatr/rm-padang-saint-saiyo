<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan masuk.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hash, $phone]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['role'] = 'customer';

            if (isset($_SESSION['pending_product'])) {
                $pid = $_SESSION['pending_product'];
                unset($_SESSION['pending_product']);
                $_SESSION['cart'][$pid] = ['qty' => 1];
                header('Location: ' . base_url('customer/cart.php'));
                exit;
            }
            header('Location: ' . base_url('index.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar — RM Padang Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>Buat Akun</h2>
        <p class="sub">Daftar untuk mulai memesan menu favorit Anda</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="field">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>No. HP (opsional)</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Kata Sandi</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-maroon" style="width:100%;justify-content:center">Daftar</button>
        </form>
        <p class="auth-alt">Sudah punya akun? <a href="<?= base_url('auth/login.php') ?>" style="color:var(--chili);font-weight:600">Masuk di sini</a></p>
        <p class="auth-alt" style="margin-top:4px"><a href="<?= base_url('index.php') ?>">&larr; Kembali ke beranda</a></p>
    </div>
</div>
</body>
</html>
