<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

if (!is_post()) {
    redirect('orders.php');
}
require_csrf();

$user = current_user();
$userId = (int) $user['id'];
$action = (string) ($_POST['action'] ?? '');
$orderId = (int) ($_POST['order_id'] ?? 0);

switch ($action) {
    case 'confirm_received':
        $result = change_order_status($orderId, 'completed', $user);
        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('orders.php?status=completed&highlight=' . $orderId);

    case 'cancel':
        $result = change_order_status($orderId, 'cancelled', $user, 'Dibatalkan oleh pembeli sebelum pesanan diproses.');
        flash($result['ok'] ? 'info' : 'danger', $result['message']);
        redirect('orders.php?highlight=' . $orderId);

    case 'submit_review':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($comment === '') {
            $comment = 'Produk sesuai deskripsi dan pelayanan penjual baik.';
        }
        $result = submit_review($orderId, $productId, $userId, $rating, $comment);
        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('orders.php?status=completed&highlight=' . $orderId);

    case 'buy_again':
        $order = order_by_id($orderId);
        if (!$order || (int) $order['buyer_id'] !== $userId) {
            flash('danger', 'Pesanan tidak ditemukan pada akun ini.');
            redirect('orders.php');
        }
        $pdo = db();
        $added = 0;
        $skipped = 0;
        $pdo->beginTransaction();
        try {
            foreach (order_items($orderId) as $item) {
                $stmt = $pdo->prepare('SELECT p.*, s.verified_status FROM products p JOIN stores s ON s.id = p.store_id WHERE p.id = ?');
                $stmt->execute([(int) $item['product_id']]);
                $product = $stmt->fetch();

                if (!$product || $product['status'] !== 'active' || $product['verified_status'] !== 'verified' || (int) $product['stock'] <= 0) {
                    $skipped++;
                    continue;
                }

                $variants = product_variants($product);
                $variant = (string) $item['variant'];
                if ($variants !== [] && !in_array($variant, $variants, true)) {
                    $variant = $variants[0];
                }
                if ($variants === []) {
                    $variant = '';
                }

                $qty = max(1, min((int) $item['qty'], (int) $product['stock']));
                $stmt = $pdo->prepare('SELECT id, qty FROM cart_items WHERE user_id = ? AND product_id = ? AND variant = ?');
                $stmt->execute([$userId, (int) $product['id'], $variant]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare('UPDATE cart_items SET qty = ? WHERE id = ?');
                    $stmt->execute([min((int) $existing['qty'] + $qty, (int) $product['stock']), (int) $existing['id']]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, variant, note, qty, created_at) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$userId, (int) $product['id'], $variant, '', $qty, now()]);
                }
                $added++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Gagal menambahkan ulang produk: ' . $e->getMessage());
            redirect('orders.php');
        }

        if ($added > 0) {
            flash('success', $added . ' produk dari pesanan ' . $order['code'] . ' ditambahkan ke keranjang.' . ($skipped > 0 ? ' ' . $skipped . ' produk dilewati karena stok habis.' : ''));
            redirect('cart.php');
        }

        flash('warning', 'Produk pada pesanan ini sedang tidak tersedia. Coba produk lain, ya.');
        redirect('orders.php');

    default:
        flash('danger', 'Aksi pesanan tidak dikenal.');
        redirect('orders.php');
}
