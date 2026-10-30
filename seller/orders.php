<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

$status = (string) ($_GET['status'] ?? 'all');
if (!isset(order_status_map()[$status])) {
    $status = 'all';
}

$counts = ['all' => 0];
foreach (order_status_map() as $key => $meta) {
    $counts[$key] = 0;
}
$stmt = db()->prepare('SELECT status, COUNT(*) AS total FROM orders WHERE store_id = ? GROUP BY status');
$stmt->execute([$storeId]);
foreach ($stmt->fetchAll() as $row) {
    $counts[(string) $row['status']] = (int) $row['total'];
    $counts['all'] += (int) $row['total'];
}

$tabs = [
    'all'        => ['label' => 'Semua', 'count' => $counts['all']],
    'placed'     => ['label' => 'Baru', 'count' => $counts['placed']],
    'processing' => ['label' => 'Diproses', 'count' => $counts['processing']],
    'ready'      => ['label' => 'Siap', 'count' => $counts['ready']],
    'completed'  => ['label' => 'Selesai', 'count' => $counts['completed']],
    'cancelled'  => ['label' => 'Dibatalkan', 'count' => $counts['cancelled']],
];

$orders = seller_orders($storeId, $status);

$page_title = 'Pesanan Masuk';
$active_nav = 'orders';
$shell_title = 'Pesanan Masuk';
$shell_subtitle = 'Terima, proses, dan selesaikan pesanan pembeli';
require __DIR__ . '/../includes/seller-layout.php';
?>

<?= tabs_nav($tabs, $status, url('seller/orders.php')) ?>

<?php if ($orders === []): ?>
    <?= empty_state(
        'box',
        $status === 'all' ? 'Belum ada pesanan masuk' : 'Tidak ada pesanan di tab ini',
        'Pesanan baru dari pembeli akan muncul di sini secara otomatis setelah pembayaran simulasi berhasil.',
        '<a class="btn btn--outline" href="' . e(url('seller/products.php')) . '">' . icon('tag', 16) . 'Kelola Produk</a>'
    ) ?>
<?php else: ?>
    <div class="order-list">
        <?php foreach ($orders as $order): ?>
            <article class="seller-order">
                <div class="seller-order__head">
                    <div class="seller-order__buyer">
                        <?= avatar((string) $order['buyer_name'], (string) $order['buyer_initials'], 'avatar--sm') ?>
                        <div>
                            <div class="seller-order__buyer-name"><?= e($order['buyer_name']) ?></div>
                            <div class="seller-order__buyer-sub"><?= e($order['code']) ?> · <?= e(tanggal((string) $order['created_at'], true)) ?></div>
                        </div>
                    </div>
                    <span class="muted small" style="margin-left:auto"><?= e(time_ago((string) $order['created_at'])) ?></span>
                    <?= status_badge((string) $order['status'], (string) $order['handover_method']) ?>
                </div>

                <div class="seller-order__body">
                    <div>
                        <div class="order-card__items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-line">
                                    <?= product_thumb($item, '', 20) ?>
                                    <div>
                                        <div class="order-line__name"><?= e($item['name_snapshot']) ?></div>
                                        <div class="order-line__meta">
                                            <?= (int) $item['qty'] ?> × <?= rupiah((int) $item['price_snapshot']) ?>
                                            <?php if ((string) $item['variant'] !== ''): ?> · <?= e($item['variant']) ?><?php endif; ?>
                                        </div>
                                        <?php if ((string) $item['note'] !== ''): ?>
                                            <div class="cart-row__note">Catatan pembeli: “<?= e($item['note']) ?>”</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-line__price"><?= rupiah((int) $item['price_snapshot'] * (int) $item['qty']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-card__totals">
                            <span>Subtotal <?= rupiah((int) $order['subtotal']) ?> · layanan <?= rupiah((int) $order['service_fee']) ?> · <?= (int) $order['shipping_fee'] > 0 ? 'antar ' . rupiah((int) $order['shipping_fee']) : 'ambil sendiri' ?></span>
                            <strong><?= rupiah((int) $order['total']) ?></strong>
                        </div>
                    </div>

                    <div class="seller-order__info">
                        <div class="seller-order__info-row">
                            <?= icon((string) ($order['handover_method'] === 'delivery' ? 'truck' : 'map-pin'), 15) ?>
                            <div>
                                <strong><?= $order['handover_method'] === 'delivery' ? 'Diantar mitra kampus' : 'Ambil di titik kampus' ?></strong>
                                <?= e((string) $order['pickup_point']) ?>
                                <?php if (!empty($order['delivery_notes'])): ?>
                                    <div style="margin-top:3px">Catatan lokasi: <?= e((string) $order['delivery_notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="seller-order__info-row">
                            <?= icon('wallet', 15) ?>
                            <div>
                                <strong>Pembayaran <?= e((string) $order['payment_method']) ?></strong>
                                <?= $order['escrow_status'] === 'held' ? 'Dana ditahan escrow' : ($order['escrow_status'] === 'released' ? 'Escrow dilepas ke tokomu' : 'Dana dikembalikan') ?>
                            </div>
                        </div>
                        <div class="seller-order__info-row">
                            <?= icon('clock', 15) ?>
                            <div>
                                <strong>Riwayat status</strong>
                                <?php $lastEvent = $order['events'][count($order['events']) - 1] ?? null; ?>
                                <?= $lastEvent ? e($lastEvent['title']) . ' · ' . e(time_ago((string) $lastEvent['created_at'])) : 'Belum ada riwayat' ?>
                            </div>
                        </div>
                        <?php if ($order['status'] === 'completed'): ?>
                            <div class="seller-order__info-row">
                                <?= icon('chart', 15) ?>
                                <div>
                                    <strong>Komisi platform <?= rupiah((int) $order['commission']) ?></strong>
                                    Dihitung <?= store_is_pro($store) ? '3%' : '5%' ?> dari subtotal saat pesanan selesai.
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                            <div class="seller-order__info-row">
                                <?= icon('x', 15) ?>
                                <div>
                                    <strong>Alasan pembatalan</strong>
                                    <?= e((string) $order['cancel_reason']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="seller-order__foot">
                    <span class="order-card__foot-note"><?= icon('shield', 13) ?><?= $order['escrow_status'] === 'held' ? 'Dana aman di escrow' : ($order['escrow_status'] === 'released' ? 'Dana sudah diteruskan' : 'Dana dikembalikan') ?></span>
                    <div class="seller-order__actions">
                        <?php if ($order['status'] === 'placed'): ?>
                            <button type="button" class="btn btn--danger-outline btn--sm" data-cancel-open data-order-id="<?= (int) $order['id'] ?>" data-order-code="<?= e($order['code']) ?>" data-cancel-action="cancel"><?= icon('x', 14) ?>Tolak</button>
                            <form method="post" action="<?= e(url('seller/order-action.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <button type="submit" class="btn btn--primary btn--sm"><?= icon('check', 14) ?>Terima &amp; Proses</button>
                            </form>
                        <?php elseif ($order['status'] === 'processing'): ?>
                            <button type="button" class="btn btn--danger-outline btn--sm" data-cancel-open data-order-id="<?= (int) $order['id'] ?>" data-order-code="<?= e($order['code']) ?>" data-cancel-action="cancel"><?= icon('x', 14) ?>Batalkan</button>
                            <form method="post" action="<?= e(url('seller/order-action.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="ready">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <button type="submit" class="btn btn--primary btn--sm"><?= icon('check-circle', 14) ?>Tandai <?= $order['handover_method'] === 'delivery' ? 'Siap Diantar' : 'Siap Diambil' ?></button>
                            </form>
                        <?php elseif ($order['status'] === 'ready'): ?>
                            <span class="small muted"><?= icon('clock', 13) ?> Menunggu pembeli menekan Konfirmasi Diterima.</span>
                        <?php elseif ($order['status'] === 'completed'): ?>
                            <span class="small muted"><?= icon('check-circle', 13) ?> Selesai pada <?= e(tanggal((string) $order['completed_at'], true)) ?>.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<dialog class="modal" id="cancelDialog" aria-labelledby="cancelTitle">
    <form method="post" action="<?= e(url('seller/order-action.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="cancelAction" value="cancel">
        <input type="hidden" name="order_id" id="cancelOrderId" value="">
        <div class="modal__head">
            <div>
                <h2 class="modal__title" id="cancelTitle">Batalkan pesanan</h2>
                <p class="modal__sub">Pesanan <span id="cancelOrderCode">—</span></p>
            </div>
            <button type="button" class="modal__close" data-dialog-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="modal__body">
            <div class="notice notice--warning" style="margin-bottom:14px">
                <?= icon('alert', 16) ?>
                <span>Stok produk akan dikembalikan dan dana pembeli dikembalikan (simulasi escrow refund). Pembeli menerima notifikasi berisi alasan ini.</span>
            </div>
            <div class="field">
                <label class="label" for="cancelReason">Alasan pembatalan <span style="color:var(--danger)">*</span></label>
                <textarea class="textarea" id="cancelReason" name="reason" required maxlength="200" rows="3" placeholder="Contoh: stok habis, dapur tutup lebih awal, atau di luar jam operasional."></textarea>
            </div>
        </div>
        <div class="modal__foot">
            <button type="button" class="btn btn--ghost" data-dialog-close>Kembali</button>
            <button type="submit" class="btn btn--danger"><?= icon('x', 15) ?>Batalkan Pesanan</button>
        </div>
    </form>
</dialog>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>
