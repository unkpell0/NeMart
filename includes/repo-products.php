<?php
declare(strict_types=1);

/* =========================================================================
   Query katalog: kategori, produk, toko, keranjang.
   ========================================================================= */

function product_base_sql(): string
{
    return "SELECT p.*,
                   s.name AS store_name, s.slug AS store_slug, s.department AS store_department,
                   s.icon AS store_icon, s.plan AS store_plan, s.verified_status AS store_verified,
                   s.pickup_point AS store_pickup,
                   c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                   COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS rating,
                   (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count,
                   CASE WHEN p.boost_until IS NOT NULL AND p.boost_until > now_local() THEN 1 ELSE 0 END AS is_featured
            FROM products p
            JOIN stores s ON s.id = p.store_id
            JOIN categories c ON c.id = p.category_id";
}

function categories_all(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    }

    return $cache;
}

function category_by_slug(string $slug): ?array
{
    foreach (categories_all() as $cat) {
        if ($cat['slug'] === $slug) {
            return $cat;
        }
    }

    return null;
}

function store_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM stores WHERE id = ?');
    $stmt->execute([$id]);
    $store = $stmt->fetch();

    return $store ?: null;
}

function store_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM stores WHERE slug = ?');
    $stmt->execute([$slug]);
    $store = $stmt->fetch();

    return $store ?: null;
}

function store_stats(int $storeId): array
{
    static $cache = [];
    if (isset($cache[$storeId])) {
        return $cache[$storeId];
    }

    $stmt = db()->prepare('SELECT COALESCE(ROUND(AVG(rating), 1), 0) AS rating, COUNT(*) AS review_count FROM reviews WHERE store_id = ?');
    $stmt->execute([$storeId]);
    $rating = $stmt->fetch() ?: ['rating' => 0, 'review_count' => 0];

    $stmt = db()->prepare("SELECT COUNT(*) AS product_count, COALESCE(SUM(sold_count), 0) AS sold FROM products WHERE store_id = ? AND status = 'active'");
    $stmt->execute([$storeId]);
    $products = $stmt->fetch() ?: ['product_count' => 0, 'sold' => 0];

    return $cache[$storeId] = [
        'rating'        => (float) $rating['rating'],
        'review_count'  => (int) $rating['review_count'],
        'product_count' => (int) $products['product_count'],
        'sold'          => (int) $products['sold'],
        'verified'      => true,
    ];
}

function product_by_id(int $id): ?array
{
    $stmt = db()->prepare(product_base_sql() . ' WHERE p.id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    return $product ?: null;
}

/**
 * Pencarian dan filter katalog. Produk Featured selalu di urutan teratas.
 *
 * @param array{q?:string,cat?:string,min?:int,max?:int,rating?:float,sort?:string,page?:int,per_page?:int,store_id?:int,exclude_id?:int} $filter
 * @return array{items:array,total:int,page:int,per_page:int,pages:int}
 */
function products_search(array $filter): array
{
    $where = ["p.status = 'active'", "s.verified_status = 'verified'"];
    $params = [];

    $q = trim((string) ($filter['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR s.name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }

    $cat = trim((string) ($filter['cat'] ?? ''));
    if ($cat !== '') {
        $where[] = 'c.slug = ?';
        $params[] = $cat;
    }

    if (!empty($filter['min'])) {
        $where[] = 'p.price >= ?';
        $params[] = (int) $filter['min'];
    }
    if (!empty($filter['max'])) {
        $where[] = 'p.price <= ?';
        $params[] = (int) $filter['max'];
    }
    if (!empty($filter['rating'])) {
        $where[] = '(SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) >= ?';
        $params[] = (float) $filter['rating'];
    }
    if (!empty($filter['store_id'])) {
        $where[] = 'p.store_id = ?';
        $params[] = (int) $filter['store_id'];
    }
    if (!empty($filter['exclude_id'])) {
        $where[] = 'p.id <> ?';
        $params[] = (int) $filter['exclude_id'];
    }

    $whereSql = implode(' AND ', $where);

    $sortMap = [
        'terlaris' => 'p.sold_count DESC, p.id DESC',
        'terbaru'  => 'p.created_at DESC, p.id DESC',
        'termurah' => 'p.price ASC, p.id DESC',
        'termahal' => 'p.price DESC, p.id DESC',
        'rating'   => 'rating DESC, p.sold_count DESC',
    ];
    $sort = (string) ($filter['sort'] ?? 'terlaris');
    $orderSql = $sortMap[$sort] ?? $sortMap['terlaris'];

    $perPage = max(1, min(48, (int) ($filter['per_page'] ?? 12)));
    $countStmt = db()->prepare("SELECT COUNT(*) FROM products p JOIN stores s ON s.id = p.store_id JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, (int) ($filter['page'] ?? 1)));
    $offset = ($page - 1) * $perPage;

    $sql = product_base_sql() . " WHERE {$whereSql} ORDER BY is_featured DESC, {$orderSql} LIMIT {$perPage} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return [
        'items'    => $stmt->fetchAll(),
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => $pages,
    ];
}

function featured_products(int $limit = 6): array
{
    $limit = max(1, min(12, $limit));
    $sql = product_base_sql() . " WHERE p.status = 'active' AND s.verified_status = 'verified'
              AND p.boost_until IS NOT NULL AND p.boost_until > now_local()
              ORDER BY p.boost_until ASC LIMIT {$limit}";
    $rows = db()->query($sql)->fetchAll();

    // Bila boost belum ada, tampilkan produk terlaris agar seksi tidak kosong.
    if ($rows === []) {
        $rows = db()->query(product_base_sql() . " WHERE p.status = 'active' AND s.verified_status = 'verified' ORDER BY p.sold_count DESC LIMIT {$limit}")->fetchAll();
    }

    return $rows;
}

function stores_highlight(int $limit = 4): array
{
    $limit = max(1, min(12, $limit));
    $sql = "SELECT s.*,
                   COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.store_id = s.id), 0) AS rating,
                   (SELECT COUNT(*) FROM reviews r WHERE r.store_id = s.id) AS review_count,
                   (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id AND p.status = 'active') AS product_count,
                   (SELECT COALESCE(SUM(p.sold_count), 0) FROM products p WHERE p.store_id = s.id AND p.status = 'active') AS sold
            FROM stores s
            WHERE s.verified_status = 'verified'
            ORDER BY (s.plan = 'pro') DESC, rating DESC, sold DESC
            LIMIT {$limit}";
    $rows = db()->query($sql)->fetchAll();

    return array_map(static function (array $row): array {
        $row['stats'] = [
            'rating'        => (float) $row['rating'],
            'review_count'  => (int) $row['review_count'],
            'product_count' => (int) $row['product_count'],
            'sold'          => (int) $row['sold'],
            'verified'      => $row['verified_status'] === 'verified',
        ];

        return $row;
    }, $rows);
}

function store_products(int $storeId): array
{
    $stmt = db()->prepare(product_base_sql() . " WHERE p.store_id = ? AND p.status = 'active' ORDER BY is_featured DESC, p.sold_count DESC");
    $stmt->execute([$storeId]);

    return $stmt->fetchAll();
}

function related_products(int $storeId, int $excludeId, int $limit = 4): array
{
    $limit = max(1, min(12, $limit));
    $stmt = db()->prepare(product_base_sql() . " WHERE p.store_id = ? AND p.id <> ? AND p.status = 'active' ORDER BY is_featured DESC, p.sold_count DESC LIMIT {$limit}");
    $stmt->execute([$storeId, $excludeId]);

    return $stmt->fetchAll();
}

function product_reviews(int $productId): array
{
    $stmt = db()->prepare('SELECT r.*, u.name AS buyer_name, u.initials AS buyer_initials
                           FROM reviews r JOIN users u ON u.id = r.buyer_id
                           WHERE r.product_id = ? ORDER BY r.created_at DESC');
    $stmt->execute([$productId]);

    return $stmt->fetchAll();
}

function store_reviews(int $storeId): array
{
    $stmt = db()->prepare('SELECT r.*, u.name AS buyer_name, u.initials AS buyer_initials, p.name AS product_name, p.id AS product_id
                           FROM reviews r
                           JOIN users u ON u.id = r.buyer_id
                           JOIN products p ON p.id = r.product_id
                           WHERE r.store_id = ? ORDER BY r.created_at DESC');
    $stmt->execute([$storeId]);

    return $stmt->fetchAll();
}

function product_variants(array $product): array
{
    $decoded = json_decode((string) ($product['variants'] ?? '[]'), true);
    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter(array_map(static fn ($v) => is_string($v) ? trim($v) : '', $decoded), static fn ($v) => $v !== ''));
}

function bump_view(int $productId): void
{
    $stmt = db()->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?');
    $stmt->execute([$productId]);
}

/* ----------------------------------------------------------------- keranjang */

function cart_items(int $userId): array
{
    $stmt = db()->prepare("SELECT ci.id AS cart_id, ci.user_id, ci.product_id, ci.variant, ci.note, ci.qty, ci.created_at AS added_at,
                                  p.name, p.price, p.stock, p.status AS product_status, p.variants, p.is_po, p.image,
                                  s.id AS store_id, s.name AS store_name, s.slug AS store_slug,
                                  s.verified_status, s.plan, s.pickup_point, s.icon AS store_icon,
                                  c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                           FROM cart_items ci
                           JOIN products p ON p.id = ci.product_id
                           JOIN stores s ON s.id = p.store_id
                           JOIN categories c ON c.id = p.category_id
                           WHERE ci.user_id = ?
                           ORDER BY s.name, ci.id");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function cart_items_by_ids(int $userId, array $ids): array
{
    $items = cart_items($userId);
    if ($ids === []) {
        return $items;
    }
    $allowed = array_map('intval', $ids);
    $filtered = array_values(array_filter($items, static fn (array $item): bool => in_array((int) $item['cart_id'], $allowed, true)));

    return $filtered !== [] ? $filtered : $items;
}

function cart_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function cart_subtotal(array $items): int
{
    $total = 0;
    foreach ($items as $item) {
        $total += (int) $item['price'] * (int) $item['qty'];
    }

    return $total;
}

/**
 * Kelompokkan item keranjang per toko, sekaligus hitung biaya layanan per order.
 *
 * @return array<int, array{store_id:int,store_name:string,store_slug:string,pickup_point:string,items:array,subtotal:int,service_fee:int}>
 */
function cart_group_by_store(array $items): array
{
    $groups = [];
    foreach ($items as $item) {
        $storeId = (int) $item['store_id'];
        if (!isset($groups[$storeId])) {
            $groups[$storeId] = [
                'store_id'     => $storeId,
                'store_name'   => (string) $item['store_name'],
                'store_slug'   => (string) ($item['store_slug'] ?? ''),
                'pickup_point' => (string) ($item['pickup_point'] ?? ''),
                'items'        => [],
                'subtotal'     => 0,
                'service_fee'  => 1000,
            ];
        }
        $groups[$storeId]['items'][] = $item;
        $groups[$storeId]['subtotal'] += (int) $item['price'] * (int) $item['qty'];
    }

    return array_values($groups);
}

