<?php
/**
 * Bootstrap aplikasi: dimuat pertama kali oleh setiap halaman.
 * Menyiapkan konfigurasi, error handling, session, koneksi database dan helper.
 */

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Konfigurasi belum dibuat. Salin config/config.example.php menjadi config/config.php lalu isi kredensial database.');
}
$GLOBALS['config'] = require $configFile;

$debug = !empty($GLOBALS['config']['app']['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set($GLOBALS['config']['app']['timezone'] ?? 'Asia/Jakarta');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

define('BASE_URL', detect_base_url());

// Header keamanan dasar
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Session yang aman (dipakai bersama oleh halaman web dan REST API)
start_app_session();

// Tangani exception yang tidak tertangkap: jangan bocorkan detail di produksi.
set_exception_handler(function (Throwable $e) use ($debug) {
    error_log('[PasarKita] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!doctype html><meta charset="utf-8"><title>Terjadi Kesalahan</title>';
    echo '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:24px;border:1px solid #eee;border-radius:12px">';
    echo '<h2>Maaf, terjadi kesalahan pada server.</h2><p>Silakan coba beberapa saat lagi.</p>';
    if ($debug) {
        echo '<pre style="white-space:pre-wrap;background:#f7f7f7;padding:12px">' . e($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>';
    }
    echo '</div>';
});
