# 5. Mockup UI — Halaman Daftar Produk

File implementasi: `products/index.php` (kartu produk: `includes/product_card.php`)
URL: `/products/index.php?q=&kategori=&urut=&page=`

## 5.1 Desktop (≥ 992 px)

```
+----------------------------------------------------------------------------------+
| [#] PasarKita   Beranda  Produk  Kategori v    [ Cari batik, kopi... ][Q]  Masuk [Daftar] (bag 2) |
+----------------------------------------------------------------------------------+
| Beranda / Produk                                                                 |
| SEMUA PRODUK                                                                     |
| Produk tradisional pilihan dari berbagai daerah di Indonesia.                    |
+----------------------------------------------------------------------------------+
| +--------------------+  Menampilkan 12 dari 18 produk                            |
| | Cari produk        |                                                           |
| | [______________]   |  +--------------+ +--------------+ +--------------+       |
| | Kategori           |  | [Kategori]   | | [Kategori]   | | [Kat] [Sisa2]|       |
| | [Semua kategori v] |  |   GAMBAR 4:3 | |   GAMBAR 4:3 | |   GAMBAR 4:3 |       |
| | Urutkan            |  |--------------| |--------------| |--------------|       |
| | [Terbaru       v]  |  | Nama Produk  | | Nama Produk  | | Nama Produk  |       |
| | [  Terapkan  ]     |  | @ Asal daerah| | @ Asal daerah| | @ Asal daerah|       |
| +--------------------+  | Rp 95.000    | | Rp 35.000    | | Rp 20.000    |       |
| +--------------------+  | /bungkus [+] | | /kotak   [+] | | /botol   [+] |       |
| | Semua kategori  ## |  +--------------+ +--------------+ +--------------+       |
| | Batik & Tenun    4 |                                                           |
| | Jamu & Herbal    3 |  ... (grid 3 kolom, 12 produk per halaman)                |
| | Kerajinan Tangan 3 |                                                           |
| | Makanan Trad.    4 |              [ 1 ] [ 2 ]   <- pagination                  |
| | Minuman & Rempah 4 |                                                           |
| +--------------------+                                                           |
+----------------------------------------------------------------------------------+
| FOOTER: tentang PasarKita | daftar kategori | tautan belanja                     |
+----------------------------------------------------------------------------------+
```

## 5.2 Mobile (< 576 px)

```
+------------------------------+
| [#] PasarKita     (bag) [=]  |
+------------------------------+
| Beranda / Produk             |
| SEMUA PRODUK                 |
+------------------------------+
| Cari produk [____________]   |
| Kategori    [Semua      v]   |
| Urutkan     [Terbaru    v]   |
| [        Terapkan        ]   |
+------------------------------+
| +-----------+ +-----------+  |
| |  GAMBAR   | |  GAMBAR   |  |
| | Nama      | | Nama      |  |
| | Rp 20.000 | | Rp 55.000 |  |
| |       [+] | |       [+] |  |
| +-----------+ +-----------+  |
|   (grid 2 kolom)             |
+------------------------------+
```

## 5.3 Elemen & Perilaku

| Elemen | Perilaku |
|---|---|
| Kolom pencarian | Mencari di nama, deskripsi, dan asal daerah (`LIKE` dengan prepared statement) |
| Dropdown kategori / daftar kategori | Filter berdasarkan `slug` kategori |
| Urutkan | Terbaru, harga terendah, harga tertinggi, nama A-Z |
| Badge kanan atas | "Sisa N" bila stok ≤ 5, "Stok habis" bila stok 0 |
| Tombol [+] | Form POST ke `cart/add.php` (qty 1, token CSRF); nonaktif bila stok habis; tidak tampil untuk admin |
| Pagination | 12 produk per halaman, parameter filter dipertahankan |
| Hasil kosong | Kartu "Produk tidak ditemukan" + tombol lihat semua produk |
