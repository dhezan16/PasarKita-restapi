<?php
/** Logout: hanya melalui POST + token CSRF */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();
logout_user();

// Mulai session baru agar flash message bisa ditampilkan
session_start();
session_regenerate_id(true);
flash('success', 'Anda telah keluar. Sampai jumpa lagi!');
redirect('index.php');
