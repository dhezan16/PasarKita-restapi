<?php
/** Login pengguna */
require_once __DIR__ . '/../includes/bootstrap.php';
require_guest();

$error = '';
$email = '';
$redirectTo = safe_redirect_path($_GET['redirect'] ?? $_POST['redirect'] ?? '', '');

// Pembatasan percobaan login sederhana per session (anti brute force dasar)
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];

if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($attempts['until'] > time()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($attempts['until'] - time()) . ' detik.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email dan password wajib diisi dengan benar.';
    } else {
        $user = db_fetch_one('SELECT id, name, password, role FROM users WHERE email = ?', 's', [$email]);
        if ($user && password_verify($password, $user['password'])) {
            // Perbarui hash bila algoritma default PHP berubah
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                db_execute('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
            }
            unset($_SESSION['login_attempts']);
            login_user($user); // session_regenerate_id(true) di dalamnya
            flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
            if ($redirectTo !== '' && !($user['role'] !== ROLE_ADMIN && strpos($redirectTo, 'admin/') === 0)) {
                redirect($redirectTo);
            }
            redirect($user['role'] === ROLE_ADMIN ? 'admin/index.php' : 'index.php');
        }
        $attempts['count']++;
        if ($attempts['count'] >= 5) {
            $attempts = ['count' => 0, 'until' => time() + 60];
        }
        $_SESSION['login_attempts'] = $attempts;
        $error = 'Email atau password salah.';
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <div class="auth-wrap mx-auto">
        <div class="text-center mb-4">
            <span class="brand-mark mb-2" style="width:56px;height:56px;font-size:1.6rem"><i class="bi bi-person"></i></span>
            <h1 class="h3 fw-bold">Masuk ke PasarKita</h1>
            <p class="text-muted">Lanjutkan belanja produk tradisional favorit Anda.</p>
        </div>
        <div class="card-soft bg-white p-4">
            <?php if ($error): ?><div class="alert alert-danger small"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
                    <div class="invalid-feedback">Masukkan email yang valid.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    <div class="invalid-feedback">Password wajib diisi.</div>
                </div>
                <button type="submit" class="btn btn-brand w-100 py-2">Masuk</button>
            </form>
        </div>
        <p class="text-center mt-3 text-muted">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>">Daftar sekarang</a></p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
