# 6. Mockup UI — Halaman Detail Produk

File implementasi: `products/detail.php`
URL: `/products/detail.php?slug=kopi-gayo-arabika-250-g`

## 6.1 Desktop

```
+----------------------------------------------------------------------------------+
| NAVBAR                                                                           |
+----------------------------------------------------------------------------------+
| Beranda / Produk / Minuman & Rempah / Kopi Gayo Arabika 250 g                    |
|                                                                                  |
| +-------------------------------------+   [Minuman & Rempah]                     |
| |                                     |   KOPI GAYO ARABIKA 250 G                |
| |                                     |   (shop) Koperasi Kopi Gayo · (pin) Aceh Tengah |
| |          GAMBAR PRODUK 4:3          |                                          |
| |                                     |   Rp 95.000  / bungkus                   |
| |                                     |   [v Stok tersedia: 28 bungkus]          |
| |                                     |                                          |
| +-------------------------------------+   +------------------------------------+ |
|                                           | Jumlah                             | |
|                                           | [-][  1  ][+]  [ Tambah ke Keranjang ]| |
|                                           | Maksimal 28 bungkus lagi ...       | |
|                                           +------------------------------------+ |
|                                           Deskripsi Produk                       |
|                                           Biji kopi arabika Gayo single origin...|
|                                           Kategori     Minuman & Rempah          |
|                                           Asal daerah  Aceh Tengah               |
|                                           Penjual      Koperasi Kopi Gayo        |
|                                           Satuan       bungkus                   |
|                                                                                  |
| PRODUK SERUPA                                                                    |
| +----------+ +----------+ +----------+ +----------+                              |
| | kartu    | | kartu    | | kartu    | | kartu    |   (maks. 4, kategori sama)   |
| +----------+ +----------+ +----------+ +----------+                              |
+----------------------------------------------------------------------------------+
```

## 6.2 Mobile

```
+------------------------------+
| NAVBAR                       |
| Beranda / Produk / ...       |
| +--------------------------+ |
| |      GAMBAR PRODUK       | |
| +--------------------------+ |
| [Minuman & Rempah]           |
| KOPI GAYO ARABIKA 250 G      |
| Rp 95.000 / bungkus          |
| [v Stok tersedia: 28]        |
| [-][ 1 ][+]                  |
| [  Tambah ke Keranjang   ]   |
| Deskripsi ...                |
| Produk Serupa (2 kolom)      |
+------------------------------+
```

## 6.3 Variasi Status

| Kondisi | Tampilan |
|---|---|
| Stok > 5 | Badge hijau "Stok tersedia: N" |
| 1 ≤ stok ≤ 5 | Badge kuning "Stok tinggal N" |
| Stok 0 | Badge abu "Stok habis", tombol nonaktif |
| Seluruh stok sudah di keranjang | Alert kuning + tautan ke keranjang |
| Login sebagai admin | Alert info + tautan "Edit produk ini" (tanpa tombol beli) |
| Slug tidak dikenal / produk nonaktif | Halaman 404 "Produk tidak ditemukan" |
