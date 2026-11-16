<?php
/** Dashboard admin */
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$stats = db_fetch_one(
    "SELECT
        (SELECT COUNT(*) FROM products) AS total_products,
        (SELECT COUNT(*) FROM products WHERE is_active = 1) AS active_products,
        (SELECT COUNT(*) FROM products WHERE stock = 0) AS out_of_stock,
        (SELECT COUNT(*) FROM orders) AS total_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status <> 'dibatalkan') AS revenue,
        (SELECT COUNT(*) FROM users WHERE role = 'pembeli') AS buyers,
        (SELECT COUNT(*) FROM categories) AS categories"
);
$recentOrders = db_fetch_all(
    'SELECT o.id, o.order_code, o.total_amount, o.status, o.created_at, u.name AS buyer
     FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC, o.id DESC LIMIT 6'
);
$lowStock = db_fetch_all('SELECT id, name, stock, unit, image FROM products WHERE stock <= 5 ORDER BY stock ASC, name LIMIT 6');
$perCategory = db_fetch_all(
    'SELECT c.name, COUNT(p.id) AS total FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.name ORDER BY c.name'
);
$maxCat = max(1, ...array_map(fn ($r) => (int) $r['total'], $perCategory ?: [['total' => 1]]));

$pageTitle = 'Dashboard';
$adminNav = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';

$cards = [
    ['Total Produk', (int) $stats['total_products'], (int) $stats['active_products'] . ' aktif, ' . (int) $stats['out_of_stock'] . ' stok habis', 'bi-box-seam', '#fbeee6', '#b4532a'],
    ['Total Pesanan', (int) $stats['total_orders'], (int) $stats['pending_orders'] . ' menunggu diproses', 'bi-receipt', '#e8f1fb', '#2563a8'],
    ['Pendapatan', rupiah($stats['revenue']), 'Tidak termasuk pesanan batal', 'bi-cash-coin', '#eaf3ec', '#3f7d4e'],
    ['Pembeli', (int) $stats['buyers'], (int) $stats['categories'] . ' kategori produk', 'bi-people', '#fdf4dc', '#a87a0b'],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Dashboard</h1>
        <p class="text-muted mb-0">Ringkasan aktivitas PasarKita hari ini, <?= e(tanggal(date('Y-m-d'), false)) ?>.</p>
    </div>
    <a href="<?= e(url('admin/products/create.php')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $hint, $icon, $bg, $fg]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card p-3 h-100 d-flex gap-3 align-items-center">
                <span class="stat-icon" style="background:<?= e($bg) ?>;color:<?= e($fg) ?>"><i class="bi <?= e($icon) ?>"></i></span>
                <div>
                    <div class="small text-muted"><?= e($label) ?></div>
                    <div class="fs-4 fw-bold" data-stat="<?= e(slugify($label)) ?>"><?= e((string) $value) ?></div>
                    <div class="small text-muted"><?= e($hint) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h2 class="h6 fw-bold mb-0">Transaksi Terbaru</h2>
                <a href="<?= e(url('admin/orders/index.php')) ?>" class="small">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Kode</th><th>Pembeli</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><a href="<?= e(url('admin/orders/detail.php?id=' . (int) $o['id'])) ?>" class="font-monospace fw-semibold"><?= e($o['order_code']) ?></a></td>
                            <td><?= e($o['buyer']) ?></td>
                            <td class="small"><?= e(tanggal($o['created_at'])) ?></td>
                            <td class="text-end"><?= rupiah($o['total_amount']) ?></td>
                            <td><?= order_status_badge($o['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentOrders): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="stat-card p-3 mb-4">
            <h2 class="h6 fw-bold mb-3">Produk per Kategori</h2>
            <?php foreach ($perCategory as $row): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span><?= e($row['name']) ?></span><span class="fw-semibold"><?= (int) $row['total'] ?></span></div>
                    <div class="progress" style="height:8px" role="progressbar" aria-label="<?= e($row['name']) ?>" aria-valuenow="<?= (int) $row['total'] ?>" aria-valuemin="0" aria-valuemax="<?= $maxCat ?>">
                        <div class="progress-bar bg-brand" style="width:<?= round((int) $row['total'] / $maxCat * 100) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="stat-card p-3">
            <h2 class="h6 fw-bold mb-3"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Stok Menipis (&le; 5)</h2>
            <?php foreach ($lowStock as $p): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?= e(product_image_url($p['image'])) ?>" class="table-thumb" alt="">
                    <div class="flex-grow-1 small"><a class="text-reset" href="<?= e(url('admin/products/edit.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?></a></div>
                    <span class="badge <?= (int) $p['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= (int) $p['stock'] ?> <?= e($p['unit']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$lowStock): ?><p class="small text-muted mb-0">Semua stok aman.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
