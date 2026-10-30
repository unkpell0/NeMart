<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$product = null;
if ($editing) {
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                           FROM products p JOIN categories c ON c.id = p.category_id
                           WHERE p.id = ? AND p.store_id = ?');
    $stmt->execute([$id, $storeId]);
    $product = $stmt->fetch();
    if (!$product) {
        http_response_code(404);
        $error_code = '404';
        $error_title = 'Produk tidak ditemukan';
        $error_message = 'Produk ini bukan milik tokomu atau sudah dihapus.';
        require __DIR__ . '/../includes/error-page.php';
        exit;
    }
}

$errors = [];
$categories = categories_all();

$values = [
    'name'        => (string) ($product['name'] ?? ''),
    'category_id' => (int) ($product['category_id'] ?? ($categories[0]['id'] ?? 1)),
    'description' => (string) ($product['description'] ?? ''),
    'price'       => (string) ($product['price'] ?? ''),
    'stock'       => (string) ($product['stock'] ?? ''),
    'variants'    => $product ? implode(', ', product_variants($product)) : '',
    'status'      => (string) ($product['status'] ?? 'active'),
];

if (is_post()) {
    require_csrf();

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'toggle_status') {
        $target = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT status FROM products WHERE id = ? AND store_id = ?');
        $stmt->execute([$target, $storeId]);
        $current = $stmt->fetchColumn();
        if ($current === false) {
            flash('danger', 'Produk tidak ditemukan di tokomu.');
        } else {
            $newStatus = $current === 'active' ? 'inactive' : 'active';
            $stmt = db()->prepare('UPDATE products SET status = ? WHERE id = ? AND store_id = ?');
            $stmt->execute([$newStatus, $target, $storeId]);
            flash('success', 'Produk berhasil ' . ($newStatus === 'active' ? 'diaktifkan kembali.' : 'dinonaktifkan dari katalog.'));
        }
        redirect('seller/products.php');
    }

    $values = [
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price'       => preg_replace('/[^0-9]/', '', (string) ($_POST['price'] ?? '')) ?: '',
        'stock'       => preg_replace('/[^0-9]/', '', (string) ($_POST['stock'] ?? '')) ?: '0',
        'variants'    => trim((string) ($_POST['variants'] ?? '')),
        'status'      => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];

    if (mb_strlen($values['name']) < 3) {
        $errors[] = 'Nama produk minimal 3 karakter.';
    }
    if (mb_strlen($values['name']) > 90) {
        $errors[] = 'Nama produk maksimal 90 karakter.';
    }
    if (!in_array($values['category_id'], array_map(static fn ($c) => (int) $c['id'], $categories), true)) {
        $errors[] = 'Pilih kategori yang tersedia.';
    }
    if ($values['price'] === '' || (int) $values['price'] < 100) {
        $errors[] = 'Harga minimal Rp100.';
    }
    if ($values['stock'] === '' || (int) $values['stock'] < 0) {
        $errors[] = 'Stok tidak boleh negatif.';
    }
    if (mb_strlen($values['description']) < 10) {
        $errors[] = 'Deskripsi minimal 10 karakter agar pembeli paham produknya.';
    }

    $variantList = array_values(array_unique(array_filter(array_map('trim', explode(',', $values['variants'])), static fn ($v) => $v !== '')));
    if (count($variantList) > 6) {
        $errors[] = 'Maksimal 6 varian per produk.';
    }

    if ($errors === []) {
        $variantsJson = json_encode($variantList, JSON_UNESCAPED_UNICODE);
        if ($editing) {
            $stmt = db()->prepare('UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stock = ?, variants = ?, status = ? WHERE id = ? AND store_id = ?');
            $stmt->execute([
                $values['name'], $values['category_id'], $values['description'], (int) $values['price'],
                (int) $values['stock'], $variantsJson, $values['status'], $id, $storeId,
            ]);
            flash('success', 'Produk “' . $values['name'] . '” berhasil diperbarui.');
        } else {
            $stmt = db()->prepare('INSERT INTO products (store_id, category_id, name, description, price, stock, variants, image, status, sold_count, view_count, boost_until, is_po, po_deadline, created_at) VALUES (?,?,?,?,?,?,?,NULL,?,0,0,NULL,0,NULL,?)');
            $stmt->execute([
                $storeId, $values['category_id'], $values['name'], $values['description'],
                (int) $values['price'], (int) $values['stock'], $variantsJson, $values['status'], now(),
            ]);
            flash('success', 'Produk “' . $values['name'] . '” ditambahkan ke toko.');
        }
        redirect('seller/products.php');
    }
}

$page_title = $editing ? 'Edit Produk' : 'Tambah Produk';
$active_nav = 'products';
$shell_title = $editing ? 'Edit Produk' : 'Tambah Produk';
$shell_subtitle = $editing ? (string) $product['name'] : 'Lengkapi informasi produk';
require __DIR__ . '/../includes/seller-layout.php';
?>

<div class="form-layout">
    <div class="panel">
        <?php if ($errors !== []): ?>
            <div class="flash flash--danger" style="margin-bottom:16px">
                <?= icon('alert', 18) ?>
                <div>
                    <strong>Periksa kembali isian berikut:</strong>
                    <ul style="margin-top:4px;padding-left:16px;list-style:disc">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('seller/product-form.php' . ($editing ? '?id=' . $id : ''))) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label class="label" for="name">Nama produk <span style="color:var(--danger)">*</span></label>
                    <input class="input" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="90" required placeholder="Contoh: Es Kopi Susu Gula Aren">
                </div>

                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label class="label" for="category_id">Kategori <span style="color:var(--danger)">*</span></label>
                        <select class="select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (int) $values['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="status">Status tampil</label>
                        <select class="select" id="status" name="status">
                            <option value="active" <?= $values['status'] === 'active' ? 'selected' : '' ?>>Aktif — tampil di katalog</option>
                            <option value="inactive" <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif — disembunyikan</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label class="label" for="price">Harga (Rp) <span style="color:var(--danger)">*</span></label>
                        <input class="input" id="price" name="price" inputmode="numeric" value="<?= e($values['price']) ?>" required placeholder="12000">
                        <span class="field__hint">Biaya layanan Rp1.000 dan ongkir ditambahkan terpisah saat checkout.</span>
                    </div>
                    <div class="field">
                        <label class="label" for="stock">Stok <span style="color:var(--danger)">*</span></label>
                        <input class="input" id="stock" name="stock" inputmode="numeric" value="<?= e($values['stock']) ?>" required placeholder="20">
                        <span class="field__hint">Stok berkurang otomatis saat ada pesanan masuk.</span>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="variants">Varian <span class="label__optional">(opsional, pisahkan dengan koma)</span></label>
                    <input class="input" id="variants" name="variants" value="<?= e($values['variants']) ?>" placeholder="Regular, Less Sugar, Extra Shot">
                    <span class="field__hint">Maksimal 6 varian. Contoh untuk laundry: Cuci Kering, Cuci Setrika, Express 6 Jam.</span>
                </div>

                <div class="field">
                    <label class="label" for="description">Deskripsi <span style="color:var(--danger)">*</span></label>
                    <textarea class="textarea" id="description" name="description" rows="5" maxlength="600" required placeholder="Jelaskan produk, bahan, porsi, atau estimasi pengerjaan."><?= e($values['description']) ?></textarea>
                    <span class="field__hint">Deskripsi yang jelas meningkatkan kepercayaan pembeli.</span>
                </div>

                <div class="notice notice--info">
                    <?= icon('info', 16) ?>
                    <span><strong>Foto produk.</strong> Pada demo ini kartu produk memakai ikon kategori yang seragam agar tampilan konsisten. Unggah foto tidak diperlukan.</span>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn--primary"><?= icon('check', 16) ?><?= $editing ? 'Simpan Perubahan' : 'Simpan Produk' ?></button>
                    <a class="btn btn--ghost" href="<?= e(url('seller/products.php')) ?>">Batal</a>
                </div>
            </div>
        </form>
    </div>

    <aside class="form-aside">
        <?php if ($editing): ?>
            <div class="panel">
                <div class="panel__title" style="margin-bottom:10px"><?= icon('tag', 16) ?>Pratinjau produk</div>
                <?= product_card(array_merge($product, [
                    'store_name'   => $store['name'],
                    'store_id'     => $storeId,
                    'rating'       => 0,
                    'review_count' => 0,
                ])) ?>
                <a class="btn btn--outline btn--full btn--sm" style="margin-top:12px" href="<?= e(url('product.php?id=' . $id)) ?>"><?= icon('external', 14) ?>Lihat di katalog</a>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel__title" style="margin-bottom:8px"><?= icon('sparkles', 16) ?>Tips produk laris</div>
            <ul class="small muted" style="display:grid;gap:8px;padding-left:0">
                <li>• Tulis porsi atau ukuran yang jelas di deskripsi.</li>
                <li>• Sertakan varian yang sering diminta (level pedas, ukuran, jenis layanan).</li>
                <li>• Jaga stok tetap akurat supaya pembeli tidak kecewa.</li>
                <li>• Setelah produk punya beberapa ulasan, pertimbangkan Boost agar muncul di Featured.</li>
            </ul>
        </div>

        <?php if ($editing): ?>
            <div class="panel">
                <div class="panel__title" style="margin-bottom:10px"><?= icon('chart', 16) ?>Statistik singkat</div>
                <div class="summary-row"><span>Terjual</span><strong><?= number_format((int) $product['sold_count'], 0, ',', '.') ?></strong></div>
                <div class="summary-row"><span>Dilihat</span><strong><?= number_format((int) $product['view_count'], 0, ',', '.') ?></strong></div>
                <div class="summary-row"><span>Status Boost</span><strong><?= !empty($product['boost_until']) && strtotime((string) $product['boost_until']) > time() ? 'Aktif s/d ' . tanggal((string) $product['boost_until']) : 'Tidak aktif' ?></strong></div>
                <a class="btn btn--yellow btn--sm btn--full" style="margin-top:10px" href="<?= e(url('seller/boost.php')) ?>"><?= icon('sparkles', 14) ?>Promosikan Produk</a>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>
