<?php
/** Admin: tambah produk */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/product.php';
require_admin();

$data = ['name' => '', 'category_id' => 0, 'price' => '', 'stock' => '0', 'unit' => 'pcs', 'origin' => '', 'seller_name' => '', 'description' => '', 'is_active' => 1];
$errors = [];

if (is_post()) {
    verify_csrf();
    [$data, $errors] = validate_product_input($_POST);
    [$imageError, $hasImage] = validate_product_image($_FILES['image'] ?? null);
    if ($imageError) {
        $errors['image'] = $imageError;
    }

    if (!$errors) {
        $imagePath = $hasImage ? store_product_image($_FILES['image']) : null;
        try {
            db_execute(
                'INSERT INTO products (category_id, name, slug, description, price, stock, unit, origin, seller_name, image, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'isssdissssi',
                [
                    $data['category_id'], $data['name'], unique_product_slug($data['name']), $data['description'],
                    (float) $data['price'], $data['stock'], $data['unit'], $data['origin'] ?: null,
                    $data['seller_name'] ?: null, $imagePath, $data['is_active'],
                ]
            );
        } catch (Throwable $e) {
            delete_product_image($imagePath); // jangan tinggalkan file yatim
            throw $e;
        }
        flash('success', 'Produk "' . $data['name'] . '" berhasil ditambahkan.');
        redirect('admin/products/index.php');
    }
}

$pageTitle = 'Tambah Produk';
$adminNav = 'products';
$submitLabel = 'Simpan Produk';
$currentImage = null;
require __DIR__ . '/../../includes/admin_header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('admin/products/index.php')) ?>">Produk</a></li><li class="breadcrumb-item active">Tambah</li></ol></nav>
<h1 class="h3 fw-bold mb-4">Tambah Produk</h1>
<?php if ($errors): ?><div class="alert alert-danger">Terdapat <?= count($errors) ?> isian yang belum valid. Periksa kembali formulir.</div><?php endif; ?>
<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
