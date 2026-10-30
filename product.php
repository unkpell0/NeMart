<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$product = $id > 0 ? product_by_id($id) : null;

if (!$product || $product['status'] !== 'active' || $product['store_verified'] !== 'verified') {
    http_response_code(404);
    $error_code = '404';
    $error_title = 'Produk tidak ditemukan';
    $error_message = 'Produk ini mungkin sudah tidak dijual atau tokonya belum terverifikasi.';
    require __DIR__ . '/includes/error-page.php';
    exit;
}

bump_view((int) $product['id']);

$store = store_by_id((int) $product['store_id']);
$stats = $store ? store_stats((int) $store['id']) : [];
$variants = product_variants($product);
$reviews = product_reviews((int) $product['id']);
$related = related_products((int) $product['store_id'], (int) $product['id'], 4);
$user = current_user();
$isOwner = $user && $user['role'] === 'seller' && $store && (int) $store['owner_id'] === (int) $user['id'];

$ratingAvg = (float) $product['rating'];
$reviewCount = (int) $product['review_count'];
$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $review) {
    $distribution[(int) $review['rating']] = ($distribution[(int) $review['rating']] ?? 0) + 1;
}

$backUrl = url('product.php?id=' . (int) $product['id']);
$currentUrl = (string) ($_SERVER['REQUEST_URI'] ?? '/product.php?id=' . (int) $product['id']);
$loginUrl = url('login.php?next=' . rawurlencode($currentUrl));

$page_title = (string) $product['name'];
$page_desc = mb_substr((string) $product['description'], 0, 150);
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb([
            'Beranda' => url('index.php'),
            '' . $product['category_name'] => url('search.php?cat=' . $product['category_slug']),
            (string) $product['name'],
        ]) ?>

        <div class="pd">
            <div class="pd__gallery">
                <div class="pd__main">
                    <?= product_thumb($product, '', 96) ?>
                </div>
                <?php if ((int) $product['is_po'] === 1): ?>
                    <div class="notice notice--warning" style="margin-top:10px">
                        <?= icon('clock', 16) ?>
                        <span><strong>Pre Order.</strong> Pesanan diproses setelah periode PO ditutup<?= $product['po_deadline'] ? ' pada ' . e(tanggal((string) $product['po_deadline'])) : '' ?>.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pd__info">
                <div class="pd__meta">
                    <a class="badge badge--outline" href="<?= e(url('search.php?cat=' . $product['category_slug'])) ?>"><?= icon((string) $product['category_icon'], 12) ?><?= e($product['category_name']) ?></a>
                    <?php if ((int) $product['is_featured'] === 1): ?>
                        <span class="badge badge--featured"><?= icon('sparkles', 12) ?>Featured</span>
                    <?php endif; ?>
                    <?php if ((int) $product['stock'] > 0): ?>
                        <span class="badge badge--success"><?= icon('check-circle', 12) ?>Stok tersedia</span>
                    <?php else: ?>
                        <span class="badge badge--neutral">Stok habis</span>
                    <?php endif; ?>
                    <?php if ((int) $product['is_po'] === 1): ?>
                        <span class="badge badge--warning">Pre Order</span>
                    <?php endif; ?>
                </div>

                <h1 class="pd__title"><?= e($product['name']) ?></h1>

                <div class="pd__subrow">
                    <?= rating_stars($ratingAvg, $reviewCount, 15) ?>
                    <span class="dot-sep"></span>
                    <span><?= number_format((int) $product['sold_count'], 0, ',', '.') ?> terjual</span>
                    <span class="dot-sep"></span>
                    <span><?= number_format((int) $product['view_count'], 0, ',', '.') ?> dilihat</span>
                </div>

                <div class="pd__price-box">
                    <div class="pd__price"><?= rupiah((int) $product['price']) ?></div>
                    <div class="pd__price-note">Harga belum termasuk biaya layanan Rp1.000 per toko dan ongkos antar saat checkout.</div>
                </div>

                <?php if ($user && $isOwner): ?>
                    <div class="notice notice--info" style="margin-bottom:16px">
                        <?= icon('info', 16) ?>
                        <span>Ini produk milikmu. <a href="<?= e(url('seller/product-form.php?id=' . (int) $product['id'])) ?>" style="font-weight:600;text-decoration:underline">Edit produk</a> di Seller Centre.</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('cart-action.php')) ?>" id="addToCartForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                    <?php if ($variants !== []): ?>
                        <div class="pd__field">
                            <div class="pd__field-head">
                                <span class="pd__field-title">Pilih varian <span style="color:var(--danger)">*</span></span>
                                <span class="pd__stock">Wajib dipilih</span>
                            </div>
                            <div class="chips">
                                <?php foreach ($variants as $index => $variant): ?>
                                    <label class="chip">
                                        <input type="radio" name="variant" value="<?= e($variant) ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                                        <span><?= e($variant) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="pd__field">
                        <div class="pd__field-head">
                            <label class="pd__field-title" for="productNote">Catatan untuk penjual <span class="label__optional">(opsional)</span></label>
                        </div>
                        <textarea class="textarea" id="productNote" name="note" maxlength="160" placeholder="Contoh: sambal dipisah, tanpa es, ambil jam 12.00."></textarea>
                    </div>

                    <div class="pd__field">
                        <div class="pd__field-head">
                            <span class="pd__field-title">Jumlah</span>
                            <span class="pd__stock">Stok tersisa <?= number_format((int) $product['stock'], 0, ',', '.') ?></span>
                        </div>
                        <div class="stepper" data-stepper>
                            <button type="button" data-step="-1" aria-label="Kurangi jumlah"><?= icon('minus', 15) ?></button>
                            <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int) $product['stock']) ?>" aria-label="Jumlah pesanan">
                            <button type="button" data-step="1" aria-label="Tambah jumlah"><?= icon('plus', 15) ?></button>
                        </div>
                    </div>

                    <?php if ($user): ?>
                        <?php if ((int) $product['stock'] <= 0): ?>
                            <div class="pd__buy">
                                <button type="button" class="btn btn--outline" disabled><?= icon('x', 17) ?>Stok Habis</button>
                            </div>
                        <?php else: ?>
                            <div class="pd__buy">
                                <button type="submit" class="btn btn--outline"><?= icon('cart', 18) ?>Tambah ke Keranjang</button>
                                <button type="submit" class="btn btn--primary" name="buy_now" value="1"><?= icon('wallet', 18) ?>Beli Sekarang</button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="pd__buy">
                            <a class="btn btn--outline" href="<?= e($loginUrl) ?>"><?= icon('cart', 18) ?>Tambah ke Keranjang</a>
                            <a class="btn btn--primary" href="<?= e($loginUrl) ?>"><?= icon('wallet', 18) ?>Beli Sekarang</a>
                        </div>
                        <p class="field__hint" style="margin:-4px 0 12px">Masuk dulu untuk menambahkan produk ke keranjang.</p>
                    <?php endif; ?>
                </form>

                <div class="pd__escrow">
                    <?= icon('shield', 20) ?>
                    <div>
                        <strong>Transaksi dilindungi escrow NeMart</strong>
                        Dana ditahan platform setelah pembayaran dan baru diteruskan ke penjual setelah kamu menekan Konfirmasi Diterima. Pembayaran pada demo ini disimulasikan.
                    </div>
                </div>

                <?php if ($store): ?>
                    <div class="pd__seller">
                        <span class="avatar avatar--lg avatar--seller"><?= icon((string) $store['icon'], 22) ?></span>
                        <div class="pd__seller-info">
                            <div class="pd__seller-name">
                                <a href="<?= e(url('store.php?id=' . (int) $store['id'])) ?>"><?= e($store['name']) ?></a>
                                <?php if ($store['verified_status'] === 'verified'): ?>
                                    <span class="badge badge--verified"><?= icon('shield', 11) ?>Terverifikasi</span>
                                <?php endif; ?>
                                <?php if ($store['plan'] === 'pro'): ?>
                                    <span class="badge badge--pro">PRO</span>
                                <?php endif; ?>
                            </div>
                            <div class="pd__seller-meta">
                                <?= icon('book', 12) ?><span><?= e($store['department']) ?></span>
                                <span class="dot-sep"></span>
                                <?= rating_stars((float) $stats['rating'], (int) $stats['review_count'], 12) ?>
                            </div>
                        </div>
                        <a class="btn btn--outline btn--sm" href="<?= e(url('store.php?id=' . (int) $store['id'])) ?>">Kunjungi</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <section class="pd-tabs" id="detail" aria-label="Detail produk">
            <div class="tabs" data-tabs="pdPanels" role="tablist">
                <div class="tabs__scroll">
                    <button type="button" class="tab is-active" data-tab="deskripsi" role="tab" aria-selected="true">Deskripsi</button>
                    <button type="button" class="tab" data-tab="ulasan" role="tab" aria-selected="false">Ulasan <span class="tab__count"><?= $reviewCount ?></span></button>
                    <button type="button" class="tab" data-tab="lainnya" role="tab" aria-selected="false">Produk Lain dari Toko</button>
                </div>
            </div>

            <div id="pdPanels">
                <div class="tab-panel is-active" data-panel="deskripsi">
                    <div class="prose">
                        <p><?= nl2br(e((string) $product['description'])) ?></p>
                        <p>Dijual oleh <strong><?= e($product['store_name']) ?></strong> (<?= e($product['store_department']) ?>). Titik ambil: <?= e((string) ($store['pickup_point'] ?? 'kantin kampus')) ?>.</p>
                        <p>Setiap pesanan dikemas dan diserahkan sesuai metode yang kamu pilih saat checkout: ambil di titik kampus atau diantar mitra dengan tambahan Rp3.000.</p>
                    </div>
                </div>

                <div class="tab-panel" data-panel="ulasan" id="ulasan">
                    <?php if ($reviews === []): ?>
                        <?= empty_state('star', 'Belum ada ulasan', 'Ulasan hanya bisa ditulis pembeli yang pesanannya sudah selesai.') ?>
                    <?php else: ?>
                        <div class="review-summary">
                            <div class="review-score">
                                <div class="review-score__value"><?= number_format($ratingAvg, 1, ',', '.') ?></div>
                                <div class="review-score__max">dari 5</div>
                                <?= rating_stars($ratingAvg, null, 16, false) ?>
                                <div class="review-score__count"><?= number_format($reviewCount, 0, ',', '.') ?> ulasan pembeli</div>
                            </div>
                            <div class="review-bars">
                                <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                                    <?php $pct = $reviewCount > 0 ? ($distribution[$star] / $reviewCount) * 100 : 0; ?>
                                    <div class="review-bar">
                                        <span><?= $star ?> <span aria-hidden="true">★</span></span>
                                        <span class="review-bar__track"><span class="review-bar__fill" style="width:<?= round($pct, 1) ?>%"></span></span>
                                        <span style="text-align:right"><?= (int) $distribution[$star] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php foreach ($reviews as $review): ?>
                            <article class="review-item">
                                <div class="review-item__head">
                                    <?= avatar((string) $review['buyer_name'], (string) $review['buyer_initials'], 'avatar--sm') ?>
                                    <div>
                                        <div class="review-item__name"><?= e($review['buyer_name']) ?></div>
                                        <div class="review-item__date"><?= e(tanggal((string) $review['created_at'])) ?> · <?= rating_stars((float) $review['rating'], null, 12, false) ?></div>
                                    </div>
                                </div>
                                <div class="review-item__body"><?= e((string) $review['comment']) ?></div>
                                <?php if (!empty($review['seller_reply'])): ?>
                                    <div class="review-reply">
                                        <strong>Balasan <?= e($product['store_name']) ?>:</strong> <?= e((string) $review['seller_reply']) ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-panel" data-panel="lainnya">
                    <?php if ($related === []): ?>
                        <?= empty_state('store', 'Belum ada produk lain', 'Toko ini baru memiliki satu produk aktif.') ?>
                    <?php else: ?>
                        <div class="grid-products">
                            <?php foreach ($related as $item): ?>
                                <?= product_card($item) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
