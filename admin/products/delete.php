<?php
/** Admin: hapus produk (POST + CSRF). Riwayat pesanan tetap ada karena FK ON DELETE SET NULL + snapshot. */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/product.php';
require_admin();

if (!is_post()) {
    redirect('admin/products/index.php');
}
verify_csrf();

$id = input_int($_POST['id'] ?? '', 0);
$product = $id > 0 ? db_fetch_one('SELECT id, name, image FROM products WHERE id = ?', 'i', [$id]) : null;
if (!$product) {
    flash('danger', 'Produk tidak ditemukan.');
    redirect('admin/products/index.php');
}

db_execute('DELETE FROM products WHERE id = ?', 'i', [$id]);
delete_product_image($product['image']);
flash('success', 'Produk "' . $product['name'] . '" berhasil dihapus.');
redirect('admin/products/index.php');
