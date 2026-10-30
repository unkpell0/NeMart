<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

require_login();
$user = current_user();
$userId = (int) $user['id'];

if (!is_post()) {
    redirect('cart.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? '');
$pdo = db();

$cartRow = static function (int $cartId, int $userId) use ($pdo): ?array {
    $stmt = $pdo->prepare('SELECT ci.*, p.name, p.stock, p.status, p.variants FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.id = ? AND ci.user_id = ?');
    $stmt->execute([$cartId, $userId]);

    return $stmt->fetch() ?: null;
};

try {
    if ($action === 'add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $variant = trim((string) ($_POST['variant'] ?? ''));
        $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 160);
        $buyNow = !empty($_POST['buy_now']);

        $stmt = $pdo->prepare("SELECT p.*, s.verified_status FROM products p JOIN stores s ON s.id = p.store_id WHERE p.id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || $product['status'] !== 'active') {
            throw new RuntimeException('Produk ini sudah tidak dijual.');
        }
        if ($product['verified_status'] !== 'verified') {
            throw new RuntimeException('Toko penjual belum terverifikasi, produk belum bisa dipesan.');
        }
        if ((int) $product['stock'] <= 0) {
            throw new RuntimeException('Stok produk ini sedang habis.');
        }

        $variants = product_variants($product);
        if ($variants !== []) {
            if ($variant === '') {
                $variant = $variants[0];
            }
            if (!in_array($variant, $variants, true)) {
                throw new RuntimeException('Varian yang dipilih tidak tersedia.');
            }
        } else {
            $variant = '';
        }

        $qty = min($qty, (int) $product['stock']);

        $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND variant = ?');
        $stmt->execute([$userId, $productId, $variant]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = min((int) $existing['qty'] + $qty, (int) $product['stock']);
            $stmt = $pdo->prepare('UPDATE cart_items SET qty = ?, note = ?, created_at = ? WHERE id = ?');
            $stmt->execute([$newQty, $note !== '' ? $note : $existing['note'], now(), (int) $existing['id']]);
            $cartId = (int) $existing['id'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, variant, note, qty, created_at) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$userId, $productId, $variant, $note, $qty, now()]);
            $cartId = (int) $pdo->lastInsertId();
        }

        if ($buyNow) {
            redirect('checkout.php?ids[]=' . $cartId);
        }

        flash('success', '“' . $product['name'] . '” ditambahkan ke keranjang.');
        redirect('product.php?id=' . $productId);
    }

    if ($action === 'update_qty') {
        $cartId = (int) ($_POST['cart_id'] ?? 0);
        $row = $cartRow($cartId, $userId);
        if (!$row) {
            throw new RuntimeException('Item keranjang tidak ditemukan.');
        }

        if (isset($_POST['delta'])) {
            $qty = (int) $row['qty'] + (int) $_POST['delta'];
        } else {
            $qty = (int) ($_POST['qty'] ?? $row['qty']);
        }
        $qty = max(1, min($qty, max(1, (int) $row['stock'])));

        $stmt = $pdo->prepare('UPDATE cart_items SET qty = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$qty, $cartId, $userId]);

        if ($qty >= (int) $row['stock']) {
            flash('warning', 'Jumlah disesuaikan dengan stok tersisa (' . (int) $row['stock'] . ').');
        }
        redirect('cart.php');
    }

    if ($action === 'delete') {
        $cartId = (int) ($_POST['cart_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$cartId, $userId]);
        flash('info', 'Item dihapus dari keranjang.');
        redirect('cart.php');
    }

    if ($action === 'clear') {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);
        flash('info', 'Keranjang dikosongkan.');
        redirect('cart.php');
    }

    throw new RuntimeException('Aksi keranjang tidak dikenal.');
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
    redirect('cart.php');
}
