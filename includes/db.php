<?php
declare(strict_types=1);

/**
 * Koneksi PDO SQLite tunggal untuk seluruh aplikasi.
 * Data disimpan di data/nemart.sqlite sehingga semua sesi & peran melihat data yang sama.
 */
function db_path(): string
{
    return dirname(__DIR__) . '/data/nemart.sqlite';
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = db_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder data tidak bisa dibuat: ' . $dir);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    /* Waktu "sekarang" selalu berasal dari PHP (Asia/Jakarta) agar konsisten
       dengan nilai TEXT yang disimpan, termasuk saat container berjalan di UTC. */
    $pdo->sqliteCreateFunction('now_local', static fn (): string => date('Y-m-d H:i:s'), 0);

    try {
        $pdo->exec('PRAGMA journal_mode = WAL');
    } catch (Throwable) {
        // Mode WAL tidak wajib; abaikan bila filesystem tidak mendukung.
    }

    return $pdo;
}
