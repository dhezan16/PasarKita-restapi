<?php
/** Halaman keranjang belanja */
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    flash('warning', 'Akun admin tidak memiliki keranjang belanja.');
    redirect('admin/index.php');
}

$items = cart_items();
$totals = cart_totals($items);
$hasStockProblem = false;
foreach ($items as $it) {
    if (!$it['stock_ok']) {
        $hasStockProblem = true;
    }
}

$pageTitle = 'Keranjang Belanja';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 section-title mb-1">Keranjang Belanja</h1>
    <p class="text-muted mb-4"><?= $totals['units'] ?> barang di keranjang Anda</p>

    <?php if (!$items): ?>
        <div class="card-soft bg-white text-center p-5">
            <i class="bi bi-bag display-3 text-muted"></i>
            <h2 class="h5 mt-3">Keranjang masih kosong</h2>
            <p class="text-muted">Yuk, temukan produk tradisional favorit Anda.</p>
            <a href="<?= e(url('products/index.php')) ?>" class="btn btn-brand">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <?php if ($hasStockProblem): ?>
            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Beberapa produk melebihi stok yang tersedia. Kurangi jumlahnya sebelum checkout.</div>
        <?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-soft bg-white">
                    <?php foreach ($items as $i => $item): ?>
                        <div class="p-3 p-md-4 <?= $i > 0 ? 'border-top' : '' ?>">
                            <div class="d-flex gap-3">
                                <a href="<?= e(url('products/detail.php?slug=' . urlencode($item['slug']))) ?>">
                                    <img src="<?= e(product_image_url($item['image'])) ?>" class="cart-thumb" alt="<?= e($item['name']) ?>">
                                </a>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <a href="<?= e(url('products/detail.php?slug=' . urlencode($item['slug']))) ?>" class="fw-semibold text-decoration-none text-reset"><?= e($item['name']) ?></a>
                                            <div class="small text-muted"><?= e($item['category_name']) ?> &middot; <?= rupiah($item['price']) ?> / <?= e($item['unit']) ?></div>
                                            <div class="small <?= $item['stock_ok'] ? 'text-muted' : 'text-danger fw-semibold' ?>">Stok tersedia: <?= (int) $item['stock'] ?></div>
                                        </div>
                                        <form action="<?= e(url('cart/remove.php')) ?>" method="post" data-confirm="Hapus produk ini dari keranjang?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light text-danger" aria-label="Hapus <?= e($item['name']) ?>" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                        <form action="<?= e(url('cart/update.php')) ?>" method="post" class="d-flex gap-2 align-items-center">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                            <div class="input-group input-group-sm qty-input">
                                                <button class="btn btn-outline-secondary" type="button" data-qty-step="-1" data-target="qty-<?= (int) $item['id'] ?>" aria-label="Kurangi">&minus;</button>
                                                <input type="number" class="form-control text-center" id="qty-<?= (int) $item['id'] ?>" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= max(1, (int) $item['stock']) ?>" data-autosubmit aria-label="Jumlah">
                                                <button class="btn btn-outline-secondary" type="button" data-qty-step="1" data-target="qty-<?= (int) $item['id'] ?>" aria-label="Tambah">+</button>
                                            </div>
                                            <noscript><button class="btn btn-sm btn-outline-brand" type="submit">Ubah</button></noscript>
                                        </form>
                                        <div class="fw-bold text-nowrap"><?= rupiah($item['subtotal']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= e(url('products/index.php')) ?>" class="btn btn-link text-decoration-none"><i class="bi bi-arrow-left"></i> Lanjut belanja</a>
                    <form action="<?= e(url('cart/remove.php')) ?>" method="post" data-confirm="Kosongkan seluruh keranjang?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="clear">
                        <button class="btn btn-link text-danger text-decoration-none" type="submit"><i class="bi bi-x-circle"></i> Kosongkan keranjang</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-soft bg-white p-4 summary-card">
                    <h2 class="h5 fw-bold mb-3">Ringkasan Belanja</h2>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal (<?= $totals['units'] ?> barang)</span><span><?= rupiah($totals['subtotal']) ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Ongkos kirim</span><span><?= rupiah($totals['shipping']) ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fs-5"><span class="fw-bold">Total</span><span class="price"><?= rupiah($totals['total']) ?></span></div>
                    <?php if ($hasStockProblem): ?>
                        <button class="btn btn-secondary w-100 py-2" disabled>Perbaiki jumlah produk</button>
                    <?php else: ?>
                        <a href="<?= e(url('checkout/index.php')) ?>" class="btn btn-brand w-100 py-2">Lanjut ke Checkout <i class="bi bi-arrow-right"></i></a>
                    <?php endif; ?>
                    <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check me-1"></i>Harga & stok diverifikasi ulang oleh server saat checkout.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
