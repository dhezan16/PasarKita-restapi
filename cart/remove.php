<?php
/** Hapus produk dari keranjang (POST + CSRF). */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    redirect('cart/index.php');
}
verify_csrf();

if (($_POST['action'] ?? '') === 'clear') {
    cart_clear();
    flash('success', 'Keranjang dikosongkan.');
} else {
    $productId = input_int($_POST['product_id'] ?? '', 0);
    if ($productId > 0 && isset(cart_raw()[$productId])) {
        cart_remove($productId);
        flash('success', 'Produk dihapus dari keranjang.');
    } else {
        flash('danger', 'Produk tidak ditemukan di keranjang.');
    }
}
redirect('cart/index.php');
