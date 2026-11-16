# 9. Dokumentasi Pengujian

## 9.1 Metode

Pengujian dilakukan dengan dua cara:

1. **Pengujian otomatis end-to-end** — skrip [`tests/run_tests.php`](../tests/run_tests.php) yang benar-benar mengirim request HTTP (dengan cookie session terpisah untuk tamu, admin, dan pembeli, termasuk token CSRF dan upload file multipart) ke aplikasi yang sedang berjalan, lalu memverifikasi isi database secara langsung melalui MySQLi.
   - Skrip membuat data ujinya sendiri (2 produk uji, 1 akun pembeli uji) dan **menghapusnya kembali** di akhir sehingga data dummy tidak berubah.
2. **Pengujian manual/visual** di browser: beranda, daftar produk, detail produk, keranjang, checkout, dashboard admin, form edit produk, serta tampilan mobile (viewport 375 × 812).

## 9.2 Lingkungan Uji

| Komponen | Versi |
|---|---|
| OS | Windows 11 (Laragon) |
| PHP | 8.5.8 (port 8090) dan 8.2.33 (port 8091), PHP built-in server |
| Database | MySQL 8.4.3 |
| Tanggal | 16 November 2026 |

Perintah yang dijalankan:

```bash
php -S 127.0.0.1:8090 -t C:/laragon/www
php tests/run_tests.php http://127.0.0.1:8090/PasarKita
```

## 9.3 Ringkasan Hasil

**92 dari 92 pengujian LULUS** pada PHP 8.5.8 dan **92 dari 92 LULUS** pada PHP 8.2.33.

| No | Fitur yang diuji (sesuai soal) | Skenario utama | Hasil |
|---|---|---|---|
| 1 | Koneksi database | Koneksi MySQLi, 5 tabel ada, 4 foreign key, DB menolak stok negatif, FK menolak kategori tidak valid, RESTRICT pada kategori | LULUS (10/10) |
| 2 | Daftar produk, pencarian, filter, detail | Jumlah produk, pencarian "kopi", input injeksi SQL, filter kategori, urutan harga, detail, 404, escape XSS | LULUS (10/10) |
| 3 | Akses tamu & CSRF | 5 halaman terproteksi redirect ke login; POST tanpa/ dengan token palsu → 419 | LULUS (7/7) |
| 4 | Login admin & CRUD produk | Password salah ditolak, regenerasi session ID, statistik dashboard, daftar transaksi, validasi input, tolak file PHP ber-ekstensi .jpg, tambah produk + upload gambar, edit stok | LULUS (15/15) |
| 5 | Registrasi & login | Password lemah ditolak, email duplikat ditolak, hash `password_hash`, logout POST+CSRF, login `password_verify` | LULUS (7/7) |
| 6 | Akses admin vs pembeli | Pembeli 403 di halaman admin & aksi hapus; admin 403 di checkout | LULUS (5/5) |
| 7 | Keranjang | Tambah, manipulasi harga dari browser diabaikan, subtotal/total, melebihi stok ditolak, ubah jumlah, jumlah negatif ditolak, hapus item | LULUS (9/9) |
| 8 | Checkout, orders, order_details, pengurangan stok | Validasi form, pesanan tersimpan, total dihitung server, 2 baris detail, snapshot harga, stok berkurang, halaman sukses, keranjang kosong, proteksi halaman sukses, riwayat pesanan | LULUS (13/13) |
| 9 | Histori harga | Harga produk diubah, harga di `order_details` tetap | LULUS (2/2) |
| 10 | Validasi stok tidak mencukupi | Stok diturunkan di tengah proses → checkout ditolak, tidak ada order/detail tersimpan, stok produk lain tidak berkurang (rollback) | LULUS (4/4) |
| 11 | Status pesanan | Ubah status, pembatalan mengembalikan stok, status final terkunci | LULUS (4/4) |
| 12 | Hapus produk | Produk & file gambar terhapus, detail pesanan tetap ada (`product_id` NULL), pembeli tetap bisa melihat pesanan lama | LULUS (5/5) |
| — | Pembersihan | Data uji dihapus | LULUS (1/1) |

## 9.4 Pengujian Manual (Browser)

| Halaman | Yang diperiksa | Hasil |
|---|---|---|
| Beranda | Hero, statistik, kategori, produk terbaru, badge stok | Sesuai |
| Detail produk | Breadcrumb, harga, stok, tombol jumlah +/−, deskripsi | Sesuai |
| Keranjang (mobile 375 px) | Item, kontrol jumlah, ringkasan, tombol checkout | Sesuai |
| Daftar produk (mobile) | Form filter, grid 2 kolom | Sesuai setelah perbaikan badge (lihat 9.5) |
| Checkout | Form alamat terisi otomatis dari profil, metode bayar, ringkasan | Sesuai setelah perbaikan teks harga (lihat 9.5) |
| Login → Dashboard admin | Flash message, 4 kartu statistik, transaksi terbaru | Sesuai |
| Edit produk | Form terisi, pratinjau gambar, switch aktif | Sesuai |

## 9.5 Error yang Ditemukan & Perbaikannya

| No | Temuan | Penyebab | Perbaikan |
|---|---|---|---|
| 1 | Uji "urutan harga termurah" gagal pada percobaan pertama | Asersi skrip uji membandingkan posisi teks "Jamu" yang juga muncul di menu kategori, bukan urutan kartu | Asersi diganti: membaca semua harga pada kartu produk dan memastikan urutannya naik |
| 2 | Uji "file gambar ikut terhapus" gagal pada percobaan pertama, padahal file benar-benar terhapus | *Stat cache* PHP pada proses skrip uji (`is_file` dipanggil sebelumnya pada path yang sama) | Menambahkan `clearstatcache()` sebelum pengecekan |
| 3 | Peringatan *deprecated* `imagedestroy()` di PHP 8.5 pada skrip uji | Fungsi sudah tidak berefek sejak PHP 8.0 | Diganti `unset($img)` |
| 4 | Harga di ringkasan checkout terpotong menjadi dua baris | Kolom sempit | Kelas `text-nowrap` pada subtotal (checkout & keranjang) |
| 5 | Badge kategori bertumpuk dengan badge stok pada kartu produk di layar ponsel | Lebar kartu 2 kolom terlalu kecil | Badge kategori disembunyikan di bawah 576 px |

Setelah perbaikan, seluruh pengujian dijalankan ulang dan **92/92 lulus** pada kedua versi PHP.

## 9.6 Yang Belum Diuji

- Aplikasi **belum diuji di server InfinityFree** karena belum di-deploy (membutuhkan login akun hosting milik pengguna). Daftar pengecekan pasca-deploy ada di [11-deployment-infinityfree.md](11-deployment-infinityfree.md#116-verifikasi-setelah-deploy).
- Aturan `.htaccess` tidak diuji secara otomatis karena PHP built-in server tidak membaca `.htaccess`; aturan tersebut berlaku di Apache (InfinityFree/XAMPP).

## 9.7 Output Lengkap Pengujian Otomatis (PHP 8.5.8)

```text

== 1. Koneksi database & struktur ==
  [PASS] Koneksi MySQLi ke database "pasarkita"
  [PASS] Tabel users tersedia
  [PASS] Tabel categories tersedia
  [PASS] Tabel products tersedia
  [PASS] Tabel orders tersedia
  [PASS] Tabel order_details tersedia
  [PASS] Foreign key terpasang (4 relasi)  (ditemukan 4)
  [PASS] Database menolak stok negatif  (error MySQL 1690)
  [PASS] Foreign key menolak kategori yang tidak ada  (error MySQL 1452)
  [PASS] Kategori yang masih dipakai produk tidak bisa dihapus (RESTRICT)

== 2. Halaman publik, daftar produk, pencarian, filter, detail ==
  [PASS] Homepage dapat dibuka (200)
  [PASS] Daftar produk tampil dengan jumlah benar  (18 produk aktif)
  [PASS] Pencarian "kopi" menemukan Kopi Gayo
  [PASS] Pencarian "kopi" tidak menampilkan produk lain (Batik)
  [PASS] Pencarian dengan input injeksi SQL aman (0 hasil, tanpa error)
  [PASS] Filter kategori Jamu & Herbal hanya menampilkan produk jamu
  [PASS] Urutan harga termurah berfungsi  (harga pertama 20000)
  [PASS] Detail produk tampil (nama, harga, stok)
  [PASS] Slug produk tidak dikenal menghasilkan 404
  [PASS] Output pencarian di-escape (anti XSS)

== 3. Proteksi halaman untuk tamu ==
  [PASS] Tamu diarahkan ke login saat membuka admin/index.php
  [PASS] Tamu diarahkan ke login saat membuka admin/products/index.php
  [PASS] Tamu diarahkan ke login saat membuka admin/orders/index.php
  [PASS] Tamu diarahkan ke login saat membuka checkout/index.php
  [PASS] Tamu diarahkan ke login saat membuka account/orders.php
  [PASS] POST tanpa token CSRF ditolak (419)
  [PASS] POST dengan token CSRF palsu ditolak (419)

== 4. Login admin & CRUD produk (termasuk upload gambar) ==
  [PASS] Login dengan password salah ditolak
  [PASS] Login admin berhasil & diarahkan ke dashboard
  [PASS] Session ID diregenerasi setelah login
  [PASS] Dashboard admin menampilkan statistik jumlah produk  (18 produk)
  [PASS] Dashboard admin menampilkan statistik jumlah pesanan  (2 pesanan)
  [PASS] Daftar transaksi admin dapat dibuka
  [PASS] Validasi menolak harga bukan angka & stok negatif
  [PASS] Produk invalid tidak tersimpan
  [PASS] Upload file PHP yang menyamar sebagai .jpg ditolak
  [PASS] Tambah produk A (dengan gambar) berhasil
  [PASS] Gambar tersimpan di uploads/products dengan nama acak
  [PASS] Tambah produk B (tanpa gambar, harga format "25.000") berhasil
  [PASS] Produk baru tampil di daftar produk admin
  [PASS] Edit stok produk B menjadi 4
  [PASS] Edit stok produk B kembali menjadi 3

== 5. Registrasi & login pembeli ==
  [PASS] Registrasi dengan password lemah ditolak
  [PASS] Registrasi dengan email yang sudah terdaftar ditolak
  [PASS] Registrasi pembeli berhasil
  [PASS] Password disimpan sebagai hash (password_hash) bukan teks asli
  [PASS] Logout (POST + CSRF) berhasil
  [PASS] Logout via GET tidak memproses apa pun (hanya redirect)
  [PASS] Login pembeli dengan password_verify berhasil

== 6. Akses pembeli ke halaman admin ==
  [PASS] Pembeli mendapat 403 di admin/index.php
  [PASS] Pembeli mendapat 403 di admin/products/create.php
  [PASS] Pembeli mendapat 403 di admin/orders/index.php
  [PASS] Pembeli tidak dapat menghapus produk
  [PASS] Admin tidak dapat checkout (403)

== 7. Keranjang belanja ==
  [PASS] Tambah produk A x2 ke keranjang
  [PASS] Keranjang menampilkan 2 produk
  [PASS] Field "price" kiriman browser diabaikan (subtotal A = Rp 20.000)
  [PASS] Subtotal & total dihitung benar (45.000 + ongkir 10.000 = 55.000)
  [PASS] Tambah melebihi stok ditolak
  [PASS] Ubah jumlah produk A menjadi 3
  [PASS] Ubah jumlah melebihi stok ditolak (tetap 3)
  [PASS] Jumlah negatif ditolak (tetap 3)
  [PASS] Hapus produk B dari keranjang

== 8. Checkout, orders, order_details, pengurangan stok ==
  [PASS] Validasi checkout menolak HP/alamat/metode bayar tidak valid
  [PASS] Checkout invalid tidak membuat pesanan
  [PASS] Checkout berhasil & diarahkan ke halaman sukses
  [PASS] Data tersimpan di tabel orders
  [PASS] Total dihitung server: subtotal 55.000 + ongkir 10.000 = 65.000
  [PASS] Data tersimpan di tabel order_details (2 baris)
  [PASS] Snapshot nama & harga produk tersimpan (A: 3 x 10.000)
  [PASS] Stok produk A berkurang 5 -> 2
  [PASS] Stok produk B berkurang 3 -> 2
  [PASS] Halaman sukses menampilkan kode pesanan
  [PASS] Keranjang dikosongkan setelah checkout
  [PASS] Tamu tidak dapat melihat halaman sukses pesanan orang lain
  [PASS] Pesanan muncul di "Pesanan Saya"

== 9. Histori harga (snapshot) setelah harga produk diubah ==
  [PASS] Harga produk A diubah menjadi 15.000
  [PASS] Harga di order_details tetap 10.000 (histori terjaga)

== 10. Validasi stok tidak mencukupi + rollback ==
  [PASS] Checkout ditolak karena stok tidak mencukupi
  [PASS] Tidak ada order baru tersimpan (rollback)
  [PASS] Stok produk A tidak ikut berkurang (rollback) tetap 2
  [PASS] Stok produk B tetap 1

== 11. Admin mengubah status & membatalkan pesanan ==
  [PASS] Status pesanan diubah menjadi "diproses"
  [PASS] Pesanan dibatalkan
  [PASS] Stok dikembalikan setelah pembatalan (A: 2 -> 5, B: 1 -> 2)
  [PASS] Pesanan yang sudah dibatalkan tidak bisa diubah lagi

== 12. Hapus produk & integritas histori ==
  [PASS] Hapus produk A berhasil
  [PASS] File gambar produk A ikut terhapus
  [PASS] Detail pesanan tetap ada, product_id menjadi NULL, nama & harga snapshot tetap
  [PASS] Pembeli tetap dapat melihat detail pesanan lama (produk dihapus)
  [PASS] Hapus produk B berhasil

== Pembersihan data uji ==
  [PASS] Data uji dihapus

==============================
HASIL: 92 / 92 pengujian LULUS
Waktu: 2026-11-16 10:54:41 | Target: http://127.0.0.1:8090/PasarKita | PHP 8.5.8 | MySQL 8.4.3
```
