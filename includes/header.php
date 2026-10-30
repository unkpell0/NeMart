<?php
/** Header publik: top bar, logo, pencarian, keranjang, notifikasi, akun. */
$user = current_user();
$cartCount = $user ? cart_count((int) $user['id']) : 0;
$unreadCount = $user ? unread_count((int) $user['id']) : 0;
$recentNotifs = $user ? recent_notifications((int) $user['id'], 5) : [];
$searchValue = is_string($_GET['q'] ?? null) ? trim((string) $_GET['q']) : '';
?>
<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__left">
            <?= icon('store', 13) ?>
            <span>Marketplace usaha mahasiswa Politeknik Negeri Malang</span>
        </div>
        <div class="topbar__right">
            <span class="topbar__item"><?= icon('shield', 13) ?><span>Escrow aman</span></span>
            <span class="topbar__item"><?= icon('check-circle', 13) ?><span>Toko terverifikasi</span></span>
        </div>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container header__inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="NeMart, kembali ke beranda">
            <span class="brand__mark"><span>N</span></span>
            <span class="brand__text">
                <span class="brand__name">NeMart</span>
                <span class="brand__tag">Polinema</span>
            </span>
        </a>

        <div class="header-search">
            <form class="header-search__form" action="<?= e(url('search.php')) ?>" method="get" role="search">
                <?= icon('search', 18) ?>
                <input type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Cari ayam geprek, kopi, jasa desain…" aria-label="Cari produk di NeMart" autocomplete="off">
                <button type="submit"><?= icon('search', 15) ?><span>Cari</span></button>
            </form>
        </div>

        <div class="header-actions">
            <a class="icon-btn" href="<?= e(url('cart.php')) ?>" aria-label="Keranjang, <?= $cartCount ?> item">
                <?= icon('cart', 22) ?>
                <?php if ($cartCount > 0): ?>
                    <span class="icon-btn__badge icon-btn__badge--cart"><?= $cartCount > 99 ? '99+' : (int) $cartCount ?></span>
                <?php endif; ?>
            </a>

            <?php if ($user): ?>
                <div class="menu-wrap">
                    <button class="icon-btn" type="button" data-menu-toggle="notifMenu" aria-expanded="false" aria-controls="notifMenu" aria-label="Notifikasi<?= $unreadCount > 0 ? ', ' . $unreadCount . ' belum dibaca' : '' ?>">
                        <?= icon('bell', 22) ?>
                        <?php if ($unreadCount > 0): ?>
                            <span class="icon-btn__badge"><?= $unreadCount > 9 ? '9+' : (int) $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="menu notif-menu" id="notifMenu" hidden>
                        <div class="menu__head">
                            <span class="menu__title">Notifikasi</span>
                            <?php if ($unreadCount > 0): ?>
                                <form method="post" action="<?= e(url('notifications.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="read_all">
                                    <button type="submit" class="btn btn--link" style="font-size:11px">Tandai semua dibaca</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="notif-menu__list">
                            <?php if ($recentNotifs === []): ?>
                                <div class="notif-item">
                                    <span class="notif-item__icon"><?= icon('bell', 16) ?></span>
                                    <div>
                                        <div class="notif-item__title">Belum ada notifikasi</div>
                                        <div class="notif-item__body">Kabar pesanan dan ulasan akan muncul di sini.</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $notif): ?>
                                    <a class="notif-item<?= (int) $notif['is_read'] === 0 ? ' is-unread' : '' ?>" href="<?= e(url('notifications.php?read=' . (int) $notif['id'])) ?>">
                                        <span class="notif-item__icon notif-item__icon--<?= e($notif['type']) ?>"><?= icon(notification_icon((string) $notif['type']), 16) ?></span>
                                        <span>
                                            <span class="notif-item__title"><?= e($notif['title']) ?></span>
                                            <span class="notif-item__body"><?= e((string) $notif['body']) ?></span>
                                            <span class="notif-item__time"><?= e(time_ago((string) $notif['created_at'])) ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="menu__footer">
                            <a class="menu__link" href="<?= e(url('notifications.php')) ?>"><?= icon('bell', 16) ?><span>Lihat semua notifikasi</span></a>
                        </div>
                    </div>
                </div>

                <div class="menu-wrap">
                    <button class="account-btn" type="button" data-menu-toggle="accountMenu" aria-expanded="false" aria-controls="accountMenu">
                        <?= avatar((string) $user['name'], (string) $user['initials']) ?>
                        <span class="account-btn__name"><?= e($user['name']) ?></span>
                        <?= icon('chevron-down', 15, 'account-btn__chevron') ?>
                    </button>
                    <div class="menu" id="accountMenu" hidden>
                        <div class="menu__head">
                            <div>
                                <div class="menu__title"><?= e($user['name']) ?></div>
                                <div class="small muted"><?= e($user['email']) ?></div>
                            </div>
                        </div>
                        <?php if ($user['role'] === 'buyer'): ?>
                            <a class="menu__link" href="<?= e(url('orders.php')) ?>"><?= icon('box', 16) ?><span>Pesanan Saya</span></a>
                        <?php elseif ($user['role'] === 'seller'): ?>
                            <a class="menu__link" href="<?= e(url('seller/dashboard.php')) ?>"><?= icon('store', 16) ?><span>Seller Centre</span></a>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <a class="menu__link" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('clipboard', 16) ?><span>Panel Admin</span></a>
                        <?php endif; ?>
                        <a class="menu__link" href="<?= e(url('notifications.php')) ?>"><?= icon('bell', 16) ?><span>Notifikasi</span></a>
                        <a class="menu__link" href="<?= e(url('cart.php')) ?>"><?= icon('cart', 16) ?><span>Keranjang<?= $cartCount > 0 ? ' (' . $cartCount . ')' : '' ?></span></a>
                        <div class="menu__sep"></div>
                        <form method="post" action="<?= e(url('logout.php')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="menu__link menu__link--danger"><?= icon('logout', 16) ?><span>Keluar</span></button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn-login" href="<?= e(url('login.php')) ?>">
                    <?= icon('user', 17) ?><span>Masuk</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
