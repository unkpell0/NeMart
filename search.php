<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$q = trim((string) ($_GET['q'] ?? ''));
$catSlug = trim((string) ($_GET['cat'] ?? ''));
$min = (int) ($_GET['min'] ?? 0);
$max = (int) ($_GET['max'] ?? 0);
$rating = (float) ($_GET['rating'] ?? 0);
$sort = (string) ($_GET['sort'] ?? 'terlaris');
$sortOptions = ['terlaris' => 'Terlaris', 'terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'rating' => 'Rating tertinggi'];
if (!isset($sortOptions[$sort])) {
    $sort = 'terlaris';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$activeCategory = $catSlug !== '' ? category_by_slug($catSlug) : null;
if ($catSlug !== '' && $activeCategory === null) {
    $catSlug = '';
}

$result = products_search([
    'q'        => $q,
    'cat'      => $catSlug,
    'min'      => $min,
    'max'      => $max,
    'rating'   => $rating,
    'sort'     => $sort,
    'page'     => $page,
    'per_page' => 12,
]);

$categoryCounts = [];
foreach (db()->query("SELECT c.slug, COUNT(p.id) AS total FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active' GROUP BY c.id")->fetchAll() as $row) {
    $categoryCounts[(string) $row['slug']] = (int) $row['total'];
}

$baseParams = ['q' => $q, 'cat' => $catSlug, 'min' => $min ?: '', 'max' => $max ?: '', 'rating' => $rating ?: '', 'sort' => $sort];
$makeUrl = static function (array $overrides = []) use ($baseParams): string {
    $merged = array_merge($baseParams, $overrides);
    $merged = array_filter($merged, static fn ($value) => $value !== '' && $value !== null && $value !== 0 && $value !== 0.0);

    return url('search.php' . ($merged === [] ? '' : '?' . http_build_query($merged)));
};

$pageTitleText = $activeCategory ? $activeCategory['name'] : ($q !== '' ? 'Hasil untuk “' . $q . '”' : 'Semua Produk');
$page_title = $pageTitleText;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb([
            'Beranda' => url('index.php'),
            'Katalog' => $makeUrl(['cat' => '', 'page' => '']),
            $pageTitleText,
        ]) ?>

        <div class="page-head">
            <div>
                <h1 class="page-title"><?= e($pageTitleText) ?></h1>
                <p class="page-sub">
                    <?php if ($q !== '' || $activeCategory || $min || $max || $rating): ?>
                        <?= number_format($result['total'], 0, ',', '.') ?> produk ditemukan
                        <?php if ($q !== ''): ?> untuk kata kunci <strong>“<?= e($q) ?>”</strong><?php endif; ?>
                    <?php else: ?>
                        Jelajahi <?= number_format($result['total'], 0, ',', '.') ?> produk dari toko mahasiswa terverifikasi.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="catalog">
            <aside class="filters" aria-label="Filter pencarian">
                <div class="filters__head">
                    <span class="filters__title"><?= icon('filter', 16) ?> Filter</span>
                    <?php if ($q !== '' || $activeCategory || $min || $max || $rating): ?>
                        <a class="btn btn--link" href="<?= e(url('search.php')) ?>" style="font-size:11.5px">Reset</a>
                    <?php endif; ?>
                </div>

                <div class="filters__group">
                    <div class="filters__label">Kategori</div>
                    <div class="filter-list">
                        <a class="filter-link<?= $activeCategory === null ? ' is-active' : '' ?>" href="<?= e($makeUrl(['cat' => '', 'page' => ''])) ?>">
                            <?= icon('layers', 15) ?><span>Semua Kategori</span>
                        </a>
                        <?php foreach (categories_all() as $category): ?>
                            <a class="filter-link<?= $catSlug === $category['slug'] ? ' is-active' : '' ?>" href="<?= e($makeUrl(['cat' => $category['slug'], 'page' => ''])) ?>">
                                <?= icon((string) $category['icon'], 15) ?><span><?= e($category['name']) ?></span>
                                <span style="margin-left:auto;font-size:10.5px;opacity:.7"><?= (int) ($categoryCounts[$category['slug']] ?? 0) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form class="filters__group" method="get" action="<?= e(url('search.php')) ?>">
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                    <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <div class="filters__label">Rentang harga</div>
                    <div class="price-range">
                        <input class="input" type="number" name="min" min="0" step="1000" placeholder="Min" value="<?= $min ?: '' ?>" aria-label="Harga minimum">
                        <span>–</span>
                        <input class="input" type="number" name="max" min="0" step="1000" placeholder="Maks" value="<?= $max ?: '' ?>" aria-label="Harga maksimum">
                    </div>
                    <div class="filters__label" style="margin-top:16px">Rating minimum</div>
                    <div class="rating-filter">
                        <?php foreach ([0 => 'Semua rating', 4 => '4 bintang ke atas', 3 => '3 bintang ke atas'] as $value => $label): ?>
                            <label class="check">
                                <input type="radio" name="rating" value="<?= $value ?: '' ?>" <?= (float) $value === $rating ? 'checked' : '' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm btn--full" style="margin-top:14px"><?= icon('filter', 14) ?>Terapkan Filter</button>
                </form>

                <div class="filters__group" style="padding-bottom:0">
                    <div class="notice notice--info" style="font-size:11px">
                        <?= icon('info', 15) ?>
                        <span>Produk berlabel <strong>Featured</strong> selalu tampil di urutan teratas hasil pencarian.</span>
                    </div>
                </div>
            </aside>

            <div>
                <div class="catalog-toolbar">
                    <div class="catalog-toolbar__title">
                        Menampilkan <span><?= number_format(count($result['items']), 0, ',', '.') ?> dari <?= number_format($result['total'], 0, ',', '.') ?> produk</span>
                    </div>
                    <form class="catalog-toolbar__sort" method="get" action="<?= e(url('search.php')) ?>">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
                        <input type="hidden" name="min" value="<?= $min ?: '' ?>">
                        <input type="hidden" name="max" value="<?= $max ?: '' ?>">
                        <input type="hidden" name="rating" value="<?= $rating ?: '' ?>">
                        <label for="sortSelect">Urutkan</label>
                        <select class="select" id="sortSelect" name="sort" data-autosubmit>
                            <?php foreach ($sortOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button type="submit" class="btn btn--outline btn--sm">Terapkan</button></noscript>
                    </form>
                </div>

                <?php if ($result['items'] === []): ?>
                    <?= empty_state(
                        'search',
                        $q !== '' ? 'Tidak ada hasil untuk “' . $q . '”' : 'Tidak ada produk yang cocok',
                        'Coba kata kunci lain atau longgarkan filter harga dan rating.',
                        '<div class="chips" style="justify-content:center">'
                        . '<a class="btn btn--outline btn--sm" href="' . e(url('search.php')) . '">Reset pencarian</a>'
                        . '<a class="btn btn--secondary btn--sm" href="' . e(url('search.php?cat=minuman')) . '">Minuman</a>'
                        . '<a class="btn btn--secondary btn--sm" href="' . e(url('search.php?cat=makanan')) . '">Makanan</a>'
                        . '<a class="btn btn--secondary btn--sm" href="' . e(url('search.php?cat=jasa-desain')) . '">Jasa Desain</a>'
                        . '</div>'
                    ) ?>
                <?php else: ?>
                    <div class="grid-products grid-products--4">
                        <?php foreach ($result['items'] as $product): ?>
                            <?= product_card($product) ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($result['pages'] > 1): ?>
                        <nav class="pagination" aria-label="Navigasi halaman">
                            <a class="pagination__link<?= $result['page'] <= 1 ? ' is-disabled' : '' ?>" href="<?= e($makeUrl(['page' => max(1, $result['page'] - 1)])) ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left', 16) ?></a>
                            <?php
                            $start = max(1, $result['page'] - 2);
                            $end = min($result['pages'], $start + 4);
                            $start = max(1, $end - 4);
                            for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a class="pagination__link<?= $i === $result['page'] ? ' is-active' : '' ?>" href="<?= e($makeUrl(['page' => $i])) ?>" <?= $i === $result['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                            <?php endfor; ?>
                            <a class="pagination__link<?= $result['page'] >= $result['pages'] ? ' is-disabled' : '' ?>" href="<?= e($makeUrl(['page' => min($result['pages'], $result['page'] + 1)])) ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right', 16) ?></a>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
