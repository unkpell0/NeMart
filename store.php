<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$slug = trim((string) ($_GET['slug'] ?? ''));
$store = $id > 0 ? store_by_id($id) : ($slug !== '' ? store_by_slug($slug) : null);

$user = current_user();
$isOwner = $user && $store && (int) $user['id'] === (int) $store['owner_id'];
$canSee = $user && in_array($user['role'], ['admin'], true);

if (!$store || ($store['verified_status'] !== 'verified' && !$isOwner && !$canSee)) {
    http_response_code(404);
    $error_code = '404';
    $error_title = 'Toko tidak ditemukan';
    $error_message = 'Toko ini belum terverifikasi atau sudah tidak aktif.';
    require __DIR__ . '/includes/error-page.php';
    exit;
}

$stats = store_stats((int) $store['id']);
$products = store_products((int) $store['id']);
$reviews = store_reviews((int) $store['id']);
$featuredCount = 0;
foreach ($products as $p) {
    if ((int) $p['is_featured'] === 1) {
        $featuredCount++;
    }
}

$page_title = (string) $store['name'];
$page_desc = mb_substr((string) $store['description'], 0, 150);
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb([
            'Beranda' => url('index.php'),
            'Katalog' => url('search.php'),
            (string) $store['name'],
        ]) ?>

        <div class="store-hero">
            <div class="store-hero__logo"><?= icon((string) $store['icon'], 34) ?></div>
            <div class="store-hero__info">
                <h1 class="store-hero__name">
                    <?= e($store['name']) ?>
                    <?php if ($store['verified_status'] === 'verified'): ?>
                        <span class="badge badge--verified"><?= icon('shield', 12) ?>Terverifikasi</span>
                    <?php endif; ?>
                    <?php if ($store['plan'] === 'pro'): ?>
                        <span class="badge badge--pro"><?= icon('sparkles', 12) ?>PRO</span>
                    <?php endif; ?>
                </h1>
                <div class="store-hero__meta">
                    <span><?= icon('book', 13) ?> <?= e($store['department']) ?></span>
                    <span class="dot-sep"></span>
                    <span><?= icon('map-pin', 13) ?> <?= e((string) $store['pickup_point']) ?></span>
                    <span class="dot-sep"></span>
                    <span>Bergabung sejak <?= e(tanggal((string) $store['joined_at'], false, true)) ?></span>
                </div>
                <p class="store-hero__lede"><?= e((string) $store['description']) ?></p>
                <div class="store-stats">
                    <div class="store-stats__item"><strong><?= number_format($stats['rating'], 1, ',', '.') ?></strong><span>Rating toko</span></div>
                    <div class="store-stats__item"><strong><?= number_format((int) $stats['review_count'], 0, ',', '.') ?></strong><span>Ulasan</span></div>
                    <div class="store-stats__item"><strong><?= number_format((int) $stats['product_count'], 0, ',', '.') ?></strong><span>Produk</span></div>
                    <div class="store-stats__item"><strong><?= number_format((int) $stats['sold'], 0, ',', '.') ?></strong><span>Terjual</span></div>
                </div>
            </div>
            <?php if ($isOwner): ?>
                <a class="btn btn--yellow" href="<?= e(url('seller/dashboard.php')) ?>" style="margin-left:auto;position:relative;z-index:1"><?= icon('sliders', 16) ?>Kelola Toko</a>
            <?php endif; ?>
        </div>

        <div class="pd-tabs" style="margin-top:20px">
            <div class="tabs" data-tabs="storePanels" role="tablist">
                <div class="tabs__scroll">
                    <button type="button" class="tab is-active" data-tab="produk" role="tab" aria-selected="true">Semua Produk <span class="tab__count"><?= count($products) ?></span></button>
                    <button type="button" class="tab" data-tab="ulasan" role="tab" aria-selected="false">Ulasan <span class="tab__count"><?= count($reviews) ?></span></button>
                    <button type="button" class="tab" data-tab="tentang" role="tab" aria-selected="false">Tentang</button>
                </div>
            </div>

            <div id="storePanels">
                <div class="tab-panel is-active" data-panel="produk">
                    <?php if ($products === []): ?>
                        <?= empty_state('tag', 'Belum ada produk', 'Penjual belum menambahkan produk aktif di toko ini.') ?>
                    <?php else: ?>
                        <?php if ($featuredCount > 0): ?>
                            <div class="notice notice--warning" style="margin-bottom:14px">
                                <?= icon('sparkles', 16) ?>
                                <span><strong><?= $featuredCount ?> produk Featured</strong> dari toko ini tampil lebih dulu karena sedang di-Boost.</span>
                            </div>
                        <?php endif; ?>
                        <div class="grid-products grid-products--4">
                            <?php foreach ($products as $product): ?>
                                <?= product_card($product) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-panel" data-panel="ulasan">
                    <?php if ($reviews === []): ?>
                        <?= empty_state('star', 'Belum ada ulasan', 'Ulasan muncul setelah pembeli menyelesaikan pesanan dan menilainya.') ?>
                    <?php else: ?>
                        <div class="review-summary">
                            <div class="review-score">
                                <div class="review-score__value"><?= number_format($stats['rating'], 1, ',', '.') ?></div>
                                <div class="review-score__max">dari 5</div>
                                <?= rating_stars((float) $stats['rating'], null, 16, false) ?>
                                <div class="review-score__count"><?= number_format((int) $stats['review_count'], 0, ',', '.') ?> ulasan pembeli</div>
                            </div>
                            <div class="prose">
                                Rating toko dihitung dari seluruh ulasan produk yang terjual di toko ini. Pembeli hanya bisa mengulas setelah pesanan berstatus selesai.
                            </div>
                        </div>
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-item">
                                <div class="review-item__head">
                                    <?= avatar((string) $review['buyer_name'], (string) $review['buyer_initials'], 'avatar--sm') ?>
                                    <div>
                                        <div class="review-item__name"><?= e($review['buyer_name']) ?></div>
                                        <div class="review-item__date">
                                            <?= e(tanggal((string) $review['created_at'])) ?> ·
                                            <?= rating_stars((float) $review['rating'], null, 12, false) ?> ·
                                            <a href="<?= e(url('product.php?id=' . (int) $review['product_id'])) ?>" style="color:var(--blue-700)"><?= e($review['product_name']) ?></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-item__body"><?= e((string) $review['comment']) ?></div>
                                <?php if (!empty($review['seller_reply'])): ?>
                                    <div class="review-reply"><strong>Balasan penjual:</strong> <?= e((string) $review['seller_reply']) ?></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-panel" data-panel="tentang">
                    <div class="dash-grid">
                        <div class="prose">
                            <p><?= nl2br(e((string) $store['description'])) ?></p>
                            <h3 style="font-size:15px;margin:18px 0 8px">Titik ambil pesanan</h3>
                            <p><?= e((string) $store['pickup_point']) ?>. Bisa juga diantar mitra dengan tambahan biaya Rp3.000 saat checkout.</p>
                            <h3 style="font-size:15px;margin:18px 0 8px">Pengiriman dan pembayaran</h3>
                            <p>Pembayaran disimulasikan (QRIS, Virtual Account, atau E-Wallet). Dana ditahan escrow NeMart dan dilepas setelah pesanan dikonfirmasi diterima.</p>
                        </div>
                        <div class="panel">
                            <div class="panel__title"><?= icon('store', 16) ?>Info toko</div>
                            <div class="summary-row" style="margin-top:12px"><span>Jurusan</span><strong><?= e($store['department']) ?></strong></div>
                            <div class="summary-row"><span>Bergabung</span><strong><?= e(tanggal((string) $store['joined_at'])) ?></strong></div>
                            <div class="summary-row"><span>Paket</span><strong><?= $store['plan'] === 'pro' ? 'PRO' : 'Free' ?></strong></div>
                            <div class="summary-row"><span>Status</span><strong><?= $store['verified_status'] === 'verified' ? 'Terverifikasi' : ucfirst((string) $store['verified_status']) ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
