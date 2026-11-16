<?php
/** Admin: detail transaksi + ubah status */
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/order.php';
require_admin();

$id = input_int($_GET['id'] ?? '', 0);

if (is_post()) {
    verify_csrf();
    $error = update_order_status($id, (string) ($_POST['status'] ?? ''));
    if ($error) {
        flash('danger', $error);
    } else {
        flash('success', 'Status pesanan diperbarui.' . (($_POST['status'] ?? '') === 'dibatalkan' ? ' Stok produk telah dikembalikan.' : ''));
    }
    redirect('admin/orders/detail.php?id=' . $id);
}

$order = $id > 0 ? db_fetch_one(
    'SELECT o.*, u.name AS buyer, u.email AS buyer_email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?',
    'i',
    [$id]
) : null;
if (!$order) {
    flash('danger', 'Transaksi tidak ditemukan.');
    redirect('admin/orders/index.php');
}
$details = db_fetch_all(
    'SELECT d.*, p.price AS current_price, p.image FROM order_details d LEFT JOIN products p ON p.id = d.product_id WHERE d.order_id = ? ORDER BY d.id',
    'i',
    [$id]
);
$locked = in_array($order['status'], ['dibatalkan', 'selesai'], true);

$pageTitle = 'Transaksi ' . $order['order_code'];
$adminNav = 'orders';
require __DIR__ . '/../../includes/admin_header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('admin/orders/index.php')) ?>">Transaksi</a></li><li class="breadcrumb-item active"><?= e($order['order_code']) ?></li></ol></nav>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 fw-bold mb-0">Transaksi <span class="font-monospace"><?= e($order['order_code']) ?></span></h1>
    <?= order_status_badge($order['status']) ?>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="stat-card">
            <div class="p-3 border-bottom"><h2 class="h6 fw-bold mb-0">Detail Produk (harga snapshot saat dipesan)</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Produk</th><th class="text-end">Harga dipesan</th><th class="text-end">Harga saat ini</th><th class="text-center">Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($details as $d): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e(product_image_url($d['image'])) ?>" class="table-thumb" alt="">
                                    <div><?= e($d['product_name']) ?><?php if ($d['product_id'] === null): ?> <span class="badge bg-light text-muted border">produk dihapus</span><?php endif; ?></div>
                                </div>
                            </td>
                            <td class="text-end"><?= rupiah($d['price']) ?></td>
                            <td class="text-end small text-muted"><?= $d['current_price'] !== null ? rupiah($d['current_price']) : '-' ?></td>
                            <td class="text-center"><?= (int) $d['quantity'] ?></td>
                            <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end"><?= rupiah($order['subtotal']) ?></td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Ongkos kirim</td><td class="text-end"><?= rupiah($order['shipping_cost']) ?></td></tr>
                        <tr><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end price"><?= rupiah($order['total_amount']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="stat-card p-4 mb-4 small">
            <h2 class="h6 fw-bold mb-3">Informasi Pesanan</h2>
            <div class="mb-2"><span class="text-muted">Tanggal:</span> <?= e(tanggal($order['created_at'])) ?></div>
            <div class="mb-2"><span class="text-muted">Akun pembeli:</span> <?= e($order['buyer']) ?> (<?= e($order['buyer_email']) ?>)</div>
            <div class="mb-2"><span class="text-muted">Penerima:</span> <?= e($order['recipient_name']) ?> &middot; <?= e($order['phone']) ?></div>
            <div class="mb-2"><span class="text-muted">Alamat:</span><br><?= nl2br(e($order['shipping_address'])) ?></div>
            <div class="mb-2"><span class="text-muted">Pembayaran:</span> <?= e(payment_label($order['payment_method'])) ?></div>
            <?php if ($order['notes']): ?><div><span class="text-muted">Catatan:</span> <?= e($order['notes']) ?></div><?php endif; ?>
        </div>
        <div class="stat-card p-4">
            <h2 class="h6 fw-bold mb-3">Ubah Status</h2>
            <?php if ($locked): ?>
                <p class="small text-muted mb-0">Pesanan berstatus final dan tidak dapat diubah lagi.</p>
            <?php else: ?>
                <form method="post" data-confirm="Ubah status pesanan ini?">
                    <?= csrf_field() ?>
                    <select name="status" class="form-select mb-2" aria-label="Status pesanan">
                        <?php foreach (['pending' => 'Menunggu', 'diproses' => 'Diproses', 'dikirim' => 'Dikirim', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan (stok dikembalikan)'] as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-brand w-100">Simpan Status</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
