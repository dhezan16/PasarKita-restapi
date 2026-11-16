<?php
/** Daftar produk + pencarian + filter kategori + urutan + pagination */
require_once __DIR__ . '/../includes/bootstrap.php';

$perPage = 12;
$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100);
$kategori = (string) ($_GET['kategori'] ?? '');
$sort = (string) ($_GET['urut'] ?? 'terbaru');
$page = max(1, input_int($_GET['page'] ?? '1', 1));

$sortOptions = [
    'terbaru' => ['Terbaru', 'p.created_at DESC, p.id DESC'],
    'termurah' => ['Harga terendah', 'p.price ASC, p.id ASC'],
    'termahal' => ['Harga tertinggi', 'p.price DESC, p.id DESC'],
    'nama' => ['Nama A-Z', 'p.name ASC'],
];
if (!isset($sortOptions[$sort])) {
    $sort = 'terbaru';
}

// Bangun WHERE secara aman: hanya nilai yang dibind, struktur SQL tetap.
$where = ['p.is_active = 1'];
$types = '';
$params = [];
$activeCategory = null;

if ($kategori !== '') {
    $activeCategory = db_fetch_one('SELECT id, name, slug, description FROM categories WHERE slug = ?', 's', [$kategori]);
    if ($activeCategory) {
        $where[] = 'p.category_id = ?';
        $types .= 'i';
        $params[] = (int) $activeCategory['id'];
    }
}
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.origin LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

$total = (int) db_fetch_value("SELECT COUNT(*) FROM products p WHERE $whereSql", $types, $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$products = db_fetch_all(
    "SELECT p.*, c.name AS category_name FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE $whereSql ORDER BY {$sortOptions[$sort][1]} LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, $offset])
);

$keepParams = array_filter(['q' => $q, 'kategori' => $activeCategory['slug'] ?? '', 'urut' => $sort !== 'terbaru' ? $sort : '']);
$cardRedirect = 'products/index.php' . ($keepParams || $page > 1 ? '?' . http_build_query($keepParams + ($page > 1 ? ['page' => $page] : [])) : '');

$pageTitle = $activeCategory ? $activeCategory['name'] : ($q !== '' ? 'Pencarian: ' . $q : 'Semua Produk');
$activeNav = 'products';
require __DIR__ . '/../includes/header.php';
?>

<div class="bg-white border-bottom mb-4">
    <div class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
                <li class="breadcrumb-item <?= $activeCategory ? '' : 'active' ?>"><?php if ($activeCategory): ?><a href="<?= e(url('products/index.php')) ?>">Produk</a><?php else: ?>Produk<?php endif; ?></li>
                <?php if ($activeCategory): ?><li class="breadcrumb-item active"><?= e($activeCategory['name']) ?></li><?php endif; ?>
            </ol>
        </nav>
        <h1 class="h3 section-title mb-1"><?= e($activeCategory['name'] ?? 'Semua Produk') ?></h1>
        <p class="text-muted mb-0"><?= e($activeCategory['description'] ?? 'Produk tradisional pilihan dari berbagai daerah di Indonesia.') ?></p>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Sidebar filter -->
        <aside class="col-lg-3">
            <form method="get" action="<?= e(url('products/index.php')) ?>" class="card-soft bg-white p-3">
                <label for="q" class="form-label fw-semibold small">Cari produk</label>
                <input type="search" class="form-control mb-3" id="q" name="q" value="<?= e($q) ?>" placeholder="Nama, deskripsi, asal..." maxlength="100">

                <label for="kategori" class="form-label fw-semibold small">Kategori</label>
                <select class="form-select mb-3" id="kategori" name="kategori">
                    <option value="">Semua kategori</option>
                    <?php foreach (all_categories() as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= ($activeCategory['slug'] ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?> (<?= (int) $cat['product_count'] ?>)</option>
                    <?php endforeach; ?>
                </select>

                <label for="urut" class="form-label fw-semibold small">Urutkan</label>
                <select class="form-select mb-3" id="urut" name="urut">
                    <?php foreach ($sortOptions as $key => [$label]): ?>
                        <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-brand w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <?php if ($q !== '' || $activeCategory || $sort !== 'terbaru'): ?>
                    <a href="<?= e(url('products/index.php')) ?>" class="btn btn-link w-100 mt-1 text-muted">Reset filter</a>
                <?php endif; ?>
            </form>

            <div class="d-none d-lg-block mt-3">
                <div class="list-group card-soft overflow-hidden">
                    <a href="<?= e(url('products/index.php')) ?>" class="list-group-item list-group-item-action border-0 <?= !$activeCategory ? 'active bg-brand' : '' ?>">Semua kategori</a>
                    <?php foreach (all_categories() as $cat): ?>
                        <a href="<?= e(url('products/index.php?kategori=' . urlencode($cat['slug']))) ?>" class="list-group-item list-group-item-action border-0 d-flex justify-content-between <?= ($activeCategory['slug'] ?? '') === $cat['slug'] ? 'active bg-brand' : '' ?>">
                            <span><i class="bi <?= e($cat['icon']) ?> me-2"></i><?= e($cat['name']) ?></span>
                            <span class="small opacity-75"><?= (int) $cat['product_count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Grid produk -->
        <section class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted small">
                    Menampilkan <strong><?= count($products) ?></strong> dari <strong><?= $total ?></strong> produk
                    <?php if ($q !== ''): ?> untuk "<strong><?= e($q) ?></strong>"<?php endif; ?>
                </p>
            </div>

            <?php if ($products): ?>
                <div class="row g-3 g-lg-4">
                    <?php foreach ($products as $p): ?>
                        <div class="col-6 col-md-4"><?php require __DIR__ . '/../includes/product_card.php'; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4"><?= render_pagination($page, $totalPages, 'products/index.php', $keepParams) ?></div>
            <?php else: ?>
                <div class="card-soft bg-white text-center p-5">
                    <i class="bi bi-search display-4 text-muted"></i>
                    <h2 class="h5 mt-3">Produk tidak ditemukan</h2>
                    <p class="text-muted">Coba kata kunci lain atau hapus filter kategori.</p>
                    <a href="<?= e(url('products/index.php')) ?>" class="btn btn-brand">Lihat semua produk</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
