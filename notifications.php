<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$userId = (int) $user['id'];

if (isset($_GET['read'])) {
    $notif = notification_by_id((int) $_GET['read'], $userId);
    if ($notif) {
        mark_notification_read((int) $notif['id'], $userId);
        $link = (string) ($notif['link'] ?? '');
        if ($link !== '' && !str_starts_with($link, '//') && !preg_match('#^https?://#i', $link)) {
            redirect($link);
        }
    }
    redirect('notifications.php');
}

if (is_post()) {
    require_csrf();
    if (($_POST['action'] ?? '') === 'read_all') {
        mark_all_notifications_read($userId);
        flash('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
    redirect('notifications.php');
}

$notifications = all_notifications($userId);
$unread = 0;
foreach ($notifications as $notif) {
    if ((int) $notif['is_read'] === 0) {
        $unread++;
    }
}

$page_title = 'Notifikasi';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main id="main">
    <div class="container page">
        <?= flash_render() ?>
        <?= breadcrumb(['Beranda' => url('index.php'), 'Notifikasi']) ?>

        <div class="page-head">
            <div>
                <h1 class="page-title">Notifikasi</h1>
                <p class="page-sub"><?= $unread > 0 ? $unread . ' notifikasi belum dibaca' : 'Semua notifikasi sudah dibaca' ?></p>
            </div>
        </div>

        <div class="notif-page">
            <div class="notif-page__head">
                <span class="small muted"><?= count($notifications) ?> notifikasi</span>
                <?php if ($unread > 0): ?>
                    <form method="post" action="<?= e(url('notifications.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="read_all">
                        <button type="submit" class="btn btn--outline btn--sm"><?= icon('check', 14) ?>Tandai semua dibaca</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($notifications === []): ?>
                <?= empty_state('bell', 'Belum ada notifikasi', 'Kabar pesanan baru, perubahan status, dan ulasan akan muncul di sini.') ?>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <a class="notif-row<?= (int) $notif['is_read'] === 0 ? ' is-unread' : '' ?>" href="<?= e(url('notifications.php?read=' . (int) $notif['id'])) ?>">
                        <span class="notif-row__icon notif-row__icon--<?= e($notif['type']) ?>"><?= icon(notification_icon((string) $notif['type']), 18) ?></span>
                        <span style="flex:1;min-width:0">
                            <span class="notif-row__title"><?= e($notif['title']) ?></span>
                            <span class="notif-row__body"><?= e((string) $notif['body']) ?></span>
                            <span class="notif-row__time"><?= e(time_ago((string) $notif['created_at'])) ?> · <?= e(tanggal((string) $notif['created_at'], true)) ?></span>
                        </span>
                        <?php if ((int) $notif['is_read'] === 0): ?>
                            <span class="notif-row__dot" aria-label="Belum dibaca"></span>
                        <?php endif; ?>
                        <span style="color:var(--text-soft);align-self:center"><?= icon('chevron-right', 16) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
