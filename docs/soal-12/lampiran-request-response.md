# Lampiran — Seluruh Request & Response Aktual (Newman, XAMPP)

Diambil otomatis dari run final koleksi `PasarKita-API.postman_collection.json` pada 16 November 2026, base URL `http://localhost/pasarkita`. Daftar berisi lebih dari dua item dipotong.

## 0.1 Info API

**Request**

```http
GET http://localhost/pasarkita/api
```

**Response aktual: 200 OK** (36 ms)

```json
{
  "success": true,
  "message": "PasarKita REST API aktif",
  "data": {
    "nama": "PasarKita REST API",
    "versi": "1.0",
    "format": "application/json",
    "endpoint": [
      {
        "method": "GET",
        "url": "http://localhost/pasarkita/api/produk.php",
        "keterangan": "Daftar produk (q, kategori, urut, halaman, limit)"
      },
      {
        "method": "GET",
        "url": "http://localhost/pasarkita/api/produk.php?id=1",
        "keterangan": "Detail produk"
      },
      {
        "method": "GET",
        "url": "http://localhost/pasarkita/api/kategori.php",
        "keterangan": "Daftar kategori"
      },
      {
        "method": "POST",
        "url": "http://localhost/pasarkita/api/auth.php",
        "keterangan": "Login (JSON email & password)"
      },
      {
        "method": "GET",
        "url": "http://localhost/pasarkita/api/auth.php",
        "keterangan": "Pengguna yang sedang login"
      },
      {
        "method": "DELETE",
        "url": "http://localhost/pasarkita/api/auth.php",
        "keterangan": "Logout"
      },
      {
        "method": "POST",
        "url": "http://localhost/pasarkita/api/pesanan.php",
        "keterangan": "Buat pesanan (wajib login pembeli)"
      },
      {
        "method": "GET",
        "url": "http://localhost/pasarkita/api/pesanan.php",
        "keterangan": "Pesanan milik pengguna / detail dengan ?id="
      }
    ]
  }
}
```

Assertion: 4 lulus dari 4

## 0.2 Logout (memastikan belum login)

**Request**

```http
DELETE http://localhost/pasarkita/api/auth.php
```

**Response aktual: 200 OK** (5 ms)

```json
{
  "success": true,
  "message": "Tidak ada sesi login yang aktif",
  "data": null
}
```

Assertion: 3 lulus dari 3

## 1.1 GET seluruh produk

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

## 1.2 GET produk dengan pencarian

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?q=kopi
```

**Response aktual: 200 OK** (8 ms)

```json
{
  "success": true,
  "message": "Data produk berhasil diambil",
  "data": [
    {
      "id": 9,
      "nama": "Kopi Gayo Arabika 250 g",
      "slug": "kopi-gayo-arabika-250-g",
      "harga": 95000,
      "stok": 28,
      "satuan": "bungkus",
      "tersedia": true,
      "asal_daerah": "Aceh Tengah",
      "penjual": "Koperasi Kopi Gayo",
      "gambar_url": "http://localhost/pasarkita/assets/img/products/kopi-gayo.svg",
      "kategori": {
        "id": 3,
        "nama": "Minuman & Rempah",
        "slug": "minuman-rempah"
      }
    }
  ],
  "meta": {
    "total": 1,
    "halaman": 1,
    "limit": 20,
    "total_halaman": 1,
    "filter": {
      "q": "kopi",
      "kategori": "",
      "urut": "terbaru"
    }
  }
}
```

Assertion: 5 lulus dari 5

## 1.3 GET produk per kategori

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?kategori=batik-tenun
```

**Response aktual: 200 OK** (6 ms)

```json
{
  "success": true,
  "message": "Data produk berhasil diambil",
  "data": [
    {
      "id": 4,
      "nama": "Kain Songket Palembang",
      "slug": "kain-songket-palembang",
      "harga": 950000,
      "stok": 3,
      "satuan": "lembar",
      "tersedia": true,
      "asal_daerah": "Palembang",
      "penjual": "Songket Bari Indah",
      "gambar_url": "http://localhost/pasarkita/assets/img/products/songket-palembang.svg",
      "kategori": {
        "id": 1,
        "nama": "Batik & Tenun",
        "slug": "batik-tenun"
      }
    },
    "... (3 item lainnya dipotong untuk dokumentasi)"
  ],
  "meta": {
    "total": 4,
    "halaman": 1,
    "limit": 20,
    "total_halaman": 1,
    "filter": {
      "q": "",
      "kategori": "batik-tenun",
      "urut": "terbaru"
    }
  }
}
```

Assertion: 4 lulus dari 4

## 1.4 GET detail produk ID valid

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

## 1.5 GET detail produk ID tidak ditemukan

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

## 1.6 GET detail produk ID bukan angka

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

## 1.7 GET seluruh kategori

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

## 2.1 POST pesanan tanpa login

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
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

**Response aktual: 401 Unauthorized** (4 ms)

```json
{
  "success": false,
  "message": "Anda harus login terlebih dahulu. Kirim POST ke /api/auth.php dengan email dan password.",
  "data": null
}
```

Assertion: 4 lulus dari 4

## 2.2 Login password salah

**Request**

```http
POST http://localhost/pasarkita/api/auth.php
Content-Type: application/json

{
  "email": "pembeli@pasarkita.test",
  "password": "salah"
}
```

**Response aktual: 401 Unauthorized** (219 ms)

```json
{
  "success": false,
  "message": "Email atau password salah.",
  "data": null
}
```

Assertion: 4 lulus dari 4

## 2.3 Login pembeli

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

## 2.4 GET pengguna yang login

**Request**

```http
GET http://localhost/pasarkita/api/auth.php
```

**Response aktual: 200 OK** (18 ms)

```json
{
  "success": true,
  "message": "Anda login sebagai Siti Rahmawati",
  "data": {
    "id": 2,
    "nama": "Siti Rahmawati",
    "email": "pembeli@pasarkita.test",
    "role": "pembeli"
  }
}
```

Assertion: 4 lulus dari 4

## 3.1 POST pesanan valid

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

## 3.2 Cek stok berkurang

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?id=7
```

**Response aktual: 200 OK** (18 ms)

```json
{
  "success": true,
  "message": "Detail produk berhasil diambil",
  "data": {
    "id": 7,
    "nama": "Dodol Garut Original 500 g",
    "slug": "dodol-garut-original-500-g",
    "harga": 35000,
    "stok": 55,
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
    "diperbarui_pada": "2026-11-16 13:35:00"
  }
}
```

Assertion: 4 lulus dari 4

## 3.3 GET detail pesanan yang dibuat

**Request**

```http
GET http://localhost/pasarkita/api/pesanan.php?id=3
```

**Response aktual: 200 OK** (6 ms)

```json
{
  "success": true,
  "message": "Detail pesanan berhasil diambil",
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

Assertion: 4 lulus dari 4

## 3.4 POST pesanan stok tidak mencukupi

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

## 3.5 POST pesanan sebagian item gagal (rollback)

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

## 3.6 Cek stok tidak berubah setelah rollback

**Request**

```http
GET http://localhost/pasarkita/api/produk.php?id=7
```

**Response aktual: 200 OK** (5 ms)

```json
{
  "success": true,
  "message": "Detail produk berhasil diambil",
  "data": {
    "id": 7,
    "nama": "Dodol Garut Original 500 g",
    "slug": "dodol-garut-original-500-g",
    "harga": 35000,
    "stok": 55,
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
    "diperbarui_pada": "2026-11-16 13:35:00"
  }
}
```

Assertion: 4 lulus dari 4

## 4.1 POST JSON tidak valid

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

## 4.2 POST data tidak lengkap

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

## 4.3 POST jumlah bukan angka

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
Content-Type: application/json

{
  "produk": [
    {
      "produk_id": 1,
      "jumlah": "dua"
    }
  ]
}
```

**Response aktual: 422 Unprocessable Entity** (5 ms)

```json
{
  "success": false,
  "message": "Data pesanan tidak valid.",
  "data": null,
  "errors": {
    "produk.0.jumlah": "jumlah wajib berupa bilangan bulat 1-1000."
  }
}
```

Assertion: 4 lulus dari 4

## 4.4 POST Content-Type bukan JSON

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

## 4.5 PUT ke endpoint pesanan

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

## 4.6 DELETE ke endpoint produk

**Request**

```http
DELETE http://localhost/pasarkita/api/produk.php?id=1
```

**Response aktual: 405 Method Not Allowed** (3 ms)

```json
{
  "success": false,
  "message": "Method DELETE tidak diizinkan untuk endpoint ini. Gunakan: GET.",
  "data": null
}
```

Assertion: 4 lulus dari 4

## 5.1 Logout pembeli

**Request**

```http
DELETE http://localhost/pasarkita/api/auth.php
```

**Response aktual: 200 OK** (6 ms)

```json
{
  "success": true,
  "message": "Logout berhasil",
  "data": null
}
```

Assertion: 4 lulus dari 4

## 5.2 Login admin

**Request**

```http
POST http://localhost/pasarkita/api/auth.php
Content-Type: application/json

{
  "email": "admin@pasarkita.test",
  "password": "admin123"
}
```

**Response aktual: 200 OK** (289 ms)

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "id": 1,
    "nama": "Administrator PasarKita",
    "email": "admin@pasarkita.test",
    "role": "admin"
  }
}
```

Assertion: 3 lulus dari 3

## 5.3 Admin tidak boleh membuat pesanan

**Request**

```http
POST http://localhost/pasarkita/api/pesanan.php
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

**Response aktual: 403 Forbidden** (7 ms)

```json
{
  "success": false,
  "message": "Hanya akun pembeli yang dapat membuat pesanan.",
  "data": null
}
```

Assertion: 4 lulus dari 4

## 5.4 Logout admin

**Request**

```http
DELETE http://localhost/pasarkita/api/auth.php
```

**Response aktual: 200 OK** (18 ms)

```json
{
  "success": true,
  "message": "Logout berhasil",
  "data": null
}
```

Assertion: 3 lulus dari 3

## 5.5 GET pengguna setelah logout

**Request**

```http
GET http://localhost/pasarkita/api/auth.php
```

**Response aktual: 401 Unauthorized** (4 ms)

```json
{
  "success": false,
  "message": "Anda harus login terlebih dahulu. Kirim POST ke /api/auth.php dengan email dan password.",
  "data": null
}
```

Assertion: 4 lulus dari 4
