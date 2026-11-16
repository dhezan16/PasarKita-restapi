<?php
/**
 * Keranjang belanja berbasis PHP Session.
 *
 * Session HANYA menyimpan pasangan [product_id => quantity].
 * Harga, nama, dan stok SELALU diambil ulang dari database, sehingga
 * harga tidak bisa dimanipulasi dari browser.
 */

const CART_MAX_QTY_PER_ITEM = 999;

function cart_raw(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/** Jumlah total unit di keranjang (untuk badge navbar). */
function cart_count(): int
{
    return (int) array_sum(cart_raw());
}

function cart_find_product(int $productId): ?array
{
    return db_fetch_one('SELECT id, name, price, stock, is_active FROM products WHERE id = ?', 'i', [$productId]);
}

/**
 * Tambah produk ke keranjang.
 * @return string|null pesan error, atau null jika berhasil
 */
function cart_add(int $productId, int $qty): ?string
{
    if ($qty < 1 || $qty > CART_MAX_QTY_PER_ITEM) {
        return 'Jumlah produk tidak valid.';
    }
    $product = cart_find_product($productId);
    if (!$product || !(int) $product['is_active']) {
        return 'Produk tidak ditemukan atau tidak tersedia.';
    }
    $cart = cart_raw();
    $newQty = ($cart[$productId] ?? 0) + $qty;
    if ($newQty > (int) $product['stock']) {
        return 'Stok "' . $product['name'] . '" tidak mencukupi. Stok tersedia: ' . (int) $product['stock'] . '.';
    }
    $_SESSION['cart'][$productId] = $newQty;
    return null;
}

/** Ubah jumlah produk. Jumlah 0 = hapus. */
function cart_update(int $productId, int $qty): ?string
{
    $cart = cart_raw();
    if (!isset($cart[$productId])) {
        return 'Produk tidak ada di keranjang.';
    }
    if ($qty <= 0) {
        cart_remove($productId);
        return null;
    }
    if ($qty > CART_MAX_QTY_PER_ITEM) {
        return 'Jumlah produk tidak valid.';
    }
    $product = cart_find_product($productId);
    if (!$product || !(int) $product['is_active']) {
        cart_remove($productId);
        return 'Produk sudah tidak tersedia dan dihapus dari keranjang.';
    }
    if ($qty > (int) $product['stock']) {
        return 'Stok "' . $product['name'] . '" tidak mencukupi. Stok tersedia: ' . (int) $product['stock'] . '.';
    }
    $_SESSION['cart'][$productId] = $qty;
    return null;
}

function cart_remove(int $productId): void
{
    cart_raw();
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Item keranjang lengkap dengan data terbaru dari database.
 * Item yang produknya sudah dihapus/nonaktif otomatis dibuang.
 */
function cart_items(): array
{
    $cart = cart_raw();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_fetch_all(
        "SELECT p.id, p.name, p.slug, p.price, p.stock, p.unit, p.image, p.is_active, c.name AS category_name
         FROM products p JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($placeholders)",
        str_repeat('i', count($ids)),
        $ids
    );

    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = $row;
    }

    $items = [];
    foreach ($cart as $pid => $qty) {
        $pid = (int) $pid;
        if (!isset($byId[$pid]) || !(int) $byId[$pid]['is_active']) {
            cart_remove($pid);
            continue;
        }
        $p = $byId[$pid];
        $p['quantity'] = (int) $qty;
        $p['subtotal'] = (float) $p['price'] * (int) $qty;
        $p['stock_ok'] = (int) $qty <= (int) $p['stock'];
        $items[] = $p;
    }
    return $items;
}

/** Hitung subtotal, ongkir, total, dan jumlah unit. */
function cart_totals(array $items): array
{
    $subtotal = 0.0;
    $units = 0;
    foreach ($items as $item) {
        $subtotal += $item['subtotal'];
        $units += $item['quantity'];
    }
    $shipping = $units > 0 ? (float) config('app.shipping_cost', 10000) : 0.0;
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total'    => $subtotal + $shipping,
        'units'    => $units,
    ];
}
