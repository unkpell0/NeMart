<?php
declare(strict_types=1);

/* =========================================================================
   Komponen tampilan yang dipakai ulang di seluruh halaman.
   ========================================================================= */

function order_status_map(): array
{
    return [
        'placed'     => ['label' => 'Dipesan',   'tone' => 'warning', 'icon' => 'clock'],
        'processing' => ['label' => 'Diproses',  'tone' => 'info',    'icon' => 'refresh'],
        'ready'      => ['label' => 'Siap',      'tone' => 'cyan',    'icon' => 'check-circle'],
        'completed'  => ['label' => 'Selesai',   'tone' => 'success', 'icon' => 'check-circle'],
        'cancelled'  => ['label' => 'Dibatalkan','tone' => 'neutral', 'icon' => 'x'],
    ];
}

function order_status_label(string $status, ?string $handover = null): string
{
    if ($status === 'ready' && $handover !== null) {
        return $handover === 'delivery' ? 'Sedang Diantar' : 'Siap Diambil';
    }

    return order_status_map()[$status]['label'] ?? ucfirst($status);
}

function status_badge(string $status, ?string $handover = null, string $extraClass = ''): string
{
    $meta = order_status_map()[$status] ?? ['label' => ucfirst($status), 'tone' => 'neutral', 'icon' => 'info'];

    return '<span class="badge badge--' . e($meta['tone']) . ' ' . e($extraClass) . '">' . icon($meta['icon'], 13) . '<span>' . e(order_status_label($status, $handover)) . '</span></span>';
}

function rating_stars(float $rating, ?int $count = null, int $size = 14, bool $showNumber = true): string
{
    $percent = max(0, min(100, ($rating / 5) * 100));
    $star = static fn (int $s): string => icon('star', $s);
    $row = str_repeat($star($size), 5);

    $html = '<span class="stars-wrap" role="img" aria-label="Rating ' . e(number_format($rating, 1, ',', '.')) . ' dari 5">';
    $html .= '<span class="stars stars--bg">' . $row . '</span>';
    $html .= '<span class="stars stars--fg" style="width:' . round($percent, 2) . '%">' . $row . '</span>';
    $html .= '</span>';
    if ($showNumber) {
        $html .= '<span class="rating-num">' . number_format($rating, 1, ',', '.') . '</span>';
    }
    if ($count !== null) {
        $html .= '<span class="rating-count">(' . number_format($count, 0, ',', '.') . ' ulasan)</span>';
    }

    return '<span class="rating">' . $html . '</span>';
}

function product_thumb(array $product, string $extraClass = '', int $iconSize = 52): string
{
    $slug = (string) ($product['category_slug'] ?? 'lainnya');
    $iconName = (string) ($product['category_icon'] ?? 'tag');
    $label = (string) ($product['category_name'] ?? 'Produk');

    return '<div class="thumb thumb--' . e($slug) . ' ' . e($extraClass) . '">'
        . '<span class="thumb__pattern" aria-hidden="true"></span>'
        . '<span class="thumb__icon">' . icon($iconName, $iconSize) . '</span>'
        . '<span class="thumb__label">' . e($label) . '</span>'
        . '</div>';
}

function product_card(array $p): string
{
    $href = url('product.php?id=' . (int) $p['id']);
    $stock = (int) ($p['stock'] ?? 0);
    $isPo = (int) ($p['is_po'] ?? 0) === 1;
    $featured = (int) ($p['is_featured'] ?? 0) === 1;
    $rating = (float) ($p['rating'] ?? 0);
    $reviewCount = (int) ($p['review_count'] ?? 0);
    $sold = (int) ($p['sold_count'] ?? 0);

    if ($stock <= 0) {
        $statusLabel = 'Habis';
        $statusTone = 'closed';
    } elseif ($isPo) {
        $statusLabel = 'Pre Order';
        $statusTone = 'po';
    } else {
        $statusLabel = 'Open';
        $statusTone = 'open';
    }

    $html = '<article class="p-card">';
    $html .= '<a class="p-card__media" href="' . e($href) . '" aria-label="' . e($p['name']) . '">';
    $html .= product_thumb($p);
    $html .= '<span class="status-dot status-dot--' . $statusTone . '">' . e($statusLabel) . '</span>';
    if ($featured) {
        $html .= '<span class="badge badge--featured p-card__featured">' . icon('sparkles', 12) . '<span>Featured</span></span>';
    }
    $html .= '</a>';

    $html .= '<div class="p-card__body">';
    $html .= '<a class="p-card__name" href="' . e($href) . '">' . e($p['name']) . '</a>';
    $html .= '<div class="p-card__price">' . rupiah((int) $p['price']) . '</div>';
    $html .= '<div class="p-card__meta">';
    $html .= '<span class="p-card__rating">' . icon('star', 13) . '<span>' . ($reviewCount > 0 ? number_format($rating, 1, ',', '.') : 'Baru') . '</span></span>';
    $html .= '<span class="p-card__sold">' . number_format($sold, 0, ',', '.') . ' terjual</span>';
    $html .= '</div>';
    $html .= '<a class="p-card__store" href="' . e(url('store.php?id=' . (int) $p['store_id'])) . '">' . icon('store', 13) . '<span>' . e($p['store_name'] ?? 'Toko') . '</span></a>';
    $html .= '<div class="p-card__escrow">' . icon('shield', 12) . '<span>Escrow Aman</span></div>';
    $html .= '</div>';
    $html .= '</article>';

    return $html;
}

function store_card(array $s, array $stats, array $productPreview = []): string
{
    $href = url('store.php?id=' . (int) $s['id']);
    $html = '<article class="store-card">';
    $html .= '<a class="store-card__banner" href="' . e($href) . '" aria-label="' . e($s['name']) . '">';
    if ($stats['verified']) {
        $html .= '<span class="badge badge--verified store-card__badge">' . icon('shield', 12) . '<span>Terverifikasi</span></span>';
    }
    $html .= '</a>';
    $html .= '<div class="store-card__body">';
    $html .= '<span class="store-card__logo">' . icon((string) ($s['icon'] ?? 'store'), 24) . '</span>';
    $html .= '<h3 class="store-card__name"><a href="' . e($href) . '">' . e($s['name']) . '</a>';
    if (($s['plan'] ?? 'free') === 'pro') {
        $html .= '<span class="badge badge--pro">PRO</span>';
    }
    $html .= '</h3>';
    $html .= '<p class="store-card__meta">' . icon('book', 13) . '<span>' . e($s['department'] ?? 'Polinema') . '</span></p>';
    $html .= '<div class="store-card__rating">';
    $html .= $stats['review_count'] > 0
        ? rating_stars((float) $stats['rating'], null, 13) . '<span class="muted">' . number_format((int) $stats['review_count'], 0, ',', '.') . ' ulasan</span>'
        : '<span class="muted">Belum ada ulasan</span>';
    $html .= '</div>';
    $html .= '<div class="store-card__stats"><span>' . number_format((int) $stats['product_count'], 0, ',', '.') . ' produk</span><span class="dot-sep"></span><span>' . number_format((int) $stats['sold'], 0, ',', '.') . ' terjual</span></div>';
    $html .= '<a class="btn btn--outline btn--sm btn--full store-card__cta" href="' . e($href) . '">Kunjungi Toko</a>';
    $html .= '</div>';
    $html .= '</article>';

    return $html;
}

function avatar(string $name, ?string $initials = null, string $extraClass = ''): string
{
    return '<span class="avatar ' . e($extraClass) . '" aria-hidden="true">' . e($initials ?: initials_of($name)) . '</span>';
}

function empty_state(string $iconName, string $title, string $text, string $action = ''): string
{
    return '<div class="empty">'
        . '<span class="empty__icon">' . icon($iconName, 30) . '</span>'
        . '<h3 class="empty__title">' . e($title) . '</h3>'
        . '<p class="empty__text">' . e($text) . '</p>'
        . ($action !== '' ? '<div class="empty__action">' . $action . '</div>' : '')
        . '</div>';
}

function section_head(string $title, string $sub = '', ?string $href = null, string $linkLabel = 'Lihat semua'): string
{
    $html = '<div class="section-head"><div><h2 class="section-title">' . e($title) . '</h2>';
    if ($sub !== '') {
        $html .= '<p class="section-sub">' . e($sub) . '</p>';
    }
    $html .= '</div>';
    if ($href !== null) {
        $html .= '<a class="link-arrow" href="' . e($href) . '">' . e($linkLabel) . icon('arrow-right', 15) . '</a>';
    }
    $html .= '</div>';

    return $html;
}

function breadcrumb(array $items): string
{
    $parts = [];
    $last = count($items) - 1;
    foreach ($items as $label => $link) {
        if (is_int($label)) {
            $parts[] = '<span aria-current="page">' . e((string) $link) . '</span>';
            continue;
        }
        $parts[] = '<a href="' . e($link) . '">' . e($label) . '</a>';
    }

    return '<nav class="breadcrumb" aria-label="Breadcrumb">' . implode('<span class="breadcrumb__sep">/</span>', $parts) . '</nav>';
}

function flash_render(): string
{
    $items = flash_take();
    if ($items === []) {
        return '';
    }

    $html = '<div class="flash-stack" role="status">';
    foreach ($items as $item) {
        $type = in_array($item['type'], ['success', 'info', 'warning', 'danger'], true) ? $item['type'] : 'info';
        $icon = ['success' => 'check-circle', 'info' => 'info', 'warning' => 'alert', 'danger' => 'alert'][$type];
        $html .= '<div class="flash flash--' . $type . '">' . icon($icon, 18) . '<span>' . e($item['message']) . '</span><button type="button" class="flash__close" data-dismiss="flash" aria-label="Tutup">' . icon('x', 14) . '</button></div>';
    }
    $html .= '</div>';

    return $html;
}

function plan_badge(array $store): string
{
    return store_is_pro($store)
        ? '<span class="badge badge--pro">' . icon('sparkles', 12) . '<span>PRO</span></span>'
        : '<span class="badge badge--neutral">Free</span>';
}

function stat_card(string $label, string $value, string $iconName, string $tone = 'blue', string $hint = ''): string
{    $html = '<div class="stat-card stat-card--' . e($tone) . '">';
    $html .= '<span class="stat-card__icon">' . icon($iconName, 20) . '</span>';
    $html .= '<span class="stat-card__label">' . e($label) . '</span>';
    $html .= '<strong class="stat-card__value">' . e($value) . '</strong>';
    if ($hint !== '') {
        $html .= '<span class="stat-card__hint">' . e($hint) . '</span>';
    }
    $html .= '</div>';

    return $html;
}

function tabs_nav(array $tabs, string $activeKey, string $baseUrl, string $param = 'status', array $keep = []): string{
    $html = '<nav class="tabs" aria-label="Filter status"><div class="tabs__scroll">';
    foreach ($tabs as $key => $tab) {
        $isActive = $key === $activeKey;
        $query = $keep;
        if ($key !== 'all') {
            $query[$param] = $key;
        }
        $href = $baseUrl . ($query === [] ? '' : '?' . http_build_query($query));
        $html .= '<a class="tab' . ($isActive ? ' is-active' : '') . '" href="' . e($href) . '"' . ($isActive ? ' aria-current="page"' : '') . '>'
            . e($tab['label']);
        if (isset($tab['count'])) {
            $html .= '<span class="tab__count">' . number_format((int) $tab['count'], 0, ',', '.') . '</span>';
        }
        $html .= '</a>';
    }
    $html .= '</div></nav>';

    return $html;
}

/**
 * QR dummy untuk layar pembayaran simulasi. Pola dibuat deterministik dari seed
 * agar terlihat seperti QR asli tanpa memindai apa pun.
 */
function qr_dummy_svg(string $seed): string
{
    $n = 25;
    $cell = 6;
    $pad = 2;
    $dim = ($n + $pad * 2) * $cell;
    $stream = str_repeat(hash('sha256', $seed), 12);
    $idx = 0;
    $rects = '';

    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $inFinder = ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
            if ($inFinder) {
                continue;
            }
            $dark = hexdec($stream[$idx % strlen($stream)]) % 2 === 0;
            $idx++;
            if ($dark) {
                $rects .= '<rect x="' . (($x + $pad) * $cell) . '" y="' . (($y + $pad) * $cell) . '" width="' . $cell . '" height="' . $cell . '" rx="1" fill="#0B2FC9"/>';
            }
        }
    }

    $finders = '';
    foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$fx, $fy]) {
        $x = ($fx + $pad) * $cell;
        $y = ($fy + $pad) * $cell;
        $finders .= '<rect x="' . $x . '" y="' . $y . '" width="' . (7 * $cell) . '" height="' . (7 * $cell) . '" fill="#0B2FC9"/>';
        $finders .= '<rect x="' . ($x + $cell) . '" y="' . ($y + $cell) . '" width="' . (5 * $cell) . '" height="' . (5 * $cell) . '" fill="#fff"/>';
        $finders .= '<rect x="' . ($x + 2 * $cell) . '" y="' . ($y + 2 * $cell) . '" width="' . (3 * $cell) . '" height="' . (3 * $cell) . '" fill="#0B2FC9"/>';
    }

    return '<svg viewBox="0 0 ' . $dim . ' ' . $dim . '" width="172" height="172" role="img" aria-label="Kode QR pembayaran simulasi">'
        . '<rect width="' . $dim . '" height="' . $dim . '" fill="#fff"/>' . $rects . $finders . '</svg>';
}
