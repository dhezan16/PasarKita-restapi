<?php
/**
 * Header & navbar halaman publik.
 * Variabel opsional: $pageTitle, $activeNav
 */
$pageTitle = $pageTitle ?? 'Marketplace Produk Tradisional';
$activeNav = $activeNav ?? '';
$user = current_user();
$appName = config('app.name', 'PasarKita');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PasarKita - marketplace yang mempertemukan penjual produk tradisional Indonesia dengan pembeli.">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm-soft">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark"><i class="bi bi-shop"></i></span>
            <span class="fw-bold"><?= e($appName) ?></span>
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-3">
            <?php if (!is_admin()): ?>
            <a href="<?= e(url('cart/index.php')) ?>" class="btn btn-light position-relative cart-btn" aria-label="Keranjang belanja">
                <i class="bi bi-bag"></i>
                <?php if (cart_count() > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-brand" id="cart-badge"><?= cart_count() ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item"><a class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">Beranda</a></li>
                <li class="nav-item"><a class="nav-link <?= $activeNav === 'products' ? 'active' : '' ?>" href="<?= e(url('products/index.php')) ?>">Produk</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Kategori</a>
                    <ul class="dropdown-menu shadow border-0">
                        <?php foreach (all_categories() as $cat): ?>
                            <li><a class="dropdown-item" href="<?= e(url('products/index.php?kategori=' . urlencode($cat['slug']))) ?>"><i class="bi <?= e($cat['icon']) ?> me-2 text-brand"></i><?= e($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <form class="d-flex my-2 my-lg-0 me-lg-3 nav-search" role="search" action="<?= e(url('products/index.php')) ?>" method="get">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" placeholder="Cari batik, kopi, jamu..." aria-label="Cari produk" value="<?= e($_GET['q'] ?? '') ?>" maxlength="100">
                    <button class="btn btn-brand" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
                            <span class="d-lg-none d-xl-inline"><?= e(explode(' ', $user['name'])[0]) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><h6 class="dropdown-header"><?= e($user['email']) ?></h6></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= e(url('account/orders.php')) ?>"><i class="bi bi-receipt me-2"></i>Pesanan Saya</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="<?= e(url('auth/logout.php')) ?>" method="post" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('auth/login.php')) ?>">Masuk</a></li>
                    <li class="nav-item ms-lg-1"><a class="btn btn-brand btn-sm px-3 mt-1 mt-lg-0" href="<?= e(url('auth/register.php')) ?>">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
<?php $flashes = get_flashes(); if ($flashes): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
