<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();

if (!is_post()) {
    redirect('seller/orders.php');
}
require_csrf();

$user = current_user();
$orderId = (int) ($_POST['order_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$reason = trim((string) ($_POST['reason'] ?? ''));
$order = order_by_id($orderId);

if (!$order || (int) $order['store_id'] !== (int) $store['id']) {
    flash('danger', 'Pesanan tidak ditemukan di toko ini.');
    redirect('seller/orders.php');
}

$targets = ['accept' => 'processing', 'ready' => 'ready', 'cancel' => 'cancelled'];
if (!isset($targets[$action])) {
    flash('danger', 'Aksi pesanan tidak dikenal.');
    redirect('seller/orders.php');
}

if ($action === 'cancel' && $reason === '') {
    flash('warning', 'Tulis alasan pembatalan agar pembeli tahu penyebabnya.');
    redirect('seller/orders.php?status=' . $order['status']);
}

$note = $action === 'cancel'
    ? $reason
    : ($action === 'accept' ? 'Penjual menerima pesanan dan mulai menyiapkan.' : '');
$result = change_order_status($orderId, $targets[$action], $user, $note);
flash($result['ok'] ? 'success' : 'danger', $result['message']);

redirect('seller/orders.php?status=' . $targets[$action]);
