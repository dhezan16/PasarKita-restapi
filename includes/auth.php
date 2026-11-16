<?php
/**
 * Autentikasi berbasis session dan proteksi halaman berdasarkan role.
 */

const ROLE_ADMIN = 'admin';
const ROLE_BUYER = 'pembeli';

/** Data user yang sedang login (dimuat ulang dari DB sekali per request). */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $user = db_fetch_one('SELECT id, name, email, phone, address, role FROM users WHERE id = ?', 'i', [(int) $_SESSION['user_id']]);
        if (!$user) {
            // Akun sudah tidak ada: bersihkan session
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === ROLE_ADMIN;
}

function is_buyer(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === ROLE_BUYER;
}

/** Login: regenerasi session ID untuk mencegah session fixation. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    // Token CSRF baru setelah perubahan hak akses
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Wajib login. Simpan halaman tujuan agar bisa kembali setelah login. */
function require_login(): void
{
    if (!is_logged_in()) {
        $current = ltrim(substr((string) ($_SERVER['REQUEST_URI'] ?? ''), strlen(BASE_URL)), '/');
        flash('warning', 'Silakan masuk terlebih dahulu.');
        redirect('auth/login.php?redirect=' . urlencode($current));
    }
}

/** Wajib memiliki role tertentu; selain itu tampilkan 403. */
function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        http_response_code(403);
        $pageTitle = 'Akses Ditolak';
        require APP_ROOT . '/includes/header.php';
        echo '<div class="container py-5 text-center"><i class="bi bi-shield-lock display-1 text-danger"></i>'
            . '<h1 class="h3 mt-3">403 - Akses Ditolak</h1><p class="text-muted">Anda tidak memiliki izin untuk membuka halaman ini.</p>'
            . '<a href="' . e(url('index.php')) . '" class="btn btn-brand">Kembali ke Beranda</a></div>';
        require APP_ROOT . '/includes/footer.php';
        exit;
    }
}

function require_admin(): void
{
    require_role(ROLE_ADMIN);
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect(is_admin() ? 'admin/index.php' : 'index.php');
    }
}
