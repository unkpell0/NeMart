<?php
declare(strict_types=1);

/** Notifikasi in-app: ditulis oleh sistem pada setiap perubahan pesanan/ulasan. */

function notify(int $userId, string $type, string $title, string $body = '', ?string $link = null, ?PDO $pdo = null): void
{
    $pdo ??= db();
    if (!in_array($type, ['order', 'review', 'system'], true)) {
        $type = 'system';
    }
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at) VALUES (?,?,?,?,?,0,?)');
    $stmt->execute([$userId, $type, $title, $body, $link, now()]);
}

function unread_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

function recent_notifications(int $userId, int $limit = 5): array
{
    $limit = max(1, min(20, $limit));
    $stmt = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT {$limit}");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function all_notifications(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function notification_by_id(int $id, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $notif = $stmt->fetch();

    return $notif ?: null;
}

function mark_notification_read(int $id, int $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
}

function mark_all_notifications_read(int $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}

function notification_icon(string $type): string
{
    return match ($type) {
        'order'  => 'box',
        'review' => 'star',
        default  => 'bell',
    };
}
