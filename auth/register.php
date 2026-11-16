<?php
/** Registrasi akun pembeli */
require_once __DIR__ . '/../includes/bootstrap.php';
require_guest();

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if (is_post()) {
    verify_csrf();

    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['address'] = trim((string) ($_POST['address'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirmation'] ?? '');

    // Validasi server-side
    if (mb_strlen($old['name']) < 3 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Nama wajib diisi (3-100 karakter).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors['email'] = 'Format email tidak valid.';
    }
    if ($old['phone'] !== '' && !preg_match('/^(\+62|62|0)[0-9]{8,13}$/', $old['phone'])) {
        $errors['phone'] = 'Nomor HP tidak valid. Contoh: 081234567890.';
    }
    if (mb_strlen($old['address']) > 500) {
        $errors['address'] = 'Alamat maksimal 500 karakter.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'Password minimal 8 karakter (maksimal 72).';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Password harus mengandung huruf dan angka.';
    }
    if ($password !== $confirm) {
        $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
    }

    if (!isset($errors['email']) && db_fetch_value('SELECT COUNT(*) FROM users WHERE email = ?', 's', [$old['email']]) > 0) {
        $errors['email'] = 'Email sudah terdaftar. Silakan masuk.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_execute(
            'INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)',
            'ssssss',
            [$old['name'], $old['email'], $hash, $old['phone'] ?: null, $old['address'] ?: null, ROLE_BUYER]
        );
        $user = db_fetch_one('SELECT id, name, role FROM users WHERE id = ?', 'i', [db_last_id()]);
        login_user($user);
        flash('success', 'Registrasi berhasil. Selamat datang di PasarKita, ' . $user['name'] . '!');
        redirect('index.php');
    }
}

$pageTitle = 'Daftar Akun';
require __DIR__ . '/../includes/header.php';

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
}
?>
<div class="container py-5">
    <div class="auth-wrap mx-auto">
        <div class="text-center mb-4">
            <span class="brand-mark mb-2" style="width:56px;height:56px;font-size:1.6rem"><i class="bi bi-person-plus"></i></span>
            <h1 class="h3 fw-bold">Buat Akun Pembeli</h1>
            <p class="text-muted">Daftar untuk mulai berbelanja produk tradisional.</p>
        </div>
        <div class="card-soft bg-white p-4">
            <?php if ($errors): ?><div class="alert alert-danger small">Periksa kembali isian formulir.</div><?php endif; ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="name" class="form-label">Nama lengkap</label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e($old['name']) ?>" required maxlength="100">
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150">
                    <?= field_error($errors, 'email') ?>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Nomor HP <span class="text-muted small">(opsional)</span></label>
                    <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($old['phone']) ?>" maxlength="20" placeholder="081234567890">
                    <?= field_error($errors, 'phone') ?>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat <span class="text-muted small">(opsional)</span></label>
                    <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="2" maxlength="500"><?= e($old['address']) ?></textarea>
                    <?= field_error($errors, 'address') ?>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                        <?= field_error($errors, 'password') ?>
                    </div>
                    <div class="col-sm-6">
                        <label for="password_confirmation" class="form-label">Ulangi password</label>
                        <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        <?= field_error($errors, 'password_confirmation') ?>
                    </div>
                </div>
                <div class="form-text mb-3">Minimal 8 karakter, kombinasi huruf dan angka.</div>
                <button type="submit" class="btn btn-brand w-100 py-2">Daftar</button>
            </form>
        </div>
        <p class="text-center mt-3 text-muted">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>">Masuk di sini</a></p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
