<?php
/**
 * Central, "professional" runtime config: timezone, safe error handling,
 * and a small DB-backed settings store the admin can edit without touching
 * code. Required once from config/db.php, so $pdo is already available.
 */

date_default_timezone_set('Asia/Dhaka');

// Never show raw PHP errors/warnings to visitors — log them to a file instead.
$logDir = __DIR__ . '/../data/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/app.log');

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    skey TEXT PRIMARY KEY,
    svalue TEXT
)");

/**
 * Reads a setting from the DB, falling back to $default if unset.
 * Cached per-request so repeated calls don't hit the DB again.
 */
function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $pdo->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    $cache[$key] = ($val !== false) ? $val : $default;
    return $cache[$key];
}

function set_setting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (?, ?)
         ON CONFLICT(skey) DO UPDATE SET svalue = excluded.svalue'
    )->execute([$key, $value]);
}
