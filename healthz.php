<?php
/** Health check untuk platform deploy (Render/Railway) dan monitoring. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $payload = [
        'status'   => 'ok',
        'app'      => 'NeMart',
        'time'     => now(),
        'products' => (int) db()->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
        'stores'   => (int) db()->query("SELECT COUNT(*) FROM stores WHERE verified_status = 'verified'")->fetchColumn(),
    ];
    http_response_code(200);
} catch (Throwable $e) {
    $payload = ['status' => 'error', 'message' => 'Database belum siap'];
    http_response_code(503);
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
