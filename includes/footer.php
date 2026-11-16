</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-5">
                <a class="d-flex align-items-center gap-2 text-white text-decoration-none mb-3" href="<?= e(url('index.php')) ?>">
                    <span class="brand-mark"><i class="bi bi-shop"></i></span>
                    <span class="fw-bold fs-5"><?= e(config('app.name', 'PasarKita')) ?></span>
                </a>
                <p class="text-white-50 mb-0">Marketplace yang mempertemukan pengrajin, petani, dan UMKM produk tradisional Indonesia dengan pembeli di seluruh Nusantara.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="text-white mb-3">Kategori</h6>
                <ul class="list-unstyled small">
                    <?php foreach (all_categories() as $cat): ?>
                        <li class="mb-1"><a href="<?= e(url('products/index.php?kategori=' . urlencode($cat['slug']))) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-6 col-lg-4">
                <h6 class="text-white mb-3">Belanja</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="<?= e(url('products/index.php')) ?>">Semua Produk</a></li>
                    <li class="mb-1"><a href="<?= e(url('cart/index.php')) ?>">Keranjang</a></li>
                    <li class="mb-1"><a href="<?= e(url('account/orders.php')) ?>">Pesanan Saya</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <p class="small text-white-50 mb-0">&copy; <?= date('Y') ?> <?= e(config('app.name', 'PasarKita')) ?> &mdash; Tugas Ujian Praktikum Pemrograman Web.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
