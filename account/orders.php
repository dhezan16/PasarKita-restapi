<?php
/** Riwayat pesanan milik pembeli yang login */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(ROLE_BUYER);

$orders = db_fetch_all(
    'SELECT id, order_code, total_items, total_amount, status, payment_method, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC',
    'i',
    [(int) current_user()['id']]
);

$pageTitle = 'Pesanan Saya';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 section-title mb-4">Pesanan Saya</h1>
    <?php if (!$orders): ?>
        <div class="card-soft bg-white text-center p-5">
            <i class="bi bi-receipt display-4 text-muted"></i>
            <h2 class="h5 mt-3">Belum ada pesanan</h2>
            <a href="<?= e(url('products/index.php')) ?>" class="btn btn-brand mt-2">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <div class="card-soft bg-white">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Kode Pesanan</th><th>Tanggal</th><th class="text-center">Barang</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="font-monospace fw-semibold"><?= e($o['order_code']) ?></td>
                            <td><?= e(tanggal($o['created_at'])) ?></td>
                            <td class="text-center"><?= (int) $o['total_items'] ?></td>
                            <td class="text-end fw-semibold"><?= rupiah($o['total_amount']) ?></td>
                            <td><?= order_status_badge($o['status']) ?></td>
                            <td class="text-end"><a href="<?= e(url('account/order_detail.php?kode=' . urlencode($o['order_code']))) ?>" class="btn btn-sm btn-outline-brand">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
