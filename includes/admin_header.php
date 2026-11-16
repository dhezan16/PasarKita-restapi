<?php
/** Layout admin (sidebar). Variabel: $pageTitle, $adminNav */
$pageTitle = $pageTitle ?? 'Dashboard';
$adminNav = $adminNav ?? '';
$user = current_user();
$navItems = [
    'dashboard' => ['admin/index.php', 'bi-speedometer2', 'Dashboard'],
    'products'  => ['admin/products/index.php', 'bi-box-seam', 'Produk'],
    'orders'    => ['admin/orders/index.php', 'bi-receipt', 'Transaksi'],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($pageTitle) ?> | Admin PasarKita</title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<aside class="admin-sidebar d-flex flex-column p-3">
    <div class="d-flex align-items-center justify-content-between mb-lg-4">
        <a href="<?= e(url('admin/index.php')) ?>" class="d-flex align-items-center gap-2 text-white text-decoration-none">
            <span class="brand-mark"><i class="bi bi-shop"></i></span>
            <span class="fw-bold">PasarKita <small class="fw-normal text-white-50">Admin</small></span>
        </a>
        <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="Menu admin"><i class="bi bi-list"></i></button>
    </div>
    <div class="collapse d-lg-flex flex-column flex-grow-1 mt-3 mt-lg-0" id="adminNav">
        <ul class="nav nav-pills flex-column gap-1 mb-auto">
            <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
                <li class="nav-item"><a href="<?= e(url($href)) ?>" class="nav-link <?= $adminNav === $key ? 'active' : '' ?>"><i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?></a></li>
            <?php endforeach; ?>
            <li class="nav-item"><a href="<?= e(url('index.php')) ?>" class="nav-link" target="_blank" rel="noopener"><i class="bi bi-globe me-2"></i>Lihat Toko</a></li>
        </ul>
        <div class="border-top border-secondary pt-3 mt-3">
            <div class="small text-white mb-1"><i class="bi bi-person-circle me-1"></i><?= e($user['name']) ?></div>
            <div class="small text-white-50 mb-2"><?= e($user['email']) ?></div>
            <form action="<?= e(url('auth/logout.php')) ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button>
            </form>
        </div>
    </div>
</aside>

<div class="admin-main">
    <div class="container-fluid p-3 p-lg-4">
        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endforeach; ?>
