<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$featured = featured_products(6);
$stores = stores_highlight(4);
$recommended = products_search(['sort' => 'terlaris', 'per_page' => 10])['items'];
$categories = categories_all();

$productCount = (int) db()->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$storeCount = (int) db()->query("SELECT COUNT(*) FROM stores WHERE verified_status = 'verified'")->fetchColumn();
$completedCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$memberCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role IN ('buyer','seller')")->fetchColumn();

$heroProductA = $featured[0] ?? null;
$heroProductB = $featured[1] ?? $recommended[1] ?? null;

$page_title = 'Beranda';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container">
        <?= flash_render() ?>
    </div>

    <section class="hero" aria-label="Sorotan NeMart">
        <div class="container">
            <div class="hero__stage" data-hero>
                <div class="hero__track">
                    <article class="hero__slide is-active" aria-hidden="false">
                        <div class="hero__content">
                            <span class="hero__eyebrow"><?= icon('sparkles', 13) ?>Marketplace mahasiswa Polinema</span>
                            <h1 class="hero__title">Dari kampus, untuk kampus.<br><em>Satu klik</em> langsung pesan.</h1>
                            <p class="hero__text">Temukan makanan, minuman, jasa, dan merch dari toko mahasiswa. Dana ditahan escrow dan baru diteruskan ke penjual setelah kamu konfirmasi pesanan diterima.</p>
                            <div class="hero__actions">
                                <a class="btn btn--yellow" href="<?= e(url('search.php')) ?>"><?= icon('search', 17) ?>Jelajahi Produk</a>
                                <a class="btn hero__ghost" href="#produk-unggulan"><?= icon('sparkles', 17) ?>Lihat Produk Unggulan</a>
                            </div>
                            <div class="hero__stats">
                                <div class="hero__stat"><strong><?= number_format($productCount, 0, ',', '.') ?>+</strong><span>Produk aktif</span></div>
                                <div class="hero__stat"><strong><?= number_format($storeCount, 0, ',', '.') ?></strong><span>Toko terverifikasi</span></div>
                                <div class="hero__stat"><strong><?= number_format($memberCount, 0, ',', '.') ?></strong><span>Bergabung</span></div>
                            </div>
                        </div>
                        <div class="hero__art" aria-hidden="true">
                            <?php if ($heroProductA): ?>
                                <div class="hero__card hero__card--a">
                                    <?= product_thumb($heroProductA, '', 30) ?>
                                    <div>
                                        <div class="hero__card-name"><?= e($heroProductA['name']) ?></div>
                                        <div class="hero__card-price"><?= rupiah((int) $heroProductA['price']) ?> · <?= e($heroProductA['store_name']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($heroProductB): ?>
                                <div class="hero__card hero__card--b">
                                    <span class="hero__card-icon"><?= icon('shield', 24) ?></span>
                                    <div>
                                        <div class="hero__card-name">Escrow NeMart menjamin dana</div>
                                        <div class="hero__card-price">Dilepas setelah pesanan diterima</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <span class="hero__card hero__card--c"><?= icon('star', 13) ?>Rating asli pembeli</span>
                            <div class="hero__float"><?= icon('check-circle', 15) ?><span><?= number_format($completedCount, 0, ',', '.') ?> pesanan selesai dengan escrow</span></div>
                        </div>
                    </article>

                    <article class="hero__slide hero__slide--2" aria-hidden="true">
                        <div class="hero__content">
                            <span class="hero__eyebrow"><?= icon('trend', 13) ?>Untuk penjual</span>
                            <h2 class="hero__title">Produkmu lebih terlihat<br>dengan <em>Boost</em> dan PRO.</h2>
                            <p class="hero__text">Boost menempatkan produk di Produk Unggulan beranda dan urutan teratas pencarian. Paket PRO membuka Data Tracking, komisi lebih kecil, dan 3 Boost gratis setiap bulan.</p>
                            <div class="hero__actions">
                                <a class="btn btn--yellow" href="<?= e(url('seller/boost.php')) ?>"><?= icon('sparkles', 17) ?>Promosi Boost</a>
                                <a class="btn hero__ghost" href="<?= e(url('seller/pro.php')) ?>"><?= icon('chart', 17) ?>Bandingkan Paket</a>
                            </div>
                        </div>
                        <div class="hero__art" aria-hidden="true">
                            <div class="hero__card hero__card--a">
                                <span class="hero__card-icon"><?= icon('chart', 24) ?></span>
                                <div>
                                    <div class="hero__card-name">Data Tracking PRO</div>
                                    <div class="hero__card-price">Funnel · waktu proses · omzet</div>
                                </div>
                            </div>
                            <div class="hero__card hero__card--b">
                                <span class="hero__card-icon" style="background:var(--yellow-100);color:#96650B"><?= icon('sparkles', 24) ?></span>
                                <div>
                                    <div class="hero__card-name">Boost 7 hari</div>
                                    <div class="hero__card-price">Rp25.000 · tampil Featured</div>
                                </div>
                            </div>
                            <span class="hero__card hero__card--c"><?= icon('wallet', 13) ?>Komisi PRO cuma 3%</span>
                            <div class="hero__float"><?= icon('star', 15) ?><span>3 Boost gratis tiap bulan untuk seller PRO</span></div>
                        </div>
                    </article>

                    <article class="hero__slide hero__slide--3" aria-hidden="true">
                        <div class="hero__content">
                            <span class="hero__eyebrow"><?= icon('shield', 13) ?>Escrow simulasi</span>
                            <h2 class="hero__title">Uang aman sampai<br>pesanan <em>diterima</em>.</h2>
                            <p class="hero__text">Saat checkout, dana ditahan platform. Penjual memproses pesanan, statusnya bisa kamu pantau, dan dana baru dilepas ke penjual setelah kamu menekan Konfirmasi Diterima.</p>
                            <div class="hero__actions">
                                <a class="btn btn--yellow" href="<?= e(url('search.php')) ?>"><?= icon('cart', 17) ?>Mulai Belanja</a>
                                <a class="btn hero__ghost" href="<?= e(url('orders.php')) ?>"><?= icon('box', 17) ?>Lacak Pesanan</a>
                            </div>
                        </div>
                        <div class="hero__art" aria-hidden="true">
                            <div class="hero__card hero__card--a">
                                <span class="hero__card-icon" style="background:var(--cyan-100);color:#0B6A80"><?= icon('box', 24) ?></span>
                                <div>
                                    <div class="hero__card-name">Pesanan dibuat</div>
                                    <div class="hero__card-price">Biaya layanan Rp1.000 per toko</div>
                                </div>
                            </div>
                            <div class="hero__card hero__card--b">
                                <span class="hero__card-icon" style="background:var(--success-bg);color:var(--success-fg)"><?= icon('check-circle', 24) ?></span>
                                <div>
                                    <div class="hero__card-name">Dikonfirmasi diterima</div>
                                    <div class="hero__card-price">Escrow dilepas ke penjual</div>
                                </div>
                            </div>
                            <span class="hero__card hero__card--c"><?= icon('truck', 13) ?>Antar mitra Rp3.000</span>
                            <div class="hero__float"><?= icon('clock', 15) ?><span>Status pesanan diperbarui penjual secara langsung</span></div>
                        </div>
                    </article>
                </div>

                <div class="hero__dots" role="tablist" aria-label="Slide sorotan">
                    <button type="button" class="hero__dot is-active" data-hero-dot aria-label="Slide 1" aria-selected="true"></button>
                    <button type="button" class="hero__dot" data-hero-dot aria-label="Slide 2" aria-selected="false"></button>
                    <button type="button" class="hero__dot" data-hero-dot aria-label="Slide 3" aria-selected="false"></button>
                </div>
                <div class="hero__controls">
                    <button type="button" class="hero__arrow" data-hero-prev aria-label="Slide sebelumnya"><?= icon('chevron-left', 17) ?></button>
                    <button type="button" class="hero__arrow" data-hero-next aria-label="Slide berikutnya"><?= icon('chevron-right', 17) ?></button>
                </div>
            </div>
        </div>
    </section>

    <section class="section" aria-labelledby="kategori-title">
        <div class="container">
            <?= section_head('Cari berdasarkan kategori', 'Delapan kategori usaha mahasiswa, dari makan siang sampai jasa desain.', url('search.php'), 'Semua produk') ?>
            <div class="cat-grid">
                <?php foreach ($categories as $cat): ?>
                    <a class="cat-card" href="<?= e(url('search.php?cat=' . $cat['slug'])) ?>">
                        <span class="cat-card__icon"><?= icon((string) $cat['icon'], 22) ?></span>
                        <span class="cat-card__name"><?= e($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="home-section" id="produk-unggulan" aria-labelledby="unggulan-title">
        <div class="container">
            <div class="panel-section panel-section--featured">
                <div class="section-head">
                    <div>
                        <h2 class="section-title" id="unggulan-title"><?= icon('sparkles', 19) ?>Produk Unggulan</h2>
                        <p class="section-sub">Dipilih penjual lewat Boost agar lebih mudah ditemukan pembeli.</p>
                    </div>
                    <span class="featured-badge"><?= icon('sparkles', 12) ?>Featured</span>
                </div>
                <?php if ($featured === []): ?>
                    <?= empty_state('tag', 'Belum ada produk unggulan', 'Produk yang di-Boost penjual akan muncul di sini.', '<a class="btn btn--primary btn--sm" href="' . e(url('search.php')) . '">Lihat semua produk</a>') ?>
                <?php else: ?>
                    <div class="grid-products grid-products--4">
                        <?php foreach ($featured as $product): ?>
                            <?= product_card($product) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="home-section" id="toko" aria-labelledby="toko-title">
        <div class="container">
            <?= section_head('Toko Mahasiswa Pilihan', 'Toko terverifikasi dengan rating terbaik dari pembeli kampus.', url('search.php'), 'Jelajahi produk') ?>
            <div class="grid-stores">
                <?php foreach ($stores as $store): ?>
                    <?= store_card($store, $store['stats']) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="home-section" aria-labelledby="rekomendasi-title">
        <div class="container">
            <?= section_head('Rekomendasi untuk Kamu', 'Produk yang paling sering dipesan mahasiswa lain.', url('search.php?sort=terlaris'), 'Lihat semua') ?>
            <div class="grid-products">
                <?php foreach ($recommended as $product): ?>
                    <?= product_card($product) ?>
                <?php endforeach; ?>
            </div>
            <div class="home-more">
                <a class="btn btn--outline" href="<?= e(url('search.php?sort=terlaris')) ?>"><?= icon('layers', 17) ?>Lihat Lebih Banyak</a>
            </div>
        </div>
    </section>

    <section class="home-section" aria-label="Jaminan NeMart">
        <div class="container">
            <div class="trust-row">
                <div class="trust-item">
                    <span class="trust-item__icon"><?= icon('shield', 20) ?></span>
                    <div><strong>Escrow aman</strong><span>Dana ditahan sampai pesanan diterima.</span></div>
                </div>
                <div class="trust-item">
                    <span class="trust-item__icon"><?= icon('check-circle', 20) ?></span>
                    <div><strong>Toko terverifikasi</strong><span>Dicek admin kampus sebelum tampil.</span></div>
                </div>
                <div class="trust-item">
                    <span class="trust-item__icon"><?= icon('star', 20) ?></span>
                    <div><strong>Ulasan asli</strong><span>Hanya pembeli yang sudah menerima pesanan.</span></div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
