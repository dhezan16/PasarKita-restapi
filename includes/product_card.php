<?php
/** Kartu produk. Membutuhkan variabel $p (baris produk + category_name). */
$detailUrl = url('products/detail.php?slug=' . urlencode($p['slug']));
$stock = (int) $p['stock'];
?>
<div class="product-card d-flex flex-column">
    <a href="<?= e($detailUrl) ?>" class="thumb d-block">
        <img src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <span class="badge badge-cat rounded-pill"><?= e($p['category_name']) ?></span>
        <?php if ($stock === 0): ?>
            <span class="badge badge-stock bg-secondary rounded-pill">Stok habis</span>
        <?php elseif ($stock <= 5): ?>
            <span class="badge badge-stock bg-warning text-dark rounded-pill">Sisa <?= $stock ?></span>
        <?php endif; ?>
    </a>
    <div class="p-3 d-flex flex-column flex-grow-1">
        <a href="<?= e($detailUrl) ?>" class="title mb-1"><?= e($p['name']) ?></a>
        <div class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i><?= e($p['origin'] ?: '-') ?></div>
        <div class="mt-auto d-flex align-items-end justify-content-between gap-2">
            <div>
                <div class="price fs-6"><?= rupiah($p['price']) ?></div>
                <div class="small text-muted">/ <?= e($p['unit']) ?></div>
            </div>
            <?php if (!is_admin()): ?>
            <form action="<?= e(url('cart/add.php')) ?>" method="post" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect" value="<?= e($cardRedirect ?? 'cart/index.php') ?>">
                <button type="submit" class="btn btn-brand btn-sm rounded-3" <?= $stock === 0 ? 'disabled' : '' ?> aria-label="Tambah <?= e($p['name']) ?> ke keranjang" title="Tambah ke keranjang">
                    <i class="bi bi-bag-plus"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
