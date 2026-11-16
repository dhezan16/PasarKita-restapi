<?php
/** Admin: edit produk (termasuk stok, harga, dan gambar) */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/product.php';
require_admin();

$id = input_int($_GET['id'] ?? '', 0);
$product = $id > 0 ? db_fetch_one('SELECT * FROM products WHERE id = ?', 'i', [$id]) : null;
if (!$product) {
    flash('danger', 'Produk tidak ditemukan.');
    redirect('admin/products/index.php');
}

$data = $product;
$data['price'] = rtrim(rtrim((string) $product['price'], '0'), '.');
$errors = [];

if (is_post()) {
    verify_csrf();
    [$data, $errors] = validate_product_input($_POST);
    [$imageError, $hasImage] = validate_product_image($_FILES['image'] ?? null);
    if ($imageError) {
        $errors['image'] = $imageError;
    }

    if (!$errors) {
        $newImage = $hasImage ? store_product_image($_FILES['image']) : null;
        $slug = $data['name'] === $product['name'] ? $product['slug'] : unique_product_slug($data['name'], $id);
        try {
            db_execute(
                'UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, unit = ?,
                        origin = ?, seller_name = ?, image = ?, is_active = ?
                 WHERE id = ?',
                'isssdissssii',
                [
                    $data['category_id'], $data['name'], $slug, $data['description'], (float) $data['price'], $data['stock'],
                    $data['unit'], $data['origin'] ?: null, $data['seller_name'] ?: null,
                    $newImage ?? $product['image'], $data['is_active'], $id,
                ]
            );
        } catch (Throwable $e) {
            delete_product_image($newImage);
            throw $e;
        }
        if ($newImage) {
            delete_product_image($product['image']); // hapus gambar lama yang diunggah
        }
        flash('success', 'Produk "' . $data['name'] . '" berhasil diperbarui.');
        redirect('admin/products/index.php');
    }
}

$pageTitle = 'Edit Produk';
$adminNav = 'products';
$submitLabel = 'Simpan Perubahan';
$currentImage = $product['image'];
require __DIR__ . '/../../includes/admin_header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('admin/products/index.php')) ?>">Produk</a></li><li class="breadcrumb-item active">Edit</li></ol></nav>
<h1 class="h3 fw-bold mb-1">Edit Produk</h1>
<p class="text-muted mb-4">Perubahan harga tidak memengaruhi pesanan lama karena harga disimpan sebagai snapshot di detail pesanan.</p>
<?php if ($errors): ?><div class="alert alert-danger">Terdapat <?= count($errors) ?> isian yang belum valid. Periksa kembali formulir.</div><?php endif; ?>
<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
