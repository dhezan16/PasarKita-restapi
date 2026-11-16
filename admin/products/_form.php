<?php
/**
 * Form produk bersama untuk create & edit.
 * Variabel: $data (nilai field), $errors, $submitLabel, $currentImage (opsional)
 */
if (!defined('APP_ROOT')) {
    http_response_code(404);
    exit;
}
$err = function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
};
$inv = function (string $key) use ($errors): string {
    return isset($errors[$key]) ? ' is-invalid' : '';
};
?>
<form method="post" enctype="multipart/form-data" novalidate class="row g-4">
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= PRODUCT_MAX_IMAGE_BYTES ?>">
    <div class="col-lg-8">
        <div class="stat-card p-4">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Nama produk <span class="text-danger">*</span></label>
                <input type="text" class="form-control<?= $inv('name') ?>" id="name" name="name" value="<?= e($data['name']) ?>" required maxlength="150">
                <?= $err('name') ?>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                    <select class="form-select<?= $inv('category_id') ?>" id="category_id" name="category_id" required>
                        <option value="">-- Pilih kategori --</option>
                        <?php foreach (all_categories() as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $data['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('category_id') ?>
                </div>
                <div class="col-md-6">
                    <label for="unit" class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control<?= $inv('unit') ?>" id="unit" name="unit" value="<?= e($data['unit']) ?>" required maxlength="30" list="unit-list" placeholder="pcs, kg, lembar, botol">
                    <datalist id="unit-list"><option value="pcs"><option value="kg"><option value="lembar"><option value="botol"><option value="bungkus"><option value="kotak"><option value="buah"><option value="pack"></datalist>
                    <?= $err('unit') ?>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="price" class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="decimal" class="form-control<?= $inv('price') ?>" id="price" name="price" value="<?= e($data['price']) ?>" required placeholder="35000">
                    </div>
                    <?= $err('price') ?>
                </div>
                <div class="col-md-6">
                    <label for="stock" class="form-label fw-semibold">Stok <span class="text-danger">*</span></label>
                    <input type="number" class="form-control<?= $inv('stock') ?>" id="stock" name="stock" value="<?= e($data['stock']) ?>" required min="0" max="100000" step="1">
                    <?= $err('stock') ?>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="origin" class="form-label fw-semibold">Asal daerah</label>
                    <input type="text" class="form-control<?= $inv('origin') ?>" id="origin" name="origin" value="<?= e($data['origin']) ?>" maxlength="100" placeholder="mis. Yogyakarta">
                    <?= $err('origin') ?>
                </div>
                <div class="col-md-6">
                    <label for="seller_name" class="form-label fw-semibold">Nama penjual / UMKM</label>
                    <input type="text" class="form-control<?= $inv('seller_name') ?>" id="seller_name" name="seller_name" value="<?= e($data['seller_name']) ?>" maxlength="100">
                    <?= $err('seller_name') ?>
                </div>
            </div>
            <div>
                <label for="description" class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
                <textarea class="form-control<?= $inv('description') ?>" id="description" name="description" rows="6" required maxlength="5000"><?= e($data['description']) ?></textarea>
                <?= $err('description') ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="stat-card p-4 mb-4">
            <label for="image" class="form-label fw-semibold">Gambar produk</label>
            <img id="image-preview" src="<?= e(product_image_url($currentImage ?? null)) ?>" class="img-fluid rounded-3 mb-3 border" alt="Pratinjau gambar" style="aspect-ratio:4/3;object-fit:cover;width:100%">
            <input type="file" class="form-control<?= $inv('image') ?>" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <?= $err('image') ?>
            <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.<?= !empty($currentImage) ? ' Kosongkan jika tidak ingin mengganti gambar.' : '' ?></div>
        </div>
        <div class="stat-card p-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= (int) $data['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Tampilkan di toko (aktif)</label>
            </div>
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-save me-1"></i><?= e($submitLabel) ?></button>
            <a href="<?= e(url('admin/products/index.php')) ?>" class="btn btn-light w-100 mt-2">Batal</a>
        </div>
    </div>
</form>
