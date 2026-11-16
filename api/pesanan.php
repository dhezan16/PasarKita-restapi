<?php
/**
 * POST /api/pesanan.php          -> membuat pesanan (wajib login sebagai pembeli)
 * GET  /api/pesanan.php          -> daftar pesanan milik pengguna yang login (admin: semua)
 * GET  /api/pesanan.php?id={id}  -> detail pesanan (hanya pemilik atau admin)
 *
 * Body POST (JSON):
 * {
 *   "produk": [ {"produk_id": 1, "jumlah": 2}, {"produk_id": 5, "jumlah": 1} ],
 *   "nama_penerima": "Siti Pembeli",          (opsional, default: nama akun)
 *   "telepon": "081234567890",                (opsional, default: telepon akun)
 *   "alamat_pengiriman": "Jl. ...",           (opsional, default: alamat akun)
 *   "catatan": "...",                         (opsional)
 *   "metode_pembayaran": "transfer" | "cod"   (opsional, default: transfer)
 * }
 *
 * Harga, subtotal, ongkos kirim dan total TIDAK diterima dari client: semuanya
 * dihitung server dari database di dalam create_order() (fungsi yang sama dengan
 * checkout web SOAL 11) yang memakai transaction + SELECT ... FOR UPDATE + rollback.
 */
require_once __DIR__ . '/helpers/bootstrap.php';

$method = api_allow_methods(['GET', 'POST']);
$user = api_require_user();

/* ---------------- GET: lihat pesanan ---------------- */
if ($method === 'GET') {
    $isAdmin = $user['role'] === ROLE_ADMIN;
    if (array_key_exists('id', $_GET)) {
        $id = api_positive_int($_GET['id']);
        if ($id === null) {
            api_error(400, 'Parameter id harus berupa bilangan bulat positif.');
        }
        $order = api_fetch_order($id);
        // Pesanan milik orang lain diperlakukan sama dengan tidak ada (tidak membocorkan keberadaannya)
        if (!$order || (!$isAdmin && $order['pembeli']['id'] !== (int) $user['id'])) {
            api_error(404, 'Pesanan tidak ditemukan');
        }
        api_success($order, 'Detail pesanan berhasil diambil');
    }

    $sql = 'SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id = o.user_id';
    $rows = $isAdmin
        ? db_fetch_all($sql . ' ORDER BY o.created_at DESC, o.id DESC LIMIT 50')
        : db_fetch_all($sql . ' WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC LIMIT 50', 'i', [(int) $user['id']]);
    api_success(
        array_map(function (array $o): array { return api_format_order($o); }, $rows),
        $rows ? 'Data pesanan berhasil diambil' : 'Belum ada pesanan',
        200,
        ['meta' => ['total' => count($rows), 'catatan' => 'Maksimal 50 pesanan terbaru.']]
    );
}

/* ---------------- POST: buat pesanan ---------------- */
if ($user['role'] !== ROLE_BUYER) {
    // Sama dengan checkout web: admin tidak dapat berbelanja
    api_error(403, 'Hanya akun pembeli yang dapat membuat pesanan.');
}

$body = api_json_body();
$errors = [];

// 1. Daftar produk -> [product_id => jumlah] (format yang sama dengan keranjang session)
$cart = [];
$items = $body['produk'] ?? null;
if (!is_array($items) || $items === [] || array_keys($items) !== range(0, count($items) - 1)) {
    $errors['produk'] = 'Field produk wajib berupa array berisi minimal 1 item, contoh: [{"produk_id": 1, "jumlah": 2}].';
} elseif (count($items) > 50) {
    $errors['produk'] = 'Maksimal 50 item dalam satu pesanan.';
} else {
    foreach ($items as $i => $item) {
        if (!is_array($item)) {
            $errors["produk.$i"] = 'Setiap item harus berupa objek {"produk_id": ..., "jumlah": ...}.';
            continue;
        }
        $pid = api_positive_int($item['produk_id'] ?? null);
        $qty = api_positive_int($item['jumlah'] ?? null, 1000);
        if ($pid === null) {
            $errors["produk.$i.produk_id"] = 'produk_id wajib berupa bilangan bulat positif.';
        }
        if ($qty === null) {
            $errors["produk.$i.jumlah"] = 'jumlah wajib berupa bilangan bulat 1-1000.';
        }
        if ($pid !== null && $qty !== null) {
            $cart[$pid] = ($cart[$pid] ?? 0) + $qty; // produk sama digabung
        }
    }
}

// 2. Data pengiriman: aturan validasi sama dengan checkout web
$field = function (string $key, string $default) use ($body, &$errors): string {
    if (!array_key_exists($key, $body) || $body[$key] === null) {
        return $default;
    }
    if (!is_string($body[$key])) {
        $errors[$key] = 'Field ' . $key . ' harus berupa teks.';
        return '';
    }
    return trim($body[$key]);
};
$shipping = [
    'recipient_name'   => $field('nama_penerima', (string) $user['name']),
    'phone'            => $field('telepon', (string) $user['phone']),
    'shipping_address' => $field('alamat_pengiriman', (string) $user['address']),
    'notes'            => $field('catatan', ''),
    'payment_method'   => $field('metode_pembayaran', 'transfer'),
];

if (!isset($errors['nama_penerima']) && (mb_strlen($shipping['recipient_name']) < 3 || mb_strlen($shipping['recipient_name']) > 100)) {
    $errors['nama_penerima'] = 'Nama penerima wajib diisi (3-100 karakter).';
}
if (!isset($errors['telepon']) && !preg_match('/^(\+62|62|0)[0-9]{8,13}$/', $shipping['phone'])) {
    $errors['telepon'] = 'Nomor telepon tidak valid. Contoh: 081234567890.';
}
if (!isset($errors['alamat_pengiriman']) && (mb_strlen($shipping['shipping_address']) < 10 || mb_strlen($shipping['shipping_address']) > 500)) {
    $errors['alamat_pengiriman'] = 'Alamat pengiriman wajib diisi (10-500 karakter).';
}
if (!isset($errors['catatan']) && mb_strlen($shipping['notes']) > 255) {
    $errors['catatan'] = 'Catatan maksimal 255 karakter.';
}
if (!isset($errors['metode_pembayaran']) && !in_array($shipping['payment_method'], ['transfer', 'cod'], true)) {
    $errors['metode_pembayaran'] = 'Metode pembayaran harus "transfer" atau "cod".';
}

if ($errors) {
    api_error(422, 'Data pesanan tidak valid.', $errors);
}

// 3. Simpan dalam satu database transaction (commit / rollback di create_order)
try {
    $created = create_order((int) $user['id'], $cart, $shipping);
} catch (OrderException $ex) {
    // Stok kurang / produk tidak tersedia: transaksi sudah di-ROLLBACK, tidak ada data tersimpan
    api_error(422, 'Pesanan gagal dibuat. Tidak ada data yang disimpan.', $ex->errors);
}

header('Location: ' . api_absolute_url(url('api/pesanan.php?id=' . $created['id'])));
api_success(api_fetch_order($created['id']), 'Pesanan berhasil dibuat', 201);
