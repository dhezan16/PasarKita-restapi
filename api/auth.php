<?php
/**
 * Autentikasi API memakai akun & session yang SAMA dengan aplikasi web SOAL 11.
 *
 * POST   /api/auth.php   {"email": "...", "password": "..."}  -> login (cookie PASARKITASESSID)
 * GET    /api/auth.php   -> data pengguna yang sedang login (401 bila belum)
 * DELETE /api/auth.php   -> logout
 *
 * Postman menyimpan cookie session secara otomatis, sehingga request berikutnya
 * (mis. POST /api/pesanan.php) dikenali sebagai pengguna tersebut.
 */
require_once __DIR__ . '/helpers/bootstrap.php';

$method = api_allow_methods(['GET', 'POST', 'DELETE']);

function api_format_user(array $u): array
{
    return ['id' => (int) $u['id'], 'nama' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
}

if ($method === 'GET') {
    $user = api_require_user();
    api_success(api_format_user($user), 'Anda login sebagai ' . $user['name']);
}

if ($method === 'DELETE') {
    $wasLoggedIn = is_logged_in();
    logout_user();
    api_success(null, $wasLoggedIn ? 'Logout berhasil' : 'Tidak ada sesi login yang aktif');
}

/* ---------------- POST: login ---------------- */
$body = api_json_body();
$email = strtolower(trim(is_string($body['email'] ?? null) ? $body['email'] : ''));
$password = is_string($body['password'] ?? null) ? $body['password'] : '';

// Pembatasan percobaan sama seperti halaman login web (5x gagal -> tunggu 60 detik)
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];
if ($attempts['until'] > time()) {
    header('Retry-After: ' . ($attempts['until'] - time()));
    api_error(429, 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($attempts['until'] - time()) . ' detik.');
}

$errors = [];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Email wajib diisi dengan format yang benar.';
}
if ($password === '') {
    $errors['password'] = 'Password wajib diisi.';
}
if ($errors) {
    api_error(422, 'Data login tidak valid.', $errors);
}

$user = db_fetch_one('SELECT id, name, email, password, role FROM users WHERE email = ?', 's', [$email]);
if (!$user || !password_verify($password, $user['password'])) {
    $attempts['count']++;
    if ($attempts['count'] >= 5) {
        $attempts = ['count' => 0, 'until' => time() + 60];
    }
    $_SESSION['login_attempts'] = $attempts;
    api_error(401, 'Email atau password salah.');
}

if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    db_execute('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
}
unset($_SESSION['login_attempts']);
login_user($user); // session_regenerate_id(true), sama seperti web

api_success(api_format_user($user), 'Login berhasil');
