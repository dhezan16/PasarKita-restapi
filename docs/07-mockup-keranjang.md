# 7. Mockup UI — Halaman Keranjang

File implementasi: `cart/index.php` (aksi: `cart/add.php`, `cart/update.php`, `cart/remove.php`)

## 7.1 Desktop

```
+----------------------------------------------------------------------------------+
| NAVBAR                                                            (bag 3)        |
+----------------------------------------------------------------------------------+
| KERANJANG BELANJA                                                                |
| 3 barang di keranjang Anda                                                       |
|                                                                                  |
| +--------------------------------------------------+  +------------------------+ |
| | [img] Batik Cap Mega Mendung               [del] |  | Ringkasan Belanja      | |
| |       Batik & Tenun · Rp 185.000 / lembar        |  |                        | |
| |       Stok tersedia: 20                          |  | Subtotal (3 barang)    | |
| |       [-][ 1 ][+]                   Rp 185.000   |  |            Rp 255.000  | |
| |--------------------------------------------------|  | Ongkos kirim Rp 10.000 | |
| | [img] Dodol Garut Original 500 g           [del] |  | ---------------------- | |
| |       Makanan Tradisional · Rp 35.000 / kotak    |  | Total     Rp 265.000   | |
| |       Stok tersedia: 57                          |  |                        | |
| |       [-][ 2 ][+]                    Rp 70.000   |  | [Lanjut ke Checkout ->]| |
| +--------------------------------------------------+  | (shield) Harga & stok  | |
| <- Lanjut belanja                (x) Kosongkan       |  | diverifikasi ulang ... | |
|                                                       +------------------------+ |
+----------------------------------------------------------------------------------+
```

## 7.2 Mobile

```
+------------------------------+
| NAVBAR              (bag 3)  |
| KERANJANG BELANJA            |
| +--------------------------+ |
| |[img] Batik Cap Mega [del]| |
| |      Rp 185.000 / lembar | |
| |[-][1][+]      Rp 185.000 | |
| +--------------------------+ |
| <- Lanjut   (x) Kosongkan    |
| +--------------------------+ |
| | Ringkasan Belanja        | |
| | Subtotal      Rp 255.000 | |
| | Ongkir         Rp 10.000 | |
| | Total         Rp 265.000 | |
| | [ Lanjut ke Checkout ]   | |
| +--------------------------+ |
+------------------------------+
```

## 7.3 Elemen & Perilaku

| Elemen | Perilaku |
|---|---|
| Input jumlah [-][n][+] | Mengirim form POST ke `cart/update.php` secara otomatis (JS); tanpa JS tersedia tombol "Ubah". Jumlah 0 = hapus, melebihi stok = ditolak |
| Tombol hapus (ikon tempat sampah) | POST ke `cart/remove.php` dengan konfirmasi |
| Kosongkan keranjang | POST `action=clear` ke `cart/remove.php` |
| Subtotal per item & total | Dihitung server dari harga di database (`cart_items()` + `cart_totals()`) |
| Peringatan stok | Bila jumlah di keranjang > stok terbaru: teks merah + tombol checkout dinonaktifkan |
| Keranjang kosong | Ilustrasi + tombol "Mulai Belanja" |
| Akun admin | Diarahkan ke dashboard (admin tidak memiliki keranjang) |
