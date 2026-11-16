<?php
/** Ubah jumlah produk di keranjang (POST + CSRF). */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    redirect('cart/index.php');
}
verify_csrf();

$productId = input_int($_POST['product_id'] ?? '', 0);
$rawQty = $_POST['quantity'] ?? '';
$qty = input_int($rawQty, -1);

if ($productId <= 0 || $qty < 0) {
    flash('danger', 'Jumlah produk harus berupa bilangan bulat 0 atau lebih.');
} else {
    $error = cart_update($productId, $qty);
    if ($error) {
        flash('danger', $error);
    } else {
        flash('success', $qty === 0 ? 'Produk dihapus dari keranjang.' : 'Jumlah produk diperbarui.');
    }
}
redirect('cart/index.php');
