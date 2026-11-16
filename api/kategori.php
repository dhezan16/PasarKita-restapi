<?php
/**
 * GET /api/kategori.php          -> seluruh kategori + jumlah produk aktif
 * GET /api/kategori.php?id={id}  -> satu kategori (404 bila tidak ada)
 */
require_once __DIR__ . '/helpers/bootstrap.php';

api_allow_methods(['GET']);

$sql = 'SELECT c.id, c.name, c.slug, c.description, c.icon,
        (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
    FROM categories c';

if (array_key_exists('id', $_GET)) {
    $id = api_positive_int($_GET['id']);
    if ($id === null) {
        api_error(400, 'Parameter id harus berupa bilangan bulat positif.');
    }
    $row = db_fetch_one($sql . ' WHERE c.id = ?', 'i', [$id]);
    if (!$row) {
        api_error(404, 'Kategori tidak ditemukan');
    }
    api_success(api_format_category($row), 'Detail kategori berhasil diambil');
}

$rows = db_fetch_all($sql . ' ORDER BY c.name');
api_success(array_map('api_format_category', $rows), 'Data kategori berhasil diambil', 200, ['meta' => ['total' => count($rows)]]);
