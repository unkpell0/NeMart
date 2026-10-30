<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

$stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                              (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count,
                              (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id) AS rating
                       FROM products p JOIN categories c ON c.id = p.category_id
                       WHERE p.store_id = ? ORDER BY CASE WHEN p.status = 'inactive' THEN 1 ELSE 0 END, p.sold_count DESC");
$stmt->execute([$storeId]);
$products = $stmt->fetchAll();

$active = 0;
$emptyStock = 0;
foreach ($products as $p) {
    if ($p['status'] === 'active') {
        $active++;
    }
    if ((int) $p['stock'] <= 0 && $p['status'] === 'active') {
        $emptyStock++;
    }
}

$page_title = 'Kelola Produk';
$active_nav = 'products';
$shell_title = 'Kelola Produk';
$shell_subtitle = count($products) . ' produk terdaftar';
require __DIR__ . '/../includes/seller-layout.php';
?>

<div class="stat-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <?= stat_card('Total Produk', (string) count($products), 'tag', 'blue') ?>
    <?= stat_card('Produk Aktif', (string) $active, 'check-circle', 'mint') ?>
    <?= stat_card('Stok Habis', (string) $emptyStock, 'alert', 'yellow') ?>
</div>

<div class="panel panel--flush">
    <div class="panel__head" style="padding:16px 18px;margin:0;border-bottom:1px solid var(--border)">
        <div>
            <div class="panel__title"><?= icon('tag', 17) ?>Daftar produk</div>
            <div class="panel__sub">Produk nonaktif tidak tampil di katalog pembeli.</div>
        </div>
        <a class="btn btn--primary btn--sm" href="<?= e(url('seller/product-form.php')) ?>"><?= icon('plus', 15) ?>Tambah Produk</a>
    </div>

    <?php if ($products === []): ?>
        <div style="padding:16px">
            <?= empty_state('tag', 'Belum ada produk', 'Tambahkan produk pertamamu agar toko tampil di katalog NeMart.', '<a class="btn btn--primary btn--sm" href="' . e(url('seller/product-form.php')) . '">Tambah Produk</a>') ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Terjual</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="cell-product">
                                    <?= product_thumb($product, '', 20) ?>
                                    <div>
                                        <div style="font-weight:600;font-size:12.5px;line-height:1.35"><?= e($product['name']) ?></div>
                                        <div class="tiny muted">
                                            <?php if ((int) $product['is_featured'] === 1): ?><span style="color:var(--warning-fg);font-weight:600">Featured</span> · <?php endif; ?>
                                            <?= (int) $product['is_po'] === 1 ? 'Pre Order · ' : '' ?>Diperbarui <?= e(time_ago((string) $product['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="muted"><?= e($product['category_name']) ?></td>
                            <td class="strong tabular"><?= rupiah((int) $product['price']) ?></td>
                            <td>
                                <span class="badge <?= (int) $product['stock'] <= 0 ? 'badge--danger' : ((int) $product['stock'] <= 5 ? 'badge--warning' : 'badge--neutral') ?>"><?= number_format((int) $product['stock'], 0, ',', '.') ?></span>
                            </td>
                            <td class="tabular"><?= number_format((int) $product['sold_count'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ((int) $product['review_count'] > 0): ?>
                                    <?= rating_stars((float) $product['rating'], null, 12) ?>
                                <?php else: ?>
                                    <span class="muted tiny">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $product['status'] === 'active' ? 'badge--success' : 'badge--neutral' ?>"><?= $product['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span>
                            </td>
                            <td>
                                <div class="admin-actions" style="justify-content:flex-end">
                                    <a class="btn btn--outline btn--sm" href="<?= e(url('seller/product-form.php?id=' . (int) $product['id'])) ?>"><?= icon('edit', 13) ?>Edit</a>
                                    <form method="post" action="<?= e(url('seller/product-form.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                        <button type="submit" class="btn btn--ghost btn--sm" data-confirm="<?= $product['status'] === 'active' ? 'Nonaktifkan produk ini? Produk tidak tampil di katalog.' : 'Aktifkan kembali produk ini?' ?>">
                                            <?= icon($product['status'] === 'active' ? 'x' : 'check', 13) ?><?= $product['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <a class="btn btn--ghost btn--sm" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>" aria-label="Lihat produk di katalog"><?= icon('eye', 14) ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>
