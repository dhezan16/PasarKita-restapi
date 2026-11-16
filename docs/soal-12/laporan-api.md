# SOAL 12 — Laporan Implementasi REST API PasarKita

| | |
|---|---|
| Aplikasi dasar | PasarKita (SOAL 11) — marketplace produk tradisional, PHP Native + MySQLi |
| Pengembangan | REST API JSON untuk produk, kategori, dan pesanan |
| Database | `pasarkita` (sama dengan SOAL 11, **tanpa perubahan struktur**) |
| Lingkungan uji | XAMPP: Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32 |
| Tanggal pengujian | 16 November 2026 |

Dokumen terkait:

- [rancangan-api.md](rancangan-api.md) — konsep, rancangan endpoint, struktur JSON.
- [pengujian-api.md](pengujian-api.md) — panduan Postman dan hasil uji aktual.
- [lampiran-request-response.md](lampiran-request-response.md) — 30 request/response lengkap.

## 1. Fungsi Web Service & REST API

Web service memungkinkan aplikasi lain mengambil dan mengirim data PasarKita melalui HTTP tanpa mengakses database secara langsung. REST API mewujudkannya dengan URL sebagai *resource* (`/api/produk.php`), HTTP method sebagai operasi (GET membaca, POST membuat), JSON sebagai format data, dan HTTP status code sebagai hasil.

Pada marketplace, API diperlukan agar:

- aplikasi mobile, aplikasi kasir, atau mitra dapat menampilkan katalog dan membuat pesanan dari sumber data yang sama dengan situs web;
- harga dan stok tetap konsisten di semua kanal;
- aturan bisnis penting tetap dijalankan di server, sehingga tidak dapat dimanipulasi klien. Aturan itu meliputi validasi stok, perhitungan total, dan transaction.

Penjelasan lengkap ada di [rancangan-api.md](rancangan-api.md#1-web-service-dan-rest-api).

## 2. Hasil Analisis Project SOAL 11

| Aspek | Temuan | Keputusan untuk API |
|---|---|---|
| Konfigurasi | `config/config.php` (di-ignore Git) berisi koneksi DB, dibaca `includes/bootstrap.php` | Dipakai ulang, tanpa file konfigurasi kedua |
| Koneksi | `includes/db.php`: singleton `db()` MySQLi + helper prepared statement (`db_fetch_all`, `db_execute`, ...) | Dipakai ulang |
| Tabel | `users`, `categories`, `products`, `orders`, `order_details` (InnoDB, utf8mb4) | Tidak diubah |
| Relasi | products→categories (RESTRICT), orders→users (RESTRICT), order_details→orders (CASCADE), order_details→products (SET NULL) | Pesanan API tetap memenuhi semua FK |
| Constraint | `stock` UNSIGNED + CHECK ≥ 0, CHECK total = subtotal + ongkir, CHECK subtotal = harga × jumlah | Dijamin oleh fungsi transaksi yang sama |
| Checkout | `checkout/index.php` → `create_order()` di `includes/order.php`: BEGIN, `SELECT ... FOR UPDATE`, validasi stok, INSERT orders + order_details (snapshot), `UPDATE stock = stock - ? WHERE stock >= ?`, COMMIT/ROLLBACK | API memanggil **fungsi yang sama**, sehingga perilaku transaksi web dan API identik |
| Identitas pembeli | Session `PASARKITASESSID`, `$_SESSION['user_id']`, role `pembeli` / `admin` | API login dengan akun asli dan memakai session yang sama; `user_id` tidak pernah diterima dari klien |

## 3. Dokumentasi Implementasi

### 3.1 File yang ditambahkan

| File | Isi |
|---|---|
| `api/helpers/bootstrap.php` | Mendefinisikan `PASARKITA_API`, memuat config, `db.php`, `auth.php`, `order.php` milik SOAL 11. Memulai session yang sama. Menyiapkan handler error, exception, dan fatal error yang selalu membalas JSON (500) tanpa detail teknis. `display_errors` selalu 0 dan output liar dibuang dengan `ob_start()` |
| `api/helpers/response.php` | `api_success()`, `api_error()` (format `success`/`message`/`data`), `api_allow_methods()` (405 + header `Allow`), `api_json_body()` (415/400), `api_positive_int()`, `api_money()`, `api_require_user()` (401) |
| `api/helpers/format.php` | Query produk + JOIN kategori, pemetaan baris database menjadi JSON produk, kategori, dan pesanan |
| `api/helpers/.htaccess` | `Require all denied`, sehingga helper tidak bisa dibuka dari browser |
| `api/produk.php` | GET daftar (pencarian, filter kategori, urutan, pagination) dan GET detail `?id=` |
| `api/kategori.php` | GET daftar kategori + jumlah produk aktif, GET `?id=` |
| `api/pesanan.php` | POST membuat pesanan, GET pesanan milik pengguna / detail `?id=` |
| `api/auth.php` | POST login, GET pengguna aktif, DELETE logout |
| `api/index.php` | GET info API |
| `docs/soal-12/*` | Dokumentasi, koleksi Postman, hasil uji |

### 3.2 File SOAL 11 yang diubah (perubahan kecil)

| File | Perubahan | Alasan |
|---|---|---|
| `includes/functions.php` | Pengaturan session dipindah ke fungsi `start_app_session()` | Dipakai bersama oleh web dan API, tanpa duplikasi |
| `includes/bootstrap.php` | Memanggil `start_app_session()` | Mengikuti perubahan di atas; perilaku tidak berubah |
| `includes/db.php` | Kelas `DatabaseUnavailableException`; dalam mode API, koneksi gagal dilempar sebagai exception | Agar API membalas JSON, bukan HTML |
| `includes/functions.php` | `verify_csrf()` membalas **403**, sebelumnya 419 | Apache XAMPP tidak mengenal kode 419 dan mengubahnya menjadi 500. Ditemukan saat uji regresi di XAMPP |
| `tests/run_tests.php`, `docs/04-flowchart-transaksi.md`, `docs/09-pengujian.md` | Kode CSRF 419 → 403 | Menyesuaikan perubahan di atas |

### 3.3 Langkah keamanan

| Kebutuhan | Implementasi |
|---|---|
| SQL injection | Semua query memakai prepared statement MySQLi. Nilai `LIKE` di-escape (`%`, `_`). Kolom `ORDER BY` dipilih dari daftar putih |
| Manipulasi harga | Klien hanya mengirim `produk_id` dan `jumlah`. Harga, subtotal, ongkir, dan total dihitung server dari database. Field `harga`/`total` dari klien diabaikan (terbukti di uji 3.1) |
| Stok negatif | `SELECT ... FOR UPDATE`, cek stok, `UPDATE ... WHERE stock >= ?` + cek `affected_rows`, CHECK constraint, kolom UNSIGNED |
| Konsistensi transaksi | Satu transaction. Satu item gagal berarti ROLLBACK seluruh pesanan (uji 3.5 & 3.6) |
| Identitas palsu | `user_id` diambil dari session login, tidak dari body |
| Hak akses | POST pesanan hanya untuk role pembeli (admin → 403). Pesanan orang lain → 404 |
| Brute force login | 5 kali gagal → 429 selama 60 detik |
| Request lintas situs | Body wajib `application/json` (415). Cookie SameSite=Lax. Tidak ada header CORS |
| Kebocoran informasi | `display_errors=0`. Warning diubah menjadi exception lalu dibalas 500 generik. Detail hanya ditulis ke log server |
| Kredensial | Hanya di `config/config.php` yang di-ignore Git |

## 4. Cara Menjalankan di XAMPP

### 4.1 Prasyarat

- XAMPP dengan PHP ≥ 7.4 (diuji: XAMPP PHP 8.2.12, MariaDB 10.4.32, Apache 2.4.58).
- Ekstensi `mysqli` aktif, sudah bawaan XAMPP. Cek dengan perintah:

```bash
C:\xampp\php\php.exe -m
```

  Pastikan `mysqli` dan `mysqlnd` ada di daftar.

### 4.2 Memindahkan project ke `C:\xampp\htdocs\pasarkita`

1. Salin seluruh isi folder project ke `C:\xampp\htdocs\pasarkita`. Sumbernya `C:\laragon\www\PasarKita` atau hasil `git clone`. Folder `.git` tidak perlu ikut.

```bash
git clone https://github.com/<username>/<nama-repo>.git C:\xampp\htdocs\pasarkita
```

   atau, bila menyalin dari Laragon (PowerShell):

```powershell
robocopy C:\laragon\www\PasarKita C:\xampp\htdocs\pasarkita /E /XD .git tests\output
```

2. Buat file konfigurasi:

```bash
copy C:\xampp\htdocs\pasarkita\config\config.example.php C:\xampp\htdocs\pasarkita\config\config.php
```

   Isi bawaan sudah sesuai XAMPP: host `localhost`, user `root`, password kosong, database `pasarkita`. Bila MySQL XAMPP Anda memakai password, isi `password` di `config.php`.

3. Buka **XAMPP Control Panel** → **Start** pada **Apache** dan **MySQL**.

### 4.3 Membuat database

Jika database `pasarkita` **belum ada** di XAMPP: buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/pasarkita.sql` → **Go**. Atau lewat command line:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\pasarkita\database\pasarkita.sql
```

> **Peringatan:** `pasarkita.sql` menjalankan `DROP TABLE IF EXISTS` lalu membuat ulang tabel. Jangan import ke database `pasarkita` yang sudah berisi data penting. Ekspor dulu (bagian 5) atau gunakan nama database lain.

### 4.4 URL pengujian localhost

| Halaman / Endpoint | URL |
|---|---|
| Aplikasi web SOAL 11 | http://localhost/pasarkita/ |
| Info API | http://localhost/pasarkita/api/ |
| Daftar produk | http://localhost/pasarkita/api/produk.php |
| Pencarian | http://localhost/pasarkita/api/produk.php?q=kopi |
| Filter kategori | http://localhost/pasarkita/api/produk.php?kategori=batik-tenun |
| Detail produk | http://localhost/pasarkita/api/produk.php?id=1 |
| Produk tidak ada | http://localhost/pasarkita/api/produk.php?id=999999 |
| Daftar kategori | http://localhost/pasarkita/api/kategori.php |
| Login (POST JSON) | http://localhost/pasarkita/api/auth.php |
| Buat pesanan (POST JSON) | http://localhost/pasarkita/api/pesanan.php |
| phpMyAdmin | http://localhost/phpmyadmin |

Request GET dapat dibuka langsung di browser. Request POST memakai Postman (lihat [pengujian-api.md](pengujian-api.md#2-cara-menguji-dengan-postman)).

## 5. Memindahkan Database dari Laragon ke XAMPP

Ikuti langkah ini bila data di Laragon sudah berubah (pesanan atau produk baru) dan ingin dibawa ke XAMPP. Database Laragon tidak diubah; hanya dibaca. Langkah ini sudah diuji: dump MySQL 8.4.3 Laragon berhasil di-import ke MariaDB 10.4.32 XAMPP.

1. **Hentikan MySQL di salah satu aplikasi.** Laragon dan XAMPP sama-sama memakai port 3306.
2. **Ekspor dari Laragon.** Jalankan Laragon → **Start All**, lalu buka Terminal Laragon:

```bash
mysqldump -u root --single-transaction --default-character-set=utf8mb4 --no-tablespaces pasarkita > C:\pasarkita_dari_laragon.sql
```

   Cara lain: buka HeidiSQL/phpMyAdmin Laragon → klik kanan database `pasarkita` → **Export database as SQL**.

3. **Stop Laragon**, lalu **Start MySQL** di XAMPP Control Panel.
4. **Import ke XAMPP tanpa menimpa database lain.** Buat database baru, lalu import:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE pasarkita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

```bash
C:\xampp\mysql\bin\mysql.exe -u root pasarkita < C:\pasarkita_dari_laragon.sql
```

   Bila `CREATE DATABASE` gagal karena `pasarkita` sudah ada di XAMPP, jangan dihapus. Gunakan nama lain, misalnya `pasarkita2`, lalu ubah `database` di `config/config.php`.

5. **Cek hasil** di phpMyAdmin XAMPP: harus ada 5 tabel. Lalu buka http://localhost/pasarkita/api/produk.php.

> Jika muncul error `Unknown collation: 'utf8mb4_0900_ai_ci'`, berarti dump berasal dari tabel yang dibuat dengan collation default MySQL 8. Ganti teks `utf8mb4_0900_ai_ci` menjadi `utf8mb4_unicode_ci` di file .sql lalu ulangi import. Tabel PasarKita memakai `utf8mb4_unicode_ci`, sehingga pada uji coba error ini tidak muncul.

## 6. Hasil Pengujian Aktual (Ringkasan)

| Pengujian | Lingkungan | Hasil |
|---|---|---|
| Koleksi Postman (Newman): 30 request, 133 assertion | XAMPP (Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32) | **133/133 lulus** |
| Koleksi yang sama | PHP 8.5.8 built-in server | **133/133 lulus** |
| Regresi SOAL 11 (`tests/run_tests.php`) | XAMPP | **92/92 lulus** (setelah perbaikan kode CSRF 419 → 403) |
| Regresi SOAL 11 | PHP 8.5.8 | **92/92 lulus** |
| Database mati → response JSON 500 tanpa detail | XAMPP | Sesuai |
| Ekspor Laragon (MySQL 8.4.3) → impor XAMPP (MariaDB 10.4.32) | Port 3307 & database uji | Berhasil, data & constraint lengkap |
| Pesanan tersimpan di `orders` + `order_details`, stok berkurang, tidak ada stok negatif | Query langsung ke MariaDB | Terverifikasi |

Rincian dan contoh response ada di [pengujian-api.md](pengujian-api.md).

## 7. Daftar Screenshot yang Perlu Diambil

| No | Screenshot | Cara |
|---|---|---|
| 1 | XAMPP Control Panel: Apache & MySQL berstatus *Running* (hijau) | Buka `xampp-control.exe` → Start keduanya |
| 2 | Folder `C:\xampp\htdocs\pasarkita` di File Explorer, terlihat folder `api` | — |
| 3 | phpMyAdmin: database `pasarkita` dengan 5 tabel | http://localhost/phpmyadmin |
| 4 | Browser: `http://localhost/pasarkita/api/produk.php` menampilkan JSON | Gunakan Chrome/Edge (JSON tampil rapi) |
| 5 | Postman: **GET seluruh produk** (1.1) dengan status 200 | Import koleksi, klik Send |
| 6 | Postman: **GET detail produk ID valid** (1.4), status 200 | — |
| 7 | Postman: **GET produk ID tidak ditemukan** (1.5), status 404 | — |
| 8 | Postman: **GET kategori** (1.7), status 200 | — |
| 9 | Postman: **Login pembeli** (2.3), status 200 | Sebelum request pesanan |
| 10 | Postman: **POST pesanan valid** (3.1), status 201 Created, tab Body + tab Headers | Tampilkan body request & response |
| 11 | Postman: **POST stok tidak mencukupi** (3.4), status 422 | — |
| 12 | Postman: **POST JSON tidak valid** (4.1), status 400 | — |
| 13 | Postman: **PUT method salah** (4.5), status 405 | — |
| 14 | Postman Runner: hasil **Run collection** (semua test hijau) | Klik kanan koleksi → Run |
| 15 | phpMyAdmin: tabel `orders` & `order_details` berisi pesanan dari Postman | Browse tabel |
| 16 | phpMyAdmin: tabel `products`, stok produk yang dipesan berkurang | Bandingkan dengan screenshot 6 |
| 17 | Aplikasi web SOAL 11 di XAMPP masih berjalan (beranda dan halaman Pesanan Saya menampilkan pesanan dari API) | Login `pembeli@pasarkita.test` |

## 8. Kesimpulan

1. Empat endpoint wajib berhasil dibuat dan diuji pada XAMPP: `GET /api/produk.php`, `GET /api/produk.php?id=`, `GET /api/kategori.php`, dan `POST /api/pesanan.php`. Endpoint pendukung login dan cek pesanan juga tersedia.
2. API membaca dan menulis database `pasarkita` yang sama dengan SOAL 11, tanpa perubahan struktur. Koneksi, autentikasi, dan logika transaksi memakai kode yang sudah ada, sehingga web dan API berperilaku identik.
3. POST pesanan memvalidasi request, mengambil harga dari database, menghitung total di server, dan menyimpan `orders` serta `order_details` dalam satu transaction. Stok berkurang, dan ROLLBACK berjalan bila ada satu item gagal. Stok terbukti tidak pernah negatif.
4. Semua response berformat JSON konsisten dengan HTTP status code yang sesuai, termasuk ketika terjadi error server.
5. Fitur SOAL 11 tetap berjalan (92/92). Pengujian di Apache XAMPP juga menemukan dan memperbaiki satu bug kode status CSRF (419 → 403).

## 9. Tindakan Manual yang Masih Diperlukan

1. Install aplikasi **Postman**, import koleksi, jalankan, dan ambil screenshot (bagian 7). Pengujian otomatis sudah dilakukan dengan Newman.
2. Jalankan Apache/MySQL dari **XAMPP Control Panel** untuk screenshot. Pada pengujian ini server dijalankan dari command line XAMPP.
3. **Push ke GitHub.** Repository lokal belum memiliki remote dan belum ada autentikasi GitHub di komputer ini. Lihat README bagian GitHub.
