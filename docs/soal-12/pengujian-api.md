# SOAL 12 — Pengujian REST API PasarKita

> Semua hasil di dokumen ini adalah **hasil eksekusi nyata** pada 16 November 2026 di komputer pengembang. Pengujian dijalankan dengan **Newman 6.2.3**, yaitu runner resmi Postman versi command line. Koleksi yang dijalankan sama persis dengan file yang di-import ke aplikasi Postman. Aplikasi **Postman desktop belum terpasang** di komputer ini, sehingga tangkapan layar Postman perlu diambil sendiri (lihat bagian 7).

## 1. Lingkungan Uji

| Komponen | Versi / Nilai |
|---|---|
| Web server | Apache 2.4.58 (Win64) bawaan **XAMPP**, `C:\xampp\apache` |
| PHP | 8.2.12 (XAMPP), ekstensi `mysqli` + `mysqlnd` aktif |
| Database | MariaDB 10.4.32 (XAMPP), database `pasarkita` hasil import `database/pasarkita.sql` |
| Lokasi aplikasi | `C:\xampp\htdocs\pasarkita` |
| Base URL | `http://localhost/pasarkita` |
| Alat uji | Newman 6.2.3 (Postman CLI) + koleksi `docs/soal-12/postman/PasarKita-API.postman_collection.json` |
| Uji silang | PHP 8.5.8 (Laragon, `php -S`) dengan koleksi yang sama: 133/133 assertion lulus |

## 2. Cara Menguji dengan Postman

1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka Postman → **Import** → pilih `docs/soal-12/postman/PasarKita-API.postman_collection.json`.
3. Periksa variabel koleksi `baseUrl`, nilainya `http://localhost/pasarkita`. Ubah bila nama folder di htdocs berbeda.
4. Klik kanan koleksi → **Run collection** → **Run PasarKita REST API**. Jalankan berurutan, karena request berikutnya memakai variabel (ID produk, ID pesanan) dan cookie login dari request sebelumnya.
5. Untuk mencoba satu per satu, mulailah dari **2.3 Login pembeli** sebelum request folder **3. Pesanan**. Postman otomatis menyimpan cookie `PASARKITASESSID`.

> Request **3.1** benar-benar membuat pesanan dan mengurangi stok 2 unit setiap kali dijalankan. Untuk mengembalikan data awal, import ulang `database/pasarkita.sql`.

Alternatif tanpa aplikasi Postman (hasilnya sama dengan "Run collection"):

```bash
npx newman run docs/soal-12/postman/PasarKita-API.postman_collection.json
```

## 3. Ringkasan Hasil Aktual

Hasil run lengkap: [hasil-newman-xampp.txt](hasil-newman-xampp.txt) (30 request, **133 assertion, 0 gagal**).

| No | Skenario wajib | Request di koleksi | Status yang diharapkan | Status aktual | Hasil |
|---|---|---|---|---|---|
| 1 | GET seluruh produk | 1.1 | 200 | 200 | LULUS |
| 2 | GET detail produk ID valid | 1.4 | 200 | 200 | LULUS |
| 3 | GET detail produk ID tidak valid | 1.5 (ID 999999) dan 1.6 (`id=abc`) | 404 dan 400 | 404 dan 400 | LULUS |
| 4 | GET seluruh kategori | 1.7 | 200 | 200 | LULUS |
| 5 | POST pesanan data valid | 3.1 | 201 | 201 | LULUS |
| 6 | POST pesanan stok tidak mencukupi | 3.4 dan 3.5 (rollback) | 422 | 422 | LULUS |
| 7 | POST JSON tidak valid | 4.1 | 400 | 400 | LULUS |
| 8 | Method/data tidak sesuai | 4.2, 4.3 (422), 4.4 (415), 4.5, 4.6 (405) | 422/415/405 | 422/415/405 | LULUS |

Pengujian tambahan:

| Request | Skenario | Status aktual |
|---|---|---|
| 1.2 / 1.3 | Pencarian `q=kopi` dan filter `kategori=batik-tenun` | 200 |
| 2.1 | POST pesanan tanpa login | 401 |
| 2.2 / 2.3 / 2.4 | Login salah, login pembeli, cek pengguna | 401 / 200 / 200 |
| 3.2 | Stok berkurang tepat 2 setelah pesanan | 200 (57 → 55) |
| 3.3 | GET detail pesanan baru (orders + order_details) | 200 |
| 3.6 | Stok tidak berubah setelah pesanan gagal (rollback) | 200 (tetap 55) |
| 5.3 | Admin mencoba membuat pesanan | 403 |
| 5.5 | GET pengguna setelah logout | 401 |
| — | Database dimatikan (port salah di config) | 500, `"Tidak dapat terhubung ke database."`, Content-Type JSON |
| — | Akses langsung `api/helpers/format.php` dari browser | 403 (diblokir `.htaccess`) |

## 4. Contoh Request & Response Aktual (8 Skenario Wajib)

Response di bawah disalin dari run Newman final di XAMPP. Daftar produk dipotong agar ringkas. Seluruh 30 request ada di [lampiran-request-response.md](lampiran-request-response.md).

### 4.1 Uji 1 — GET seluruh produk

**1.1 GET seluruh produk**

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?limit=100
```

**Response aktual: 200 OK** (19 ms)

```json
{
  "success": true,
  "message": "Data produk berhasil diambil",
  "data": [
    {
      "id": 18,
      "nama": "Minyak Kayu Putih Buru 60 ml",
      "slug": "minyak-kayu-putih-buru-60-ml",
      "harga": 55000,
      "stok": 0,
      "satuan": "botol",
      "tersedia": false,
      "asal_daerah": "Pulau Buru, Maluku",
      "penjual": "Penyulingan Waeapo",
      "gambar_url": "http://localhost/pasarkita/assets/img/products/kayu-putih.svg",
      "kategori": {
        "id": 5,
        "nama": "Jamu & Herbal",
        "slug": "jamu-herbal"
      }
    },
    "... (17 item lainnya dipotong untuk dokumentasi)"
  ],
  "meta": {
    "total": 18,
    "halaman": 1,
    "limit": 100,
    "total_halaman": 1,
    "filter": {
      "q": "",
      "kategori": "",
      "urut": "terbaru"
    }
  }
}
```

Assertion: 7 lulus dari 7


### 4.2 Uji 2 — GET detail produk ID valid

**1.4 GET detail produk ID valid**

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?id=7
```

**Response aktual: 200 OK** (6 ms)

```json
{
  "success": true,
  "message": "Detail produk berhasil diambil",
  "data": {
    "id": 7,
    "nama": "Dodol Garut Original 500 g",
    "slug": "dodol-garut-original-500-g",
    "harga": 35000,
    "stok": 57,
    "satuan": "kotak",
    "tersedia": true,
    "asal_daerah": "Garut",
    "penjual": "Dodol Picnic Garut",
    "gambar_url": "http://localhost/pasarkita/assets/img/products/dodol.svg",
    "kategori": {
      "id": 2,
      "nama": "Makanan Tradisional",
      "slug": "makanan-tradisional"
    },
    "deskripsi": "Dodol legit khas Garut dari ketan, gula aren, dan santan kelapa. Dibungkus satuan, cocok untuk oleh-oleh.",
    "url_halaman": "http://localhost/pasarkita/products/detail.php?slug=dodol-garut-original-500-g",
    "dibuat_pada": "2026-11-04 11:00:00",
    "diperbarui_pada": "2026-11-16 13:34:56"
  }
}
```

Assertion: 6 lulus dari 6


### 4.3 Uji 3 — GET detail produk ID tidak valid

**1.5 GET detail produk ID tidak ditemukan**

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?id=999999
```

**Response aktual: 404 Not Found** (6 ms)

```json
{
  "success": false,
  "message": "Produk tidak ditemukan",
  "data": null
}
```

Assertion: 5 lulus dari 5


**1.6 GET detail produk ID bukan angka**

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?id=abc
```

**Response aktual: 400 Bad Request** (5 ms)

```json
{
  "success": false,
  "message": "Parameter id harus berupa bilangan bulat positif.",
  "data": null
}
```

Assertion: 4 lulus dari 4


### 4.4 Uji 4 — GET seluruh kategori

**1.7 GET seluruh kategori**

**Request**

```http
GET http://localhost/pasarkita/api/kategori.php
```

**Response aktual: 200 OK** (7 ms)

```json
{
  "success": true,
  "message": "Data kategori berhasil diambil",
  "data": [
    {
      "id": 1,
      "nama": "Batik & Tenun",
      "slug": "batik-tenun",
      "deskripsi": "Kain batik tulis, batik cap, tenun, dan songket dari berbagai daerah.",
      "ikon": "bi-palette",
      "jumlah_produk": 4
    },
    "... (4 item lainnya dipotong untuk dokumentasi)"
  ],
  "meta": {
    "total": 5
  }
}
```

Assertion: 5 lulus dari 5


### 4.5 Uji 5 — POST pesanan valid

Body sengaja memuat `"harga": 1` dan `"total": 1000`. Response menunjukkan server **mengabaikan** keduanya dan memakai harga database: Rp 35.000 × 2 + ongkir Rp 10.000 = Rp 80.000.

Sebelum POST, pengguna login dulu (request 2.3):

**2.3 Login pembeli**

**Request**

```http
POST http://localhost/pasarkita/api/auth.php
Content-Type: application/json

{
  "email": "pembeli@pasarkita.test",
  "password": "pembeli123"
}
```

**Response aktual: 200 OK** (269 ms)

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "id": 2,
    "nama": "Siti Rahmawati",
    "email": "pembeli@pasarkita.test",
    "role": "pembeli"
  }
}
```

Assertion: 5 lulus dari 5


**3.1 POST pesanan valid**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [
    {
      "produk_id": 7,
      "jumlah": 2
    }
  ],
  "nama_penerima": "Siti Pembeli",
  "telepon": "081234567890",
  "alamat_pengiriman": "Jl. Merdeka No. 10, Bandung, Jawa Barat 40111",
  "catatan": "Uji Postman",
  "metode_pembayaran": "transfer",
  "harga": 1,
  "total": 1000
}
```

**Response aktual: 201 Created** (9 ms)

```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat",
  "data": {
    "id": 3,
    "kode_pesanan": "PK2611167E7910",
    "status": "pending",
    "pembeli": {
      "id": 2,
      "nama": "Siti Rahmawati",
      "email": "pembeli@pasarkita.test"
    },
    "nama_penerima": "Siti Pembeli",
    "telepon": "081234567890",
    "alamat_pengiriman": "Jl. Merdeka No. 10, Bandung, Jawa Barat 40111",
    "catatan": "Uji Postman",
    "metode_pembayaran": "transfer",
    "total_item": 2,
    "subtotal": 70000,
    "ongkos_kirim": 10000,
    "total": 80000,
    "dibuat_pada": "2026-11-16 13:35:00",
    "items": [
      {
        "produk_id": 7,
        "nama_produk": "Dodol Garut Original 500 g",
        "harga": 35000,
        "jumlah": 2,
        "subtotal": 70000
      }
    ]
  }
}
```

Assertion: 7 lulus dari 7


### 4.6 Uji 6 — POST stok tidak mencukupi

**3.4 POST pesanan stok tidak mencukupi**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [
    {
      "produk_id": 7,
      "jumlah": 56
    }
  ],
  "alamat_pengiriman": "Jl. Merdeka No. 10, Bandung, Jawa Barat 40111"
}
```

**Response aktual: 422 Unprocessable Entity** (7 ms)

```json
{
  "success": false,
  "message": "Pesanan gagal dibuat. Tidak ada data yang disimpan.",
  "data": null,
  "errors": [
    "Stok \"Dodol Garut Original 500 g\" tidak mencukupi (tersisa 55, diminta 56)."
  ]
}
```

Assertion: 5 lulus dari 5


Pesanan berisi satu item valid dan satu item yang stoknya habis ditolak seluruhnya (ROLLBACK):

**3.5 POST pesanan sebagian item gagal (rollback)**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [
    {
      "produk_id": 7,
      "jumlah": 1
    },
    {
      "produk_id": 18,
      "jumlah": 1
    }
  ],
  "alamat_pengiriman": "Jl. Merdeka No. 10, Bandung, Jawa Barat 40111"
}
```

**Response aktual: 422 Unprocessable Entity** (19 ms)

```json
{
  "success": false,
  "message": "Pesanan gagal dibuat. Tidak ada data yang disimpan.",
  "data": null,
  "errors": [
    "Stok \"Minyak Kayu Putih Buru 60 ml\" tidak mencukupi (tersisa 0, diminta 1)."
  ]
}
```

Assertion: 4 lulus dari 4


### 4.7 Uji 7 — POST JSON tidak valid

**4.1 POST JSON tidak valid**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{"produk": [ {"produk_id": 1, "jumlah": 2 }
```

**Response aktual: 400 Bad Request** (6 ms)

```json
{
  "success": false,
  "message": "Format JSON tidak valid: Syntax error.",
  "data": null
}
```

Assertion: 5 lulus dari 5


### 4.8 Uji 8 — Method atau data tidak sesuai

**4.5 PUT ke endpoint pesanan**

**Request**

```http
PUT http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [
    {
      "produk_id": 1,
      "jumlah": 1
    }
  ]
}
```

**Response aktual: 405 Method Not Allowed** (4 ms)

```json
{
  "success": false,
  "message": "Method PUT tidak diizinkan untuk endpoint ini. Gunakan: GET, POST.",
  "data": null
}
```

Assertion: 5 lulus dari 5


**4.4 POST Content-Type bukan JSON**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/x-www-form-urlencoded

produk_id=1&jumlah=2
```

**Response aktual: 415 Unsupported Media Type** (5 ms)

```json
{
  "success": false,
  "message": "Content-Type harus application/json.",
  "data": null
}
```

Assertion: 4 lulus dari 4


**4.2 POST data tidak lengkap**

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [],
  "telepon": "12"
}
```

**Response aktual: 422 Unprocessable Entity** (7 ms)

```json
{
  "success": false,
  "message": "Data pesanan tidak valid.",
  "data": null,
  "errors": {
    "produk": "Field produk wajib berupa array berisi minimal 1 item, contoh: [{\"produk_id\": 1, \"jumlah\": 2}].",
    "telepon": "Nomor telepon tidak valid. Contoh: 081234567890."
  }
}
```

Assertion: 5 lulus dari 5


## 5. Verifikasi Database Setelah Pengujian

Query dijalankan langsung di MariaDB XAMPP setelah run final (database di-import ulang sesaat sebelum run):

```sql
SELECT o.id, o.order_code, o.total_items, o.subtotal, o.total_amount, o.status,
       d.product_name, d.price, d.quantity
FROM orders o JOIN order_details d ON d.order_id = o.id WHERE o.id > 2;
```

```
id  order_code      total_items  subtotal  total_amount  status   product_name                price     quantity
3   PK2611167E7910  2            70000.00  80000.00      pending  Dodol Garut Original 500 g  35000.00  2
```

```sql
SELECT id, name, stock FROM products WHERE id = 7;          -- 7 | Dodol Garut Original 500 g | 55  (awal 57)
SELECT COUNT(*) AS stok_negatif FROM products WHERE stock < 0; -- 0
```

Kesimpulan verifikasi:

- Hanya **satu** pesanan baru yang tersimpan, yaitu dari request 3.1. Request 3.4 dan 3.5 yang gagal tidak meninggalkan baris di `orders` maupun `order_details`.
- `order_details.price` berisi 35000 (harga database), bukan nilai `harga: 1` yang dikirim klien.
- Stok berkurang tepat sesuai jumlah pesanan dan tidak ada stok negatif.

## 6. Regresi Fitur SOAL 11

Pengujian end-to-end SOAL 11 (`tests/run_tests.php`, 92 pengujian: halaman publik, login, CRUD produk + upload, keranjang, checkout, rollback, admin) dijalankan ulang setelah API ditambahkan:

| Lingkungan | Hasil |
|---|---|
| XAMPP Apache 2.4.58 + PHP 8.2.12 + MariaDB 10.4.32 | **92 / 92 LULUS** ([regresi-soal11-xampp.txt](regresi-soal11-xampp.txt)) |
| PHP 8.5.8 built-in server + MariaDB 10.4.32 | **92 / 92 LULUS** |

Saat regresi pertama di Apache, dua pengujian CSRF sempat **gagal**. Penyebabnya: halaman web menolak token CSRF dengan kode HTTP 419, kode non-standar yang tidak dikenal Apache sehingga diubah menjadi 500. Request tetap ditolak, tetapi kode statusnya salah. Masalah ini tidak muncul sebelumnya karena pengujian SOAL 11 memakai PHP built-in server. Perbaikannya: kode diganti menjadi **403 Forbidden** di `includes/functions.php` (`verify_csrf()`), lalu pengujian diulang dan lulus 92/92.

## 7. Uji Ekspor–Impor Database Laragon → XAMPP

Panduan pada [laporan-api.md](laporan-api.md#5-memindahkan-database-dari-laragon-ke-xampp) sudah diuji:

1. MySQL 8.4.3 Laragon dijalankan sementara di port 3307 (tidak bentrok dengan XAMPP), lalu di-dump dengan `mysqldump`.
2. Hasil dump di-import ke MariaDB 10.4.32 XAMPP ke database uji `pasarkita_uji_impor`, bukan `pasarkita`. Import selesai tanpa error.
3. Hasil import: 2 users, 5 categories, 18 products, 2 orders, 4 order_details, 4 foreign key, dan 8 CHECK constraint.
4. Database uji dihapus kembali.

## 8. Yang Belum Diuji

| Hal | Alasan | Tindakan |
|---|---|---|
| Menjalankan koleksi di **aplikasi Postman desktop** | Postman belum terpasang di komputer ini. Koleksi diuji dengan Newman, runner resmi Postman yang mengeksekusi file yang sama | Install Postman, import koleksi, lalu Run collection dan ambil screenshot |
| Menyalakan Apache/MySQL lewat **XAMPP Control Panel** (GUI) | Server dijalankan dari command line (`httpd.exe`, `mysqld.exe`) milik instalasi XAMPP yang sama | Jalankan dari Control Panel dan ambil screenshot |
| Klien selain Postman (aplikasi mobile, dsb.) | Di luar cakupan SOAL 12 | — |
