# 1. Identifikasi Entitas — PasarKita

PasarKita adalah marketplace sederhana yang mempertemukan **penjual produk tradisional** (UMKM, pengrajin, petani) dengan **pembeli**. Pengelolaan katalog dilakukan oleh **admin**, sedangkan identitas penjual dicatat pada setiap produk (atribut `seller_name` dan `origin`).

## 1.1 Aktor Sistem

| Aktor | Deskripsi | Hak akses utama |
|---|---|---|
| Tamu (belum login) | Pengunjung umum | Melihat beranda, daftar & detail produk, mencari, memfilter, mengisi keranjang, registrasi, login |
| Pembeli (`role = pembeli`) | Pengguna terdaftar yang berbelanja | Semua hak tamu + checkout, melihat riwayat & detail pesanan sendiri |
| Admin (`role = admin`) | Pengelola marketplace | Dashboard statistik, CRUD produk, upload gambar, kelola stok & harga, melihat & mengubah status transaksi. Tidak bisa berbelanja |

## 1.2 Entitas Data

| No | Entitas | Tabel | Deskripsi | Atribut kunci |
|---|---|---|---|---|
| 1 | Pengguna | `users` | Akun admin & pembeli | `id` (PK), `email` (UNIQUE), `password` (hash), `role` |
| 2 | Kategori | `categories` | Pengelompokan produk (Batik & Tenun, Makanan Tradisional, dll.) | `id` (PK), `slug` (UNIQUE) |
| 3 | Produk | `products` | Barang yang dijual beserta harga, stok, asal daerah & penjual | `id` (PK), `category_id` (FK), `slug` (UNIQUE), `price`, `stock` |
| 4 | Pesanan | `orders` | Header transaksi: siapa yang memesan, alamat, total, status | `id` (PK), `order_code` (UNIQUE), `user_id` (FK) |
| 5 | Detail Pesanan | `order_details` | Baris item dalam pesanan, berisi **snapshot** nama & harga produk | `id` (PK), `order_id` (FK), `product_id` (FK, nullable) |

Keranjang belanja **bukan entitas database**: keranjang disimpan di PHP Session dalam bentuk `[product_id => jumlah]` dan baru menjadi `orders` + `order_details` saat checkout berhasil.

## 1.3 Relasi Antar Entitas

| Relasi | Kardinalitas | Keterangan | Aturan FK |
|---|---|---|---|
| `categories` → `products` | 1 : N | Satu kategori memiliki banyak produk | `ON DELETE RESTRICT` (kategori yang masih dipakai tidak bisa dihapus) |
| `users` → `orders` | 1 : N | Satu pembeli dapat membuat banyak pesanan | `ON DELETE RESTRICT` (akun yang punya transaksi tidak bisa dihapus) |
| `orders` → `order_details` | 1 : N | Satu pesanan memiliki satu atau lebih item | `ON DELETE CASCADE` |
| `products` → `order_details` | 1 : N | Satu produk dapat muncul di banyak pesanan | `ON DELETE SET NULL` (histori pesanan tetap ada saat produk dihapus) |

Relasi `orders` ↔ `products` adalah **many-to-many** yang dipecah oleh tabel asosiatif `order_details`.

## 1.4 Aturan Bisnis

1. Email pengguna unik; password disimpan dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
2. Stok produk tidak boleh negatif (`INT UNSIGNED` + `CHECK (stock >= 0)` + kondisi `stock >= qty` saat update).
3. Harga yang dibayar pembeli **selalu** diambil dari database saat checkout, bukan dari browser.
4. Nama dan harga produk disalin ke `order_details` saat dipesan (snapshot) sehingga perubahan harga/penghapusan produk tidak mengubah histori.
5. Checkout dijalankan dalam satu *database transaction*; bila satu item gagal (stok kurang/produk nonaktif) seluruh pesanan di-*rollback*.
6. Ongkos kirim flat (default Rp 10.000, diatur di `config/config.php`) dan dihitung di server.
7. Status pesanan: `pending` → `diproses` → `dikirim` → `selesai`, atau `dibatalkan`. Pembatalan mengembalikan stok. Status `selesai`/`dibatalkan` bersifat final.
