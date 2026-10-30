<?php
/**
 * Uji alur demo NeMart lewat HTTP (server: php -S 127.0.0.1:8123 -t nepublikt).
 * Jalankan: php tools/smoke.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

$baseOverride = getenv('NEMART_BASE') ?: '';
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseOverride = substr($arg, 7);
    }
}
define('BASE', rtrim($baseOverride !== '' ? $baseOverride : 'http://127.0.0.1:8123', '/'));

$pass = 0;
$fail = 0;
$failures = [];

function ok(string $label, bool $condition, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($condition) {
        $pass++;
        echo "  PASS  {$label}\n";
    } else {
        $fail++;
        $failures[] = $label . ($detail !== '' ? ' — ' . $detail : '');
        echo "  FAIL  {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

class Browser
{
    private string $jar;

    public function __construct(string $name)
    {
        $this->jar = __DIR__ . '/../data/cookies-' . preg_replace('/[^a-z0-9-]/', '', $name) . '.txt';
        if (is_file($this->jar)) {
            @unlink($this->jar);
        }
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    /** @return array{code:int,body:string,url:string} */
    public function req(string $method, string $path, ?array $data = null): array
    {
        $ch = curl_init(BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 6,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => ['Accept: text/html'],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data ?? []));
        }
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);

        return ['code' => $code, 'body' => is_string($body) ? $body : '', 'url' => $url, 'error' => $error];
    }

    public function csrf(string $path = '/login.php'): string
    {
        $page = $this->req('GET', $path);
        if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $page['body'], $m)) {
            return $m[1];
        }

        return '';
    }

    public function login(int $userId): array
    {
        $token = $this->csrf('/login.php');
        $page = $this->req('POST', '/login.php', ['csrf_token' => $token, 'user_id' => $userId, 'next' => '']);

        return $page;
    }
}

function clean(string $html): bool
{
    foreach (['Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Uncaught'] as $needle) {
        if (str_contains($html, $needle)) {
            return false;
        }
    }

    return true;
}

function has(string $html, string $needle): bool
{
    return str_contains($html, $needle);
}

/** Buat satu pesanan baru lewat HTTP dan kembalikan kode pesanannya. */
function checkoutSingle(Browser $buyer, int $productId): string
{
    $token = $buyer->csrf('/product.php?id=' . $productId);
    $buyer->req('POST', '/cart-action.php', [
        'csrf_token' => $token,
        'action'     => 'add',
        'product_id' => $productId,
        'qty'        => 1,
    ]);
    $cart = $buyer->req('GET', '/cart.php');
    preg_match_all('/name="ids\[\]" value="(\d+)"/', $cart['body'], $m);
    $ids = array_map('intval', $m[1] ?? []);
    $token = $buyer->csrf('/checkout.php');
    $res = $buyer->req('POST', '/checkout.php', [
        'csrf_token'     => $token,
        'ids'            => $ids,
        'handover'       => 'pickup',
        'payment_method' => 'E-Wallet',
    ]);
    preg_match('/<div class="success-codes">([\s\S]*?)<\/div>/', $res['body'], $block);
    preg_match('/NM-\d{6}-\d{4}/', $block[1] ?? '', $code);

    return $code[0] ?? '';
}

function orderIdByCode(string $ordersHtml, string $code): int
{
    preg_match('/id="order-(\d+)"[\s\S]*?' . preg_quote($code, '/') . '/', $ordersHtml, $m);

    return (int) ($m[1] ?? 0);
}

function sellerOrderIdByCode(string $html, string $code): int
{
    preg_match('/' . preg_quote($code, '/') . '[\s\S]{0,6000}?name="order_id" value="(\d+)"/', $html, $m);

    return (int) ($m[1] ?? 0);
}

echo "== Halaman publik ==\n";
$guest = new Browser('guest');
foreach ([
    '/index.php' => 'Produk Unggulan',
    '/search.php' => 'Katalog',
    '/search.php?q=kopi' => 'Kopi Kampus',
    '/search.php?cat=minuman&sort=termurah&rating=4' => 'Menampilkan',
    '/search.php?q=zzz-tidak-ada' => 'Tidak ada hasil',
    '/product.php?id=7' => 'Es Kopi Susu Gula Aren',
    '/product.php?id=32' => 'Jaket Angkatan 2025',
    '/store.php?id=2' => 'Kopi Kampus',
    '/store.php?slug=kreatif-design' => 'Kreatif Design Studio',
    '/login.php' => 'Masuk sebagai siapa',
] as $path => $needle) {
    $res = $guest->req('GET', $path);
    ok("GET {$path} → 200", $res['code'] === 200, 'code=' . $res['code']);
    ok("  isi memuat “{$needle}”", has($res['body'], $needle));
    ok('  tanpa galat PHP', clean($res['body']));
}

$res = $guest->req('GET', '/product.php?id=9999');
ok('Produk tak ada → 404', $res['code'] === 404, 'code=' . $res['code']);

$res = $guest->req('GET', '/orders.php');
ok('Tamu buka pesanan diarahkan ke login', has($res['url'], 'login.php'), $res['url']);

echo "\n== Skenario pembeli (Marvelino) ==\n";
$buyer = new Browser('buyer');
$res = $buyer->login(1);
ok('Login pembeli berhasil', has($res['body'], 'Selamat datang') || has($res['body'], 'Masuk sebagai'), $res['url']);

$res = $buyer->req('GET', '/cart.php');
ok('Keranjang awal kosong', has($res['body'], 'Keranjangmu masih kosong'));

$token = $buyer->csrf('/product.php?id=7');
$res = $buyer->req('POST', '/cart-action.php', [
    'csrf_token' => $token,
    'action'     => 'add',
    'product_id' => 7,
    'variant'    => 'Less Sugar',
    'note'       => 'Es sedikit, ambil jam 12',
    'qty'        => 2,
]);
ok('Tambah ke keranjang', str_contains($res['url'], 'product.php?id=7'), $res['url']);
ok('Flash sukses tampil', has($res['body'], 'ditambahkan ke keranjang'));

$token = $buyer->csrf('/product.php?id=1');
$buyer->req('POST', '/cart-action.php', [
    'csrf_token' => $token,
    'action'     => 'add',
    'product_id' => 1,
    'variant'    => 'Level 2',
    'note'       => '',
    'qty'        => 1,
]);

$res = $buyer->req('GET', '/cart.php');
ok('Keranjang menampilkan item', has($res['body'], 'Es Kopi Susu Gula Aren'));
ok('Subtotal keranjang Rp39.000', has($res['body'], 'Rp39.000'));
ok('Dua toko tampil di keranjang', has($res['body'], 'Dapur Mahasiswa') && has($res['body'], 'Kopi Kampus'));
ok('Badge keranjang terisi', has($res['body'], 'icon-btn__badge'));
ok('Checkout memakai biaya per toko', has($res['body'], 'Rp1.000 per toko'));

preg_match_all('/name="ids\[\]" value="(\d+)"[^>]*aria-label="Pilih ([^"]+)"/', $res['body'], $m, PREG_SET_ORDER);
$cartIds = [];
$cartNames = [];
foreach ($m as $row) {
    $cartIds[] = (int) $row[1];
    $cartNames[(int) $row[1]] = $row[2];
}
ok('Dua item keranjang ditemukan', count($cartIds) === 2, 'ids=' . implode(',', $cartIds));
$kopiCartId = 0;
$geprekCartId = 0;
foreach ($cartNames as $id => $name) {
    if (str_contains($name, 'Kopi')) {
        $kopiCartId = (int) $id;
    }
    if (str_contains($name, 'Geprek')) {
        $geprekCartId = (int) $id;
    }
}
ok('Item kopi & geprek teridentifikasi', $kopiCartId > 0 && $geprekCartId > 0);

$token = $buyer->csrf('/cart.php');
$res = $buyer->req('POST', '/cart-action.php', ['csrf_token' => $token, 'action' => 'update_qty', 'cart_id' => $kopiCartId, 'delta' => 1]);
ok('Ubah jumlah keranjang', has($res['body'], 'Rp36.000'), 'subtotal kopi 3 × 12.000');

$token = $buyer->csrf('/checkout.php?ids[]=' . $kopiCartId . '&ids[]=' . $geprekCartId);
$res = $buyer->req('POST', '/checkout.php', [
    'csrf_token'      => $token,
    'ids'             => [$kopiCartId, $geprekCartId],
    'handover'        => 'delivery',
    'delivery_notes'  => 'Gedung B lantai 3, ruang B-302',
    'payment_method'  => 'QRIS',
]);
ok('Checkout membuat pesanan', has($res['body'], 'Pesanan Telah Dipesan'), $res['url']);
preg_match('/<div class="success-codes">([\s\S]*?)<\/div>/', $res['body'], $codesBlock);
preg_match_all('/NM-\d{6}-\d{4}/', $codesBlock[1] ?? '', $codeMatches);
$codes = array_values(array_unique($codeMatches[0] ?? []));
ok('Dua pesanan dibuat (satu per toko)', count($codes) === 2, implode(',', $codes));
ok('Halaman sukses tanpa galat', clean($res['body']));
ok('Ongkir dihitung per toko (Rp6.000)', has($res['body'], 'antar Rp3.000') && has($res['body'], 'Rp59.000'), 'total 59.000');

$res = $buyer->req('GET', '/cart.php');
ok('Keranjang kosong setelah checkout', has($res['body'], 'Keranjangmu masih kosong'));

$res = $buyer->req('GET', '/orders.php');
ok('Pesanan tampil di Pesanan Saya', has($res['body'], $codes[0] ?? ''));
ok('Status Dipesan tampil', has($res['body'], 'Dipesan'));

preg_match_all('/id="order-(\d+)"[\s\S]*?<\/article>/', $res['body'], $cards, PREG_SET_ORDER);
$geprekOrderId = 0;
$geprekCode = '';
foreach ($cards as $card) {
    if (str_contains($card[0], 'Ayam Geprek')) {
        $geprekOrderId = (int) $card[1];
        if (preg_match('/NM-\d{6}-\d{4}/', $card[0], $cm)) {
            $geprekCode = $cm[0];
        }
        break;
    }
}
ok('Pesanan Dapur Mahasiswa ditemukan', $geprekOrderId > 0, 'id=' . $geprekOrderId);

echo "\n== Skenario penjual (Aditya) ==\n";
$seller = new Browser('seller');
$res = $seller->login(2);
ok('Login penjual berhasil', has($res['body'], 'Dashboard'), $res['url']);

$res = $seller->req('GET', '/seller/orders.php?status=placed');
ok('Pesanan baru muncul di Seller Centre', has($res['body'], $geprekCode));
preg_match('/name="order_id" value="(\d+)"/', $res['body'], $m);
$sellerOrderId = (int) ($m[1] ?? 0);
ok('Order ID penjual ditemukan', $sellerOrderId > 0, 'id=' . $sellerOrderId . ' geprekCode=' . $geprekCode);

$token = $seller->csrf('/seller/orders.php');
$res = $seller->req('POST', '/seller/order-action.php', ['csrf_token' => $token, 'action' => 'accept', 'order_id' => $sellerOrderId]);
ok('Terima pesanan → Diproses', has($res['body'], 'Diproses'));

$token = $seller->csrf('/seller/orders.php?status=processing');
$res = $seller->req('POST', '/seller/order-action.php', ['csrf_token' => $token, 'action' => 'ready', 'order_id' => $sellerOrderId]);
ok('Tandai siap', has($res['body'], 'Siap Diambil') || has($res['body'], 'Sedang Diantar'));

$res = $seller->req('GET', '/seller/dashboard.php');
ok('Dashboard seller tampil', has($res['body'], 'Omzet Selesai'));
ok('Dashboard tanpa galat', clean($res['body']));

$res = $seller->req('GET', '/seller/tracking.php');
ok('Data Tracking terkunci untuk seller Free', has($res['body'], 'Data Tracking khusus seller PRO'));

echo "\n== Pembeli melihat tracking & konfirmasi ==\n";
$res = $buyer->req('GET', '/orders.php?status=ready');
ok('Pembeli melihat status Siap', has($res['body'], 'Konfirmasi Diterima'));
ok('Timeline memuat perubahan status', has($res['body'], 'Pesanan dibuat')
    && has($res['body'], 'Pesanan diterima penjual')
    && (has($res['body'], 'Pesanan sedang diantar') || has($res['body'], 'Pesanan siap diambil')));

$token = $buyer->csrf('/orders.php');
$res = $buyer->req('POST', '/order-action.php', ['csrf_token' => $token, 'action' => 'confirm_received', 'order_id' => $sellerOrderId]);
ok('Konfirmasi diterima → Selesai', has($res['body'], 'Selesai'));
ok('Escrow dilepas', has($res['body'], 'Escrow dilepas'));

preg_match('/data-order-id="' . $sellerOrderId . '"\s+data-product-id="(\d+)"/', $res['body'], $reviewMatch);
$reviewProductId = (int) ($reviewMatch[1] ?? 0);
ok('Tombol beri ulasan tersedia', $reviewProductId > 0, 'product=' . $reviewProductId);

$token = $buyer->csrf('/orders.php');
$res = $buyer->req('POST', '/order-action.php', [
    'csrf_token' => $token,
    'action'     => 'submit_review',
    'order_id'   => $sellerOrderId,
    'product_id' => $reviewProductId,
    'rating'     => 5,
    'comment'    => 'Uji otomatis: kopi enak dan cepat siap.',
]);
ok('Ulasan terkirim', has($res['body'], 'Terima kasih'));

$res = $buyer->req('GET', '/product.php?id=' . $reviewProductId);
ok('Ulasan tampil di produk', has($res['body'], 'Uji otomatis: kopi enak dan cepat siap.'));

$res = $buyer->req('GET', '/notifications.php');
ok('Notifikasi pembeli tampil', has($res['body'], 'Pesanan siap') || has($res['body'], 'Balasan') || has($res['body'], 'notif-row'));

echo "\n== Pembatalan & penjagaan transisi ==\n";
$token = $seller->csrf('/seller/orders.php');
$res = $seller->req('POST', '/seller/order-action.php', ['csrf_token' => $token, 'action' => 'accept', 'order_id' => $sellerOrderId]);
ok('Transisi tidak sah ditolak server', has($res['body'], 'tidak diizinkan'), 'terima ulang pesanan selesai');

$cancelCode = checkoutSingle($buyer, 13);
ok('Pesanan uji pembatalan dibuat', $cancelCode !== '');
$ordersPage = $buyer->req('GET', '/orders.php');
$cancelId = orderIdByCode($ordersPage['body'], $cancelCode);
ok('Pesanan pembatalan ditemukan', $cancelId > 0);
$token = $buyer->csrf('/orders.php');
$res = $buyer->req('POST', '/order-action.php', ['csrf_token' => $token, 'action' => 'cancel', 'order_id' => $cancelId]);
ok('Pembeli dapat membatalkan pesanan Dipesan', has($res['body'], 'Dibatalkan'));
ok('Escrow dikembalikan saat batal', has($res['body'], 'Dana dikembalikan'));

$rejectCode = checkoutSingle($buyer, 2);
ok('Pesanan uji penolakan dibuat', $rejectCode !== '');
$res = $seller->req('GET', '/seller/orders.php?status=placed');
$rejectId = sellerOrderIdByCode($res['body'], $rejectCode);
ok('Pesanan baru tampil di daftar penjual', $rejectId > 0);
$token = $seller->csrf('/seller/orders.php');
$res = $seller->req('POST', '/seller/order-action.php', [
    'csrf_token' => $token,
    'action'     => 'cancel',
    'order_id'   => $rejectId,
    'reason'     => 'Stok habis untuk hari ini.',
]);
ok('Penjual dapat menolak dengan alasan', has($res['body'], 'Dibatalkan'));
$res = $buyer->req('GET', '/orders.php?status=cancelled');
ok('Alasan penolakan terlihat pembeli', has($res['body'], 'Stok habis untuk hari ini'));

echo "\n== Seller PRO (Salsa) & Data Tracking ==\n";
$pro = new Browser('pro');
$pro->login(3);
$res = $pro->req('GET', '/seller/tracking.php');
ok('Data Tracking terbuka untuk PRO', has($res['body'], 'Funnel pesanan'));
ok('Timeline tracking tampil', has($res['body'], 'Timeline pesanan'));
ok('Grafik omzet tampil', has($res['body'], 'Omzet 7 hari terakhir'));
ok('Tracking tanpa galat', clean($res['body']));

$res = $pro->req('GET', '/seller/boost.php');
ok('Halaman Boost terbuka', has($res['body'], 'Beli Boost'));
$token = $pro->csrf('/seller/boost.php');
$res = $pro->req('POST', '/seller/boost.php', ['csrf_token' => $token, 'action' => 'buy', 'product_id' => 8, 'duration' => 3, 'payment_option' => 'pro_free']);
ok('Boost gratis PRO berhasil', has($res['body'], 'Boost aktif') || has($res['body'], 'Kuota gratis PRO'));

$res = $buyer->req('GET', '/index.php');
ok('Produk Boost tampil di beranda', has($res['body'], 'Matcha Latte'));

echo "\n== Admin ==\n";
$admin = new Browser('admin');
$res = $admin->login(4);
ok('Login admin berhasil', has($res['body'], 'Dashboard Admin'));
$res = $admin->req('GET', '/admin/sellers.php');
ok('Toko pending tampil', has($res['body'], 'Rina Craft'));
$token = $admin->csrf('/admin/sellers.php');
$res = $admin->req('POST', '/admin/sellers.php', ['csrf_token' => $token, 'action' => 'approve', 'store_id' => 7]);
ok('Verifikasi toko disetujui', has($res['body'], 'disetujui'));

$res = $guest->req('GET', '/store.php?id=7');
ok('Toko baru tampil di katalog', $res['code'] === 200 && has($res['body'], 'Tas Rajut Handmade'), 'code=' . $res['code']);

$res = $guest->req('GET', '/search.php?q=rajut');
ok('Produk toko baru muncul di pencarian', has($res['body'], 'Tas Rajut Handmade'));

$res = $admin->req('GET', '/admin/orders.php');
ok('Monitor pesanan tampil', has($res['body'], 'Monitor Pesanan') && has($res['body'], $geprekCode));
ok('Monitor tanpa galat', clean($res['body']));

echo "\n== Guard akses ==\n";
$res = $buyer->req('GET', '/seller/dashboard.php');
ok('Pembeli tidak bisa buka seller', has($res['url'], 'login.php') && has($res['url'], 'error=unauthorized'), $res['url']);
$res = $seller->req('GET', '/admin/dashboard.php');
ok('Penjual tidak bisa buka admin', has($res['url'], 'login.php') && has($res['url'], 'error=unauthorized'), $res['url']);

echo "\n== Reset data demo ==\n";
$token = $admin->csrf('/admin/dashboard.php');
$res = $admin->req('POST', '/admin/dashboard.php', ['csrf_token' => $token, 'action' => 'reset_demo']);
ok('Reset demo berhasil', has($res['body'], 'direset'));
$res = $buyer->req('GET', '/orders.php');
ok('Setelah reset, pesanan uji hilang', !has($res['body'], $geprekCode));
$res = $buyer->req('GET', '/cart.php');
ok('Setelah reset, keranjang kosong', has($res['body'], 'Keranjangmu masih kosong'));

echo "\n== Ringkasan ==\n";
echo "Lulus: {$pass} · Gagal: {$fail}\n";
foreach ($failures as $failure) {
    echo " - {$failure}\n";
}
exit($fail === 0 ? 0 : 1);

