<?php
// Elemen tanda tangan desain: siluet atap gonjong (rumah gadang) sebagai pembatas section
function gonjong_divider($classes = '') {
    echo '<svg class="gonjong-divider ' . $classes . '" viewBox="0 0 1200 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,80 L0,40 C60,10 100,55 160,30 C210,10 240,50 300,20 C350,0 380,45 440,25
                 C500,5 530,50 600,30 C670,10 700,55 760,25 C820,0 850,45 900,20
                 C960,0 1000,50 1060,30 C1110,15 1150,45 1200,40 L1200,80 Z"></path>
    </svg>';
}

function logo_mark() {
    echo '<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 30 C8 16 14 26 20 14 C26 26 32 16 36 30 L36 34 L4 34 Z" fill="currentColor"/>
        <circle cx="20" cy="10" r="3" fill="currentColor"/>
    </svg>';
}

function render_ticker() {
    $phrases = [
        '100% HALAL', 'REMPAH NUSANTARA ASLI', 'MASAKAN SEGAR TIAP HARI',
        'BAYAR VIA QRIS', 'RENDANG OTENTIK KHAS MINANG', 'AMBIL DI TEMPAT TANPA ANTRE',
    ];
    $items = '';
    foreach (array_merge($phrases, $phrases) as $p) { // digandakan agar loop mulus
        $items .= '<span>' . htmlspecialchars($p) . '&nbsp;&nbsp;•&nbsp;&nbsp;</span>';
    }
    echo '<div class="ticker"><div class="ticker-track">' . $items . '</div></div>';
}

function render_navbar() {
    $user = current_user();
    ?>
    <div class="nav">
        <div class="container nav-inner">
            <a href="<?= base_url('index.php') ?>" class="brand"><?php logo_mark(); ?> RM Padang Saint Saiyo</a>
            <div class="nav-links">
                <a href="<?= base_url('index.php') ?>#menu">Menu</a>
                <a href="<?= base_url('index.php') ?>#tentang">Tentang</a>
                <?php if ($user && $user['role'] === 'customer'): ?>
                    <a href="<?= base_url('customer/orders.php') ?>">Pesanan Saya</a>
                    <button type="button" id="cartOpenBtn" class="cart-pill" style="background:none;border:none;font:inherit;color:inherit;cursor:pointer;padding:0">Keranjang
                        <?php $c = cart_count(); if ($c > 0): ?><span class="cart-badge" id="cartCountBadge"><?= $c ?></span><?php endif; ?>
                    </button>
                    <span class="nav-greeting"><span class="nav-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                    <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-outline btn-sm">Keluar</a>
                <?php elseif ($user && $user['role'] === 'admin'): ?>
                    <span class="nav-greeting"><span class="nav-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                    <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-gold btn-sm">Dashboard Admin</a>
                    <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-outline btn-sm">Keluar</a>
                <?php else: ?>
                    <a href="<?= base_url('auth/login.php') ?>" class="btn btn-outline btn-sm">Masuk</a>
                    <a href="<?= base_url('auth/register.php') ?>" class="btn btn-gold btn-sm">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function render_cart_drawer() {
    $user = current_user();
    if (!$user || $user['role'] !== 'customer') return;
    ?>
    <div class="drawer-backdrop" id="cartBackdrop"></div>
    <aside class="cart-drawer" id="cartDrawer">
        <div class="drawer-head">
            <h3>Keranjang Anda</h3>
            <button type="button" id="cartCloseBtn" class="drawer-close">&times;</button>
        </div>
        <div class="drawer-body" id="cartDrawerBody">
            <p class="cart-empty">Memuat...</p>
        </div>
        <div class="drawer-foot">
            <div class="drawer-total"><span>Total</span><strong class="mono" id="cartDrawerTotal">Rp0</strong></div>
            <a href="<?= base_url('customer/checkout.php') ?>" class="btn btn-chili" style="width:100%;justify-content:center">Lanjut ke Pembayaran QRIS</a>
        </div>
    </aside>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const API = <?= json_encode(base_url('customer/cart_api.php')) ?>;
        const drawer = document.getElementById('cartDrawer');
        const backdrop = document.getElementById('cartBackdrop');
        const openBtn = document.getElementById('cartOpenBtn');
        const closeBtn = document.getElementById('cartCloseBtn');
        const body = document.getElementById('cartDrawerBody');
        const totalEl = document.getElementById('cartDrawerTotal');
        const badge = () => document.getElementById('cartCountBadge');

        function renderCart(data) {
            body.innerHTML = data.html;
            totalEl.textContent = data.total_formatted;
            let b = badge();
            if (data.count > 0) {
                if (!b) {
                    b = document.createElement('span');
                    b.className = 'cart-badge'; b.id = 'cartCountBadge';
                    openBtn.appendChild(b);
                }
                b.textContent = data.count;
            } else if (b) { b.remove(); }
        }

        function loadCart() {
            fetch(API, { headers: { 'X-Requested-With': 'fetch' } })
                .then(r => r.json()).then(renderCart).catch(() => {});
        }

        function openDrawer() { drawer.classList.add('open'); backdrop.classList.add('open'); loadCart(); }
        function closeDrawer() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }

        if (openBtn) openBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);

        // Delegasi klik untuk tombol qty/hapus di dalam drawer
        body.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-act]');
            if (!btn) return;
            const fd = new FormData();
            fd.append('action', btn.dataset.act);
            fd.append('product_id', btn.dataset.pid);
            fetch(API, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
                .then(r => r.json()).then(renderCart).catch(() => {});
        });

        // Intersep semua tombol "+" pesan di kartu menu supaya tidak pindah halaman
        document.querySelectorAll('form[action$="order.php"]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                fetch(<?= json_encode(base_url('customer/order.php')) ?>, {
                    method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' }
                }).then(r => r.json()).then(res => {
                    if (res.need_login) { window.location = res.redirect; return; }
                    if (!res.ok) { if (res.message) alert(res.message); return; }
                    if (badge()) { badge().textContent = res.count; }
                    else if (openBtn) {
                        const b = document.createElement('span');
                        b.className = 'cart-badge'; b.id = 'cartCountBadge'; b.textContent = res.count;
                        openBtn.appendChild(b);
                    }
                    openDrawer();
                }).catch(() => { form.submit(); }); // fallback kalau fetch gagal
            });
        });
    });
    </script>
    <?php
}

function render_admin_sidebar($active) {
    $links = [
        'dashboard' => ['url' => 'admin/dashboard.php', 'label' => 'Dashboard'],
        'products'  => ['url' => 'admin/products.php', 'label' => 'Kelola Menu'],
        'stock'     => ['url' => 'admin/stock.php', 'label' => 'Kelola Stok'],
        'orders'    => ['url' => 'admin/orders.php', 'label' => 'Pesanan Masuk'],
        'laporan'   => ['url' => 'admin/laporan.php', 'label' => 'Laporan Keuntungan'],
    ];
    ?>
    <div class="sidebar">
        <div class="brand"><?php logo_mark(); ?> Saint Saiyo</div>
        <?php foreach ($links as $key => $l): ?>
            <a href="<?= base_url($l['url']) ?>" class="side-link <?= $active === $key ? 'active' : '' ?>"><?= $l['label'] ?></a>
        <?php endforeach; ?>
        <a href="<?= base_url('index.php') ?>" class="side-link">Lihat Landing Page</a>
        <a href="<?= base_url('auth/logout.php') ?>" class="side-link">Keluar</a>
    </div>
    <?php
}

function render_customer_sidebar($active) { /* tidak dipakai, customer pakai navbar biasa */ }

function render_footer() {
    ?>
    <footer>
        <div class="container footer-inner">
            <div>
                <strong style="color:var(--gold-soft)">RM Padang Saint Saiyo</strong><br>
                Jl. Margonda Raya No. 12, Depok, Jawa Barat<br>
                Buka setiap hari, 09.00 – 21.00 WIB
            </div>
            <div>
                WhatsApp: 0812-3456-7890<br>
                Instagram: @saintsaiyo.padang<br>
                &copy; <?= date('Y') ?> RM Padang Saint Saiyo
            </div>
        </div>
    </footer>
    <script>
    // Reveal-on-scroll sederhana untuk kartu menu / about
    document.addEventListener('DOMContentLoaded', () => {
        const els = document.querySelectorAll('.reveal');
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in'); });
        }, { threshold: 0.15 });
        els.forEach(el => io.observe(el));
    });
    </script>
    <?php
}
