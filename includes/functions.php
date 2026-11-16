<?php
/**
 * Fungsi bantu umum: escape output, URL, redirect, flash message, CSRF, format.
 */

/** Escape output HTML untuk mencegah XSS. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Deteksi URL dasar aplikasi (mendukung subfolder seperti /PasarKita). */
function detect_base_url(): string
{
    $configured = config('app.base_url');
    if ($configured !== null) {
        return rtrim((string) $configured, '/');
    }
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(APP_ROOT);
    if ($docRoot && $appRoot && stripos($appRoot, $docRoot) === 0) {
        $path = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        return rtrim($path, '/');
    }
    return '';
}

/** Membuat URL absolut-path ke halaman aplikasi. */
/**
 * Memulai session dengan cookie aman. Dipakai halaman web dan REST API (api/)
 * sehingga login di salah satunya berlaku untuk keduanya.
 */
function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('PASARKITASESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_URL === '' ? '/' : BASE_URL . '/'),
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    $target = preg_match('#^https?://#i', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Hanya izinkan redirect ke path lokal aplikasi (mencegah open redirect). */
function safe_redirect_path(?string $path, string $fallback = 'index.php'): string
{
    $path = (string) $path;
    if ($path === '' || !preg_match('#^[a-zA-Z0-9_\-/]+\.php(\?[a-zA-Z0-9_\-=&%.+]*)?$#', $path) || strpos($path, '//') !== false || strpos($path, '..') !== false) {
        return $fallback;
    }
    return ltrim($path, '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------------------------- Flash message ---------------------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* -------------------------------- CSRF -------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validasi token CSRF pada request POST. Hentikan request bila tidak valid. */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // 403 (standar HTTP). Kode 419 tidak dikenal Apache dan berubah menjadi 500.
        http_response_code(403);
        exit('<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;max-width:600px;margin:60px auto">'
            . '<h2>Sesi formulir kedaluwarsa</h2><p>Token keamanan (CSRF) tidak valid. Silakan muat ulang halaman dan coba lagi.</p>'
            . '<a href="' . e(url('index.php')) . '">Kembali ke beranda</a></div>');
    }
}

/* ------------------------------- Format ------------------------------- */

function rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function tanggal($datetime, bool $withTime = true): string
{
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime((string) $datetime);
    if (!$ts) {
        return '-';
    }
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'produk';
}

/** URL gambar produk; fallback ke placeholder bila kosong. */
function product_image_url(?string $image): string
{
    if (!$image) {
        return asset('img/placeholder.svg');
    }
    return url($image);
}

/** Label & warna badge status pesanan. */
function order_status_badge(string $status): string
{
    $map = [
        'pending'    => ['Menunggu', 'warning text-dark'],
        'diproses'   => ['Diproses', 'info text-dark'],
        'dikirim'    => ['Dikirim', 'primary'],
        'selesai'    => ['Selesai', 'success'],
        'dibatalkan' => ['Dibatalkan', 'secondary'],
    ];
    [$label, $class] = $map[$status] ?? [$status, 'light text-dark'];
    return '<span class="badge rounded-pill bg-' . $class . '">' . e($label) . '</span>';
}

function payment_label(string $method): string
{
    return ['transfer' => 'Transfer Bank', 'cod' => 'Bayar di Tempat (COD)'][$method] ?? $method;
}

/** Ambil nilai integer positif dari input (GET/POST). */
function input_int($value, int $default = 0): int
{
    if (is_string($value) && preg_match('/^\d{1,9}$/', $value)) {
        return (int) $value;
    }
    if (is_int($value)) {
        return $value;
    }
    return $default;
}

/** Daftar semua kategori (dipakai navbar & filter). */
function all_categories(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = db_fetch_all('SELECT c.id, c.name, c.slug, c.icon, c.description,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
            FROM categories c ORDER BY c.name');
    }
    return $cache;
}

/** Render pagination Bootstrap. $params = query string lain yang dipertahankan. */
function render_pagination(int $page, int $totalPages, string $basePath, array $params = []): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $query = http_build_query(array_merge($params, ['page' => $i]));
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e(url($basePath) . '?' . $query) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}
