<?php
/** Checkout: formulir pengiriman + proses pembuatan pesanan */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/order.php';

require_role(ROLE_BUYER);
$user = current_user();

$items = cart_items();
if (!$items) {
    flash('warning', 'Keranjang Anda kosong.');
    redirect('cart/index.php');
}

$errors = [];
$orderErrors = [];
$old = [
    'recipient_name'   => $user['name'],
    'phone'            => (string) $user['phone'],
    'shipping_address' => (string) $user['address'],
    'notes'            => '',
    'payment_method'   => 'transfer',
];
$paymentMethods = ['transfer' => 'Transfer Bank', 'cod' => 'Bayar di Tempat (COD)'];

if (is_post()) {
    verify_csrf();

    foreach (array_keys($old) as $key) {
        $old[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if (mb_strlen($old['recipient_name']) < 3 || mb_strlen($old['recipient_name']) > 100) {
        $errors['recipient_name'] = 'Nama penerima wajib diisi (3-100 karakter).';
    }
    if (!preg_match('/^(\+62|62|0)[0-9]{8,13}$/', $old['phone'])) {
        $errors['phone'] = 'Nomor HP tidak valid. Contoh: 081234567890.';
    }
    if (mb_strlen($old['shipping_address']) < 10 || mb_strlen($old['shipping_address']) > 500) {
        $errors['shipping_address'] = 'Alamat lengkap wajib diisi (10-500 karakter).';
    }
    if (mb_strlen($old['notes']) > 255) {
        $errors['notes'] = 'Catatan maksimal 255 karakter.';
    }
    if (!isset($paymentMethods[$old['payment_method']])) {
        $errors['payment_method'] = 'Pilih metode pembayaran yang tersedia.';
    }

    if (!$errors) {
        try {
            // Hanya ID produk & jumlah dari session yang dipakai; harga diambil dari DB.
            $order = create_order((int) $user['id'], cart_raw(), $old);
            cart_clear();
            $_SESSION['last_order_code'] = $order['order_code'];
            redirect('checkout/success.php?kode=' . urlencode($order['order_code']));
        } catch (OrderException $ex) {
            $orderErrors = $ex->errors;
            $items = cart_items(); // muat ulang stok terbaru
        }
    }
}

$totals = cart_totals($items);

$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('cart/index.php')) ?>">Keranjang</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>
    <h1 class="h3 section-title mb-4">Checkout</h1>

    <?php if ($orderErrors): ?>
        <div class="alert alert-danger">
            <strong><i class="bi bi-x-octagon me-1"></i>Pesanan gagal dibuat. Tidak ada data yang tersimpan.</strong>
            <ul class="mb-0 mt-2"><?php foreach ($orderErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            <a href="<?= e(url('cart/index.php')) ?>" class="alert-link">Perbarui keranjang</a>
        </div>
    <?php endif; ?>

    <form method="post" class="row g-4" novalidate>
        <?= csrf_field() ?>
        <div class="col-lg-7">
            <div class="card-soft bg-white p-4 mb-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-geo-alt text-brand me-2"></i>Alamat Pengiriman</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="recipient_name" class="form-label">Nama penerima</label>
                        <input type="text" class="form-control <?= isset($errors['recipient_name']) ? 'is-invalid' : '' ?>" id="recipient_name" name="recipient_name" value="<?= e($old['recipient_name']) ?>" required maxlength="100">
                        <div class="invalid-feedback"><?= e($errors['recipient_name'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($old['phone']) ?>" required maxlength="20" placeholder="081234567890">
                        <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
                    </div>
                    <div class="col-12">
                        <label for="shipping_address" class="form-label">Alamat lengkap</label>
                        <textarea class="form-control <?= isset($errors['shipping_address']) ? 'is-invalid' : '' ?>" id="shipping_address" name="shipping_address" rows="3" required maxlength="500" placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan, kota, kode pos"><?= e($old['shipping_address']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errors['shipping_address'] ?? '') ?></div>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Catatan untuk penjual <span class="text-muted small">(opsional)</span></label>
                        <input type="text" class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" id="notes" name="notes" value="<?= e($old['notes']) ?>" maxlength="255">
                        <div class="invalid-feedback"><?= e($errors['notes'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <div class="card-soft bg-white p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-wallet2 text-brand me-2"></i>Metode Pembayaran</h2>
                <?php foreach ($paymentMethods as $value => $label): ?>
                    <div class="form-check border rounded-3 p-3 ps-5 mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay-<?= e($value) ?>" value="<?= e($value) ?>" <?= $old['payment_method'] === $value ? 'checked' : '' ?>>
                        <label class="form-check-label w-100" for="pay-<?= e($value) ?>">
                            <span class="fw-semibold"><?= e($label) ?></span>
                            <span class="d-block small text-muted"><?= $value === 'transfer' ? 'Instruksi pembayaran dikonfirmasi oleh admin setelah pesanan dibuat.' : 'Bayar tunai kepada kurir saat barang diterima.' ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if (isset($errors['payment_method'])): ?><div class="text-danger small"><?= e($errors['payment_method']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-soft bg-white p-4 summary-card">
                <h2 class="h5 fw-bold mb-3">Ringkasan Pesanan</h2>
                <?php foreach ($items as $item): ?>
                    <div class="d-flex gap-3 mb-3">
                        <img src="<?= e(product_image_url($item['image'])) ?>" class="cart-thumb" alt="<?= e($item['name']) ?>">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold"><?= e($item['name']) ?></div>
                            <div class="text-muted"><?= (int) $item['quantity'] ?> &times; <?= rupiah($item['price']) ?></div>
                            <?php if (!$item['stock_ok']): ?><div class="text-danger">Stok tersisa <?= (int) $item['stock'] ?></div><?php endif; ?>
                        </div>
                        <div class="small fw-semibold text-nowrap"><?= rupiah($item['subtotal']) ?></div>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span><?= rupiah($totals['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Ongkos kirim</span><span><?= rupiah($totals['shipping']) ?></span></div>
                <hr>
                <div class="d-flex justify-content-between mb-3 fs-5"><span class="fw-bold">Total Bayar</span><span class="price"><?= rupiah($totals['total']) ?></span></div>
                <button type="submit" class="btn btn-brand w-100 py-2"><i class="bi bi-lock me-1"></i>Buat Pesanan</button>
                <p class="small text-muted mt-3 mb-0">Dengan menekan tombol di atas, stok akan divalidasi dan pesanan dicatat secara permanen.</p>
            </div>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
