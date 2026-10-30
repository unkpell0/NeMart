<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$items = cart_items((int) $user['id']);
$groups = cart_group_by_store($items);
$subtotal = cart_subtotal($items);
$totalQty = 0;
foreach ($items as $item) {
    $totalQty += (int) $item['qty'];
}

$page_title = 'Keranjang';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb(['Beranda' => url('index.php'), 'Keranjang']) ?>

        <div class="page-head">
            <div>
                <h1 class="page-title">Keranjang</h1>
                <p class="page-sub"><?= $items === [] ? 'Belum ada produk di keranjangmu.' : number_format($totalQty, 0, ',', '.') . ' item dari ' . count($groups) . ' toko' ?></p>
            </div>
        </div>

        <?php if ($items === []): ?>
            <?= empty_state(
                'cart',
                'Keranjangmu masih kosong',
                'Mulai belanja dari katalog dan tambahkan produk ke keranjang. Pesananmu nanti bisa dilacak sampai selesai.',
                '<a class="btn btn--primary" href="' . e(url('search.php')) . '">' . icon('search', 17) . 'Mulai Belanja</a>'
            ) ?>
        <?php else: ?>
            <div class="cart-toolbar">
                <label class="check">
                    <input type="checkbox" data-cart-check-all checked>
                    <span>Pilih semua item</span>
                </label>
                <form method="post" action="<?= e(url('cart-action.php')) ?>" onsubmit="return confirm('Kosongkan seluruh keranjang?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn--ghost btn--sm"><?= icon('trash', 14) ?>Kosongkan keranjang</button>
                </form>
            </div>

            <div class="cart-layout" data-cart-page>
                <div>
                    <?php foreach ($groups as $group): ?>
                        <section class="cart-group" aria-label="Produk dari <?= e($group['store_name']) ?>">
                            <div class="cart-group__head">
                                <?= icon('store', 16) ?>
                                <a href="<?= e(url('store.php?id=' . (int) $group['store_id'])) ?>"><?= e($group['store_name']) ?></a>
                                <span class="dot-sep"></span>
                                <span class="muted" style="font-size:11.5px"><?= e($group['pickup_point']) ?></span>
                                <span class="badge badge--success" style="margin-left:auto"><?= icon('shield', 11) ?>Escrow</span>
                            </div>

                            <?php foreach ($group['items'] as $item): ?>
                                <div class="cart-row" data-cart-row data-price="<?= (int) $item['price'] ?>" data-qty="<?= (int) $item['qty'] ?>">
                                    <div class="cart-row__check">
                                        <input type="checkbox" name="ids[]" value="<?= (int) $item['cart_id'] ?>" form="checkout-form" class="cart-check" data-cart-check checked aria-label="Pilih <?= e($item['name']) ?>">
                                    </div>
                                    <a href="<?= e(url('product.php?id=' . (int) $item['product_id'])) ?>">
                                        <?= product_thumb($item, '', 26) ?>
                                    </a>
                                    <div>
                                        <div class="cart-row__name"><a href="<?= e(url('product.php?id=' . (int) $item['product_id'])) ?>"><?= e($item['name']) ?></a></div>
                                        <?php if ((string) $item['variant'] !== ''): ?>
                                            <div class="cart-row__variant">Varian: <?= e($item['variant']) ?></div>
                                        <?php endif; ?>
                                        <?php if ((string) $item['note'] !== ''): ?>
                                            <div class="cart-row__note">Catatan: <?= e($item['note']) ?></div>
                                        <?php endif; ?>
                                        <div class="cart-row__price"><?= rupiah((int) $item['price']) ?> <span class="muted tiny">/ item</span></div>
                                    </div>
                                    <div class="cart-row__side">
                                        <form method="post" action="<?= e(url('cart-action.php')) ?>" class="stepper" aria-label="Ubah jumlah <?= e($item['name']) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_qty">
                                            <input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>">
                                            <button type="submit" name="delta" value="-1" aria-label="Kurangi jumlah" <?= (int) $item['qty'] <= 1 ? 'disabled' : '' ?>><?= icon('minus', 14) ?></button>
                                            <input type="text" value="<?= (int) $item['qty'] ?>" readonly aria-label="Jumlah saat ini">
                                            <button type="submit" name="delta" value="1" aria-label="Tambah jumlah" <?= (int) $item['qty'] >= (int) $item['stock'] ? 'disabled' : '' ?>><?= icon('plus', 14) ?></button>
                                        </form>
                                        <div class="cart-row__total"><?= rupiah((int) $item['price'] * (int) $item['qty']) ?></div>
                                        <form method="post" action="<?= e(url('cart-action.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>">
                                            <button type="submit" class="cart-row__remove" aria-label="Hapus <?= e($item['name']) ?>"><?= icon('trash', 12) ?>Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>

                    <div class="notice notice--info" style="margin-top:14px">
                        <?= icon('shield', 17) ?>
                        <span><strong>Escrow NeMart.</strong> Dana ditahan setelah pembayaran dan diteruskan ke penjual hanya setelah kamu menekan Konfirmasi Diterima di halaman Pesanan Saya.</span>
                    </div>
                </div>

                <aside class="cart-summary" aria-label="Ringkasan keranjang">
                    <div class="summary-card">
                        <div class="summary-card__title">Ringkasan keranjang</div>
                        <div class="summary-row"><span>Item dipilih</span><strong><span id="cartSelectedCount"><?= number_format($totalQty, 0, ',', '.') ?></span> item</strong></div>
                        <div class="summary-row"><span>Subtotal</span><strong id="cartSelectedSubtotal"><?= rupiah($subtotal) ?></strong></div>
                        <div class="summary-note">Biaya layanan <strong>Rp1.000 per toko</strong> dan ongkos antar (Rp0 ambil sendiri / Rp3.000 diantar) dihitung di checkout.</div>
                        <form id="checkout-form" method="get" action="<?= e(url('checkout.php')) ?>">
                            <button type="submit" class="btn btn--primary btn--full" id="cartCheckoutBtn"><?= icon('arrow-right', 17) ?>Lanjut ke Checkout</button>
                        </form>
                        <a class="btn btn--ghost btn--full" href="<?= e(url('search.php')) ?>" style="margin-top:4px">Lanjut belanja</a>
                    </div>
                    <div class="summary-note" style="text-align:center;margin-top:10px">
                        <?= icon('lock', 12) ?> Pembayaran disimulasikan pada demo ini.
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
