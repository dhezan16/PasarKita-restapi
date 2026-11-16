<?php
/**
 * Bootstrap REST API PasarKita.
 *
 * Memakai ulang konfigurasi (config/config.php), koneksi MySQLi (includes/db.php),
 * autentikasi session (includes/auth.php) dan logika transaksi (includes/order.php)
 * milik aplikasi web SOAL 11 — tidak ada koneksi database kedua.
 *
 * Perbedaannya dengan includes/bootstrap.php: semua error dibalas dalam JSON
 * dan warning/notice PHP tidak pernah ditampilkan.
 */

define('PASARKITA_API', true);
define('APP_ROOT', dirname(__DIR__, 2));

// Tangkap output liar (warning, spasi) agar response selalu JSON murni
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/format.php';

// Warning/notice diubah menjadi exception -> dibalas 500 JSON, detail hanya ke log server
set_error_handler(function (int $no, string $str, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $no)) {
        return false; // dibungkam dengan operator @
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(function (Throwable $e): void {
    if ($e instanceof DatabaseUnavailableException) {
        api_error(500, 'Tidak dapat terhubung ke database.');
    }
    error_log('[PasarKita API] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    api_error(500, 'Terjadi kesalahan pada server.');
});

// Fatal error (tidak bisa ditangkap exception handler)
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[PasarKita API] Fatal: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        api_error(500, 'Terjadi kesalahan pada server.');
    }
});

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    api_error(500, 'Konfigurasi server belum lengkap (config/config.php tidak ditemukan).');
}
$GLOBALS['config'] = require $configFile;
date_default_timezone_set($GLOBALS['config']['app']['timezone'] ?? 'Asia/Jakarta');

require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/order.php';

define('BASE_URL', detect_base_url());

// Session yang sama dengan aplikasi web (cookie PASARKITASESSID)
start_app_session();
