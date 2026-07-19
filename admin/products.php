<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partials.php';
require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $image_name = '';
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $image_name = 'menu_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../assets/img/' . $image_name);
            }
        }
        $stmt = $pdo->prepare("INSERT INTO products (name, category, subcategory, badge, price, cost_price, stock, description, image_url) VALUES (?,?,?,?,?,?,?,?,?)");
        $badge = $_POST['badge'] ?: null;
        $stmt->execute([
            trim($_POST['name']), $_POST['category'], trim($_POST['subcategory']), $badge, (int)$_POST['price'],
            (int)$_POST['cost_price'], (int)$_POST['stock'], trim($_POST['description']), $image_name
        ]);
        $msg = 'Menu baru ditambahkan.';
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, cost_price=?, description=? WHERE id=?");
        $stmt->execute([
            trim($_POST['name']), $_POST['category'], (int)$_POST['price'],
            (int)$_POST['cost_price'], trim($_POST['description']), (int)$_POST['id']
        ]);
        $msg = 'Menu diperbarui.';
    } elseif ($action === 'toggle') {
        $pdo->prepare("UPDATE products SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_POST['id']]);
        $msg = 'Status menu diubah.';
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY category, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Menu — Saint Saiyo</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="dash">
    <?php render_admin_sidebar('products'); ?>
    <div class="dash-main">
        <div class="dash-header"><h1>Kelola Menu</h1></div>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

        <div class="panel">
            <h2>Tambah Menu Baru</h2>
            <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;align-items:end">
                <input type="hidden" name="action" value="create">
                <div class="field" style="margin:0"><label>Nama Menu</label><input type="text" name="name" required></div>
                <div class="field" style="margin:0"><label>Kategori</label>
                    <select name="category"><option value="makanan">Makanan</option><option value="minuman">Minuman</option></select>
                </div>
                <div class="field" style="margin:0"><label>Sub-kategori (tab filter)</label><input type="text" name="subcategory" placeholder="mis. Daging &amp; Sapi" required></div>
                <div class="field" style="margin:0"><label>Label (opsional)</label>
                    <select name="badge">
                        <option value="">Tidak ada</option>
                        <option value="signature">Signature</option>
                        <option value="favorit">Favorit</option>
                        <option value="terlaris">Terlaris</option>
                    </select>
                </div>
                <div class="field" style="margin:0"><label>Harga Jual</label><input type="number" name="price" required></div>
                <div class="field" style="margin:0"><label>Harga Modal</label><input type="number" name="cost_price" required></div>
                <div class="field" style="margin:0;grid-column:1/4"><label>Deskripsi</label><input type="text" name="description"></div>
                <div class="field" style="margin:0"><label>Stok Awal</label><input type="number" name="stock" value="0" required></div>
                <div class="field" style="margin:0"><label>Foto Menu (opsional)</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"></div>
                <button class="btn btn-maroon" type="submit">Simpan Menu</button>
            </form>
        </div>

        <div class="panel">
            <h2>Daftar Menu</h2>
            <table>
                <thead><tr><th>Nama</th><th>Kategori</th><th>Sub-kategori</th><th>Label</th><th>Harga</th><th>Modal</th><th>Stok</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= ucfirst($p['category']) ?></td>
                    <td><?= htmlspecialchars($p['subcategory'] ?? '-') ?></td>
                    <td><?= $p['badge'] ? ucfirst($p['badge']) : '-' ?></td>
                    <td class="mono"><?= rupiah($p['price']) ?></td>
                    <td class="mono"><?= rupiah($p['cost_price']) ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td><span class="tag <?= $p['is_active'] ? 'selesai' : 'dibatalkan' ?>"><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline" style="color:var(--maroon);border-color:var(--maroon)" type="submit"><?= $p['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
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
