# 11. Panduan Deployment — GitHub & InfinityFree

> Status: aplikasi **siap di-deploy**, tetapi **belum di-upload** ke InfinityFree. Langkah di bawah harus dilakukan manual oleh pemilik akun karena membutuhkan login ke panel hosting.

## 11.1 Persiapan Repository GitHub

File rahasia sudah dikecualikan oleh `.gitignore`:

| Diabaikan | Alasan |
|---|---|
| `config/config.php` | Berisi password database |
| `uploads/products/*` (kecuali `.gitkeep`) | File unggahan pengguna |
| `tests/output/`, `*.log` | Hasil pengujian lokal |

Langkah publikasi (jalankan dari folder project):

```bash
git init
git add .
git status
```

Pastikan `config/config.php` **tidak** muncul di daftar `git status`. Lalu:

```bash
git commit -m "PasarKita: marketplace produk tradisional"
```

Buat repository kosong di https://github.com/new (tanpa README), kemudian:

```bash
git branch -M main
git remote add origin https://github.com/<username>/<nama-repo>.git
git push -u origin main
```

Ganti `<username>/<nama-repo>` dengan milik Anda sendiri.

## 11.2 Membuat Akun & Database di InfinityFree

1. Daftar/masuk di https://www.infinityfree.com lalu **Create Account** (pilih subdomain gratis, mis. `namaanda.infinityfreeapp.com`, atau domain sendiri).
2. Buka **Control Panel** (VistaPanel) akun hosting tersebut.
3. Masuk ke **MySQL Databases** → isi nama database (mis. `pasarkita`) → **Create Database**.
4. Catat informasi yang ditampilkan panel (halaman *MySQL Databases* / *Account Details*):

| Informasi | Contoh format (bukan nilai asli) |
|---|---|
| MySQL Hostname | salin persis dari panel, mis. `sqlXXX.byetcluster.com` |
| MySQL Username | `if0_XXXXXXXX` |
| MySQL Password | password akun hosting (lihat *Account Details* → *Show*) |
| Database Name | `if0_XXXXXXXX_pasarkita` |

## 11.3 Import Database

1. Di Control Panel klik **phpMyAdmin** di samping database yang dibuat.
2. Pilih database `if0_XXXXXXXX_pasarkita` di panel kiri.
3. Tab **Import** → pilih file **`database/pasarkita_hosting.sql`** → **Go/Kirim**.
   - Gunakan file *hosting*, bukan `pasarkita.sql`, karena pengguna InfinityFree tidak diizinkan menjalankan `CREATE DATABASE`.
4. Pastikan muncul 5 tabel: `categories`, `order_details`, `orders`, `products`, `users`.

## 11.4 Konfigurasi Database Hosting

Buat file `config/config.php` **di komputer Anda** (jangan di-commit) dari `config.example.php`, lalu isi:

```php
<?php
return [
    'db' => [
        'host'     => 'sqlXXX.byetcluster.com',    // salin PERSIS MySQL Hostname dari panel
        'port'     => 3306,
        'username' => 'if0_XXXXXXXX',              // MySQL Username
        'password' => 'PASSWORD_HOSTING_ANDA',     // MySQL Password
        'database' => 'if0_XXXXXXXX_pasarkita',    // Database Name
    ],
    'app' => [
        'name'          => 'PasarKita',
        'base_url'      => '',      // '' bila aplikasi di root htdocs
        'debug'         => false,   // WAJIB false di hosting publik
        'timezone'      => 'Asia/Jakarta',
        'shipping_cost' => 10000,
    ],
];
```

> InfinityFree **tidak** menerima koneksi dari `localhost`; selalu gunakan hostname `sqlXXX...` yang tertulis di panel. Error `getaddrinfo ... Name or service not known` berarti hostname salah ketik atau tidak dikenali DNS: salin ulang dari Control Panel → MySQL Databases, pastikan tidak ada spasi. Hostname ini hanya bisa diakses dari server InfinityFree (alamatnya IP internal), jadi tidak bisa dites dari komputer lokal.

## 11.5 Upload File Aplikasi

**Opsi A — File Manager (Online)**
1. Control Panel → **Online File Manager** → buka folder **`htdocs`**.
2. Hapus file bawaan `index2.html` (jika ada).
3. Upload seluruh isi folder project **kecuali** `.git/`, `tests/output/`, dan `docs/` (opsional). Pertahankan struktur folder. Cara cepat: kompres menjadi `.zip`, upload, lalu gunakan fitur *Extract* bila tersedia.
4. Upload `config/config.php` versi hosting (langkah 11.4) ke `htdocs/config/`.

**Opsi B — FTP (FileZilla)**
1. Ambil kredensial FTP di Control Panel → **FTP Details** (host `ftpupload.net`, username `if0_XXXXXXXX`, password akun hosting, port 21).
2. Hubungkan FileZilla, buka folder `/htdocs` di sisi server.
3. Seret seluruh isi folder project ke `/htdocs` (struktur sama seperti di atas).

Struktur akhir di server:

```
htdocs/
├── index.php
├── .htaccess
├── account/  admin/  assets/  auth/  cart/  checkout/  config/  database/
├── includes/  products/  uploads/products/
```

Folder `config/`, `includes/`, `database/`, `docs/`, `tests/` sudah dilindungi `.htaccess` (`Require all denied`), dan `uploads/` menolak eksekusi skrip.

## 11.6 Verifikasi Setelah Deploy

Buka `https://namaanda.infinityfreeapp.com/` (SSL gratis dapat diaktifkan lewat menu **SSL Certificates**; aktivasi bisa memakan waktu). Lakukan pengecekan manual:

| No | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Buka beranda | Hero, kategori, dan 8 produk terbaru tampil |
| 2 | Buka `/config/config.php` di browser | 403 Forbidden |
| 3 | Login `admin@pasarkita.test` | Masuk ke dashboard admin |
| 4 | **Ganti password admin** (buat akun baru atau update hash via phpMyAdmin) | Akun uji tidak lagi bisa dipakai orang lain |
| 5 | Tambah produk dengan gambar | Gambar tersimpan di `uploads/products/` dan tampil |
| 6 | Login pembeli → tambah ke keranjang → checkout | Halaman sukses + stok berkurang |
| 7 | Dashboard → Transaksi | Pesanan baru muncul |

Skrip `tests/run_tests.php` juga dapat dijalankan dari komputer lokal terhadap URL hosting **hanya bila** `config/config.php` lokal diarahkan ke database hosting (koneksi MySQL InfinityFree dari luar biasanya **diblokir**, sehingga pengujian otomatis praktis hanya untuk lingkungan lokal).

## 11.7 Cara Mengganti Password Admin di Hosting

Jalankan di komputer lokal:

```bash
php -r "echo password_hash('PasswordBaruYangKuat123', PASSWORD_DEFAULT), PHP_EOL;"
```

Salin hasil hash, lalu di phpMyAdmin hosting jalankan:

```sql
UPDATE users SET password = 'HASIL_HASH_DI_ATAS' WHERE email = 'admin@pasarkita.test';
```

## 11.8 Catatan Batasan InfinityFree

- Tidak ada akses SSH/terminal; semua lewat File Manager/FTP/phpMyAdmin.
- Batas ukuran upload file per request terbatas; aplikasi sendiri membatasi gambar 2 MB.
- Ada perlindungan anti-bot (cookie JavaScript) di sisi InfinityFree, sehingga akses via `curl`/skrip otomatis ke situs hosting dapat gagal walaupun situs normal di browser.
- Jangan pernah menyimpan password database di repository publik — cukup di `config/config.php` pada server.
