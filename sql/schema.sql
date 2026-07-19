-- ============================================================
-- RM Padang Saint Saiyo — Skema Database
-- Import file ini lewat phpMyAdmin: New DB "rm_padang_saiyo" > Import
-- Aman diimpor ulang (drop & recreate) kalau kamu update dari versi sebelumnya.
-- ============================================================

CREATE DATABASE IF NOT EXISTS rm_padang_saiyo CHARACTER SET utf8mb4;
USE rm_padang_saiyo;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------
-- USERS (customer & admin dalam satu tabel, dibedakan role)
-- ---------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Akun admin default -> email: admin@saintsaiyo.com / password: admin123
INSERT INTO users (name, email, password, role) VALUES
('Pemilik RM Padang Saint Saiyo', 'admin@saintsaiyo.com',
 '$2y$10$8D5OxFDdmL/EgTh/KMbclue5MrpehYYpB393k1OKhCq2/QDU.CBVm', 'admin');

-- ---------------------------------------------------------
-- PRODUCTS (menu makanan & minuman)
-- category = untuk logika bisnis (pemisahan makanan/minuman di laporan & kasir)
-- subcategory = untuk tab filter tampilan di landing page (lebih rinci)
-- badge = label kecil di kartu menu: signature / favorit / terlaris (boleh kosong)
-- ---------------------------------------------------------
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category ENUM('makanan','minuman') NOT NULL,
  subcategory VARCHAR(40) DEFAULT NULL,
  badge VARCHAR(20) DEFAULT NULL,
  price INT NOT NULL,
  cost_price INT NOT NULL DEFAULT 0,   -- harga modal, untuk laporan untung
  stock INT NOT NULL DEFAULT 0,
  description VARCHAR(255) DEFAULT '',
  image_url VARCHAR(255) DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, category, subcategory, badge, price, cost_price, stock, description, image_url) VALUES
('Rendang Daging', 'makanan', 'Daging & Sapi', 'signature', 28000, 16000, 25, 'Daging sapi dimasak berjam-jam dengan santan & rempah khas Minang.', 'rendang.jpg'),
('Dendeng Balado', 'makanan', 'Daging & Sapi', NULL, 26000, 15000, 18, 'Daging tipis digoreng kering, disiram sambal balado pedas.', 'dendeng.jpg'),
('Ayam Pop', 'makanan', 'Olahan Ayam', 'favorit', 22000, 12000, 20, 'Ayam goreng khas Padang, gurih tanpa terlalu berminyak.', 'ayampop.jpg'),
('Gulai Ikan Kakap', 'makanan', 'Gulai & Ikan', 'terlaris', 25000, 14000, 15, 'Ikan kakap segar dalam kuah gulai kuning yang kaya rempah.', 'gulaiikan.jpg'),
('Sayur Nangka Lodeh', 'makanan', 'Pelengkap', NULL, 12000, 6000, 30, 'Nangka muda dimasak santan, pelengkap wajib nasi Padang.', 'sayurnangka.jpg'),
('Perkedel Kentang', 'makanan', 'Pelengkap', NULL, 5000, 2000, 40, 'Kentang goreng lembut isi rempah, favorit anak-anak.', 'perkedel.jpg'),
('Telur Dadar Padang', 'makanan', 'Pelengkap', NULL, 8000, 3500, 35, 'Telur dadar tebal khas Padang, digoreng renyah di pinggir.', 'telordadar.jpg'),
('Nasi Putih', 'makanan', 'Pelengkap', NULL, 6000, 2500, 100, 'Nasi putih pulen, porsi standar rumah makan Padang.', 'nasiputih.jpg'),
('Es Teh Manis', 'minuman', 'Minuman Segar', NULL, 6000, 1500, 60, 'Teh manis dingin segar, teman santap khas Padang.', 'esteh.jpg'),
('Es Campur Padang', 'minuman', 'Minuman Segar', 'favorit', 15000, 6000, 20, 'Campuran alpukat, cincau, kolang-kaling, dan sirup gula aren.', 'escampur.jpg'),
('Teh Talua', 'minuman', 'Minuman Segar', NULL, 12000, 5000, 15, 'Teh telur khas Minang, kental dan hangat.', 'tehtalua.jpg'),
('Air Mineral', 'minuman', 'Minuman Segar', NULL, 5000, 2000, 80, 'Air mineral kemasan botol dingin.', 'airmineral.jpg');

-- ---------------------------------------------------------
-- ORDERS (transaksi pemesanan dari customer)
-- ---------------------------------------------------------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_code VARCHAR(20) NOT NULL UNIQUE,
  fulfillment_type ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  delivery_address VARCHAR(255) DEFAULT NULL,
  delivery_fee INT NOT NULL DEFAULT 0,
  total_amount INT NOT NULL,
  payment_method ENUM('qris','tunai') NOT NULL DEFAULT 'qris',
  status ENUM('menunggu_pembayaran','dibayar','diproses','diantar','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ---------------------------------------------------------
-- ORDER_ITEMS (detail item per transaksi)
-- ---------------------------------------------------------
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(120) NOT NULL,  -- disalin saat order, biar histori tak berubah walau produk diedit
  price INT NOT NULL,
  cost_price INT NOT NULL DEFAULT 0,
  qty INT NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ---------------------------------------------------------
-- STOCK_MOVEMENTS (log setiap perubahan stok, manual maupun otomatis dari transaksi)
-- ---------------------------------------------------------
CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  change_qty INT NOT NULL,          -- positif = tambah, negatif = kurang
  reason VARCHAR(100) NOT NULL,     -- 'restock_manual', 'penjualan', 'koreksi_admin'
  note VARCHAR(255) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
);
