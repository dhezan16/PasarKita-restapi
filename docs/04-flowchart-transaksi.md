# 4. Flowchart Proses Transaksi

Flowchart ini menggambarkan alur aktual pada `cart/*.php`, `checkout/index.php`, dan fungsi `create_order()` di `includes/order.php`.

## 4.1 Alur Belanja hingga Checkout

```mermaid
flowchart TD
    A([Mulai]) --> B[Pengguna membuka daftar / detail produk]
    B --> C{Klik Tambah ke Keranjang}
    C --> D{Token CSRF valid?}
    D -- Tidak --> D1[Tolak request HTTP 419] --> B
    D -- Ya --> E{"Produk aktif dan jumlah + isi keranjang ≤ stok?"}
    E -- Tidak --> E1[Flash: stok tidak mencukupi] --> B
    E -- Ya --> F["Simpan ke Session: cart[product_id] = qty<br/>(tanpa harga)"]
    F --> G[Halaman Keranjang:<br/>harga & stok dibaca ulang dari DB,<br/>hitung subtotal, ongkir, total]
    G --> H{Aksi pengguna}
    H -- Ubah jumlah --> H1{"1 ≤ qty ≤ stok? (0 = hapus)"}
    H1 -- Tidak --> H2[Flash error, jumlah tidak berubah] --> G
    H1 -- Ya --> H3[Perbarui session] --> G
    H -- Hapus item --> H4[unset cart product_id] --> G
    H -- Lanjut belanja --> B
    H -- Checkout --> I{Sudah login?}
    I -- Belum --> I1[Redirect ke login<br/>lalu kembali ke checkout] --> I
    I -- Ya --> J{Role pembeli?}
    J -- Bukan / admin --> J1[HTTP 403 Akses Ditolak] --> Z([Selesai])
    J -- Ya --> K[Isi form: penerima, HP, alamat,<br/>metode bayar, catatan]
    K --> L{Validasi server-side OK?}
    L -- Tidak --> L1[Tampilkan pesan kesalahan] --> K
    L -- Ya --> M[[create_order — lihat 4.2]]
    M --> N{Berhasil?}
    N -- Tidak --> N1[Tampilkan daftar error stok,<br/>tidak ada data tersimpan] --> G
    N -- Ya --> O[Kosongkan keranjang]
    O --> P[Halaman sukses: kode pesanan & ringkasan]
    P --> Z
```

## 4.2 Detail `create_order()` — Database Transaction

```mermaid
flowchart TD
    S([create_order]) --> T[BEGIN TRANSACTION]
    T --> U[Urutkan product_id dalam keranjang]
    U --> V[Ambil item berikutnya]
    V --> W["SELECT id, name, price, stock, is_active<br/>FROM products WHERE id = ? FOR UPDATE"]
    W --> X{"Produk ada dan aktif?"}
    X -- Tidak --> X1[Catat error]
    X -- Ya --> Y{"stock ≥ qty?"}
    Y -- Tidak --> X1
    Y -- Ya --> Y1["Hitung subtotal = harga DB x qty"]
    X1 --> AA{Masih ada item?}
    Y1 --> AA
    AA -- Ya --> V
    AA -- Tidak --> AB{Ada error?}
    AB -- Ya --> RB[ROLLBACK] --> FAIL([Lempar OrderException])
    AB -- Tidak --> AC["INSERT INTO orders<br/>(total = subtotal + ongkir dari server)"]
    AC --> AD["INSERT INTO order_details<br/>(snapshot product_name & price)"]
    AD --> AE["UPDATE products SET stock = stock - qty<br/>WHERE id = ? AND stock >= qty"]
    AE --> AF{affected_rows = 1?}
    AF -- Tidak --> RB
    AF -- Ya --> AG{Item berikutnya?}
    AG -- Ya --> AD
    AG -- Tidak --> CM[COMMIT]
    CM --> OK([Kembalikan id & order_code])
```

## 4.3 Alur Status Pesanan (Admin)

```mermaid
stateDiagram-v2
    [*] --> pending : checkout berhasil
    pending --> diproses
    pending --> dikirim
    diproses --> dikirim
    dikirim --> selesai
    pending --> dibatalkan : stok dikembalikan
    diproses --> dibatalkan : stok dikembalikan
    dikirim --> dibatalkan : stok dikembalikan
    selesai --> [*]
    dibatalkan --> [*]
```

Diagram di atas menunjukkan alur normal. Secara teknis admin dapat memilih status apa pun di antara `pending`, `diproses`, `dikirim`, `selesai`, `dibatalkan` selama status saat ini **belum final**; setelah `selesai` atau `dibatalkan`, status tidak dapat diubah lagi.

Perubahan ke `dibatalkan` juga dijalankan dalam satu transaksi (`update_order_status()`): status diperbarui dan stok setiap produk yang masih ada dikembalikan sekaligus.
