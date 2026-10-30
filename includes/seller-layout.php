<?php
/**
 * Kerangka Seller Centre: sidebar, topbar, dan pembuka konten.
 * Variabel yang dipakai: $store, $page_title, $active_nav, $shell_title, $shell_subtitle.
 */
$user = current_user();
$active_nav = $active_nav ?? '';
$shell_title = $shell_title ?? ($page_title ?? 'Seller Centre');
$shell_subtitle = $shell_subtitle ?? '';
$body_class = 'has-shell';
$notifCount = $user ? unread_count((int) $user['id']) : 0;
$newOrders = (int) (seller_summary((int) $store['id'])['new_orders'] ?? 0);

$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard',      'icon' => 'home',      'href' => 'seller/dashboard.php'],
    ['key' => 'orders',    'label' => 'Pesanan',        'icon' => 'box',       'href' => 'seller/orders.php', 'count' => $newOrders],
    ['key' => 'products',  'label' => 'Produk',         'icon' => 'tag',       'href' => 'seller/products.php'],
    ['key' => 'reviews',   'label' => 'Ulasan',         'icon' => 'star',      'href' => 'seller/reviews.php'],
    ['key' => 'boost',     'label' => 'Promosi Boost',  'icon' => 'sparkles',  'href' => 'seller/boost.php'],
    ['key' => 'pro',       'label' => 'Langganan PRO',  'icon' => 'wallet',    'href' => 'seller/pro.php'],
    ['key' => 'tracking',  'label' => 'Data Tracking',  'icon' => 'chart',     'href' => 'seller/tracking.php'],
];
require __DIR__ . '/head.php';
?>
<div class="shell">
    <aside class="sidebar" id="sellerSidebar" aria-label="Navigasi Seller Centre">
        <a class="sidebar__brand" href="<?= e(url('seller/dashboard.php')) ?>" aria-label="NeMart Seller Centre">
            <span class="sidebar__brand-logo"><img src="<?= e(asset('img/logo.png')) ?>" alt="NeMart" width="643" height="200"></span>
            <span class="sidebar__brand-role">Seller Centre</span>
        </a>

        <div class="sidebar__store">
            <span class="avatar avatar--sm"><?= icon((string) ($store['icon'] ?: 'store'), 16) ?></span>
            <div style="min-width:0">
                <div class="sidebar__store-name"><?= e($store['name']) ?></div>
                <div class="sidebar__store-meta"><?= e($store['department'] ?: 'Polinema') ?></div>
            </div>
        </div>

        <nav class="sidebar__nav">
            <?php foreach ($navItems as $item): ?>
                <?php if ($item['key'] === 'pro'): ?>
                    <div class="sidebar__label">Paket &amp; Analitik</div>
                <?php endif; ?>
                <a class="sidebar__link<?= $active_nav === $item['key'] ? ' is-active' : '' ?>" href="<?= e(url($item['href'])) ?>">
                    <?= icon($item['icon'], 17) ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if (!empty($item['count'])): ?>
                        <span class="sidebar__link-count"><?= (int) $item['count'] ?></span>
                    <?php endif; ?>
                    <?php if ($item['key'] === 'tracking' && !store_is_pro($store)): ?>
                        <span style="margin-left:auto;display:inline-flex"><?= icon('lock', 13) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <div class="sidebar__label">Lainnya</div>
            <a class="sidebar__link" href="<?= e(url('store.php?id=' . (int) $store['id'])) ?>"><?= icon('store', 17) ?><span>Lihat Toko Publik</span></a>
            <a class="sidebar__link" href="<?= e(url('notifications.php')) ?>"><?= icon('bell', 17) ?><span>Notifikasi</span><?php if ($notifCount > 0): ?><span class="sidebar__link-count"><?= (int) $notifCount ?></span><?php endif; ?></a>
        </nav>

        <div class="sidebar__foot">
            <?php if (store_is_pro($store)): ?>
                <div class="plan-card">
                    <div class="plan-card__title"><?= icon('sparkles', 13) ?>Paket PRO aktif</div>
                    <div class="plan-card__text">Komisi 3%, 3 Boost gratis/bulan, dan Data Tracking terbuka sampai <?= e(tanggal((string) $store['pro_until'])) ?>.</div>
                    <a class="btn btn--sm btn--full" href="<?= e(url('seller/pro.php')) ?>">Kelola langganan</a>
                </div>
            <?php else: ?>
                <div class="plan-card">
                    <div class="plan-card__title"><?= icon('sparkles', 13) ?>Paket Free</div>
                    <div class="plan-card__text">Komisi 5% dan tanpa Boost gratis. Upgrade untuk membuka Data Tracking.</div>
                    <a class="btn btn--sm btn--full" href="<?= e(url('seller/pro.php')) ?>">Upgrade ke PRO</a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <div class="shell__overlay" data-sidebar-close></div>

    <div class="shell__main">
        <div class="shell__topbar">
            <button class="icon-btn shell__menu-btn" type="button" data-sidebar-toggle aria-label="Buka menu seller" aria-controls="sellerSidebar" aria-expanded="false">
                <?= icon('menu', 22) ?>
            </button>
            <div>
                <div class="shell__title"><?= e($shell_title) ?></div>
                <?php if ($shell_subtitle !== ''): ?>
                    <div class="shell__subtitle"><?= e($shell_subtitle) ?></div>
                <?php endif; ?>
            </div>
            <div class="shell__actions">
                <a class="icon-btn" href="<?= e(url('notifications.php')) ?>" aria-label="Notifikasi">
                    <?= icon('bell', 20) ?>
                    <?php if ($notifCount > 0): ?><span class="icon-btn__badge"><?= $notifCount > 9 ? '9+' : (int) $notifCount ?></span><?php endif; ?>
                </a>
                <a class="btn btn--outline btn--sm" href="<?= e(url('store.php?id=' . (int) $store['id'])) ?>">
                    <?= icon('external', 15) ?><span>Lihat Toko</span>
                </a>
                <form method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-btn" aria-label="Keluar dari akun"><?= icon('logout', 19) ?></button>
                </form>
            </div>
        </div>

        <div class="shell__content">
            <?= flash_render() ?>
