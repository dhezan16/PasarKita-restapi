<?php
/** Detail pesanan milik pembeli */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(ROLE_BUYER);

$code = (string) ($_GET['kode'] ?? '');
$order = null;
if (preg_match('/^[A-Z0-9]{6,30}$/', $code)) {
    $order = db_fetch_one('SELECT * FROM orders WHERE order_code = ? AND user_id = ?', 'si', [$code, (int) current_user()['id']]);
}
if (!$order) {
    flash('danger', 'Pesanan tidak ditemukan.');
    redirect('account/orders.php');
}
$details = db_fetch_all(
    'SELECT d.*, p.slug, p.image, p.price AS current_price FROM order_details d
     LEFT JOIN products p ON p.id = d.product_id WHERE d.order_id = ? ORDER BY d.id',
    'i',
    [(int) $order['id']]
);

$pageTitle = 'Pesanan ' . $order['order_code'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('account/orders.php')) ?>">Pesanan Saya</a></li>
            <li class="breadcrumb-item active"><?= e($order['order_code']) ?></li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 section-title mb-0">Pesanan <span class="font-monospace"><?= e($order['order_code']) ?></span></h1>
        <?= order_status_badge($order['status']) ?>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-soft bg-white">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Produk</th><th class="text-center">Jumlah</th><th class="text-end">Harga saat dipesan</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= e(product_image_url($d['image'])) ?>" class="table-thumb" alt="">
                                        <div>
                                            <?php if ($d['slug']): ?>
                                                <a href="<?= e(url('products/detail.php?slug=' . urlencode($d['slug']))) ?>" class="text-reset fw-semibold text-decoration-none"><?= e($d['product_name']) ?></a>
                                            <?php else: ?>
                                                <span class="fw-semibold"><?= e($d['product_name']) ?></span> <span class="badge bg-light text-muted border">produk dihapus</span>
                                            <?php endif; ?>
                                            <?php if ($d['current_price'] !== null && (float) $d['current_price'] !== (float) $d['price']): ?>
                                                <div class="small text-muted">Harga sekarang: <?= rupiah($d['current_price']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?= (int) $d['quantity'] ?></td>
                                <td class="text-end"><?= rupiah($d['price']) ?></td>
                                <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft bg-white p-4 small">
                <div class="mb-2"><span class="text-muted">Tanggal:</span> <?= e(tanggal($order['created_at'])) ?></div>
                <div class="mb-2"><span class="text-muted">Penerima:</span> <?= e($order['recipient_name']) ?> (<?= e($order['phone']) ?>)</div>
                <div class="mb-2"><span class="text-muted">Alamat:</span><br><?= nl2br(e($order['shipping_address'])) ?></div>
                <div class="mb-2"><span class="text-muted">Pembayaran:</span> <?= e(payment_label($order['payment_method'])) ?></div>
                <?php if ($order['notes']): ?><div class="mb-2"><span class="text-muted">Catatan:</span> <?= e($order['notes']) ?></div><?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= rupiah($order['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Ongkos kirim</span><span><?= rupiah($order['shipping_cost']) ?></span></div>
                <div class="d-flex justify-content-between fs-6 fw-bold mt-2"><span>Total</span><span class="price"><?= rupiah($order['total_amount']) ?></span></div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
