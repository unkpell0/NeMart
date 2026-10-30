<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];

$rawIds = $_POST['ids'] ?? $_GET['ids'] ?? [];
$ids = is_array($rawIds) ? array_values(array_filter(array_map('intval', $rawIds))) : [];

if (is_post()) {
    require_csrf();

    $items = cart_items_by_ids($userId, $ids);
    if ($items === []) {
        flash('warning', 'Keranjangmu kosong. Tambahkan produk dulu, ya.');
        redirect('cart.php');
    }

    $handover = ($_POST['handover'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
    $notes = trim((string) ($_POST['delivery_notes'] ?? ''));
    $paymentMethod = (string) ($_POST['payment_method'] ?? 'QRIS');
    $pickupChoice = trim((string) ($_POST['pickup_point'] ?? ''));

    if ($handover === 'delivery' && $notes === '') {
        flash('warning', 'Isi catatan lokasi pengantaran agar mitra bisa menemukan tempatmu.');
        redirect('checkout.php?' . http_build_query(['ids' => array_column($items, 'cart_id')]));
    }

    try {
        $result = create_orders_from_cart($userId, $items, [
            'handover'       => $handover,
            'pickup_point'   => $pickupChoice,
            'delivery_notes' => $notes,
            'payment_method' => $paymentMethod,
        ]);
        flash('success', 'Pembayaran simulasi berhasil. Pesanan diteruskan ke penjual.');
        redirect('order-success.php?code=' . rawurlencode($result['orders'][0]['code']));
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect('checkout.php?' . http_build_query(['ids' => array_column($items, 'cart_id')]));
    }
}

$items = cart_items_by_ids($userId, $ids);
if ($items === []) {
    flash('info', 'Keranjangmu masih kosong. Pilih produk dulu sebelum checkout.');
    redirect('cart.php');
}

$groups = cart_group_by_store($items);
$subtotal = cart_subtotal($items);
$storeCount = count($groups);
$serviceTotal = 1000 * $storeCount;
$totalQty = 0;
foreach ($items as $item) {
    $totalQty += (int) $item['qty'];
}
$initialTotal = $subtotal + $serviceTotal;
$selectedIds = array_map(static fn (array $i): int => (int) $i['cart_id'], $items);

$page_title = 'Checkout';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb([
            'Beranda' => url('index.php'),
            'Keranjang' => url('cart.php'),
            'Checkout',
        ]) ?>

        <div class="page-head">
            <div>
                <h1 class="page-title">Checkout</h1>
                <p class="page-sub"><?= number_format($totalQty, 0, ',', '.') ?> item dari <?= $storeCount ?> toko · pembayaran disimulasikan</p>
            </div>
            <span class="badge badge--warning"><?= icon('info', 12) ?>Mode demo: pembayaran disimulasikan</span>
        </div>

        <form id="checkout-form" method="post" action="<?= e(url('checkout.php')) ?>" data-checkout data-subtotal="<?= $subtotal ?>" data-service="<?= $serviceTotal ?>" data-shipping="3000" data-stores="<?= $storeCount ?>">
            <?= csrf_field() ?>
            <?php foreach ($selectedIds as $id): ?>
                <input type="hidden" name="ids[]" value="<?= $id ?>">
            <?php endforeach; ?>

            <div class="checkout-layout">
                <div>
                    <section class="co-block" aria-labelledby="co-handover">
                        <div class="co-block__head">
                            <span class="co-step">1</span>
                            <div>
                                <h2 class="co-block__title" id="co-handover">Serah terima pesanan</h2>
                                <p class="small muted">Pilih cara menerima pesanan. Ongkos antar masuk ke total.</p>
                            </div>
                        </div>

                        <div class="radio-cards">
                            <label class="radio-card">
                                <input type="radio" name="handover" value="pickup" checked>
                                <span class="radio-card__icon"><?= icon('store', 19) ?></span>
                                <span style="flex:1">
                                    <span class="radio-card__title">Ambil di titik kampus <span class="radio-card__fee">Rp0</span></span>
                                    <span class="radio-card__desc">Ambil sendiri di titik yang tertera pada tiap toko.</span>
                                </span>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="handover" value="delivery">
                                <span class="radio-card__icon"><?= icon('truck', 19) ?></span>
                                <span style="flex:1">
                                    <span class="radio-card__title">Diantar mitra kampus <span class="radio-card__fee">+<?= rupiah(3000) ?> / toko</span></span>
                                    <span class="radio-card__desc">Diantar ke lokasi yang kamu tulis, estimasi 30–60 menit.</span>
                                </span>
                            </label>
                        </div>

                        <div class="co-pickup-list" id="coPickupPanel">
                            <?php foreach ($groups as $group): ?>
                                <div class="co-pickup">
                                    <?= icon('map-pin', 16) ?>
                                    <div>
                                        <strong><?= e($group['store_name']) ?></strong>
                                        <?= e($group['pickup_point']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="coDeliveryPanel" hidden style="margin-top:12px">
                            <div class="field">
                                <label class="label" for="coDeliveryNotes">Catatan lokasi pengantaran</label>
                                <textarea class="textarea" id="coDeliveryNotes" name="delivery_notes" rows="3" placeholder="Contoh: Gedung B lantai 3, ruang B-302, titip ke resepsionis. Sertakan nomor HP yang aktif."></textarea>
                                <span class="field__hint">Mitra kampus akan menghubungi lewat nomor yang terdaftar pada akun demo.</span>
                            </div>
                        </div>
                    </section>

                    <section class="co-block" aria-labelledby="co-summary">
                        <div class="co-block__head">
                            <span class="co-step">2</span>
                            <div style="flex:1">
                                <h2 class="co-block__title" id="co-summary">Ringkasan pesanan per toko</h2>
                                <p class="small muted">Satu pesanan dibuat untuk setiap toko, dibayar dalam satu transaksi.</p>
                            </div>
                            <a class="btn btn--outline btn--sm" href="<?= e(url('cart.php')) ?>"><?= icon('edit', 14) ?>Ubah jumlah</a>
                        </div>

                        <?php foreach ($groups as $group): ?>
                            <div class="co-store">
                                <div class="co-store__head">
                                    <?= icon('store', 15) ?>
                                    <a href="<?= e(url('store.php?id=' . (int) $group['store_id'])) ?>" style="color:inherit;margin:0"><?= e($group['store_name']) ?></a>
                                    <a href="<?= e(url('store.php?id=' . (int) $group['store_id'])) ?>">Kunjungi toko</a>
                                </div>
                                <?php foreach ($group['items'] as $item): ?>
                                    <div class="co-item">
                                        <?= product_thumb($item, '', 22) ?>
                                        <div>
                                            <div class="co-item__name"><?= e($item['name']) ?></div>
                                            <div class="co-item__meta">
                                                <?= (int) $item['qty'] ?> × <?= rupiah((int) $item['price']) ?>
                                                <?php if ((string) $item['variant'] !== ''): ?> · <?= e($item['variant']) ?><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="co-item__price"><?= rupiah((int) $item['price'] * (int) $item['qty']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="success-order__row" style="padding-top:10px"><span>Subtotal toko</span><strong><?= rupiah((int) $group['subtotal']) ?></strong></div>
                                <div class="success-order__row" style="padding-top:0"><span>Biaya layanan escrow</span><strong><?= rupiah(1000) ?></strong></div>
                                <div class="success-order__row" style="padding-top:0;padding-bottom:12px"><span>Ongkos antar</span><strong>Sesuai pilihan serah terima</strong></div>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section class="co-block" aria-labelledby="co-payment">
                        <div class="co-block__head">
                            <span class="co-step">3</span>
                            <div>
                                <h2 class="co-block__title" id="co-payment">Metode pembayaran</h2>
                                <p class="small muted">Pilihan tampilan untuk demo. Tidak ada gateway pembayaran sungguhan.</p>
                            </div>
                        </div>
                        <div class="co-payment">
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="QRIS" checked>
                                <span class="radio-card__icon"><?= icon('qr', 18) ?></span>
                                <span class="radio-card__title">QRIS</span>
                                <span class="radio-card__desc">Pindai QR simulasi</span>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="Virtual Account">
                                <span class="radio-card__icon"><?= icon('credit-card', 18) ?></span>
                                <span class="radio-card__title">Virtual Account</span>
                                <span class="radio-card__desc">Nomor VA simulasi</span>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="payment_method" value="E-Wallet">
                                <span class="radio-card__icon"><?= icon('smartphone', 18) ?></span>
                                <span class="radio-card__title">E-Wallet</span>
                                <span class="radio-card__desc">Saldo simulasi</span>
                            </label>
                        </div>
                        <div class="notice notice--warning co-demo">
                            <?= icon('info', 16) ?>
                            <span><strong>Mode demo:</strong> pembayaran disimulasikan. Setelah menekan “Saya Sudah Bayar”, server langsung membuat pesanan dan mengirim notifikasi ke penjual.</span>
                        </div>
                    </section>
                </div>

                <aside class="cart-summary" aria-label="Ringkasan pembayaran">
                    <div class="summary-card">
                        <div class="summary-card__title">Ringkasan pembayaran</div>
                        <div class="summary-row"><span>Subtotal (<?= number_format($totalQty, 0, ',', '.') ?> item)</span><strong><?= rupiah($subtotal) ?></strong></div>
                        <div class="summary-row"><span>Biaya layanan (<?= $storeCount ?> toko × Rp1.000)</span><strong><?= rupiah($serviceTotal) ?></strong></div>
                        <div class="summary-row"><span>Ongkos antar</span><strong id="coShipping">Gratis</strong></div>
                        <div class="summary-total"><span>Total bayar</span><strong id="coTotal"><?= rupiah($initialTotal) ?></strong></div>
                        <div class="summary-note">Dana ditahan escrow dan diteruskan ke penjual setelah kamu konfirmasi pesanan diterima.</div>
                        <button type="button" class="btn btn--primary btn--full" data-dialog-open="payDialog" style="margin-top:16px">
                            <?= icon('lock', 16) ?>Bayar Sekarang
                        </button>
                        <div class="summary-note" style="text-align:center"><?= icon('info', 11) ?> Pembayaran disimulasikan, tanpa uang nyata.</div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</main>

<dialog class="modal" id="payDialog" aria-labelledby="payTitle">
    <div class="modal__head">
        <div>
            <h2 class="modal__title" id="payTitle">Konfirmasi pembayaran</h2>
            <p class="modal__sub">Simulasi pembayaran · <span id="payMethodName">QRIS</span></p>
        </div>
        <button type="button" class="modal__close" data-dialog-close aria-label="Tutup"><?= icon('x', 16) ?></button>
    </div>
    <div class="modal__body">
        <div class="modal-qr">
            <div class="qr-box"><?= qr_dummy_svg('nemart-' . $userId . '-' . $subtotal . '-' . date('Ymd')) ?></div>
            <div style="text-align:center">
                <div class="small muted">Total pembayaran</div>
                <div class="qr-amount" id="payAmount"><?= rupiah($initialTotal) ?></div>
            </div>
            <p class="qr-hint">Kode QR ini hanya simulasi dan tidak bisa dipindai untuk transaksi nyata.</p>
            <div class="pay-steps">
                <div><b>1</b><span>Buka aplikasi pembayaran pilihanmu (QRIS, VA, atau e-wallet).</span></div>
                <div><b>2</b><span>Pindai kode QR di atas atau salin nomor virtual account simulasi.</span></div>
                <div><b>3</b><span>Tekan “Saya Sudah Bayar” untuk membuat pesanan di database.</span></div>
            </div>
        </div>
    </div>
    <div class="modal__foot">
        <button type="button" class="btn btn--ghost" data-dialog-close>Batal</button>
        <button type="submit" form="checkout-form" class="btn btn--primary"><?= icon('check-circle', 17) ?>Saya Sudah Bayar</button>
    </div>
</dialog>
<?php require __DIR__ . '/includes/footer.php'; ?>
