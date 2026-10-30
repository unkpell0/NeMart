<?php
declare(strict_types=1);

/* ------------------------------------------------------------------ escaping */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* --------------------------------------------------------------------- uang */

function rupiah(int|float|string|null $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

/* --------------------------------------------------------------- URL & base */

function url(string $path = ''): string
{
    $base = defined('APP_BASE') ? APP_BASE : '';
    $path = ltrim($path, '/');

    return $path === '' ? ($base === '' ? '/' : $base . '/') : $base . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function current_path(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
}

/* ------------------------------------------------------------------ tanggal */

function now(): string
{
    return date('Y-m-d H:i:s');
}

function tanggal(?string $datetime, bool $withTime = false, bool $withYear = true): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $text = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1];
    if ($withYear) {
        $text .= ' ' . date('Y', $ts);
    }
    if ($withTime) {
        $text .= ', ' . date('H:i', $ts);
    }

    return $text;
}

function tanggal_lengkap(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }

    return $hari[(int) date('w', $ts)] . ', ' . tanggal($datetime, true);
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        return 'baru saja';
    }
    if ($diff < 60) {
        return 'baru saja';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);

        return $m . ' menit lalu';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);

        return $h . ' jam lalu';
    }
    if ($diff < 86400 * 7) {
        $d = (int) floor($diff / 86400);

        return $d . ' hari lalu';
    }

    return tanggal($datetime);
}

/* ------------------------------------------------------------------ inisial */

function initials_of(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));
    if ($parts === []) {
        return 'NM';
    }
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

    return mb_strtoupper($first . $last);
}

/* -------------------------------------------------------------------- flash */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($items) ? $items : [];
}

/* --------------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token = null): bool
{
    $token ??= (string) ($_POST['csrf_token'] ?? '');
    $expected = (string) ($_SESSION['csrf'] ?? '');

    return $expected !== '' && $token !== '' && hash_equals($expected, $token);
}

function require_csrf(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        $error_code = '419';
        $error_title = 'Sesi formulir kedaluwarsa';
        $error_message = 'Halaman ini terlalu lama terbuka. Muat ulang halaman lalu kirim formulirnya sekali lagi.';
        require dirname(__DIR__) . '/includes/error-page.php';
        exit;
    }
}

/* ------------------------------------------------------------------- input */

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function input_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? $_GET[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }

    return (int) $value;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* -------------------------------------------------------------------- ikon */

function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = icon_paths();
    $body = $paths[$name] ?? $paths['tag'];
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';

    return '<svg' . $cls . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

function icon_paths(): array
{
    static $paths = null;
    if ($paths !== null) {
        return $paths;
    }

    $paths = [
        'store'       => '<path d="M4 9h16l-1-5H5L4 9Z"/><path d="M5 9v11h14V9"/><path d="M4 9a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0"/><path d="M10 20v-6h4v6"/>',
        'utensils'    => '<path d="M5 3v7a2 2 0 0 0 4 0V3"/><path d="M7 10v11"/><path d="M17 3c-1.5 1.5-2 3.5-2 6s.5 4 2 4"/><path d="M17 3v18"/>',
        'coffee'      => '<path d="M4 8h12v5a6 6 0 0 1-12 0V8Z"/><path d="M16 9h2a3 3 0 0 1 0 6h-2"/><path d="M4 21h13"/><path d="M8 3v2M12 3v2"/>',
        'cake'        => '<path d="M4 21h16"/><path d="M5 21v-7h14v7"/><path d="M5 14c0-2 2-3 3.5-2S11 14 12 14s2.5-3 4-2 3 0 3 2"/><path d="M12 7V4"/>',
        'print'       => '<path d="M7 8V3h10v5"/><path d="M5 8h14a2 2 0 0 1 2 2v6h-4"/><path d="M7 16H3v-6a2 2 0 0 1 2-2"/><path d="M7 14h10v7H7z"/>',
        'laundry'     => '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="13" r="4"/><path d="M8 7h.01M11 7h.01"/>',
        'pen'         => '<path d="m14 4 6 6-9.5 9.5L4 21l1.5-6.5L14 4Z"/><path d="m12.5 5.5 6 6"/>',
        'shirt'       => '<path d="M8 3 4 5l-1.5 5L6 11v10h12V11l3.5-1L20 5l-4-2a4 4 0 0 1-8 0Z"/>',
        'book'        => '<path d="M4 5a2 2 0 0 1 2-2h5v18H6a2 2 0 0 1-2-2V5Z"/><path d="M20 5a2 2 0 0 0-2-2h-5v18h5a2 2 0 0 0 2-2V5Z"/>',
        'tag'         => '<path d="M12.6 2.7a2 2 0 0 0-1.4-.6H4a2 2 0 0 0-2 2v7.2a2 2 0 0 0 .6 1.4l8.4 8.4a2 2 0 0 0 2.8 0l7.2-7.2a2 2 0 0 0 0-2.8l-8.4-8.4Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart'        => '<path d="M3 4h2l2.5 11h10L20 7H6"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>',
        'bell'        => '<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6Z"/><path d="M10.5 20a2 2 0 0 0 3 0"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'star'        => '<path fill="currentColor" stroke="none" d="M12 3.4l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.8l-5.2 2.7 1-5.8L3.5 9.6l5.9-.9L12 3.4Z"/>',
        'check'       => '<path d="m5 13 4 4L19 7"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
        'chevron-right'=>'<path d="m9 6 6 6-6 6"/>',
        'chevron-left'=> '<path d="m15 6-6 6 6 6"/>',
        'arrow-right' => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
        'arrow-left'  => '<path d="M20 12H4"/><path d="m10 6-6 6 6 6"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'minus'       => '<path d="M5 12h14"/>',
        'trash'       => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
        'x'           => '<path d="M6 6l12 12M18 6 6 18"/>',
        'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'home'        => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/>',
        'box'         => '<path d="m12 3 9 5v8l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/>',
        'clipboard'   => '<path d="M9 4h6v3H9z"/><path d="M9 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-3"/><path d="M8 12h8M8 16h5"/>',
        'chart'       => '<path d="M4 4v16h16"/><rect x="7" y="12" width="3" height="5"/><rect x="12" y="9" width="3" height="8"/><rect x="17" y="6" width="3" height="11"/>',
        'trend'       => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'wallet'      => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.2"/>',
        'shield'      => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'truck'       => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'map-pin'     => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'logout'      => '<path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5"/><path d="M15 8l4 4-4 4"/><path d="M19 12H9"/>',
        'edit'        => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m14.5 5.5 4 4"/>',
        'eye'         => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.6"/>',
        'message'     => '<path d="M4 5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9l-5 4V6a1 1 0 0 1 1-1Z"/>',
        'lock'        => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 15v2"/>',
        'sparkles'    => '<path d="M12 3l1.8 4.9L19 9.7l-5.2 1.8L12 16.4l-1.8-4.9L5 9.7l5.2-1.8L12 3Z"/><path d="M18.5 15.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z"/>',
        'filter'      => '<path d="M4 6h16l-6 7v6l-4-2v-4L4 6Z"/>',
        'qr'          => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z"/><path d="M20 14v1M20 19v2M17 21h4"/>',
        'credit-card' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/><path d="M6 15h3"/>',
        'smartphone'  => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/>',
        'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 8h.01"/>',
        'alert'       => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5M12 17h.01"/>',
        'refresh'     => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v5h-5"/>',
        'external'    => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'flame'       => '<path d="M12 3c3 4 6 5.5 6 9a6 6 0 0 1-12 0c0-2 1-3.5 2.5-5 .5 1.5 1.5 2 2.5 2 0-2 .5-4 1-6Z"/>',
        'layers'      => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'sliders'     => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'mouse-pointer'=> '<path d="m4 4 7 17 2.5-7 7-2.5L4 4Z"/>',
    ];

    return $paths;
}
