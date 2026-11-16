<?php
/** Admin: daftar transaksi */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$statuses = ['pending' => 'Menunggu', 'diproses' => 'Diproses', 'dikirim' => 'Dikirim', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
$status = (string) ($_GET['status'] ?? '');
$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 50);
$page = max(1, input_int($_GET['page'] ?? '1', 1));
$perPage = 15;

$where = ['1=1'];
$types = '';
$params = [];
if (isset($statuses[$status])) {
    $where[] = 'o.status = ?';
    $types .= 's';
    $params[] = $status;
} else {
    $status = '';
}
if ($q !== '') {
    $where[] = '(o.order_code LIKE ? OR u.name LIKE ? OR o.recipient_name LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

$total = (int) db_fetch_value("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id WHERE $whereSql", $types, $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$orders = db_fetch_all(
    "SELECT o.*, u.name AS buyer, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.user_id
     WHERE $whereSql ORDER BY o.created_at DESC, o.id DESC LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, ($page - 1) * $perPage])
);
$countByStatus = [];
foreach (db_fetch_all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $r) {
    $countByStatus[$r['status']] = (int) $r['n'];
}

$pageTitle = 'Daftar Transaksi';
$adminNav = 'orders';
require __DIR__ . '/../../includes/admin_header.php';
?>
<h1 class="h3 fw-bold mb-1">Daftar Transaksi</h1>
<p class="text-muted mb-4"><?= $total ?> transaksi</p>

<ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item"><a class="nav-link <?= $status === '' ? 'active bg-brand' : 'text-dark' ?>" href="<?= e(url('admin/orders/index.php')) ?>">Semua (<?= array_sum($countByStatus) ?>)</a></li>
    <?php foreach ($statuses as $key => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $status === $key ? 'active bg-brand' : 'text-dark' ?>" href="<?= e(url('admin/orders/index.php?status=' . $key)) ?>"><?= e($label) ?> (<?= $countByStatus[$key] ?? 0 ?>)</a></li>
    <?php endforeach; ?>
</ul>

<form class="mb-3" method="get" style="max-width:420px">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <div class="input-group">
        <input type="search" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Cari kode pesanan / nama pembeli">
        <button class="btn btn-outline-brand" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Kode</th><th>Pembeli</th><th>Tanggal</th><th class="text-center">Barang</th><th>Pembayaran</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td class="font-monospace fw-semibold"><?= e($o['order_code']) ?></td>
                    <td><div><?= e($o['buyer']) ?></div><div class="small text-muted"><?= e($o['buyer_email']) ?></div></td>
                    <td class="small"><?= e(tanggal($o['created_at'])) ?></td>
                    <td class="text-center"><?= (int) $o['total_items'] ?></td>
                    <td class="small"><?= e(payment_label($o['payment_method'])) ?></td>
                    <td class="text-end fw-semibold"><?= rupiah($o['total_amount']) ?></td>
                    <td><?= order_status_badge($o['status']) ?></td>
                    <td class="text-end"><a href="<?= e(url('admin/orders/detail.php?id=' . (int) $o['id'])) ?>" class="btn btn-sm btn-outline-brand">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= render_pagination($page, $totalPages, 'admin/orders/index.php', array_filter(['status' => $status, 'q' => $q])) ?></div>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
