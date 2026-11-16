# 8. Dokumentasi Instalasi (Lokal)

## 8.1 Kebutuhan

| Komponen | Versi minimum | Diuji pada |
|---|---|---|
| PHP | 8.0 (ekstensi `mysqli`, `mbstring`, `fileinfo`; `gd` hanya untuk skrip uji) | PHP 8.2.33 dan 8.5.8 |
| MySQL / MariaDB | MySQL 8.0.16+ / MariaDB 10.4+ (CHECK constraint aktif) | MySQL 8.4.3 |
| Web server | Apache (Laragon/XAMPP) atau PHP built-in server | PHP built-in server |
| Browser | Chrome/Edge/Firefox terbaru | — |
| Internet | Diperlukan untuk memuat Bootstrap 5.3.3, Bootstrap Icons, dan Google Fonts dari CDN | — |

## 8.2 Langkah Instalasi dengan Laragon

1. **Salin folder project** ke `C:\laragon\www\PasarKita`.
2. **Jalankan Laragon** → klik *Start All* (Apache/Nginx + MySQL).
3. **Import database**
   - Cara A (HeidiSQL/phpMyAdmin): buka *File → Run SQL file* lalu pilih `database/pasarkita.sql`.
   - Cara B (terminal Laragon):
     ```bash
     mysql -u root < database/pasarkita.sql
     ```
   File ini membuat database `pasarkita`, 5 tabel, dan data dummy. **Perhatian:** file menjalankan `DROP TABLE IF EXISTS`, sehingga data lama pada database `pasarkita` akan terhapus.
4. **Buat file konfigurasi**
   ```bash
   copy config\config.example.php config\config.php
   ```
   Lalu sesuaikan isi `config/config.php`:
   ```php
   'db' => [
       'host' => 'localhost', 'port' => 3306,
       'username' => 'root', 'password' => '', 'database' => 'pasarkita',
   ],
   'app' => [ 'base_url' => null, 'debug' => true, ... ],
   ```
   `base_url = null` berarti dideteksi otomatis (mis. `/PasarKita`). `debug = true` hanya untuk lokal.
5. **Buka aplikasi**: `http://localhost/PasarKita/` (atau `http://pasarkita.test/` jika *Auto Virtual Hosts* Laragon aktif).
6. **Pastikan folder `uploads/products/` dapat ditulis** oleh web server (di Windows biasanya sudah otomatis).

## 8.3 Alternatif: PHP Built-in Server

Dari folder induk (`C:\laragon\www`):

```bash
php -S 127.0.0.1:8090 -t C:/laragon/www
```

Lalu buka `http://127.0.0.1:8090/PasarKita/`. Catatan: built-in server tidak membaca `.htaccess`, jadi gunakan hanya untuk pengembangan/pengujian.

## 8.4 Akun Uji

| Role | Email | Password |
|---|---|---|
| Admin | `admin@pasarkita.test` | `admin123` |
| Pembeli | `pembeli@pasarkita.test` | `pembeli123` |

Akun pembeli baru juga dapat dibuat lewat menu **Daftar**.

## 8.5 Struktur Folder

```
PasarKita/
├── index.php                 # Homepage
├── .htaccess                 # Proteksi Apache (no listing, blok file sensitif)
├── .gitignore                # config.php & file upload tidak di-commit
├── README.md
├── config/
│   ├── config.example.php    # Contoh konfigurasi (tanpa kredensial asli)
│   └── config.php            # Konfigurasi lokal (dibuat manual, di-ignore Git)
├── database/
│   ├── pasarkita.sql         # Struktur + data dummy (lokal)
│   └── pasarkita_hosting.sql # Versi hosting tanpa CREATE DATABASE/USE
├── includes/                 # Bootstrap, koneksi DB, helper, auth, cart, order, layout
├── auth/                     # login.php, register.php, logout.php
├── products/                 # index.php (daftar/cari/filter), detail.php
├── cart/                     # index.php, add.php, update.php, remove.php
├── checkout/                 # index.php (form + proses), success.php
├── account/                  # orders.php, order_detail.php (riwayat pembeli)
├── admin/                    # index.php (dashboard)
│   ├── products/             # index, create, edit, delete, _form
│   └── orders/               # index, detail (ubah status)
├── assets/                   # css/style.css, js/app.js, img/ (gambar produk SVG lokal)
├── uploads/products/         # Gambar hasil upload admin (+ .htaccess anti eksekusi skrip)
├── docs/                     # Dokumentasi tugas
└── tests/run_tests.php       # Pengujian end-to-end otomatis (CLI)
```

## 8.6 Masalah Umum

| Gejala | Solusi |
|---|---|
| "Konfigurasi belum dibuat" | Salin `config/config.example.php` menjadi `config/config.php` |
| "Tidak dapat terhubung ke database" | Pastikan MySQL berjalan dan isi `config.php` benar |
| CSS/ikon tidak tampil | Periksa koneksi internet (Bootstrap dimuat dari CDN jsDelivr) |
| Link mengarah ke URL yang salah | Isi `app.base_url` secara manual, mis. `'/PasarKita'` atau `''` |
| Upload gambar gagal | Pastikan folder `uploads/products` ada dan dapat ditulis; ukuran ≤ 2 MB; format JPG/PNG/WEBP |
| Error `CHECK constraint` saat import | Gunakan MySQL ≥ 8.0.16 atau MariaDB ≥ 10.4 |
