<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/partials.php';

$subcategory = $_GET['kategori'] ?? 'semua';
$all_subcats = $pdo->query("SELECT DISTINCT subcategory FROM products WHERE is_active = 1 ORDER BY subcategory")->fetchAll(PDO::FETCH_COLUMN);

$where = '';
$params = [];
if ($subcategory !== 'semua' && in_array($subcategory, $all_subcats)) {
    $where = 'AND subcategory = ?';
    $params[] = $subcategory;
}
$stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 $where ORDER BY category, name");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Untuk foto hero yang berganti-ganti: pakai foto produk berbadge kalau ada, kalau tidak pakai emoji
$hero_candidates = $pdo->query("SELECT name, image_url FROM products WHERE badge IS NOT NULL AND is_active = 1 LIMIT 4")->fetchAll();
$total_active = $pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1")->fetch()['c'];
$user_role_for_scroll = current_user()['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RM Padang Saint Saiyo — Rendang Otentik, Rasa yang Diingat</title>
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>

<?php render_navbar(); ?>
<?php render_cart_drawer(); ?>
<?php render_ticker(); ?>

<section class="hero">
    <div class="hero-inner">
        <div>
            <span class="eyebrow">Autentik &amp; Modern — Sejak 1998</span>
            <h1><span class="line">Cita Rasa</span> <span class="line text-outline">Minang.</span></h1>
            <p>Setiap hidangan adalah warisan budaya — dimasak perlahan dengan rempah pilihan, membawa kehangatan Ranah Minang ke meja makanmu.</p>
            <div class="hero-actions">
                <a href="#menu" class="btn btn-gold">Lihat Menu</a>
                <a href="#tentang" class="btn btn-outline">Tentang Kami</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><b><?= $total_active ?><em>+</em></b><span>Pilihan Menu</span></div>
                <div class="hero-stat"><b>100<em>%</em></b><span>Rempah Asli</span></div>
                <div class="hero-stat"><b>8 <em>Jam</em></b><span>Rendang Slow Cooked</span></div>
                <div class="hero-stat"><b>Tiap <em>Hari</em></b><span>Dimasak Segar</span></div>
            </div>
        </div>
        <div class="hero-art">
            <a href="#menu" class="hero-cta-float">Pesan Sekarang &rarr;</a>
            <div class="hero-plate" id="heroPlate">
                <?php if ($hero_candidates): foreach ($hero_candidates as $i => $h):
                    $img_path = __DIR__ . '/assets/img/' . $h['image_url'];
                    $has_photo = $h['image_url'] && file_exists($img_path);
                ?>
                <div class="slide <?= $i === 0 ? 'active' : '' ?>">
                    <?php if ($has_photo): ?>
                        <img src="<?= base_url('assets/img/' . $h['image_url']) ?>" alt="<?= htmlspecialchars($h['name']) ?>">
                    <?php else: ?>
                        <span>🍛</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; else: ?>
                <div class="slide active"><span>🍛</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php gonjong_divider(); ?>
</section>

<section class="about" id="tentang">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Filosofi Dapur Kami</span>
            <h2>Tiga Hal yang Tidak Kami Tawar</h2>
            <p>Karena masakan Padang yang baik adalah soal kesabaran, bukan kecepatan.</p>
        </div>
        <div class="about-grid">
            <div class="about-card reveal">
                <span class="icon">🥥</span>
                <span class="num">01</span>
                <h3>Santan Segar Tiap Pagi</h3>
                <p>Diperas langsung dari kelapa pilihan, tidak memakai santan instan, supaya kuah gulai dan rendang tetap kental alami.</p>
            </div>
            <div class="about-card reveal">
                <span class="icon">🔥</span>
                <span class="num">02</span>
                <h3>Rempah Disangrai, Bukan Ditumis Sekilas</h3>
                <p>Setiap bumbu disangrai perlahan sampai keluar minyaknya sendiri — inilah yang membuat rendang kami tahan lama dan makin nikmat.</p>
            </div>
            <div class="about-card reveal">
                <span class="icon">🍚</span>
                <span class="num">03</span>
                <h3>Nasi Selalu Baru Ditanak</h3>
                <p>Kami menanak nasi dalam batch kecil sepanjang hari, supaya tidak ada nasi yang menginap dari kemarin.</p>
            </div>
        </div>
    </div>
</section>

<section id="menu">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Menu Kami</span>
            <h2>Pilih, Tambahkan ke Keranjang, Bayar QRIS</h2>
            <p>Semua menu dimasak fresh hari ini. Stok ditampilkan langsung dari dapur.</p>
        </div>
        <div class="menu-filter">
            <a href="?kategori=semua#menu" class="filter-btn <?= $subcategory === 'semua' ? 'active' : '' ?>">Semua</a>
            <?php foreach ($all_subcats as $sc): ?>
                <a href="?kategori=<?= urlencode($sc) ?>#menu" class="filter-btn <?= $subcategory === $sc ? 'active' : '' ?>"><?= htmlspecialchars($sc) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="menu-grid">
            <?php foreach ($products as $i => $p):
                $habis = $p['stock'] <= 0; $sedikit = $p['stock'] > 0 && $p['stock'] <= 5;
                $img_path = __DIR__ . '/assets/img/' . $p['image_url'];
                $has_photo = $p['image_url'] && file_exists($img_path);
                $badge_labels = ['signature' => 'Signature', 'favorit' => 'Favorit', 'terlaris' => 'Terlaris'];
            ?>
            <div class="menu-card reveal">
                <div class="menu-photo" <?= $has_photo ? 'style="background:none"' : '' ?>>
                    <?php if ($p['badge']): ?>
                        <span class="cat-badge <?= $p['badge'] ?>"><?= $badge_labels[$p['badge']] ?? $p['badge'] ?></span>
                    <?php endif; ?>
                    <?php if ($has_photo): ?>
                        <img class="menu-photo-img" src="<?= base_url('assets/img/' . $p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <span class="emoji"><?= $p['category'] === 'makanan' ? '🍽️' : '🥤' ?></span>
                    <?php endif; ?>
                </div>
                <div class="menu-body">
                    <span class="eyebrow-num">Menu-<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <p><?= htmlspecialchars($p['description']) ?></p>
                    <div class="menu-foot">
                        <div>
                            <span class="price"><?= rupiah($p['price']) ?></span><br>
                            <?php if ($habis): ?>
                                <span class="stock-note low">Stok habis</span>
                            <?php elseif ($sedikit): ?>
                                <span class="stock-note low">Sisa <?= $p['stock'] ?></span>
                            <?php else: ?>
                                <span class="stock-note">Stok <?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="<?= base_url('customer/order.php') ?>">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="add-btn" <?= $habis ? 'disabled' : '' ?> title="<?= $habis ? 'Stok habis' : 'Pesan ' . htmlspecialchars($p['name']) ?>">+</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php render_footer(); ?>
<script>
// Rotasi otomatis foto di hero (kalau ada lebih dari satu slide)
(function(){
    const plate = document.getElementById('heroPlate');
    if (!plate) return;
    const slides = plate.querySelectorAll('.slide');
    if (slides.length <= 1) return;
    let idx = 0;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) return;
    setInterval(() => {
        slides[idx].classList.remove('active');
        idx = (idx + 1) % slides.length;
        slides[idx].classList.add('active');
    }, 4000);
})();

<?php if ($user_role_for_scroll === 'customer'): ?>
// Pelanggan yang sudah login langsung diarahkan ke bagian menu — kecuali mereka memang
// sedang menuju bagian lain (mis. klik link "Tentang") atau baru saja menambah item ke keranjang.
(function(){
    if (window.location.hash) return; // biarkan kalau memang menuju anchor tertentu
    const menu = document.getElementById('menu');
    if (!menu) return;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    menu.scrollIntoView({ behavior: prefersReduced ? 'auto' : 'smooth', block: 'start' });
})();
<?php endif; ?>
</script>
</body>
</html>
