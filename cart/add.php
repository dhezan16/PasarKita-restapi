<?php
/** Tambah produk ke keranjang (POST + CSRF). Hanya product_id & jumlah yang diterima dari browser. */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    redirect('cart/index.php');
}
verify_csrf();

if (is_admin()) {
    flash('warning', 'Akun admin tidak dapat berbelanja. Silakan gunakan akun pembeli.');
    redirect('admin/index.php');
}

$productId = input_int($_POST['product_id'] ?? '', 0);
$qty = input_int($_POST['quantity'] ?? '1', 0);
$back = safe_redirect_path($_POST['redirect'] ?? '', 'cart/index.php');

$error = $productId > 0 ? cart_add($productId, $qty) : 'Produk tidak valid.';
if ($error) {
    flash('danger', $error);
} else {
    $name = db_fetch_value('SELECT name FROM products WHERE id = ?', 'i', [$productId]);
    flash('success', '"' . $name . '" berhasil ditambahkan ke keranjang.');
}
redirect($back);
