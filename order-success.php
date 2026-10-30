<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$code = trim((string) ($_GET['code'] ?? ''));
$order = $code !== '' ? order_by_code($code) : null;

if (!$order || (int) $order['buyer_id'] !== (int) $user['id']) {
    http_response_code(404);
    $error_code = '404';
    $error_title = 'Pesanan tidak ditemukan';
    $error_message = 'Kode pesanan tidak cocok dengan akun ini. Buka halaman Pesanan Saya untuk melihat daftar pesananmu.';
    require __DIR__ . '/includes/error-page.php';
    exit;
}

$orders = orders_by_payment_ref((string) $order['payment_ref'], (int) $user['id']);
$grandTotal = 0;
$totalItems = 0;
foreach ($orders as $row) {
    $grandTotal += (int) $row['total'];
    $totalItems += (int) $row['item_qty'];
}
$firstOrderId = (int) ($orders[0]['id'] ?? $order['id']);

$page_title = 'Pesanan Telah Dipesan';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <div class="success-wrap">
            <div class="success-card">
                <div class="success-icon"><?= icon('check', 42) ?></div>
                <h1 class="success-title">Pesanan Telah Dipesan</h1>
                <p class="success-sub">
                    Pembayaran simulasi berhasil pada <?= e(tanggal_lengkap(now())) ?>. Pesananmu sudah diteruskan ke penjual dan akan muncul di Seller Centre mereka.
                </p>

                <div class="success-codes">
                    <?php foreach ($orders as $row): ?>
                        <span class="code-chip"><?= icon('box', 13) ?><?= e($row['code']) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="success-orders">
                    <?php foreach ($orders as $row): ?>
                        <div class="success-order">
                            <div class="success-order__head">
                                <span class="success-order__store"><?= icon('store', 15) ?><?= e($row['store_name']) ?></span>
                                <span class="badge badge--warning"><?= icon('clock', 12) ?>Menunggu penjual</span>
                            </div>
                            <?php foreach ($row['items'] as $item): ?>
                                <div class="success-order__row">
                                    <span><?= (int) $item['qty'] ?> × <?= e($item['name_snapshot']) ?><?= $item['variant'] !== '' ? ' · ' . e($item['variant']) : '' ?></span>
                                    <strong><?= rupiah((int) $item['price_snapshot'] * (int) $item['qty']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <div class="success-order__row success-order__total">
                                <span>Total pesanan (termasuk layanan <?= rupiah((int) $row['service_fee']) ?><?= (int) $row['shipping_fee'] > 0 ? ' + antar ' . rupiah((int) $row['shipping_fee']) : '' ?>)</span>
                                <strong><?= rupiah((int) $row['total']) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="success-note">
                    <?= icon('shield', 20) ?>
                    <div>
                        <strong>Dana ditahan escrow hingga kamu konfirmasi pesanan diterima.</strong>
                        Setelah penjual menandai pesanan siap, buka Pesanan Saya lalu tekan Konfirmasi Diterima. Dana baru diteruskan ke penjual setelah itu.
                    </div>
                </div>

                <div class="success-actions">
                    <a class="btn btn--primary" href="<?= e(url('orders.php?highlight=' . $firstOrderId)) ?>"><?= icon('box', 17) ?>Lacak Pesanan</a>
                    <a class="btn btn--outline" href="<?= e(url('search.php')) ?>"><?= icon('cart', 17) ?>Lanjut Belanja</a>
                </div>

                <p class="small muted" style="margin-top:16px">
                    <?= number_format($totalItems, 0, ',', '.') ?> item dari <?= count($orders) ?> toko · Total <?= rupiah($grandTotal) ?> · Metode <?= e((string) $orders[0]['payment_method']) ?>
                </p>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
