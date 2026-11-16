# 2. Rancangan Database — `pasarkita`

- DBMS: MySQL 8.0.16+ / MariaDB 10.4+ (diuji pada MySQL 8.4.3)
- Engine: InnoDB (mendukung foreign key & transaction)
- Charset: `utf8mb4` / `utf8mb4_unicode_ci`
- File SQL: [`database/pasarkita.sql`](../database/pasarkita.sql) (lokal) dan [`database/pasarkita_hosting.sql`](../database/pasarkita_hosting.sql) (hosting, tanpa `CREATE DATABASE`/`USE`)

## 2.1 Tabel `users`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| id | INT UNSIGNED | Tidak | AUTO_INCREMENT | **PK** |
| name | VARCHAR(100) | Tidak | | Nama lengkap |
| email | VARCHAR(150) | Tidak | | **UNIQUE** `uq_users_email` |
| password | VARCHAR(255) | Tidak | | Hash `password_hash()` (bcrypt) |
| phone | VARCHAR(20) | Ya | NULL | |
| address | TEXT | Ya | NULL | |
| role | ENUM('admin','pembeli') | Tidak | 'pembeli' | INDEX `idx_users_role` |
| created_at | TIMESTAMP | Tidak | CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP | Tidak | CURRENT_TIMESTAMP ON UPDATE | |

## 2.2 Tabel `categories`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| id | INT UNSIGNED | Tidak | AUTO_INCREMENT | **PK** |
| name | VARCHAR(80) | Tidak | | **UNIQUE** |
| slug | VARCHAR(100) | Tidak | | **UNIQUE**, dipakai di URL filter |
| description | VARCHAR(255) | Ya | NULL | |
| icon | VARCHAR(50) | Tidak | 'bi-tag' | Nama ikon Bootstrap Icons |
| created_at | TIMESTAMP | Tidak | CURRENT_TIMESTAMP | |

## 2.3 Tabel `products`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| id | INT UNSIGNED | Tidak | AUTO_INCREMENT | **PK** |
| category_id | INT UNSIGNED | Tidak | | **FK** → `categories.id` (UPDATE CASCADE, DELETE RESTRICT) |
| name | VARCHAR(150) | Tidak | | INDEX `idx_products_name` |
| slug | VARCHAR(170) | Tidak | | **UNIQUE**, URL detail produk |
| description | TEXT | Tidak | | |
| price | DECIMAL(12,2) | Tidak | | `CHECK (price >= 0)` |
| stock | INT UNSIGNED | Tidak | 0 | `CHECK (stock >= 0)` |
| unit | VARCHAR(30) | Tidak | 'pcs' | Satuan (kg, lembar, botol, ...) |
| origin | VARCHAR(100) | Ya | NULL | Daerah asal |
| seller_name | VARCHAR(100) | Ya | NULL | Nama penjual / UMKM |
| image | VARCHAR(255) | Ya | NULL | Path relatif (mis. `uploads/products/xxx.jpg`) |
| is_active | TINYINT(1) | Tidak | 1 | `CHECK (is_active IN (0,1))`; INDEX bersama `created_at` |
| created_at / updated_at | TIMESTAMP | Tidak | CURRENT_TIMESTAMP | |

## 2.4 Tabel `orders`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| id | INT UNSIGNED | Tidak | AUTO_INCREMENT | **PK** |
| order_code | VARCHAR(30) | Tidak | | **UNIQUE**, format `PK` + `yymmdd` + 6 heksadesimal acak |
| user_id | INT UNSIGNED | Tidak | | **FK** → `users.id` (UPDATE CASCADE, DELETE RESTRICT) |
| recipient_name | VARCHAR(100) | Tidak | | |
| phone | VARCHAR(20) | Tidak | | |
| shipping_address | TEXT | Tidak | | |
| notes | VARCHAR(255) | Ya | NULL | |
| payment_method | ENUM('transfer','cod') | Tidak | 'transfer' | |
| total_items | INT UNSIGNED | Tidak | | `CHECK (total_items > 0)` |
| subtotal | DECIMAL(14,2) | Tidak | | |
| shipping_cost | DECIMAL(12,2) | Tidak | 0 | |
| total_amount | DECIMAL(14,2) | Tidak | | `CHECK (total_amount = subtotal + shipping_cost)` |
| status | ENUM('pending','diproses','dikirim','selesai','dibatalkan') | Tidak | 'pending' | INDEX |
| created_at / updated_at | TIMESTAMP | Tidak | CURRENT_TIMESTAMP | INDEX `created_at` |

## 2.5 Tabel `order_details`

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| id | INT UNSIGNED | Tidak | AUTO_INCREMENT | **PK** |
| order_id | INT UNSIGNED | Tidak | | **FK** → `orders.id` (DELETE CASCADE) |
| product_id | INT UNSIGNED | Ya | | **FK** → `products.id` (DELETE SET NULL) |
| product_name | VARCHAR(150) | Tidak | | **Snapshot** nama saat dipesan |
| price | DECIMAL(12,2) | Tidak | | **Snapshot** harga satuan saat dipesan |
| quantity | INT UNSIGNED | Tidak | | `CHECK (quantity > 0)` |
| subtotal | DECIMAL(14,2) | Tidak | | `CHECK (subtotal = price * quantity)` |

UNIQUE `(order_id, product_id)` mencegah produk yang sama tercatat dua kali dalam satu pesanan.

## 2.6 Konsistensi Data Transaksi

| Kebutuhan | Implementasi |
|---|---|
| Stok tidak negatif | `INT UNSIGNED` + `CHECK` + `UPDATE ... SET stock = stock - ? WHERE id = ? AND stock >= ?` (dicek `affected_rows = 1`) |
| Tidak ada pesanan setengah jadi | `begin_transaction()` → insert `orders` → insert `order_details` → update stok → `commit()`; error apa pun → `rollback()` |
| Race condition dua pembeli | `SELECT ... FOR UPDATE` mengunci baris produk selama transaksi; ID diurutkan untuk mencegah deadlock |
| Histori harga | `order_details.price` & `product_name` disalin saat checkout |
| Produk dihapus | `product_id` menjadi `NULL`, baris detail tetap ada |
| Total valid | `CHECK (total_amount = subtotal + shipping_cost)` dan `CHECK (subtotal = price * quantity)` |

## 2.7 Data Dummy

| Data | Jumlah | Keterangan |
|---|---|---|
| users | 2 | `admin@pasarkita.test` / `admin123` (admin), `pembeli@pasarkita.test` / `pembeli123` (pembeli) |
| categories | 5 | Batik & Tenun, Makanan Tradisional, Minuman & Rempah, Kerajinan Tangan, Jamu & Herbal |
| products | 18 | Termasuk 1 produk stok habis (Minyak Kayu Putih) dan beberapa stok menipis |
| orders | 2 | 1 `selesai`, 1 `diproses` |
| order_details | 4 | Kopi Gayo pada pesanan #1 tercatat Rp 90.000 sedangkan harga sekarang Rp 95.000 (contoh snapshot harga) |

> Akun di atas hanya untuk pengujian. Ganti password admin setelah deploy ke hosting publik.
