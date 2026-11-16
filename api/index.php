<?php
/** GET /api/ -> informasi singkat & daftar endpoint REST API PasarKita */
require_once __DIR__ . '/helpers/bootstrap.php';

api_allow_methods(['GET']);

$base = api_absolute_url(url('api'));
api_success([
    'nama'     => 'PasarKita REST API',
    'versi'    => '1.0',
    'format'   => 'application/json',
    'endpoint' => [
        ['method' => 'GET',    'url' => $base . '/produk.php',      'keterangan' => 'Daftar produk (q, kategori, urut, halaman, limit)'],
        ['method' => 'GET',    'url' => $base . '/produk.php?id=1', 'keterangan' => 'Detail produk'],
        ['method' => 'GET',    'url' => $base . '/kategori.php',    'keterangan' => 'Daftar kategori'],
        ['method' => 'POST',   'url' => $base . '/auth.php',        'keterangan' => 'Login (JSON email & password)'],
        ['method' => 'GET',    'url' => $base . '/auth.php',        'keterangan' => 'Pengguna yang sedang login'],
        ['method' => 'DELETE', 'url' => $base . '/auth.php',        'keterangan' => 'Logout'],
        ['method' => 'POST',   'url' => $base . '/pesanan.php',     'keterangan' => 'Buat pesanan (wajib login pembeli)'],
        ['method' => 'GET',    'url' => $base . '/pesanan.php',     'keterangan' => 'Pesanan milik pengguna / detail dengan ?id='],
    ],
], 'PasarKita REST API aktif');
