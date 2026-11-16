<?php
/**
 * Query & pemetaan baris database menjadi struktur JSON API.
 * Nama field JSON memakai Bahasa Indonesia; nama kolom database tetap seperti SOAL 11.
 */

if (!defined('PASARKITA_API')) {
    http_response_code(404);
    exit;
}

/** SELECT produk + kategori (dipakai daftar & detail). */
const API_PRODUCT_SELECT = 'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.unit, p.origin,
        p.seller_name, p.image, p.created_at, p.updated_at,
        c.id AS category_id, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON c.id = p.category_id';

function api_format_product(array $row, bool $detail = false): array
{
    $stock = (int) $row['stock'];
    $product = [
        'id'          => (int) $row['id'],
        'nama'        => $row['name'],
        'slug'        => $row['slug'],
        'harga'       => api_money($row['price']),
        'stok'        => $stock,
        'satuan'      => $row['unit'],
        'tersedia'    => $stock > 0,
        'asal_daerah' => $row['origin'],
        'penjual'     => $row['seller_name'],
        'gambar_url'  => api_absolute_url(product_image_url($row['image'])),
        'kategori'    => [
            'id'   => (int) $row['category_id'],
            'nama' => $row['category_name'],
            'slug' => $row['category_slug'],
        ],
    ];
    if ($detail) {
        $product['deskripsi'] = $row['description'];
        $product['url_halaman'] = api_absolute_url(url('products/detail.php?slug=' . rawurlencode($row['slug'])));
        $product['dibuat_pada'] = $row['created_at'];
        $product['diperbarui_pada'] = $row['updated_at'];
    }
    return $product;
}

function api_format_category(array $row): array
{
    return [
        'id'            => (int) $row['id'],
        'nama'          => $row['name'],
        'slug'          => $row['slug'],
        'deskripsi'     => $row['description'],
        'ikon'          => $row['icon'],
        'jumlah_produk' => (int) $row['product_count'],
    ];
}

/** Ambil pesanan lengkap beserta detailnya, atau null. */
function api_fetch_order(int $orderId): ?array
{
    $order = db_fetch_one(
        'SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?',
        'i',
        [$orderId]
    );
    if (!$order) {
        return null;
    }
    $details = db_fetch_all(
        'SELECT product_id, product_name, price, quantity, subtotal FROM order_details WHERE order_id = ? ORDER BY id',
        'i',
        [$orderId]
    );
    return api_format_order($order, $details);
}

function api_format_order(array $o, ?array $details = null): array
{
    $order = [
        'id'                => (int) $o['id'],
        'kode_pesanan'      => $o['order_code'],
        'status'            => $o['status'],
        'pembeli'           => ['id' => (int) $o['user_id'], 'nama' => $o['user_name'] ?? null, 'email' => $o['user_email'] ?? null],
        'nama_penerima'     => $o['recipient_name'],
        'telepon'           => $o['phone'],
        'alamat_pengiriman' => $o['shipping_address'],
        'catatan'           => $o['notes'],
        'metode_pembayaran' => $o['payment_method'],
        'total_item'        => (int) $o['total_items'],
        'subtotal'          => api_money($o['subtotal']),
        'ongkos_kirim'      => api_money($o['shipping_cost']),
        'total'             => api_money($o['total_amount']),
        'dibuat_pada'       => $o['created_at'],
    ];
    if ($details !== null) {
        $order['items'] = array_map(function (array $d): array {
            return [
                'produk_id'   => $d['product_id'] !== null ? (int) $d['product_id'] : null,
                'nama_produk' => $d['product_name'],
                'harga'       => api_money($d['price']),
                'jumlah'      => (int) $d['quantity'],
                'subtotal'    => api_money($d['subtotal']),
            ];
        }, $details);
    }
    return $order;
}
