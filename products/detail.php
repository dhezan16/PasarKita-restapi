<?php
/** Detail produk */
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$product = null;
if ($slug !== '' && preg_match('/^[a-z0-9\-]{1,170}$/', $slug)) {
    $product = db_fetch_one(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.is_active = 1',
        's',
        [$slug]
    );
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produk tidak ditemukan';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><i class="bi bi-box-seam display-1 text-muted"></i>'
        . '<h1 class="h3 mt-3">Produk tidak ditemukan</h1><p class="text-muted">Produk mungkin sudah dihapus atau tidak tersedia.</p>'
        . '<a class="btn btn-brand" href="' . e(url('products/index.php')) . '">Kembali ke daftar produk</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$related = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? AND p.is_active = 1 ORDER BY p.created_at DESC LIMIT 4',
    'ii',
    [(int) $product['category_id'], (int) $product['id']]
);

$stock = (int) $product['stock'];
$inCart = (int) (cart_raw()[(int) $product['id']] ?? 0);
$maxAdd = max(0, $stock - $inCart);

$pageTitle = $product['name'];
$activeNav = 'products';
$cardRedirect = 'products/detail.php?slug=' . $product['slug'];
require __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('products/index.php')) ?>">Produk</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('products/index.php?kategori=' . urlencode($product['category_slug']))) ?>"><?= e($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-6">
            <div class="detail-image">
                <img src="<?= e(product_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
            </div>
        </div>
        <div class="col-lg-6">
            <span class="badge bg-brand-soft text-brand rounded-pill px-3 py-2 mb-2"><?= e($product['category_name']) ?></span>
            <h1 class="h2 fw-bold mb-2"><?= e($product['name']) ?></h1>
            <div class="text-muted mb-3"><i class="bi bi-shop me-1"></i><?= e($product['seller_name'] ?: 'Penjual PasarKita') ?> &middot; <i class="bi bi-geo-alt me-1"></i><?= e($product['origin'] ?: '-') ?></div>

            <div class="d-flex align-items-baseline gap-2 mb-3">
                <span class="price display-6"><?= rupiah($product['price']) ?></span>
                <span class="text-muted">/ <?= e($product['unit']) ?></span>
            </div>

            <div class="mb-4">
                <?php if ($stock === 0): ?>
                    <span class="badge bg-secondary fs-6"><i class="bi bi-x-circle me-1"></i>Stok habis</span>
                <?php elseif ($stock <= 5): ?>
                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle me-1"></i>Stok tinggal <?= $stock ?> <?= e($product['unit']) ?></span>
                <?php else: ?>
                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Stok tersedia: <?= $stock ?> <?= e($product['unit']) ?></span>
                <?php endif; ?>
                <?php if ($inCart > 0): ?>
                    <span class="badge bg-light text-dark border fs-6 ms-1"><?= $inCart ?> di keranjang</span>
                <?php endif; ?>
            </div>

            <?php if (is_admin()): ?>
                <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Anda masuk sebagai admin. <a href="<?= e(url('admin/products/edit.php?id=' . (int) $product['id'])) ?>">Edit produk ini</a>.</div>
            <?php elseif ($maxAdd > 0): ?>
                <form action="<?= e(url('cart/add.php')) ?>" method="post" class="card-soft bg-white p-3 mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="redirect" value="cart/index.php">
                    <label for="qty" class="form-label fw-semibold small">Jumlah</label>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="input-group qty-input">
                            <button class="btn btn-outline-secondary" type="button" data-qty-step="-1" data-target="qty" aria-label="Kurangi">&minus;</button>
                            <input type="number" class="form-control text-center" id="qty" name="quantity" value="1" min="1" max="<?= $maxAdd ?>" required>
                            <button class="btn btn-outline-secondary" type="button" data-qty-step="1" data-target="qty" aria-label="Tambah">+</button>
                        </div>
                        <button type="submit" class="btn btn-brand flex-grow-1"><i class="bi bi-bag-plus me-1"></i>Tambah ke Keranjang</button>
                    </div>
                    <div class="form-text">Maksimal <?= $maxAdd ?> <?= e($product['unit']) ?> lagi dapat ditambahkan.</div>
                </form>
            <?php elseif ($stock > 0): ?>
                <div class="alert alert-warning">Seluruh stok produk ini sudah ada di keranjang Anda. <a href="<?= e(url('cart/index.php')) ?>">Lihat keranjang</a>.</div>
            <?php else: ?>
                <button class="btn btn-secondary w-100 mb-4" disabled>Stok habis</button>
            <?php endif; ?>

            <h2 class="h6 fw-bold">Deskripsi Produk</h2>
            <p class="text-muted" style="white-space:pre-line"><?= e($product['description']) ?></p>

            <dl class="row spec-list small mb-0">
                <dt class="col-4">Kategori</dt><dd class="col-8"><?= e($product['category_name']) ?></dd>
                <dt class="col-4">Asal daerah</dt><dd class="col-8"><?= e($product['origin'] ?: '-') ?></dd>
                <dt class="col-4">Penjual</dt><dd class="col-8"><?= e($product['seller_name'] ?: '-') ?></dd>
                <dt class="col-4">Satuan</dt><dd class="col-8"><?= e($product['unit']) ?></dd>
            </dl>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="mt-5">
            <h2 class="h4 section-title mb-3">Produk Serupa</h2>
            <div class="row g-3 g-lg-4">
                <?php foreach ($related as $p): ?>
                    <div class="col-6 col-md-3"><?php require __DIR__ . '/../includes/product_card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
