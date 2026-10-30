<?php
/**
 * Kerangka Panel Admin: sidebar, topbar, pembuka konten.
 * Variabel: $page_title, $active_nav, $shell_title, $shell_subtitle.
 */
$user = current_user();
$active_nav = $active_nav ?? '';
$shell_title = $shell_title ?? ($page_title ?? 'Panel Admin');
$shell_subtitle = $shell_subtitle ?? '';
$body_class = 'has-shell';
$notifCount = $user ? unread_count((int) $user['id']) : 0;

$pendingCount = (int) db()->query("SELECT COUNT(*) FROM stores WHERE verified_status = 'pending'")->fetchColumn();
$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard',          'icon' => 'home',      'href' => 'admin/dashboard.php'],
    ['key' => 'sellers',   'label' => 'Verifikasi Penjual', 'icon' => 'shield',    'href' => 'admin/sellers.php', 'count' => $pendingCount],
    ['key' => 'orders',    'label' => 'Monitor Pesanan',    'icon' => 'clipboard', 'href' => 'admin/orders.php'],
];
require __DIR__ . '/head.php';
?>
<div class="shell">
    <aside class="sidebar" id="adminSidebar" aria-label="Navigasi Panel Admin">
        <a class="sidebar__brand" href="<?= e(url('admin/dashboard.php')) ?>">
            <span class="brand__mark"><span>N</span></span>
            <span class="brand__text">
                <span class="brand__name">Panel Admin</span>
                <span class="brand__tag">NeMart Polinema</span>
            </span>
        </a>

        <div class="sidebar__store">
            <span class="avatar avatar--sm"><?= e(initials_of((string) $user['name'])) ?></span>
            <div style="min-width:0">
                <div class="sidebar__store-name"><?= e($user['name']) ?></div>
                <div class="sidebar__store-meta">Pengawas platform</div>
            </div>
        </div>

        <nav class="sidebar__nav">
            <?php foreach ($navItems as $item): ?>
                <a class="sidebar__link<?= $active_nav === $item['key'] ? ' is-active' : '' ?>" href="<?= e(url($item['href'])) ?>">
                    <?= icon($item['icon'], 17) ?>
                    <span><?= e($item['label']) ?></span>
                    <?php if (!empty($item['count'])): ?>
                        <span class="sidebar__link-count"><?= (int) $item['count'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <div class="sidebar__label">Lainnya</div>
            <a class="sidebar__link" href="<?= e(url('index.php')) ?>"><?= icon('store', 17) ?><span>Lihat Marketplace</span></a>
            <a class="sidebar__link" href="<?= e(url('notifications.php')) ?>"><?= icon('bell', 17) ?><span>Notifikasi</span><?php if ($notifCount > 0): ?><span class="sidebar__link-count"><?= (int) $notifCount ?></span><?php endif; ?></a>
        </nav>

        <div class="sidebar__foot">
            <div class="plan-card">
                <div class="plan-card__title"><?= icon('info', 13) ?>Mode demo</div>
                <div class="plan-card__text">Data disimpan di SQLite. Gunakan tombol reset di dashboard sebelum presentasi.</div>
            </div>
        </div>
    </aside>

    <div class="shell__overlay" data-sidebar-close></div>

    <div class="shell__main">
        <div class="shell__topbar">
            <button class="icon-btn shell__menu-btn" type="button" data-sidebar-toggle aria-label="Buka menu admin" aria-controls="adminSidebar" aria-expanded="false">
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
                <a class="btn btn--outline btn--sm" href="<?= e(url('index.php')) ?>">
                    <?= icon('external', 15) ?><span>Marketplace</span>
                </a>
                <form method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-btn" aria-label="Keluar dari akun"><?= icon('logout', 19) ?></button>
                </form>
            </div>
        </div>

        <div class="shell__content">
            <?= flash_render() ?>
