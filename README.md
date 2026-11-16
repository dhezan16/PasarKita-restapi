# PasarKita — Marketplace Produk Tradisional Indonesia

PasarKita adalah aplikasi web marketplace sederhana yang mempertemukan penjual produk tradisional (batik, tenun, kuliner khas, rempah, kerajinan, jamu) dengan pembeli. Dibuat sebagai tugas ujian praktikum pemrograman web.

**Teknologi:** PHP Native (tanpa framework) · MySQL + MySQLi (prepared statement) · Bootstrap 5.3 · HTML/CSS/JavaScript · PHP Session

## Fitur

| Area | Fitur |
|---|---|
| Publik | Beranda, daftar produk, pencarian, filter kategori, urutan & pagination, detail produk, keranjang, checkout, halaman sukses pesanan |
| Autentikasi | Registrasi, login, logout, `password_hash()` / `password_verify()`, session + regenerasi ID, role admin & pembeli |
| Pembeli | Riwayat & detail pesanan (harga snapshot saat dipesan) |
| Admin | Dashboard statistik, CRUD produk, upload gambar, kelola stok & harga, daftar transaksi, ubah status (batal → stok kembali) |
| Transaksi | Database transaction (commit/rollback), `SELECT ... FOR UPDATE`, validasi stok, snapshot harga, stok tidak bisa negatif |
| Keamanan | Prepared statement, validasi server-side, escape output, CSRF token, proteksi role, validasi upload, anti-manipulasi harga, kredensial di luar repo |

## Instalasi Cepat (Laragon / XAMPP)

```bash
mysql -u root < database/pasarkita.sql
```

```bash
copy config\config.example.php config\config.php
```

Sesuaikan kredensial di `config/config.php`, lalu buka `http://localhost/PasarKita/`.

Akun uji (hanya untuk pengujian, ganti di hosting publik):

| Role | Email | Password |
|---|---|---|
| Admin | admin@pasarkita.test | admin123 |
| Pembeli | pembeli@pasarkita.test | pembeli123 |

Panduan lengkap: [docs/08-instalasi.md](docs/08-instalasi.md)

## Pengujian

```bash
php tests/run_tests.php http://localhost/PasarKita
```

Skrip melakukan 92 pengujian end-to-end (HTTP + verifikasi database) dan membersihkan data ujinya sendiri. Hasil terakhir: **92/92 lulus** (PHP 8.2.33 & 8.5.8, MySQL 8.4.3). Detail: [docs/09-pengujian.md](docs/09-pengujian.md)

## Deployment ke InfinityFree

1. Buat database di Control Panel → **MySQL Databases**.
2. Import **`database/pasarkita_hosting.sql`** lewat phpMyAdmin.
3. Buat `config/config.php` berisi hostname `sqlXXX.byetcluster.com`, username, password, dan nama database dari panel; set `debug => false`.
4. Upload seluruh file ke folder `htdocs` (File Manager atau FTP).

Panduan lengkap: [docs/11-deployment-infinityfree.md](docs/11-deployment-infinityfree.md)

## Struktur Folder

```
config/      konfigurasi (config.example.php; config.php tidak di-commit)
database/    file SQL struktur + data dummy
includes/    bootstrap, koneksi DB, helper, auth, cart, order, layout
auth/        login, register, logout
products/    daftar & detail produk
cart/        keranjang (lihat, tambah, ubah, hapus)
checkout/    checkout & halaman sukses
account/     pesanan saya
admin/       dashboard, produk, transaksi
assets/      css, js, gambar
uploads/     gambar produk hasil upload
docs/        dokumentasi tugas
tests/       pengujian otomatis
```

## Dokumentasi

| No | Dokumen |
|---|---|
| 1 | [Identifikasi entitas](docs/01-identifikasi-entitas.md) |
| 2 | [Rancangan database](docs/02-rancangan-database.md) |
| 3 | [ERD (Mermaid)](docs/03-erd.md) |
| 4 | [Flowchart transaksi (Mermaid)](docs/04-flowchart-transaksi.md) |
| 5 | [Mockup daftar produk](docs/05-mockup-daftar-produk.md) |
| 6 | [Mockup detail produk](docs/06-mockup-detail-produk.md) |
| 7 | [Mockup keranjang](docs/07-mockup-keranjang.md) |
| 8 | [Instalasi](docs/08-instalasi.md) |
| 9 | [Pengujian](docs/09-pengujian.md) |
| 10 | [Draft laporan akhir](docs/10-laporan-akhir.md) |
| 11 | [Deployment GitHub & InfinityFree](docs/11-deployment-infinityfree.md) |

## Catatan

Gambar produk adalah ilustrasi SVG buatan sendiri yang disimpan lokal di `assets/img/products/`. Bootstrap, Bootstrap Icons, dan font dimuat dari CDN sehingga membutuhkan koneksi internet.
