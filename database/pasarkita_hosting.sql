-- =====================================================================
--  PasarKita - Marketplace Produk Tradisional Indonesia
--  Struktur database + data dummy
--  Kompatibel: MySQL 8.0.16+ / MariaDB 10.4+ (CHECK constraint aktif)
-- =====================================================================
--  VERSI HOSTING (InfinityFree): pilih database Anda di phpMyAdmin lalu Import file ini.
--  Hosting (InfinityFree): database dibuat lewat panel, jadi HAPUS/abaikan
--  dua baris CREATE DATABASE dan USE di bawah, atau gunakan file
--  database/pasarkita_hosting.sql yang tidak memuat baris tersebut.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;


DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Tabel users: akun admin dan pembeli
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL COMMENT 'hash dari password_hash()',
    phone       VARCHAR(20)  NULL,
    address     TEXT         NULL,
    role        ENUM('admin','pembeli') NOT NULL DEFAULT 'pembeli',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel categories: kategori produk
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icon        VARCHAR(50)  NOT NULL DEFAULT 'bi-tag',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel products: produk tradisional
--   stock UNSIGNED + CHECK  -> stok tidak pernah negatif
--   category ON DELETE RESTRICT -> kategori yang dipakai tidak bisa dihapus
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id  INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(170) NOT NULL,
    description  TEXT NOT NULL,
    price        DECIMAL(12,2) NOT NULL,
    stock        INT UNSIGNED NOT NULL DEFAULT 0,
    unit         VARCHAR(30)  NOT NULL DEFAULT 'pcs',
    origin       VARCHAR(100) NULL COMMENT 'daerah asal produk',
    seller_name  VARCHAR(100) NULL COMMENT 'nama penjual / UMKM',
    image        VARCHAR(255) NULL COMMENT 'path relatif dari root aplikasi',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_name (name),
    KEY idx_products_active_created (is_active, created_at),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_stock CHECK (stock >= 0),
    CONSTRAINT chk_products_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel orders: header transaksi
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_code       VARCHAR(30)  NOT NULL,
    user_id          INT UNSIGNED NOT NULL,
    recipient_name   VARCHAR(100) NOT NULL,
    phone            VARCHAR(20)  NOT NULL,
    shipping_address TEXT         NOT NULL,
    notes            VARCHAR(255) NULL,
    payment_method   ENUM('transfer','cod') NOT NULL DEFAULT 'transfer',
    total_items      INT UNSIGNED NOT NULL,
    subtotal         DECIMAL(14,2) NOT NULL,
    shipping_cost    DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount     DECIMAL(14,2) NOT NULL,
    status           ENUM('pending','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_code (order_code),
    KEY idx_orders_user (user_id),
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_orders_items CHECK (total_items > 0),
    CONSTRAINT chk_orders_amounts CHECK (subtotal >= 0 AND shipping_cost >= 0 AND total_amount = subtotal + shipping_cost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel order_details: item transaksi
--   product_name & price = SNAPSHOT saat dipesan (histori harga terjaga)
--   product_id ON DELETE SET NULL -> produk boleh dihapus, histori tetap ada
-- ---------------------------------------------------------------------
CREATE TABLE order_details (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NULL,
    product_name  VARCHAR(150) NOT NULL,
    price         DECIMAL(12,2) NOT NULL COMMENT 'harga satuan saat dipesan',
    quantity      INT UNSIGNED NOT NULL,
    subtotal      DECIMAL(14,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_product (order_id, product_id),
    KEY idx_order_details_product (product_id),
    CONSTRAINT fk_details_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_details_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_details_qty CHECK (quantity > 0),
    CONSTRAINT chk_details_price CHECK (price >= 0),
    CONSTRAINT chk_details_subtotal CHECK (subtotal = price * quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATA DUMMY
-- Akun uji:
--   admin@pasarkita.test   / admin123   (role admin)
--   pembeli@pasarkita.test / pembeli123 (role pembeli)
-- =====================================================================

INSERT INTO users (id, name, email, password, phone, address, role) VALUES
(1, 'Administrator PasarKita', 'admin@pasarkita.test', '$2y$12$IgXJqn7rtg7NZY8zU3e9eu4CypoVxuuS1j2pERASbR5p60EDn7XuS', '081200000001', 'Kantor PasarKita, Yogyakarta', 'admin'),
(2, 'Siti Rahmawati', 'pembeli@pasarkita.test', '$2y$12$Hj.IY90.3UrkHy1L.bk1xe3ovSuRGOFkM.tfl7l6rlMBxVKcnzcKG', '081234567890', 'Jl. Malioboro No. 10, Yogyakarta 55271', 'pembeli');

INSERT INTO categories (id, name, slug, description, icon) VALUES
(1, 'Batik & Tenun', 'batik-tenun', 'Kain batik tulis, batik cap, tenun, dan songket dari berbagai daerah.', 'bi-palette'),
(2, 'Makanan Tradisional', 'makanan-tradisional', 'Olahan makanan khas Nusantara siap kirim.', 'bi-egg-fried'),
(3, 'Minuman & Rempah', 'minuman-rempah', 'Kopi, teh, gula aren, dan rempah pilihan petani lokal.', 'bi-cup-hot'),
(4, 'Kerajinan Tangan', 'kerajinan-tangan', 'Anyaman, gerabah, dan wayang buatan pengrajin lokal.', 'bi-brush'),
(5, 'Jamu & Herbal', 'jamu-herbal', 'Jamu racikan tradisional dan minyak herbal alami.', 'bi-flower1');

INSERT INTO products (id, category_id, name, slug, description, price, stock, unit, origin, seller_name, image, is_active, created_at) VALUES
(1, 1, 'Batik Tulis Motif Kawung', 'batik-tulis-motif-kawung', 'Kain batik tulis halus bermotif kawung klasik, dikerjakan dengan canting dan pewarna alami. Ukuran 2 x 1,15 meter, bahan katun primissima.', 350000, 11, 'lembar', 'Yogyakarta', 'Sanggar Batik Kawung', 'assets/img/products/batik-kawung.svg', 1, '2026-11-01 09:00:00'),
(2, 1, 'Kain Tenun Ikat Sumba', 'kain-tenun-ikat-sumba', 'Tenun ikat tradisional Sumba dengan motif kuda dan manusia, ditenun manual selama berminggu-minggu. Cocok untuk koleksi maupun busana.', 750000, 5, 'lembar', 'Sumba, NTT', 'Kelompok Tenun Marapu', 'assets/img/products/tenun-sumba.svg', 1, '2026-11-01 09:10:00'),
(3, 1, 'Batik Cap Mega Mendung', 'batik-cap-mega-mendung', 'Batik cap khas Cirebon bermotif awan mega mendung dengan gradasi biru. Bahan katun, nyaman dipakai sehari-hari.', 185000, 20, 'lembar', 'Cirebon', 'Batik Trusmi Asri', 'assets/img/products/batik-mega-mendung.svg', 1, '2026-11-02 10:00:00'),
(4, 1, 'Kain Songket Palembang', 'kain-songket-palembang', 'Songket tenun benang emas dengan motif bunga tabur, tampil mewah untuk acara adat dan pernikahan.', 950000, 3, 'lembar', 'Palembang', 'Songket Bari Indah', 'assets/img/products/songket-palembang.svg', 1, '2026-11-02 10:30:00'),
(5, 2, 'Rendang Daging Sapi 500 g', 'rendang-daging-sapi-500-g', 'Rendang daging sapi asli Minang dimasak hingga kering selama 8 jam dengan santan dan rempah. Dikemas vakum, tahan 1 bulan.', 125000, 25, 'pack', 'Padang', 'Dapur Uni Ros', 'assets/img/products/rendang.svg', 1, '2026-11-03 08:00:00'),
(6, 2, 'Gudeg Kaleng Jogja', 'gudeg-kaleng-jogja', 'Gudeg nangka muda lengkap dengan krecek dan telur dalam kemasan kaleng steril, tahan hingga 1 tahun.', 45000, 40, 'kaleng', 'Yogyakarta', 'Gudeg Bu Tjitro', 'assets/img/products/gudeg.svg', 1, '2026-11-03 08:30:00'),
(7, 2, 'Dodol Garut Original 500 g', 'dodol-garut-original-500-g', 'Dodol legit khas Garut dari ketan, gula aren, dan santan kelapa. Dibungkus satuan, cocok untuk oleh-oleh.', 35000, 57, 'kotak', 'Garut', 'Dodol Picnic Garut', 'assets/img/products/dodol.svg', 1, '2026-11-04 11:00:00'),
(8, 2, 'Pempek Palembang Paket Kenyang', 'pempek-palembang-paket-kenyang', 'Paket pempek kapal selam, lenjer, dan adaan dari ikan tenggiri segar, lengkap dengan cuko. Dikirim beku.', 85000, 15, 'paket', 'Palembang', 'Pempek Cek Mira', 'assets/img/products/pempek.svg', 1, '2026-11-04 11:30:00'),
(9, 3, 'Kopi Gayo Arabika 250 g', 'kopi-gayo-arabika-250-g', 'Biji kopi arabika Gayo single origin, sangrai medium dengan aroma floral dan rasa cokelat. Tersedia dalam bentuk biji.', 95000, 28, 'bungkus', 'Aceh Tengah', 'Koperasi Kopi Gayo', 'assets/img/products/kopi-gayo.svg', 1, '2026-11-05 07:00:00'),
(10, 3, 'Teh Hitam Kayu Aro', 'teh-hitam-kayu-aro', 'Teh hitam ortodoks dari perkebunan Kayu Aro di kaki Gunung Kerinci. Seduhan berwarna merah pekat dan harum.', 40000, 35, 'kotak', 'Kerinci, Jambi', 'Kebun Teh Kayu Aro', 'assets/img/products/teh-kayu-aro.svg', 1, '2026-11-05 07:30:00'),
(11, 3, 'Gula Aren Organik 1 kg', 'gula-aren-organik-1-kg', 'Gula aren cetak organik dari nira pohon aren tanpa bahan pengawet. Manis legit untuk kue dan minuman.', 38000, 50, 'kg', 'Lebak, Banten', 'Tani Aren Baduy', 'assets/img/products/gula-aren.svg', 1, '2026-11-06 09:00:00'),
(12, 3, 'Wedang Uwuh Instan', 'wedang-uwuh-instan', 'Minuman rempah khas Imogiri berisi secang, jahe, cengkih, kayu manis, dan daun pala. Cukup seduh air panas.', 25000, 45, 'bungkus', 'Bantul, Yogyakarta', 'Wedang Uwuh Imogiri', 'assets/img/products/wedang-uwuh.svg', 1, '2026-11-06 09:30:00'),
(13, 4, 'Tas Anyaman Rotan Bulat', 'tas-anyaman-rotan-bulat', 'Tas bulat anyaman rotan ata buatan tangan pengrajin Bali, dengan lapisan kain batik di bagian dalam.', 225000, 8, 'buah', 'Tenganan, Bali', 'Ata Craft Bali', 'assets/img/products/tas-rotan.svg', 1, '2026-11-07 10:00:00'),
(14, 4, 'Wayang Kulit Arjuna', 'wayang-kulit-arjuna', 'Wayang kulit tokoh Arjuna dari kulit kerbau yang ditatah dan disungging manual. Tinggi sekitar 50 cm, cocok untuk hiasan dinding.', 450000, 4, 'buah', 'Surakarta', 'Sanggar Wayang Manyaran', 'assets/img/products/wayang-arjuna.svg', 1, '2026-11-07 10:30:00'),
(15, 4, 'Vas Gerabah Kasongan', 'vas-gerabah-kasongan', 'Vas gerabah tanah liat dengan ukiran tangan khas Kasongan, finishing natural terakota. Tinggi 35 cm.', 120000, 10, 'buah', 'Bantul, Yogyakarta', 'Gerabah Kasongan Jaya', 'assets/img/products/gerabah.svg', 1, '2026-11-08 08:00:00'),
(16, 5, 'Jamu Kunyit Asam 500 ml', 'jamu-kunyit-asam-500-ml', 'Jamu kunyit asam segar dari kunyit, asam jawa, dan gula aren tanpa pengawet. Simpan di lemari es.', 20000, 49, 'botol', 'Sukoharjo', 'Jamu Mbok Darmi', 'assets/img/products/jamu-kunyit.svg', 1, '2026-11-08 08:30:00'),
(17, 5, 'Jamu Beras Kencur 500 ml', 'jamu-beras-kencur-500-ml', 'Jamu beras kencur racikan tradisional untuk menjaga stamina. Dibuat segar setiap hari.', 20000, 2, 'botol', 'Sukoharjo', 'Jamu Mbok Darmi', 'assets/img/products/beras-kencur.svg', 1, '2026-11-09 08:00:00'),
(18, 5, 'Minyak Kayu Putih Buru 60 ml', 'minyak-kayu-putih-buru-60-ml', 'Minyak kayu putih murni hasil penyulingan daun kayu putih Pulau Buru. Hangat dan melegakan.', 55000, 0, 'botol', 'Pulau Buru, Maluku', 'Penyulingan Waeapo', 'assets/img/products/kayu-putih.svg', 1, '2026-11-09 08:30:00');

-- Contoh transaksi. Perhatikan detail pesanan #1: Kopi Gayo dipesan pada
-- harga Rp 90.000 (snapshot), sedangkan harga produk sekarang Rp 95.000.
INSERT INTO orders (id, order_code, user_id, recipient_name, phone, shipping_address, notes, payment_method, total_items, subtotal, shipping_cost, total_amount, status, created_at) VALUES
(1, 'PK261110A1B2C3', 2, 'Siti Rahmawati', '081234567890', 'Jl. Malioboro No. 10, Yogyakarta 55271', 'Mohon dibungkus rapi.', 'transfer', 3, 530000, 10000, 540000, 'selesai', '2026-11-10 14:20:00'),
(2, 'PK261114D4E5F6', 2, 'Siti Rahmawati', '081234567890', 'Jl. Malioboro No. 10, Yogyakarta 55271', NULL, 'cod', 4, 125000, 10000, 135000, 'diproses', '2026-11-14 19:05:00');

INSERT INTO order_details (order_id, product_id, product_name, price, quantity, subtotal) VALUES
(1, 1, 'Batik Tulis Motif Kawung', 350000, 1, 350000),
(1, 9, 'Kopi Gayo Arabika 250 g', 90000, 2, 180000),
(2, 7, 'Dodol Garut Original 500 g', 35000, 3, 105000),
(2, 16, 'Jamu Kunyit Asam 500 ml', 20000, 1, 20000);

ALTER TABLE users AUTO_INCREMENT = 3;
ALTER TABLE categories AUTO_INCREMENT = 6;
ALTER TABLE products AUTO_INCREMENT = 19;
ALTER TABLE orders AUTO_INCREMENT = 3;
