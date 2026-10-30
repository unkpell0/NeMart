<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

$durations = [
    1 => ['label' => '24 jam', 'price' => 5000, 'desc' => 'Cocok untuk promo menu hari ini.'],
    3 => ['label' => '3 hari', 'price' => 12000, 'desc' => 'Paling sering dipilih toko mahasiswa.'],
    7 => ['label' => '7 hari', 'price' => 25000, 'desc' => 'Tampil Featured selama satu minggu penuh.'],
];

$stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                       FROM products p JOIN categories c ON c.id = p.category_id
                       WHERE p.store_id = ? AND p.status = 'active' ORDER BY p.name");
$stmt->execute([$storeId]);
$products = $stmt->fetchAll();

$stmt = db()->prepare("SELECT COUNT(*) FROM boosts WHERE store_id = ? AND source = 'pro_free' AND strftime('%Y-%m', created_at) = ?");
$stmt->execute([$storeId, date('Y-m')]);
$freeUsed = (int) $stmt->fetchColumn();
$freeQuota = store_is_pro($store) ? max(0, 3 - $freeUsed) : 0;

if (is_post()) {
    require_csrf();

    if (($_POST['action'] ?? '') === 'buy') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $days = (int) ($_POST['duration'] ?? 0);
        $useFree = (string) ($_POST['payment_option'] ?? 'paid') === 'pro_free';

        $stmt = db()->prepare("SELECT * FROM products WHERE id = ? AND store_id = ? AND status = 'active'");
        $stmt->execute([$productId, $storeId]);
        $product = $stmt->fetch();

        if (!$product) {
            flash('danger', 'Pilih produk aktif milik tokomu untuk di-Boost.');
            redirect('seller/boost.php');
        }
        if (!isset($durations[$days])) {
            flash('danger', 'Durasi Boost tidak valid.');
            redirect('seller/boost.php');
        }
        if ($useFree && $freeQuota <= 0) {
            flash('danger', 'Kuota Boost gratis PRO bulan ini sudah habis.');
            redirect('seller/boost.php');
        }

        $price = $useFree ? 0 : $durations[$days]['price'];
        $baseTs = time();
        if (!empty($product['boost_until']) && strtotime((string) $product['boost_until']) > $baseTs) {
            $baseTs = strtotime((string) $product['boost_until']);
        }
        $until = date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $baseTs));

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE products SET boost_until = ? WHERE id = ? AND store_id = ?');
            $stmt->execute([$until, $productId, $storeId]);
            $stmt = $pdo->prepare("INSERT INTO boosts (store_id, product_id, starts_at, ends_at, price, source, created_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$storeId, $productId, now(), $until, $price, $useFree ? 'pro_free' : 'paid', now()]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Gagal mengaktifkan Boost: ' . $e->getMessage());
            redirect('seller/boost.php');
        }

        flash('success', 'Boost aktif untuk “' . $product['name'] . '” sampai ' . tanggal($until, true) . '. Produk tampil Featured di beranda dan teratas pencarian.');
        redirect('seller/boost.php');
    }
}

$stmt = db()->prepare('SELECT b.*, p.name, p.view_count, p.sold_count, c.slug AS category_slug, c.name AS category_name, c.icon AS category_icon
                       FROM boosts b JOIN products p ON p.id = b.product_id JOIN categories c ON c.id = p.category_id
                       WHERE b.store_id = ? ORDER BY b.ends_at DESC LIMIT 6');
$stmt->execute([$storeId]);
$boostHistory = $stmt->fetchAll();

$stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                       FROM products p JOIN categories c ON c.id = p.category_id
                       WHERE p.store_id = ? AND p.boost_until IS NOT NULL AND p.boost_until > now_local()
                       ORDER BY p.boost_until ASC");
$stmt->execute([$storeId]);
$activeBoosts = $stmt->fetchAll();

$page_title = 'Promosi Boost';
$active_nav = 'boost';
$shell_title = 'Promosi Boost';
$shell_subtitle = 'Tampilkan produk di Produk Unggulan dan urutan teratas pencarian';
require __DIR__ . '/../includes/seller-layout.php';
?>

<div class="dash-grid">
    <div>
        <div class="panel">
            <div class="panel__head">
                <div>
                    <div class="panel__title"><?= icon('sparkles', 17) ?>Beli Boost</div>
                    <div class="panel__sub">Tampil dengan label Featured di beranda, hasil pencarian, dan halaman toko.</div>
                </div>
                <span class="badge badge--warning"><?= icon('info', 12) ?>Pembayaran simulasi</span>
            </div>

            <?php if ($products === []): ?>
                <?= empty_state('tag', 'Belum ada produk aktif', 'Tambahkan produk aktif dulu sebelum membeli Boost.', '<a class="btn btn--primary btn--sm" href="' . e(url('seller/product-form.php')) . '">Tambah Produk</a>') ?>
            <?php else: ?>
                <form method="post" action="<?= e(url('seller/boost.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="buy">

                    <div class="field">
                        <label class="label" for="boostProduct">Produk yang di-Boost</label>
                        <select class="select" id="boostProduct" name="product_id" required>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product['id'] ?>">
                                    <?= e($product['name']) ?> — <?= rupiah((int) $product['price']) ?>
                                    <?= !empty($product['boost_until']) && strtotime((string) $product['boost_until']) > time() ? ' (sedang Featured)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top:16px">
                        <span class="label">Durasi Boost</span>
                        <div class="boost-durations" style="margin-top:8px">
                            <?php foreach ($durations as $days => $duration): ?>
                                <label class="boost-option">
                                    <input type="radio" name="duration" value="<?= $days ?>" <?= $days === 3 ? 'checked' : '' ?> required>
                                    <span class="boost-option__days"><?= e($duration['label']) ?></span>
                                    <span class="boost-option__price"><?= rupiah($duration['price']) ?></span>
                                    <span class="boost-option__desc"><?= e($duration['desc']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($freeQuota > 0): ?>
                        <div style="margin-top:16px">
                            <span class="label">Cara pembayaran</span>
                            <div class="radio-cards" style="margin-top:8px">
                                <label class="radio-card radio-card--compact">
                                    <input type="radio" name="payment_option" value="paid" checked>
                                    <span class="radio-card__icon"><?= icon('wallet', 17) ?></span>
                                    <span style="flex:1"><span class="radio-card__title">Bayar simulasi</span><span class="radio-card__desc">Potongan harga sesuai durasi.</span></span>
                                </label>
                                <label class="radio-card radio-card--compact boost-option--free">
                                    <input type="radio" name="payment_option" value="pro_free">
                                    <span class="radio-card__icon"><?= icon('sparkles', 17) ?></span>
                                    <span style="flex:1"><span class="radio-card__title">Kuota gratis PRO <span class="badge badge--pro">PRO</span></span><span class="radio-card__desc">Sisa <?= $freeQuota ?> dari 3 Boost gratis bulan ini.</span></span>
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="payment_option" value="paid">
                    <?php endif; ?>

                    <div class="notice notice--warning" style="margin-top:16px">
                        <?= icon('info', 16) ?>
                        <span><strong>Mode demo:</strong> tidak ada pembayaran nyata. Setelah tombol ditekan, produk langsung tampil Featured dan Boost kedaluwarsa otomatis sesuai durasi.</span>
                    </div>

                    <button type="submit" class="btn btn--yellow btn--full" style="margin-top:14px"><?= icon('sparkles', 17) ?>Aktifkan Boost Sekarang</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($activeBoosts !== []): ?>
            <div class="panel">
                <div class="panel__head">
                    <div class="panel__title"><?= icon('flame', 17) ?>Sedang Featured</div>
                    <span class="small muted"><?= count($activeBoosts) ?> produk</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Produk</th><th>Berakhir</th><th>Sisa</th><th>Dilihat</th></tr></thead>
                        <tbody>
                            <?php foreach ($activeBoosts as $product): ?>
                                <?php $remaining = max(0, (int) ceil((strtotime((string) $product['boost_until']) - time()) / 86400)); ?>
                                <tr>
                                    <td>
                                        <div class="cell-product">
                                            <?= product_thumb($product, '', 18) ?>
                                            <div>
                                                <div style="font-weight:600;font-size:12.5px"><?= e($product['name']) ?></div>
                                                <div class="tiny muted"><?= rupiah((int) $product['price']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small"><?= e(tanggal((string) $product['boost_until'], true)) ?></td>
                                    <td><span class="badge badge--warning"><?= $remaining ?> hari</span></td>
                                    <td class="tabular"><?= number_format((int) $product['view_count'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="panel">
            <div class="panel__title"><?= icon('info', 17) ?>Cara kerja Boost</div>
            <ul class="small muted" style="display:grid;gap:10px;margin-top:10px">
                <li><strong style="color:var(--text)">1. Pilih produk & durasi.</strong> Boost aktif langsung setelah pembayaran simulasi.</li>
                <li><strong style="color:var(--text)">2. Muncul di Featured.</strong> Produk ditampilkan di seksi Produk Unggulan beranda dan teratas hasil pencarian yang cocok.</li>
                <li><strong style="color:var(--text)">3. Tanpa cron.</strong> Boost berhenti otomatis karena setiap query memeriksa waktu berakhir.</li>
                <li><strong style="color:var(--text)">4. Kuota PRO.</strong> Seller PRO mendapat 3 Boost gratis per bulan (sisa <?= $freeQuota ?> bulan ini).</li>
            </ul>
        </div>

        <div class="panel">
            <div class="panel__title"><?= icon('clock', 17) ?>Riwayat Boost</div>
            <?php if ($boostHistory === []): ?>
                <p class="small muted" style="margin-top:8px">Belum ada riwayat Boost.</p>
            <?php else: ?>
                <div style="margin-top:8px;display:grid;gap:10px">
                    <?php foreach ($boostHistory as $boost): ?>
                        <div style="display:flex;gap:8px;align-items:flex-start;padding-bottom:10px;border-bottom:1px dashed var(--border)">
                            <span class="badge <?= $boost['source'] === 'pro_free' ? 'badge--pro' : 'badge--neutral' ?>" style="flex-shrink:0"><?= $boost['source'] === 'pro_free' ? 'Gratis PRO' : rupiah((int) $boost['price']) ?></span>
                            <div style="min-width:0">
                                <div style="font-size:12px;font-weight:600;line-height:1.35"><?= e($boost['name']) ?></div>
                                <div class="tiny muted"><?= e(tanggal((string) $boost['starts_at'])) ?> – <?= e(tanggal((string) $boost['ends_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>

