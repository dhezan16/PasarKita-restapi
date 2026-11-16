<?php
/**
 * Validasi input produk & upload gambar (dipakai oleh admin create/edit).
 */

const PRODUCT_UPLOAD_DIR = 'uploads/products';
const PRODUCT_MAX_IMAGE_BYTES = 2097152; // 2 MB
const PRODUCT_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validasi & normalisasi data produk dari $_POST.
 * @return array [array $data, array $errors]
 */
function validate_product_input(array $input): array
{
    $data = [
        'name'        => trim((string) ($input['name'] ?? '')),
        'category_id' => input_int($input['category_id'] ?? '', 0),
        'price'       => trim((string) ($input['price'] ?? '')),
        'stock'       => trim((string) ($input['stock'] ?? '')),
        'unit'        => trim((string) ($input['unit'] ?? '')),
        'origin'      => trim((string) ($input['origin'] ?? '')),
        'seller_name' => trim((string) ($input['seller_name'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'is_active'   => !empty($input['is_active']) ? 1 : 0,
    ];
    $errors = [];

    if (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 150) {
        $errors['name'] = 'Nama produk wajib diisi (3-150 karakter).';
    }
    if ($data['category_id'] <= 0 || !db_fetch_value('SELECT COUNT(*) FROM categories WHERE id = ?', 'i', [$data['category_id']])) {
        $errors['category_id'] = 'Pilih kategori yang valid.';
    }
    // Harga: "35000", "35000.50", atau format ribuan "35.000"; rentang Rp 100 - 999.999.999
    $priceNormalized = $data['price'];
    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $priceNormalized)) {
        $priceNormalized = str_replace('.', '', $priceNormalized);
    }
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $priceNormalized) || (float) $priceNormalized < 100 || (float) $priceNormalized > 999999999) {
        $errors['price'] = 'Harga harus berupa angka antara Rp 100 dan Rp 999.999.999.';
    } else {
        $data['price'] = $priceNormalized;
    }
    if (!preg_match('/^\d{1,6}$/', $data['stock']) || (int) $data['stock'] > 100000) {
        $errors['stock'] = 'Stok harus bilangan bulat 0 - 100.000 (tidak boleh negatif).';
    } else {
        $data['stock'] = (int) $data['stock'];
    }
    if ($data['unit'] === '' || mb_strlen($data['unit']) > 30 || !preg_match('/^[\p{L}0-9 ]+$/u', $data['unit'])) {
        $errors['unit'] = 'Satuan wajib diisi (huruf/angka, maks 30 karakter), mis. pcs, kg, lembar.';
    }
    if (mb_strlen($data['origin']) > 100) {
        $errors['origin'] = 'Asal daerah maksimal 100 karakter.';
    }
    if (mb_strlen($data['seller_name']) > 100) {
        $errors['seller_name'] = 'Nama penjual maksimal 100 karakter.';
    }
    if (mb_strlen($data['description']) < 10 || mb_strlen($data['description']) > 5000) {
        $errors['description'] = 'Deskripsi wajib diisi (10-5000 karakter).';
    }

    return [$data, $errors];
}

/**
 * Validasi file upload gambar produk.
 * @return array [?string $error, bool $hasFile]
 */
function validate_product_image(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, false];
    }
    if (!is_array($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE  => 'Ukuran file melebihi batas server.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
            UPLOAD_ERR_PARTIAL   => 'File hanya terunggah sebagian.',
        ];
        return [$map[$file['error']] ?? 'Upload gambar gagal (kode ' . (int) $file['error'] . ').', true];
    }
    if (is_array($file['error']) || !is_uploaded_file($file['tmp_name'])) {
        return ['File upload tidak valid.', true];
    }
    if ($file['size'] <= 0 || $file['size'] > PRODUCT_MAX_IMAGE_BYTES) {
        return ['Ukuran gambar maksimal 2 MB.', true];
    }
    // Periksa tipe asli file dari isinya (bukan dari nama/ekstensi kiriman browser)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(PRODUCT_ALLOWED_MIME[$mime])) {
        return ['Format gambar harus JPG, PNG, atau WEBP.', true];
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['Ekstensi file harus .jpg, .jpeg, .png, atau .webp.', true];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 50 || $info[1] < 50 || $info[0] > 6000 || $info[1] > 6000) {
        return ['File bukan gambar yang valid (dimensi 50-6000 piksel).', true];
    }
    return [null, true];
}

/**
 * Pindahkan file upload ke folder uploads dengan nama acak.
 * @return string path relatif dari root aplikasi
 */
function store_product_image(array $file): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $ext = PRODUCT_ALLOWED_MIME[$finfo->file($file['tmp_name'])];
    $dir = APP_ROOT . '/' . PRODUCT_UPLOAD_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = date('Ymd') . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Gagal menyimpan file gambar.');
    }
    return PRODUCT_UPLOAD_DIR . '/' . $filename;
}

/** Hapus file gambar hanya jika berada di folder uploads (gambar bawaan tidak dihapus). */
function delete_product_image(?string $path): void
{
    if (!$path || strpos($path, PRODUCT_UPLOAD_DIR . '/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    $full = APP_ROOT . '/' . $path;
    if (is_file($full)) {
        unlink($full);
    }
}

/** Buat slug unik dari nama produk. */
function unique_product_slug(string $name, int $ignoreId = 0): string
{
    $base = mb_substr(slugify($name), 0, 150);
    $slug = $base;
    $i = 2;
    while (db_fetch_value('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?', 'si', [$slug, $ignoreId])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
