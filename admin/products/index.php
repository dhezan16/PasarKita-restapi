<?php
/** Admin: daftar produk */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$categoryId = input_int($_GET['kategori'] ?? '', 0);
$page = max(1, input_int($_GET['page'] ?? '1', 1));
$perPage = 10;

$where = ['1=1'];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.seller_name LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $types .= 'ss';
    array_push($params, $like, $like);
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $types .= 'i';
    $params[] = $categoryId;
}
$whereSql = implode(' AND ', $where);
$total = (int) db_fetch_value("SELECT COUNT(*) FROM products p WHERE $whereSql", $types, $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$products = db_fetch_all(
    "SELECT p.*, c.name AS category_name,
            (SELECT COALESCE(SUM(d.quantity),0) FROM order_details d WHERE d.product_id = p.id) AS sold
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE $whereSql ORDER BY p.created_at DESC, p.id DESC LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, ($page - 1) * $perPage])
);

$pageTitle = 'Kelola Produk';
$adminNav = 'products';
require __DIR__ . '/../../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Kelola Produk</h1>
        <p class="text-muted mb-0"><?= $total ?> produk ditemukan</p>
    </div>
    <a href="<?= e(url('admin/products/create.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
</div>

<form class="stat-card p-3 mb-3 row g-2 align-items-end" method="get">
    <div class="col-md-6">
        <label class="form-label small" for="q">Cari nama / penjual</label>
        <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" maxlength="100">
    </div>
    <div class="col-md-4">
        <label class="form-label small" for="kategori">Kategori</label>
        <select class="form-select" id="kategori" name="kategori">
            <option value="">Semua</option>
            <?php foreach (all_categories() as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-brand" type="submit"><i class="bi bi-search me-1"></i>Filter</button></div>
</form>

<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Produk</th><th>Kategori</th><th class="text-end">Harga</th><th class="text-center">Stok</th><th class="text-center">Terjual</th><th>Status</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(product_image_url($p['image'])) ?>" class="table-thumb" alt="">
                            <div>
                                <div class="fw-semibold"><?= e($p['name']) ?></div>
                                <div class="small text-muted"><?= e($p['seller_name'] ?: '-') ?> &middot; <?= e($p['origin'] ?: '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= e($p['category_name']) ?></td>
                    <td class="text-end"><?= rupiah($p['price']) ?></td>
                    <td class="text-center"><span class="badge <?= (int) $p['stock'] === 0 ? 'bg-danger' : ((int) $p['stock'] <= 5 ? 'bg-warning text-dark' : 'bg-light text-dark border') ?>"><?= (int) $p['stock'] ?> <?= e($p['unit']) ?></span></td>
                    <td class="text-center"><?= (int) $p['sold'] ?></td>
                    <td><?= (int) $p['is_active'] ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ((int) $p['is_active']): ?>
                            <a href="<?= e(url('products/detail.php?slug=' . urlencode($p['slug']))) ?>" class="btn btn-sm btn-light" target="_blank" rel="noopener" title="Lihat"><i class="bi bi-eye"></i></a>
                        <?php endif; ?>
                        <a href="<?= e(url('admin/products/edit.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form action="<?= e(url('admin/products/delete.php')) ?>" method="post" class="d-inline" data-confirm="Hapus produk &quot;<?= e($p['name']) ?>&quot;? Riwayat pesanan tetap tersimpan.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada produk.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($page, $totalPages, 'admin/products/index.php', array_filter(['q' => $q, 'kategori' => $categoryId ?: ''])) ?></div>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
