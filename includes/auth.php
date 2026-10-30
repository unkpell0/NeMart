<?php
declare(strict_types=1);

/** Identitas login disimpan di sesi; data bisnis selalu dibaca dari database. */

function current_user(): ?array
{
    static $cached = false;

    if ($cached !== false) {
        return $cached;
    }

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return $cached = null;
    }

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);

        return $cached = null;
    }

    return $cached = $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): string
{
    return (string) (current_user()['role'] ?? 'guest');
}

/** Simpan tujuan setelah login agar pembeli kembali ke halaman produk. */
function intended_url(string $fallback = 'index.php'): string
{
    $next = (string) ($_GET['next'] ?? $_SESSION['intended'] ?? '');

    if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
        return $next;
    }

    return $fallback;
}

function require_login(): void
{
    if (current_user() !== null) {
        return;
    }
    $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
    flash('info', 'Masuk dulu untuk melanjutkan, ya.');
    redirect('login.php?next=' . rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? url('index.php'))));
}

function require_role(string ...$roles): void
{
    $user = current_user();

    if ($user === null) {
        require_login();
    }

    if (!in_array($user['role'], $roles, true)) {
        redirect('login.php?error=unauthorized');
    }
}

function require_admin(): void
{
    require_role('admin');
}

function seller_store(?int $ownerId = null): ?array
{
    static $cache = [];

    $ownerId ??= (int) (current_user()['id'] ?? 0);
    if ($ownerId <= 0) {
        return null;
    }
    if (array_key_exists($ownerId, $cache)) {
        return $cache[$ownerId];
    }

    $stmt = db()->prepare('SELECT * FROM stores WHERE owner_id = ? LIMIT 1');
    $stmt->execute([$ownerId]);
    $store = $stmt->fetch();

    return $cache[$ownerId] = $store ?: null;
}

function require_seller(): array
{
    require_role('seller');
    $store = seller_store();

    if ($store === null) {
        http_response_code(403);
        $error_title = 'Toko belum tersedia';
        $error_message = 'Akun ini belum memiliki toko terdaftar. Hubungi admin kampus untuk mendaftarkannya.';
        require dirname(__DIR__) . '/includes/error-page.php';
        exit;
    }

    return $store;
}

function store_is_pro(array $store): bool
{
    return ($store['plan'] ?? 'free') === 'pro'
        && !empty($store['pro_until'])
        && strtotime((string) $store['pro_until']) >= time();
}

function commission_rate(array $store): int
{
    return store_is_pro($store) ? 3 : 5;
}
