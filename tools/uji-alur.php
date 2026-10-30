<?php
/**
 * NeMart — uji alur demo dari sisi pembeli, penjual, dan admin.
 *
 * Pemakaian:
 *   php tools/uji-alur.php
 *   php tools/uji-alur.php --base=https://nemart-xxxx.onrender.com
 *   php tools/uji-alur.php --keep-data          (jangan reset di akhir)
 *   php tools/uji-alur.php --no-reset          (jangan reset sama sekali)
 *
 * PERINGATAN: secara default skrip MERESET data demo di target sebelum dan
 * sesudah pengujian agar hasilnya deterministik. Jangan arahkan ke data nyata.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

/* ------------------------------------------------------------------ opsi */

$base = getenv('NEMART_BASE') ?: 'http://127.0.0.1:8123';
$noReset = false;
$keepData = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $base = substr($arg, 7);
    } elseif ($arg === '--no-reset') {
        $noReset = true;
        $keepData = true;
    } elseif ($arg === '--keep-data') {
        $keepData = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Pemakaian: php tools/uji-alur.php [--base=URL] [--keep-data|--no-reset]\n";
        exit(0);
    }
}

$base = rtrim($base, '/');
if (!preg_match('#^https?://#i', $base)) {
    fwrite(STDERR, "URL target tidak valid: {$base}\n");
    exit(2);
}

$pass = 0;
$fail = 0;
$failures = [];
$step = 0;

/* --------------------------------------------------------------- utilitas */

function phase(string $title): void
{
    echo "\n== {$title} ==\n";
}

function check(string $label, bool $condition, string $detail = ''): void
{
    global $pass, $fail, $failures, $step;
    $step++;
    if ($condition) {
        $pass++;
        echo sprintf("  [%02d] LULUS  %s\n", $step, $label);
    } else {
        $fail++;
        $failures[] = $label . ($detail !== '' ? ' — ' . $detail : '');
        echo sprintf("  [%02d] GAGAL  %s%s\n", $step, $label, $detail !== '' ? ' — ' . $detail : '');
    }
}

function contains(string $haystack, string $needle): bool
{
    return $needle !== '' && str_contains($haystack, $needle);
}

function tokenFrom(string $html): string
{
    return preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m) ? $m[1] : '';
}

/* -------------------------------------------------------------- HTTP client */

final class Client
{
    private string $jar;

    public function __construct(string $name)
    {
        $dir = dirname(__DIR__) . '/data';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->jar = $dir . '/cookies-uji-' . preg_replace('/[^a-z0-9-]/', '', $name) . '.txt';
        @unlink($this->jar);
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    /** @return array{code:int,body:string,url:string,type:string} */
    public function request(string $method, string $path, ?array $data = null): array
    {
        $ch = curl_init(BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'NeMart-UjiAlur/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data ?? []));
        }
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            throw new RuntimeException("Koneksi gagal ke {$path}: {$error}");
        }

        return ['code' => $code, 'body' => is_string($body) ? $body : '', 'url' => $url, 'type' => $type];
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    public function csrf(string $path): string
    {
        $page = $this->get($path);
        $token = tokenFrom($page['body']);
        if ($token === '') {
            throw new RuntimeException('Token CSRF tidak ditemukan di ' . $path);
        }

        return $token;
    }

    public function login(int $userId): array
    {
        $token = $this->csrf('/login.php');

        return $this->post('/login.php', [
            'csrf_token' => $token,
            'user_id'    => $userId,
            'next'       => '',
        ]);
    }
}

define('BASE', $base);

/* ------------------------------------------------------------------ mulai */

echo "NeMart — uji alur Pembeli -> Penjual -> Admin\n";
echo "Target : {$base}\n";
echo 'Mode   : ' . ($noReset ? 'tanpa reset data (--no-reset)' : ($keepData ? 'reset di awal, data dipertahankan di akhir' : 'reset data sebelum & sesudah uji')) . "\n";

if (!str_contains($base, '127.0.0.1') && !str_contains($base, 'localhost')) {
    echo "PERINGATAN: target bukan localhost. Data demo di target akan direset.\n";
}

try {
    $guest = new Client('guest');
    $buyer = new Client('buyer');
    $seller = new Client('seller');
    $pro = new Client('pro');
    $admin = new Client('admin');

    /* ============================================================ 0. siap */

    phase('0. Kesiapan aplikasi & reset data');

    $health = $guest->get('/healthz.php');
    $wakes = 0;
    while ($health['code'] !== 200 && $wakes < 6) {
        echo "  ... instance belum siap, menunggu 10 detik ({$wakes}/6)\n";
        sleep(10);
        $health = $guest->get('/healthz.php');
        $wakes++;
    }
    $healthJson = json_decode($health['body'], true);
    check('Health check /healthz.php merespons 200', $health['code'] === 200, 'code=' . $health['code']);
    check('Status database "ok"', is_array($healthJson) && ($healthJson['status'] ?? '') === 'ok');

    $home = $guest->get('/index.php');
    check('Beranda tampil', $home['code'] === 200 && contains($home['body'], 'Produk Unggulan'));
    check('Logo NeMart terpasang di header', contains($home['body'], 'assets/img/logo.png'));
    check('Favicon NeMart tersedia', contains($home['body'], 'assets/img/favicon.png'));

    $logo = $guest->get('/assets/img/logo.png');
    check('File logo dapat diakses', $logo['code'] === 200 && str_starts_with($logo['body'], "\x89PNG"), 'code=' . $logo['code']);
    $favicon = $guest->get('/assets/img/favicon.png');
    check('File favicon dapat diakses', $favicon['code'] === 200 && str_starts_with($favicon['body'], "\x89PNG"), 'code=' . $favicon['code']);

    $adminLogin = $admin->login(4);
    check('Login admin berhasil', contains($adminLogin['body'], 'Dashboard Admin'));
    $dashboard = $admin->get('/admin/dashboard.php');
    check('Dashboard admin tampil', contains($dashboard['body'], 'Reset Data Demo'));

    if (!$noReset) {
        $reset = $admin->post('/admin/dashboard.php', [
            'csrf_token' => tokenFrom($dashboard['body']),
            'action'     => 'reset_demo',
        ]);
        check('Reset data demo berhasil', contains($reset['body'], 'direset'));
    } else {
        echo "  [--] Reset data dilewati (--no-reset)\n";
    }

    /* ========================================================= 1. pembeli */

    phase('1. Pembeli masuk dan memeriksa kondisi awal');

    $login = $buyer->login(1);
    check('Login sebagai Marvelino R.', contains($login['body'], 'Marvelino R.') && (contains($login['body'], 'Selamat datang') || contains($login['body'], 'Beranda')));
    $cart = $buyer->get('/cart.php');
    check('Keranjang awal kosong', contains($cart['body'], 'Keranjangmu masih kosong'));
    $notif = $buyer->get('/notifications.php');
    check('Halaman notifikasi terbuka', $notif['code'] === 200 && contains($notif['body'], 'Notifikasi'));

    /* ========================================================== 2. katalog */

    phase('2. Katalog, pencarian, dan produk Featured');

    $search = $buyer->get('/search.php?q=kopi');
    check('Pencarian "kopi" menampilkan hasil', contains($search['body'], 'Hasil untuk') && contains($search['body'], 'Es Kopi Susu Gula Aren'));
    check('Produk Featured muncul dengan label', contains($search['body'], 'Featured'));
    preg_match('/<article class="p-card">([\s\S]*?)<\/article>/', $search['body'], $firstCard);
    check('Produk Featured berada di urutan teratas', isset($firstCard[1]) && contains($firstCard[1], 'Featured'));

    $cat = $buyer->get('/search.php?cat=minuman');
    check('Filter kategori Minuman bekerja', $cat['code'] === 200 && contains($cat['body'], 'Minuman') && contains($cat['body'], 'Es Kopi Susu'));

    $empty = $buyer->get('/search.php?q=zzz-tidak-ada');
    check('Pencarian tanpa hasil menampilkan empty state', contains($empty['body'], 'Tidak ada hasil'));

    /* ======================================================== 3. detail */

    phase('3. Detail produk dan tambah ke keranjang');

    $product = $buyer->get('/product.php?id=1');
    check('Detail produk tampil', $product['code'] === 200 && contains($product['body'], 'Ayam Geprek Sambal Bawang'));
    check('Harga produk tampil', contains($product['body'], 'Rp15.000'));
    check('Info escrow tampil di produk', contains($product['body'], 'Transaksi dilindungi escrow'));

    $add = $buyer->post('/cart-action.php', [
        'csrf_token' => tokenFrom($product['body']),
        'action'     => 'add',
        'product_id' => 1,
        'variant'    => 'Level 2',
        'note'       => 'Uji alur otomatis',
        'qty'        => 2,
    ]);
    check('Produk berhasil ditambahkan ke keranjang', contains($add['body'], 'ditambahkan ke keranjang'));

    /* ======================================================= 4. keranjang */

    phase('4. Keranjang: item, jumlah, dan subtotal');

    $cart = $buyer->get('/cart.php');
    check('Item tampil di keranjang', contains($cart['body'], 'Ayam Geprek Sambal Bawang') && contains($cart['body'], 'Level 2'));
    check('Subtotal 2 x Rp15.000 = Rp30.000', contains($cart['body'], 'Rp30.000'));
    check('Kotak centang item tersedia', contains($cart['body'], 'name="ids[]"'));

    preg_match('/name="ids\[\]" value="(\d+)"/', $cart['body'], $cartIdMatch);
    $cartId = (int) ($cartIdMatch[1] ?? 0);
    if ($cartId <= 0) {
        throw new RuntimeException('ID item keranjang tidak ditemukan.');
    }

    $cart = $buyer->post('/cart-action.php', [
        'csrf_token' => tokenFrom($cart['body']),
        'action'     => 'update_qty',
        'cart_id'    => $cartId,
        'delta'      => 1,
    ]);
    check('Jumlah keranjang berubah menjadi 3', contains($cart['body'], 'Rp45.000'), 'subtotal 3 x Rp15.000');

    /* ======================================================== 5. checkout */

    phase('5. Checkout dan pembayaran simulasi');

    $checkout = $buyer->get('/checkout.php?ids[]=' . $cartId);
    check('Halaman checkout tampil', $checkout['code'] === 200 && contains($checkout['body'], 'Serah terima pesanan'));
    check('Pilihan pembayaran simulasi tersedia', contains($checkout['body'], 'QRIS') && contains($checkout['body'], 'Virtual Account'));
    check('Biaya layanan per toko tampil', contains($checkout['body'], 'Rp1.000'));

    $paid = $buyer->post('/checkout.php', [
        'csrf_token'     => tokenFrom($checkout['body']),
        'ids'            => [$cartId],
        'handover'       => 'delivery',
        'delivery_notes' => 'Gedung B lantai 3, ruang B-302 (uji otomatis)',
        'payment_method' => 'QRIS',
    ]);
    check('Pembayaran simulasi membuat pesanan', contains($paid['body'], 'Pesanan Telah Dipesan'));
    preg_match('/<div class="success-codes">([\s\S]*?)<\/div>/', $paid['body'], $codeBlock);
    $code = preg_match('/NM-\d{6}-\d{4}/', $codeBlock[1] ?? '', $codeMatch) ? $codeMatch[0] : '';
    check('Kode pesanan tampil', $code !== '', 'kode=' . $code);
    if ($code === '') {
        throw new RuntimeException('Kode pesanan tidak ditemukan di halaman sukses.');
    }
    check('Total termasuk layanan & ongkir = Rp49.000', contains($paid['body'], 'Rp49.000'), '45.000 + 1.000 + 3.000');

    $cartAfter = $buyer->get('/cart.php');
    check('Keranjang dikosongkan setelah checkout', contains($cartAfter['body'], 'Keranjangmu masih kosong'));

    $orders = $buyer->get('/orders.php');
    check('Pesanan muncul di Pesanan Saya', contains($orders['body'], $code));
    check('Status awal pesanan Dipesan', contains($orders['body'], 'Dipesan'));

    /* ========================================================= 6. penjual */

    phase('6. Penjual menerima dan menyiapkan pesanan');

    $sellerLogin = $seller->login(2);
    check('Login penjual Aditya', contains($sellerLogin['body'], 'Dashboard') && contains($sellerLogin['body'], 'Dapur Mahasiswa'));
    $sellerHome = $seller->get('/seller/dashboard.php');
    check('Pesanan baru tampil di dashboard penjual', contains($sellerHome['body'], $code));

    $placed = $seller->get('/seller/orders.php?status=placed');
    check('Pesanan baru masuk ke Seller Centre', contains($placed['body'], $code));
    $sellerOrderId = preg_match('/' . preg_quote($code, '/') . '[\s\S]{0,8000}?name="order_id" value="(\d+)"/', $placed['body'], $sellerIdMatch)
        ? (int) $sellerIdMatch[1] : 0;
    check('ID pesanan ditemukan', $sellerOrderId > 0, 'id=' . $sellerOrderId);

    $accepted = $seller->post('/seller/order-action.php', [
        'csrf_token' => tokenFrom($placed['body']),
        'action'     => 'accept',
        'order_id'   => $sellerOrderId,
    ]);
    check('Terima pesanan -> status Diproses', contains($accepted['body'], 'Diproses'));

    $processing = $seller->get('/seller/orders.php?status=processing');
    $ready = $seller->post('/seller/order-action.php', [
        'csrf_token' => tokenFrom($processing['body']),
        'action'     => 'ready',
        'order_id'   => $sellerOrderId,
    ]);
    check('Tandai siap -> status Sedang Diantar', contains($ready['body'], 'Sedang Diantar'));
    check('Notifikasi pembeli dikirim oleh penjual', contains($ready['body'], $code));

    $freeTracking = $seller->get('/seller/tracking.php');
    check('Data Tracking terkunci untuk penjual Free', contains($freeTracking['body'], 'Data Tracking khusus seller PRO'));

    /* ================================================== 7. tracking pembeli */

    phase('7. Pembeli melihat tracking dan konfirmasi diterima');

    $tracking = $buyer->get('/orders.php?status=ready');
    check('Pembeli melihat pesanan siap', contains($tracking['body'], 'Konfirmasi Diterima'));
    check('Timeline menampilkan perubahan status', contains($tracking['body'], 'Pesanan dibuat') && contains($tracking['body'], 'Pesanan diterima penjual') && contains($tracking['body'], 'Pesanan sedang diantar'));

    $completed = $buyer->post('/order-action.php', [
        'csrf_token' => tokenFrom($tracking['body']),
        'action'     => 'confirm_received',
        'order_id'   => $sellerOrderId,
    ]);
    check('Konfirmasi diterima -> Selesai', contains($completed['body'], 'Selesai'));
    check('Escrow dilepas ke penjual', contains($completed['body'], 'Escrow dilepas'));

    /* ========================================================== 8. ulasan */

    phase('8. Pembeli memberi rating & ulasan, penjual membalas');

    $reviewProductId = preg_match('/data-order-id="' . $sellerOrderId . '"[\s\S]{0,300}?data-product-id="(\d+)"/', $completed['body'], $reviewMatch)
        ? (int) $reviewMatch[1] : 0;
    check('Tombol Beri Ulasan tersedia', $reviewProductId > 0, 'product=' . $reviewProductId);

    $comment = 'Uji alur otomatis: kopi enak dan pesanan cepat sampai.';
    $review = $buyer->post('/order-action.php', [
        'csrf_token' => tokenFrom($completed['body']),
        'action'     => 'submit_review',
        'order_id'   => $sellerOrderId,
        'product_id' => $reviewProductId,
        'rating'     => 5,
        'comment'    => $comment,
    ]);
    check('Ulasan berhasil dikirim', contains($review['body'], 'Terima kasih'));

    $productAfter = $buyer->get('/product.php?id=' . $reviewProductId);
    check('Ulasan tampil di halaman produk', contains($productAfter['body'], $comment));

    $sellerReviews = $seller->get('/seller/reviews.php');
    check('Ulasan masuk ke Seller Centre', contains($sellerReviews['body'], $comment));
    $reviewId = preg_match('/' . preg_quote($comment, '/') . '[\s\S]{0,6000}?name="review_id" value="(\d+)"/', $sellerReviews['body'], $reviewIdMatch)
        ? (int) $reviewIdMatch[1] : 0;
    check('ID ulasan ditemukan', $reviewId > 0);

    $replyText = 'Terima kasih sudah memesan, ditunggu order berikutnya!';
    $replied = $seller->post('/seller/reviews.php', [
        'csrf_token' => tokenFrom($sellerReviews['body']),
        'action'     => 'reply',
        'review_id'  => $reviewId,
        'reply'      => $replyText,
    ]);
    check('Penjual dapat membalas ulasan', contains($replied['body'], 'Balasan ulasan sudah dikirim'));
    $productAfterReply = $buyer->get('/product.php?id=' . $reviewProductId);
    check('Balasan penjual tampil di produk', contains($productAfterReply['body'], $replyText));

    /* ====================================================== 9. seller PRO */

    phase('9. Penjual PRO: Data Tracking dan Boost');

    $proLogin = $pro->login(3);
    check('Login penjual PRO Salsa', contains($proLogin['body'], 'Kopi Kampus'));
    $proTracking = $pro->get('/seller/tracking.php');
    check('Data Tracking terbuka untuk PRO', contains($proTracking['body'], 'Funnel pesanan'));
    check('Timeline tracking tersedia', contains($proTracking['body'], 'Timeline pesanan'));
    check('Grafik omzet 7 hari tampil', contains($proTracking['body'], 'Omzet 7 hari terakhir'));
    check('Performa Boost tampil', contains($proTracking['body'], 'Performa Boost'));

    $boostPage = $pro->get('/seller/boost.php');
    $boost = $pro->post('/seller/boost.php', [
        'csrf_token'     => tokenFrom($boostPage['body']),
        'action'         => 'buy',
        'product_id'     => 8,
        'duration'       => 3,
        'payment_option' => 'paid',
    ]);
    check('Boost berhasil diaktifkan', contains($boost['body'], 'Boost aktif'));

    $boostedSearch = $guest->get('/search.php?q=matcha');
    preg_match('/<article class="p-card">([\s\S]*?)<\/article>/', $boostedSearch['body'], $boostedCard);
    check('Produk Boost menjadi Featured di pencarian', isset($boostedCard[1]) && contains($boostedCard[1], 'Featured') && contains($boostedCard[1], 'Matcha Latte'));

    /* =========================================================== 10. admin */

    phase('10. Admin: verifikasi penjual, monitor pesanan');

    $adminLogin = $admin->login(4);
    check('Login admin', contains($adminLogin['body'], 'Dashboard Admin'));
    $pending = $admin->get('/admin/sellers.php');
    check('Toko pending tampil', contains($pending['body'], 'Rina Craft') && contains($pending['body'], 'Pending'));
    $storeId = preg_match('/name="action" value="approve"[\s\S]{0,500}?name="store_id" value="(\d+)"/', $pending['body'], $storeMatch)
        ? (int) $storeMatch[1] : 0;
    check('ID toko pending ditemukan', $storeId > 0, 'id=' . $storeId);

    $approved = $admin->post('/admin/sellers.php', [
        'csrf_token' => tokenFrom($pending['body']),
        'action'     => 'approve',
        'store_id'   => $storeId,
    ]);
    check('Toko berhasil diverifikasi', contains($approved['body'], 'disetujui'));

    $storePage = $guest->get('/store.php?id=' . $storeId);
    check('Toko terverifikasi tampil di katalog', $storePage['code'] === 200 && contains($storePage['body'], 'Tas Rajut Handmade'));
    $searchStore = $guest->get('/search.php?q=rajut');
    check('Produk toko baru muncul di pencarian', contains($searchStore['body'], 'Tas Rajut Handmade'));

    $monitor = $admin->get('/admin/orders.php');
    check('Pesanan terpantau di admin', contains($monitor['body'], $code));

    /* =========================================================== 11. reset */

    if (!$keepData) {
        phase('11. Reset data demo dan verifikasi pembersihan');
        $dash = $admin->get('/admin/dashboard.php');
        $reset = $admin->post('/admin/dashboard.php', [
            'csrf_token' => tokenFrom($dash['body']),
            'action'     => 'reset_demo',
        ]);
        check('Reset data demo berhasil', contains($reset['body'], 'direset'));

        $cartReset = $buyer->get('/cart.php');
        check('Keranjang kembali kosong', contains($cartReset['body'], 'Keranjangmu masih kosong'));
        $ordersReset = $buyer->get('/orders.php');
        check('Pesanan uji hilang setelah reset', !contains($ordersReset['body'], $code));
        $storeReset = $guest->get('/store.php?id=' . $storeId);
        check('Toko pending kembali tersembunyi', $storeReset['code'] === 404, 'code=' . $storeReset['code']);
        $healthEnd = $guest->get('/healthz.php');
        check('Aplikasi tetap sehat setelah reset', $healthEnd['code'] === 200);
    } else {
        echo "\n  [--] Reset akhir dilewati (--keep-data/--no-reset)\n";
    }
} catch (Throwable $e) {
    echo "\n!! Pengujian dihentikan: " . $e->getMessage() . "\n";
    echo "Lulus: {$pass} | Gagal: {$fail}\n";
    exit(1);
}

/* ---------------------------------------------------------------- ringkasan */

echo "\n=====================================================\n";
echo "Target  : {$base}\n";
echo "Langkah : " . ($pass + $fail) . " pemeriksaan\n";
echo "Lulus   : {$pass}\n";
echo "Gagal   : {$fail}\n";
echo "=====================================================\n";

if ($fail > 0) {
    echo "\nYang gagal:\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "Semua alur pembeli -> penjual -> admin berjalan baik.\n";
exit(0);
