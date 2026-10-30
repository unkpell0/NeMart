<?php
declare(strict_types=1);

/**
 * Titik masuk semua halaman: konfigurasi, sesi, koneksi database, seed otomatis.
 */

date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/data/php-error.log');

define('APP_NAME', 'NeMart');
define('APP_TAGLINE', 'Connecting Student Businesses, One Click at a Time');

/* Deteksi base URL agar aplikasi bisa berjalan di root domain maupun subfolder. */
$docRootRaw = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
$docRoot = $docRootRaw !== '' ? (string) realpath($docRootRaw) : '';
$docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
$appDir = rtrim(str_replace('\\', '/', (string) realpath(dirname(__DIR__))), '/');
$appBase = '';
if ($docRoot !== '' && $appDir !== $docRoot && str_starts_with($appDir, $docRoot . '/')) {
    $appBase = substr($appDir, strlen($docRoot));
}
define('APP_BASE', $appBase);

if (session_status() !== PHP_SESSION_ACTIVE) {
    /* Simpan sesi di dalam folder aplikasi agar tidak bergantung konfigurasi host. */
    $sessionDir = dirname(__DIR__) . '/data/sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0775, true);
    }
    if (is_dir($sessionDir)) {
        session_save_path($sessionDir);
    }

    session_name('NEMART_SESSION');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../db/seed.php';
require_once __DIR__ . '/repo-products.php';
require_once __DIR__ . '/repo-orders.php';
require_once __DIR__ . '/repo-notif.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ui.php';

/* Database selalu siap dipakai: skema + seed dijalankan otomatis saat pertama kali.
   Kunci berkas mencegah dua request pertama melakukan seed bersamaan. */
$seedLock = @fopen(dirname(__DIR__) . '/data/.seed.lock', 'c');
if ($seedLock !== false) {
    @flock($seedLock, LOCK_EX);
}
nemart_seed(db());
if ($seedLock !== false) {
    @flock($seedLock, LOCK_UN);
    @fclose($seedLock);
}
