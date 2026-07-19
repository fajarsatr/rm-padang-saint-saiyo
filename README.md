# RM Padang Saint Saiyo — Web App (PHP + MySQL)

Aplikasi pemesanan online untuk RM Padang Saint Saiyo, dengan dua peran:
- **Pembeli**: lihat landing page & menu, daftar/masuk, pesan, bayar QRIS, lihat riwayat pesanan.
- **Admin/Pemilik**: kelola menu, kelola stok, kelola pesanan masuk, lihat laporan keuntungan.

## Cara Menjalankan (cukup 4 langkah)

1. **Install XAMPP** (atau Laragon) — https://www.apachefriends.org, lalu nyalakan modul **Apache** dan **MySQL** dari control panel.

2. **Taruh folder ini** (`rmpadang`) ke dalam folder `htdocs` XAMPP:
   - Windows: `C:\xampp\htdocs\rmpadang`
   - Laragon: `C:\laragon\www\rmpadang`

3. **Import database**:
   - Buka `http://localhost/phpmyadmin`
   - Klik tab **Import**, pilih file `sql/schema.sql` dari folder ini, klik **Go**.
   - Ini otomatis membuat database `rm_padang_saiyo` beserta 12 menu contoh dan 1 akun admin.

4. **Buka di browser**: `http://localhost/rmpadang/index.php`

Selesai — tidak perlu `npm install`, tidak perlu command line.

## Akun untuk Demo

| Peran | Email | Password |
|---|---|---|
| Admin/Pemilik | admin@saintsaiyo.com | admin123 |
| Pembeli | (daftar sendiri lewat tombol "Daftar") | — |

## Alur Pemakaian

**Sebagai pembeli:**
1. Buka landing page → scroll ke bagian Menu → klik "Pesan Sekarang" pada menu apa saja.
2. Karena belum login, otomatis diarahkan ke halaman Login/Daftar.
3. Daftar akun baru → otomatis kembali dan item tadi sudah ada di Keranjang.
4. Di Keranjang, atur jumlah pesanan → klik "Lanjut ke Pembayaran QRIS".
5. Muncul kode QRIS (simulasi, untuk keperluan tugas) → klik "Saya Sudah Bayar".
6. Stok menu otomatis berkurang, status pesanan berubah jadi "Dibayar", dan muncul di "Pesanan Saya".

**Sebagai admin:**
1. Login dengan akun admin di atas → masuk ke Dashboard.
2. **Kelola Menu**: tambah menu baru, ubah harga/harga modal, nonaktifkan menu yang habis.
3. **Kelola Stok**: tambah/kurangi stok manual, lihat riwayat pergerakan stok (termasuk otomatis dari penjualan).
4. **Pesanan Masuk**: lihat pesanan yang sudah dibayar, ubah status jadi Diproses/Selesai/Batal.
5. **Laporan Keuntungan**: lihat omzet, modal, untung, dan margin per hari/7 hari/30 hari, plus rincian per menu.

## Catatan Teknis

- **Stack**: PHP native (tanpa framework) + MySQL, memakai PDO untuk koneksi database yang aman dari SQL injection.
- **QRIS**: karena keterbatasan tugas/demo, QRIS digambar sebagai pola visual (canvas) yang unik per transaksi — ini BUKAN QRIS resmi dari bank/PJSP, murni untuk simulasi tampilan sesuai ketentuan soal ("keluaran gambar QRIS diperbolehkan").
- **Password** disimpan ter-hash (bcrypt via `password_hash()`), tidak pernah disimpan sebagai teks biasa.
- **Struktur folder**:
  ```
  rmpadang/
    index.php              → landing page publik
    auth/                  → login, register, logout
    customer/              → order, cart, checkout (QRIS), riwayat
    admin/                 → dashboard, kelola menu, stok, pesanan, laporan
    includes/              → db.php (koneksi), functions.php (helper), partials.php (navbar/footer/sidebar)
    assets/css/style.css   → seluruh styling
    sql/schema.sql         → skema database + data contoh
  ```
- Kalau MySQL kamu punya password root (bukan kosong), ubah di `includes/db.php` pada variabel `$DB_PASS`.

## Deploy Online ke Railway (opsional)

Selain jalan di localhost, aplikasi ini juga bisa di-hosting online lewat **Railway** (railway.app), yang mendukung PHP + MySQL sungguhan (bukan serverless seperti Vercel, jadi sesi login dan koneksi database tetap stabil).

1. **Push folder ini ke repository GitHub** (termasuk `Dockerfile` yang sudah disediakan).
2. **Buat project baru di Railway** → pilih "Deploy from GitHub repo" → pilih repository ini. Railway otomatis mendeteksi `Dockerfile` dan membangun image-nya.
3. **Tambahkan service MySQL**: di dalam project yang sama, klik "New" → "Database" → "Add MySQL". Railway otomatis menyediakan database kosong dan menyuntikkan variabel koneksi (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`) ke semua service lain di project yang sama.
4. **Import skema database**: buka tab "Data" pada service MySQL di Railway (atau connect lewat MySQL client/phpMyAdmin memakai kredensial yang Railway berikan), lalu jalankan isi `sql/schema.sql`.
5. **Set variabel PORT** (biasanya otomatis oleh Railway) dan tunggu proses deploy selesai. Railway akan memberi kamu URL publik, misalnya `https://namaproject.up.railway.app`.
6. Buka URL itu diikuti `/index.php` untuk mengakses aplikasi.

Catatan: `includes/db.php` sudah disesuaikan agar otomatis memakai variabel environment dari Railway kalau tersedia, dan otomatis kembali ke pengaturan localhost (XAMPP/Laragon) kalau variabel itu tidak ada. Jadi kode yang sama bisa dipakai di kedua tempat tanpa diedit manual.

Untuk foto menu yang diunggah lewat "Kelola Menu" supaya tidak hilang saat Railway redeploy, sebaiknya tambahkan **Railway Volume** yang di-mount ke folder `assets/img` (lewat tab "Settings" pada service, bagian "Volumes"). Tanpa volume, foto yang diunggah bisa hilang saat container di-restart atau di-redeploy.
