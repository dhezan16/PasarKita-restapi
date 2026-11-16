<?php
/**
 * GET /api/produk.php            -> daftar produk aktif (pencarian, filter kategori, urutan, pagination)
 * GET /api/produk.php?id={id}    -> detail satu produk (404 bila tidak ada / nonaktif)
 */
require_once __DIR__ . '/helpers/bootstrap.php';

api_allow_methods(['GET']);

/* ---------------- Detail produk ---------------- */
if (array_key_exists('id', $_GET)) {
    $id = api_positive_int($_GET['id']);
    if ($id === null) {
        api_error(400, 'Parameter id harus berupa bilangan bulat positif.');
    }
    $row = db_fetch_one(API_PRODUCT_SELECT . ' WHERE p.id = ? AND p.is_active = 1', 'i', [$id]);
    if (!$row) {
        api_error(404, 'Produk tidak ditemukan');
    }
    api_success(api_format_product($row, true), 'Detail produk berhasil diambil');
}

/* ---------------- Daftar produk ---------------- */
$sorts = [
    'terbaru'    => 'p.created_at DESC, p.id DESC',
    'harga_asc'  => 'p.price ASC, p.id ASC',
    'harga_desc' => 'p.price DESC, p.id ASC',
    'nama'       => 'p.name ASC, p.id ASC',
];

$q = trim((string) ($_GET['q'] ?? ''));
$kategori = trim((string) ($_GET['kategori'] ?? ''));
$urut = (string) ($_GET['urut'] ?? 'terbaru');
$halaman = isset($_GET['halaman']) ? api_positive_int($_GET['halaman'], 100000) : 1;
$limit = isset($_GET['limit']) ? api_positive_int($_GET['limit'], 100) : 20;

$errors = [];
if (mb_strlen($q) > 100) {
    $errors['q'] = 'Kata kunci maksimal 100 karakter.';
}
if (!isset($sorts[$urut])) {
    $errors['urut'] = 'Pilihan urut: ' . implode(', ', array_keys($sorts)) . '.';
}
if ($halaman === null) {
    $errors['halaman'] = 'Halaman harus bilangan bulat positif.';
}
if ($limit === null) {
    $errors['limit'] = 'Limit harus bilangan bulat 1-100.';
}
if ($errors) {
    api_error(400, 'Parameter pencarian tidak valid.', $errors);
}

$where = ['p.is_active = 1'];
$types = '';
$params = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.origin LIKE ?)';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($kategori !== '') {
    // Terima slug ("batik-tenun") atau ID numerik kategori
    $cat = ctype_digit($kategori)
        ? db_fetch_one('SELECT id FROM categories WHERE id = ?', 'i', [(int) $kategori])
        : db_fetch_one('SELECT id FROM categories WHERE slug = ?', 's', [$kategori]);
    if (!$cat) {
        api_error(404, 'Kategori tidak ditemukan');
    }
    $where[] = 'p.category_id = ?';
    $types .= 'i';
    $params[] = (int) $cat['id'];
}

$whereSql = ' WHERE ' . implode(' AND ', $where);
$total = (int) db_fetch_value('SELECT COUNT(*) FROM products p' . $whereSql, $types, $params);
$totalPages = max(1, (int) ceil($total / $limit));
$offset = ($halaman - 1) * $limit;

$rows = db_fetch_all(
    API_PRODUCT_SELECT . $whereSql . ' ORDER BY ' . $sorts[$urut] . ' LIMIT ? OFFSET ?',
    $types . 'ii',
    array_merge($params, [$limit, $offset])
);

api_success(
    array_map('api_format_product', $rows),
    $total > 0 ? 'Data produk berhasil diambil' : 'Tidak ada produk yang sesuai',
    200,
    ['meta' => [
        'total'         => $total,
        'halaman'       => $halaman,
        'limit'         => $limit,
        'total_halaman' => $totalPages,
        'filter'        => ['q' => $q, 'kategori' => $kategori, 'urut' => $urut],
    ]]
);
