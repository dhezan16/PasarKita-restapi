<?php
/**
 * Koneksi database (MySQLi) dan helper query berbasis prepared statement.
 */

/** Dilempar saat koneksi database gagal dalam mode REST API. */
class DatabaseUnavailableException extends RuntimeException
{
}

/**
 * Mengembalikan koneksi mysqli tunggal (singleton).
 */
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $cfg = $GLOBALS['config']['db'];
    // Lempar exception untuk setiap error MySQLi agar mudah di-rollback.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // trim(): spasi tak sengaja saat copy-paste dari panel hosting membuat hostname gagal di-resolve
        $conn = @new mysqli(trim($cfg['host']), trim($cfg['username']), $cfg['password'], trim($cfg['database']), (int) ($cfg['port'] ?? 3306));
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('[PasarKita] Koneksi database gagal: ' . $e->getMessage());
        if (defined('PASARKITA_API')) {
            // REST API: biarkan handler JSON di api/bootstrap.php yang membalas (tanpa detail sensitif)
            throw new DatabaseUnavailableException('Koneksi database gagal.');
        }
        http_response_code(500);
        $msg = !empty($GLOBALS['config']['app']['debug']) ? ' (' . e($e->getMessage()) . ')' : '';
        exit('<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;max-width:600px;margin:60px auto">'
            . '<h2>Tidak dapat terhubung ke database.</h2><p>Periksa pengaturan pada config/config.php' . $msg . '</p></div>');
    }

    return $conn;
}

/**
 * Menyiapkan, mengikat parameter, dan mengeksekusi query.
 *
 * @param string $types  string tipe bind_param, mis. "isd"
 * @param array  $params nilai parameter sesuai urutan placeholder "?"
 */
function db_query(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** Ambil semua baris sebagai array asosiatif. */
function db_fetch_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($sql, $types, $params);
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Ambil satu baris atau null. */
function db_fetch_one(string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_query($sql, $types, $params);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Ambil satu nilai (kolom pertama baris pertama). */
function db_fetch_value(string $sql, string $types = '', array $params = [])
{
    $row = db_fetch_one($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** Eksekusi INSERT/UPDATE/DELETE dan kembalikan jumlah baris terpengaruh. */
function db_execute(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_query($sql, $types, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/** ID terakhir dari INSERT. */
function db_last_id(): int
{
    return (int) db()->insert_id;
}
