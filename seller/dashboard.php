<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

$summary = seller_summary($storeId);
$orders = seller_orders($storeId);
$latestOrders = array_slice($orders, 0, 5);
$products = db()->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN stock <= 0 AND status = 'active' THEN 1 ELSE 0 END) AS empty FROM products WHERE store_id = ?");
$products->execute([$storeId]);
$productStats = $products->fetch() ?: ['total' => 0, 'active' => 0, 'empty' => 0];

$topProducts = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                              FROM products p JOIN categories c ON c.id = p.category_id
                              WHERE p.store_id = ? AND p.status = 'active'
                              ORDER BY p.sold_count DESC LIMIT 3");
$topProducts->execute([$storeId]);
$topProducts = $topProducts->fetchAll();

$page_title = 'Dashboard';
$active_nav = 'dashboard';
$shell_title = 'Dashboard';
$shell_subtitle = 'Ringkasan ' . $store['name'];
require __DIR__ . '/../includes/seller-layout.php';
?>

<?php if ($summary['new_orders'] > 0): ?>
    <div class="notice notice--warning" style="margin-bottom:16px">
        <?= icon('bell', 18) ?>
        <div style="flex:1">
            <strong><?= (int) $summary['new_orders'] ?> pesanan baru menunggu diterima.</strong>
            Pembeli sudah membayar (simulasi) dan dana ditahan escrow. Terima pesanan agar pembeli melihat status Diproses.
        </div>
        <a class="btn btn--yellow btn--sm" href="<?= e(url('seller/orders.php?status=placed')) ?>">Proses Sekarang</a>
    </div>
<?php endif; ?>

<div class="stat-grid">
    <?= stat_card('Pesanan Baru', number_format((int) $summary['new_orders'], 0, ',', '.'), 'bell', 'yellow', 'Status Dipesan') ?>
    <?= stat_card('Perlu Diproses', number_format((int) $summary['processing_orders'], 0, ',', '.'), 'refresh', 'indigo', 'Status Diproses') ?>
    <?= stat_card('Omzet Selesai', rupiah($summary['revenue']), 'wallet', 'mint', 'Escrow sudah dilepas') ?>
    <?= stat_card('Rating Toko', number_format($summary['rating'], 1, ',', '.'), 'star', 'cyan', (int) $summary['review_count'] . ' ulasan') ?>
</div>

<div class="dash-grid">
    <div>
        <div class="panel">
        <div class="panel__head">
            <div>
                <div class="panel__title"><?= icon('box', 17) ?>Pesanan terbaru</div>
                <div class="panel__sub">Pesanan yang dibuat pembeli langsung muncul di sini.</div>
            </div>
            <a class="link-arrow" href="<?= e(url('seller/orders.php')) ?>">Semua pesanan<?= icon('arrow-right', 14) ?></a>
        </div>

        <?php if ($latestOrders === []): ?>
            <?= empty_state('box', 'Belum ada pesanan', 'Pesanan pembeli akan tampil di sini beserta tombol aksinya.') ?>
        <?php else: ?>
            <div class="quick-list">
                <?php foreach ($latestOrders as $order): ?>
                    <div class="quick-item">
                        <?= avatar((string) $order['buyer_name'], (string) $order['buyer_initials'], 'avatar--sm') ?>
                        <div class="quick-item__body">
                            <div class="quick-item__title">
                                <?= e($order['buyer_name']) ?>
                                <span class="muted tiny"><?= e($order['code']) ?></span>
                                <?= status_badge((string) $order['status'], (string) $order['handover_method']) ?>
                            </div>
                            <div class="quick-item__meta">
                                <?= e($order['items'][0]['name_snapshot'] ?? 'Pesanan') ?><?= count($order['items']) > 1 ? ' +' . (count($order['items']) - 1) . ' produk lain' : '' ?>
                                · <?= rupiah((int) $order['total']) ?> · <?= e(time_ago((string) $order['created_at'])) ?>
                            </div>
                        </div>
                        <div class="admin-actions">
                            <?php if ($order['status'] === 'placed'): ?>
                                <form method="post" action="<?= e(url('seller/order-action.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button type="submit" class="btn btn--primary btn--sm"><?= icon('check', 13) ?>Terima</button>
                                </form>
                            <?php elseif ($order['status'] === 'processing'): ?>
                                <form method="post" action="<?= e(url('seller/order-action.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="ready">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button type="submit" class="btn btn--primary btn--sm"><?= icon('check', 13) ?>Tandai Siap</button>
                                </form>
                            <?php else: ?>
                                <a class="btn btn--outline btn--sm" href="<?= e(url('seller/orders.php?status=' . $order['status'])) ?>">Detail</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>

        <div class="panel" style="margin-top:16px">
            <div class="panel__head">
                <div class="panel__title"><?= icon('flame', 17) ?>Produk terlaris</div>
                <a class="link-arrow" href="<?= e(url('seller/products.php')) ?>">Kelola produk<?= icon('arrow-right', 14) ?></a>
            </div>
            <?php if ($topProducts === []): ?>
                <p class="small muted">Belum ada produk aktif di toko ini.</p>
            <?php else: ?>
                <div class="quick-list">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="quick-item">
                            <span class="badge badge--neutral" style="flex-shrink:0">#<?= $index + 1 ?></span>
                            <?= product_thumb($product, 'thumb--sm', 18) ?>
                            <div class="quick-item__body">
                                <div class="quick-item__title"><?= e($product['name']) ?></div>
                                <div class="quick-item__meta"><?= rupiah((int) $product['price']) ?> · stok <?= number_format((int) $product['stock'], 0, ',', '.') ?></div>
                            </div>
                            <div style="text-align:right;flex-shrink:0">
                                <div class="strong tabular" style="font-size:13px"><?= number_format((int) $product['sold_count'], 0, ',', '.') ?></div>
                                <div class="tiny muted">terjual</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <?php if (store_is_pro($store)): ?>
            <div class="plan-banner" style="margin-bottom:16px">
                <div class="plan-banner__body">
                    <div class="plan-banner__title"><?= icon('sparkles', 17) ?>Paket PRO aktif</div>
                    <p class="plan-banner__text">Komisi 3%, 3 Boost gratis per bulan, dan Data Tracking terbuka sampai <?= e(tanggal((string) $store['pro_until'])) ?>.</p>
                </div>
                <a class="btn btn--yellow btn--sm" href="<?= e(url('seller/tracking.php')) ?>">Buka Data Tracking</a>
            </div>
        <?php else: ?>
            <div class="plan-banner" style="margin-bottom:16px">
                <div class="plan-banner__body">
                    <div class="plan-banner__title"><?= icon('sparkles', 17) ?>Upgrade ke PRO</div>
                    <p class="plan-banner__text">Komisi turun jadi 3%, dapat 3 Boost gratis per bulan, dan Data Tracking pesanan terbuka. Rp25.000 per bulan (simulasi).</p>
                </div>
                <a class="btn btn--yellow btn--sm" href="<?= e(url('seller/pro.php')) ?>">Lihat Paket</a>
            </div>
        <?php endif; ?>

        <div class="panel" style="margin-bottom:16px">
            <div class="panel__head">
                <div class="panel__title"><?= icon('chart', 17) ?>Performa toko</div>
            </div>
            <div class="summary-row"><span>Pesanan selesai</span><strong><?= number_format((int) $summary['completed_orders'], 0, ',', '.') ?></strong></div>
            <div class="summary-row"><span>Komisi platform terpotong</span><strong><?= rupiah($summary['commission']) ?></strong></div>
            <div class="summary-row"><span>Produk aktif</span><strong><?= number_format((int) $productStats['active'], 0, ',', '.') ?> / <?= number_format((int) $productStats['total'], 0, ',', '.') ?></strong></div>
            <div class="summary-row"><span>Produk stok habis</span><strong><?= number_format((int) $productStats['empty'], 0, ',', '.') ?></strong></div>
            <div class="summary-row"><span>Boost aktif</span><strong><?= (int) db()->query("SELECT COUNT(*) FROM products WHERE store_id = " . $storeId . " AND boost_until > now_local()")->fetchColumn() ?></strong></div>
        </div>

        <div class="panel">
            <div class="panel__head">
                <div class="panel__title"><?= icon('sparkles', 17) ?>Aksi cepat</div>
            </div>
            <div class="btn-row" style="flex-direction:column;align-items:stretch">
                <a class="btn btn--outline" href="<?= e(url('seller/product-form.php')) ?>"><?= icon('plus', 16) ?>Tambah Produk Baru</a>
                <a class="btn btn--outline" href="<?= e(url('seller/boost.php')) ?>"><?= icon('sparkles', 16) ?>Promosikan Produk</a>
                <a class="btn btn--outline" href="<?= e(url('seller/reviews.php')) ?>"><?= icon('star', 16) ?>Balas Ulasan</a>
                <a class="btn btn--ghost" href="<?= e(url('store.php?id=' . $storeId)) ?>"><?= icon('external', 16) ?>Lihat halaman toko</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>

