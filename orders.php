<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];
$status = (string) ($_GET['status'] ?? 'all');
if (!isset(order_status_map()[$status])) {
    $status = 'all';
}
$highlight = (int) ($_GET['highlight'] ?? 0);

$counts = ['all' => 0];
foreach (order_status_map() as $key => $meta) {
    $counts[$key] = 0;
}
$stmt = db()->prepare('SELECT status, COUNT(*) AS total FROM orders WHERE buyer_id = ? GROUP BY status');
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $counts[(string) $row['status']] = (int) $row['total'];
    $counts['all'] += (int) $row['total'];
}

$tabs = [
    'all'        => ['label' => 'Semua', 'count' => $counts['all']],
    'placed'     => ['label' => 'Dipesan', 'count' => $counts['placed']],
    'processing' => ['label' => 'Diproses', 'count' => $counts['processing']],
    'ready'      => ['label' => 'Siap', 'count' => $counts['ready']],
    'completed'  => ['label' => 'Selesai', 'count' => $counts['completed']],
    'cancelled'  => ['label' => 'Dibatalkan', 'count' => $counts['cancelled']],
];

$orders = buyer_orders($userId, $status);

$page_title = 'Pesanan Saya';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb(['Beranda' => url('index.php'), 'Pesanan Saya']) ?>

        <div class="page-head">
            <div>
                <h1 class="page-title">Pesanan Saya</h1>
                <p class="page-sub">Pantau status pesanan, konfirmasi penerimaan, dan beri ulasan.</p>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= e(url('notifications.php')) ?>"><?= icon('bell', 15) ?>Notifikasi</a>
        </div>

        <?= tabs_nav($tabs, $status, url('orders.php')) ?>

        <?php if ($orders === []): ?>
            <?= empty_state(
                'box',
                $status === 'all' ? 'Belum ada pesanan' : 'Tidak ada pesanan di tab ini',
                'Pesanan yang kamu buat akan muncul di sini lengkap dengan timeline status dari penjual.',
                '<a class="btn btn--primary" href="' . e(url('search.php')) . '">' . icon('search', 17) . 'Cari Produk</a>'
            ) ?>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <article class="order-card<?= $highlight === (int) $order['id'] ? ' is-highlight' : '' ?>" id="order-<?= (int) $order['id'] ?>">
                        <div class="order-card__head">
                            <span class="order-card__store"><?= icon('store', 16) ?><a href="<?= e(url('store.php?id=' . (int) $order['store_id'])) ?>"><?= e($order['store_name']) ?></a></span>
                            <span class="order-card__code"><?= e($order['code']) ?></span>
                            <span class="order-card__date"><?= e(tanggal((string) $order['created_at'], true)) ?></span>
                            <span class="order-card__status"><?= status_badge((string) $order['status'], (string) $order['handover_method']) ?></span>
                        </div>

                        <div class="order-card__body">
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
                                                    <?php if ((string) $item['note'] !== ''): ?> · Catatan: <?= e($item['note']) ?><?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="order-line__price"><?= rupiah((int) $item['price_snapshot'] * (int) $item['qty']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php
                                    $unreviewed = array_values(array_filter($order['items'], static fn (array $item): bool => !isset($order['reviewed'][(int) $item['product_id']])));
                                    ?>
                                    <?php if ($unreviewed !== []): ?>
                                        <div class="btn-row" style="margin-top:12px">
                                            <?php foreach ($unreviewed as $item): ?>
                                                <button type="button" class="btn btn--yellow btn--sm" data-review-open
                                                        data-order-id="<?= (int) $order['id'] ?>"
                                                        data-product-id="<?= (int) $item['product_id'] ?>"
                                                        data-product-name="<?= e($item['name_snapshot']) ?>">
                                                    <?= icon('star', 14) ?>Beri Ulasan: <?= e($item['name_snapshot']) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="small muted" style="margin-top:10px"><?= icon('check-circle', 13) ?> Semua produk pada pesanan ini sudah diulas. Terima kasih!</p>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="order-card__totals">
                                    <span>Subtotal <?= rupiah((int) $order['subtotal']) ?> · layanan <?= rupiah((int) $order['service_fee']) ?> · <?= $order['handover_method'] === 'delivery' ? 'antar ' . rupiah((int) $order['shipping_fee']) : 'ambil sendiri' ?></span>
                                    <strong><?= rupiah((int) $order['total']) ?></strong>
                                </div>
                            </div>

                            <div class="order-card__timeline">
                                <div class="order-card__timeline-title"><?= icon('clock', 12) ?>Timeline pesanan</div>
                                <div class="timeline timeline--compact">
                                    <?php foreach ($order['events'] as $index => $event): ?>
                                        <?php $isLast = $index === count($order['events']) - 1; ?>
                                        <div class="timeline__item <?= $isLast ? 'is-active' : 'is-done' ?>">
                                            <span class="timeline__dot"></span>
                                            <div class="timeline__title"><?= e($event['title']) ?></div>
                                            <div class="timeline__time"><?= e(tanggal((string) $event['created_at'], true)) ?></div>
                                            <?php if (!empty($event['note'])): ?>
                                                <div class="timeline__note"><?= e((string) $event['note']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-card__foot">
                            <span class="order-card__foot-note">
                                <?= icon('shield', 13) ?>
                                <?= $order['escrow_status'] === 'held' ? 'Dana ditahan escrow' : ($order['escrow_status'] === 'released' ? 'Escrow dilepas ke penjual' : 'Dana dikembalikan ke pembeli') ?>
                            </span>
                            <div class="order-card__actions">
                                <?php if ($order['status'] === 'placed'): ?>
                                    <form method="post" action="<?= e(url('order-action.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <button type="submit" class="btn btn--danger-outline btn--sm" data-confirm="Batalkan pesanan ini? Dana akan dikembalikan (simulasi)."><?= icon('x', 14) ?>Batalkan Pesanan</button>
                                    </form>
                                <?php elseif ($order['status'] === 'ready'): ?>
                                    <form method="post" action="<?= e(url('order-action.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="confirm_received">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <button type="submit" class="btn btn--primary btn--sm" data-confirm="Konfirmasi pesanan sudah kamu terima? Escrow akan dilepas ke penjual."><?= icon('check-circle', 15) ?>Konfirmasi Diterima</button>
                                    </form>
                                <?php elseif ($order['status'] === 'completed' || $order['status'] === 'cancelled'): ?>
                                    <form method="post" action="<?= e(url('order-action.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="buy_again">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <button type="submit" class="btn btn--outline btn--sm"><?= icon('refresh', 14) ?>Beli Lagi</button>
                                    </form>
                                <?php elseif ($order['status'] === 'processing'): ?>
                                    <span class="small muted">Menunggu penjual menandai siap.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<dialog class="modal" id="reviewDialog" aria-labelledby="reviewTitle">
    <form method="post" action="<?= e(url('order-action.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit_review">
        <input type="hidden" name="order_id" id="reviewOrderId" value="">
        <input type="hidden" name="product_id" id="reviewProductId" value="">
        <div class="modal__head">
            <div>
                <h2 class="modal__title" id="reviewTitle">Beri Ulasan</h2>
                <p class="modal__sub" id="reviewProductName">Produk</p>
            </div>
            <button type="button" class="modal__close" data-dialog-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="modal__body">
            <div class="field">
                <span class="label">Rating <span style="color:var(--danger)">*</span></span>
                <div class="star-input">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <input type="radio" name="rating" id="reviewStar<?= $star ?>" value="<?= $star ?>" <?= $star === 5 ? 'checked' : '' ?> required>
                        <label for="reviewStar<?= $star ?>" aria-label="<?= $star ?> bintang"><?= icon('star', 30) ?></label>
                    <?php endfor; ?>
                </div>
                <span class="field__hint">Pilih 1 sampai 5 bintang. Rating langsung memperbarui nilai produk dan toko.</span>
            </div>
            <div class="field" style="margin-top:14px">
                <label class="label" for="reviewComment">Komentar <span class="label__optional">(opsional)</span></label>
                <textarea class="textarea" id="reviewComment" name="comment" maxlength="400" rows="4" placeholder="Ceritakan pengalamanmu: rasa, kecepatan, kerapian kemasan…"></textarea>
            </div>
        </div>
        <div class="modal__foot">
            <button type="button" class="btn btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="btn btn--primary"><?= icon('star', 16) ?>Kirim Ulasan</button>
        </div>
    </form>
</dialog>
<?php require __DIR__ . '/includes/footer.php'; ?>
