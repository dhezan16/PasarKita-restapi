<?php
/**
 * Helper response & request untuk REST API PasarKita.
 *
 * Semua response memakai format yang sama:
 *   { "success": bool, "message": string, "data": mixed|null, ...tambahan (meta/errors) }
 */

if (!defined('PASARKITA_API')) {
    http_response_code(404);
    exit;
}

/**
 * Kirim response JSON lalu hentikan eksekusi.
 * Buffer output dibuang dulu agar warning/notice PHP tidak pernah ikut terkirim.
 */
function api_send(int $status, bool $success, string $message, $data = null, array $extra = []): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
    }
    $body = ['success' => $success, 'message' => $message, 'data' => $data] + $extra;
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/** Response sukses (200 secara default). */
function api_success($data, string $message = 'Data berhasil diambil', int $status = 200, array $extra = []): void
{
    api_send($status, true, $message, $data, $extra);
}

/** Response gagal. $errors (opsional) berisi rincian validasi per field. */
function api_error(int $status, string $message, $errors = null): void
{
    api_send($status, false, $message, null, $errors !== null ? ['errors' => $errors] : []);
}

/** Pastikan HTTP method diizinkan; selain itu 405 + header Allow. */
function api_allow_methods(array $allowed): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        api_error(405, 'Method ' . $method . ' tidak diizinkan untuk endpoint ini. Gunakan: ' . implode(', ', $allowed) . '.');
    }
    return $method;
}

/**
 * Baca body JSON. Wajib Content-Type: application/json (sekaligus mencegah
 * pengiriman lintas situs lewat form HTML biasa), harus berupa objek JSON.
 */
function api_json_body(): array
{
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    if (strpos($contentType, 'application/json') !== 0) {
        api_error(415, 'Content-Type harus application/json.');
    }
    $raw = (string) file_get_contents('php://input', false, null, 0, 65537);
    if (strlen($raw) > 65536) {
        api_error(400, 'Body request terlalu besar (maksimal 64 KB).');
    }
    if (trim($raw) === '') {
        api_error(400, 'Body request kosong. Kirim data pesanan dalam format JSON.');
    }
    try {
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        api_error(400, 'Format JSON tidak valid: ' . $e->getMessage() . '.');
    }
    if (!is_array($data) || ($data !== [] && array_keys($data) === range(0, count($data) - 1))) {
        api_error(400, 'Body JSON harus berupa objek, contoh: {"produk": [...]}.');
    }
    return $data;
}

/** Bilangan bulat positif dari query string / JSON, atau null bila tidak valid. */
function api_positive_int($value, int $max = PHP_INT_MAX): ?int
{
    if (is_int($value)) {
        $int = $value;
    } elseif (is_string($value) && preg_match('/^[1-9][0-9]{0,9}$/', $value)) {
        $int = (int) $value;
    } else {
        return null;
    }
    return ($int >= 1 && $int <= $max) ? $int : null;
}

/** Angka uang: 95000.00 -> 95000 (int), 35000.5 -> 35000.5 */
function api_money($value)
{
    $f = round((float) $value, 2);
    return floor($f) == $f ? (int) $f : $f;
}

/** URL absolut (untuk gambar & tautan halaman web). */
function api_absolute_url(string $path): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(:[0-9]{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host . $path;
}

/** Wajib login (session yang sama dengan aplikasi web). */
function api_require_user(): array
{
    $user = current_user();
    if (!$user) {
        api_error(401, 'Anda harus login terlebih dahulu. Kirim POST ke /api/auth.php dengan email dan password.');
    }
    return $user;
}
