<?php
/** Homepage PasarKita */
require_once __DIR__ . '/includes/bootstrap.php';

$latestProducts = db_fetch_all(
    'SELECT p.*, c.name AS category_name FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.created_at DESC, p.id DESC LIMIT 8'
);
$stats = db_fetch_one(
    'SELECT (SELECT COUNT(*) FROM products WHERE is_active = 1) AS products,
            (SELECT COUNT(DISTINCT seller_name) FROM products WHERE is_active = 1) AS sellers,
            (SELECT COUNT(DISTINCT origin) FROM products WHERE is_active = 1) AS regions'
);

$pageTitle = 'Beranda';
$activeNav = 'home';
$cardRedirect = 'index.php';
require __DIR__ . '/includes/header.php';
?>

<section class="hero py-5 mb-5">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-white text-brand rounded-pill px-3 py-2 mb-3"><i class="bi bi-stars me-1"></i>Produk asli UMKM Nusantara</span>
                <h1 class="display-5 mb-3">Belanja produk tradisional langsung dari pengrajinnya.</h1>
                <p class="lead text-white-50 mb-4">Batik, tenun, kuliner khas, rempah, hingga jamu &mdash; dikurasi dari berbagai daerah di Indonesia dan dikirim ke rumah Anda.</p>
                <form class="hero-search mb-4" action="<?= e(url('products/index.php')) ?>" method="get" role="search">
                    <div class="input-group input-group-lg shadow rounded-4 overflow-hidden">
                        <input type="search" name="q" class="form-control" placeholder="Mau cari produk apa hari ini?" aria-label="Cari produk" maxlength="100">
                        <button class="btn btn-dark" type="submit"><i class="bi bi-search me-1"></i> Cari</button>
                    </div>
                </form>
                <div class="d-flex flex-wrap gap-3">
                    <div class="hero-stat"><div class="fs-4 fw-bold"><?= (int) $stats['products'] ?>+</div><div class="small text-white-50">Produk aktif</div></div>
                    <div class="hero-stat"><div class="fs-4 fw-bold"><?= (int) $stats['sellers'] ?></div><div class="small text-white-50">Penjual & UMKM</div></div>
                    <div class="hero-stat"><div class="fs-4 fw-bold"><?= (int) $stats['regions'] ?></div><div class="small text-white-50">Daerah asal</div></div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="row g-3">
                    <div class="col-7"><div class="hero-art"><img src="<?= e(asset('img/products/batik-kawung.svg')) ?>" class="img-fluid" alt="Batik tulis motif kawung"></div></div>
                    <div class="col-5 d-flex flex-column gap-3 pt-5">
                        <div class="hero-art" style="transform:rotate(-3deg)"><img src="<?= e(asset('img/products/kopi-gayo.svg')) ?>" class="img-fluid" alt="Kopi Gayo"></div>
                        <div class="hero-art" style="transform:rotate(2deg)"><img src="<?= e(asset('img/products/jamu-kunyit.svg')) ?>" class="img-fluid" alt="Jamu kunyit asam"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container mb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="section-title h3 mb-1">Jelajahi Kategori</h2>
            <p class="text-muted mb-0">Temukan produk sesuai kebutuhan Anda.</p>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach (all_categories() as $cat): ?>
            <div class="col-6 col-md-4 col-lg">
                <a href="<?= e(url('products/index.php?kategori=' . urlencode($cat['slug']))) ?>" class="category-card d-block p-3 h-100 text-center">
                    <span class="category-icon mb-2"><i class="bi <?= e($cat['icon']) ?>"></i></span>
                    <div class="fw-semibold"><?= e($cat['name']) ?></div>
                    <div class="small text-muted"><?= (int) $cat['product_count'] ?> produk</div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container mb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="section-title h3 mb-1">Produk Terbaru</h2>
            <p class="text-muted mb-0">Baru saja hadir dari para penjual kami.</p>
        </div>
        <a href="<?= e(url('products/index.php')) ?>" class="btn btn-outline-brand btn-sm">Lihat semua <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3 g-lg-4">
        <?php foreach ($latestProducts as $p): ?>
            <div class="col-6 col-md-4 col-lg-3"><?php require __DIR__ . '/includes/product_card.php'; ?></div>
        <?php endforeach; ?>
        <?php if (!$latestProducts): ?>
            <div class="col-12"><div class="alert alert-light">Belum ada produk.</div></div>
        <?php endif; ?>
    </div>
</section>

<section class="container mb-4">
    <div class="card-soft bg-white p-4 p-lg-5">
        <div class="row g-4">
            <div class="col-md-4 d-flex gap-3">
                <span class="feature-icon"><i class="bi bi-patch-check"></i></span>
                <div><h3 class="h6 fw-bold mb-1">Produk Asli Daerah</h3><p class="text-muted small mb-0">Setiap produk mencantumkan daerah asal dan nama penjualnya.</p></div>
            </div>
            <div class="col-md-4 d-flex gap-3">
                <span class="feature-icon"><i class="bi bi-shield-lock"></i></span>
                <div><h3 class="h6 fw-bold mb-1">Transaksi Aman</h3><p class="text-muted small mb-0">Harga dihitung di server dan stok divalidasi sebelum pesanan dibuat.</p></div>
            </div>
            <div class="col-md-4 d-flex gap-3">
                <span class="feature-icon"><i class="bi bi-people"></i></span>
                <div><h3 class="h6 fw-bold mb-1">Dukung UMKM</h3><p class="text-muted small mb-0">Belanja Anda membantu pengrajin dan petani lokal terus berkarya.</p></div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
