<?php
/** Halaman keberhasilan pesanan */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$code = (string) ($_GET['kode'] ?? '');
$order = null;
if (preg_match('/^[A-Z0-9]{6,30}$/', $code)) {
    // Pembeli hanya boleh melihat pesanannya sendiri
    $order = db_fetch_one('SELECT * FROM orders WHERE order_code = ? AND user_id = ?', 'si', [$code, (int) current_user()['id']]);
}
if (!$order) {
    flash('danger', 'Pesanan tidak ditemukan.');
    redirect('index.php');
}
$details = db_fetch_all('SELECT * FROM order_details WHERE order_id = ? ORDER BY id', 'i', [(int) $order['id']]);

$pageTitle = 'Pesanan Berhasil';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <div class="mx-auto" style="max-width:720px">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3" style="width:88px;height:88px">
                <i class="bi bi-check2-circle display-4"></i>
            </div>
            <h1 class="h3 fw-bold">Terima kasih, pesanan Anda berhasil dibuat!</h1>
            <p class="text-muted">Simpan kode pesanan berikut untuk melacak status pesanan Anda.</p>
            <div class="d-inline-block bg-white border rounded-3 px-4 py-2 fs-4 fw-bold font-monospace text-brand" id="order-code"><?= e($order['order_code']) ?></div>
        </div>

        <div class="card-soft bg-white p-4 mb-4">
            <div class="row g-3 small mb-3">
                <div class="col-sm-6"><div class="text-muted">Tanggal</div><div class="fw-semibold"><?= e(tanggal($order['created_at'])) ?></div></div>
                <div class="col-sm-6"><div class="text-muted">Status</div><div><?= order_status_badge($order['status']) ?></div></div>
                <div class="col-sm-6"><div class="text-muted">Penerima</div><div class="fw-semibold"><?= e($order['recipient_name']) ?> (<?= e($order['phone']) ?>)</div></div>
                <div class="col-sm-6"><div class="text-muted">Pembayaran</div><div class="fw-semibold"><?= e(payment_label($order['payment_method'])) ?></div></div>
                <div class="col-12"><div class="text-muted">Alamat pengiriman</div><div class="fw-semibold"><?= nl2br(e($order['shipping_address'])) ?></div></div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Produk</th><th class="text-center">Jumlah</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($details as $d): ?>
                        <tr>
                            <td><?= e($d['product_name']) ?></td>
                            <td class="text-center"><?= (int) $d['quantity'] ?></td>
                            <td class="text-end"><?= rupiah($d['price']) ?></td>
                            <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end"><?= rupiah($order['subtotal']) ?></td></tr>
                        <tr><td colspan="3" class="text-end text-muted">Ongkos kirim</td><td class="text-end"><?= rupiah($order['shipping_cost']) ?></td></tr>
                        <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end price"><?= rupiah($order['total_amount']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="alert alert-light border small">
            <i class="bi bi-info-circle me-1"></i>
            <?php if ($order['payment_method'] === 'transfer'): ?>
                Admin PasarKita akan menghubungi nomor HP Anda untuk konfirmasi pembayaran transfer.
            <?php else: ?>
                Siapkan pembayaran tunai sebesar <strong><?= rupiah($order['total_amount']) ?></strong> saat pesanan tiba.
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="<?= e(url('account/orders.php')) ?>" class="btn btn-outline-brand"><i class="bi bi-receipt me-1"></i>Lihat Pesanan Saya</a>
            <a href="<?= e(url('products/index.php')) ?>" class="btn btn-brand">Belanja Lagi</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
