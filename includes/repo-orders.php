<?php
declare(strict_types=1);

/* =========================================================================
   Mesin pesanan: pembuatan order transaksional, transisi status, timeline,
   ulasan. Semua perubahan status menulis order_events + notifications.
   ========================================================================= */

const ORDER_TRANSITIONS = [
    'placed'     => ['processing', 'cancelled'],
    'processing' => ['ready', 'cancelled'],
    'ready'      => ['completed'],
    'completed'  => [],
    'cancelled'  => [],
];

function generate_order_code(PDO $pdo): string
{
    for ($attempt = 0; $attempt < 12; $attempt++) {
        $code = 'NM-' . date('ymd') . '-' . sprintf('%04d', random_int(1000, 9999));
        $stmt = $pdo->prepare('SELECT 1 FROM orders WHERE code = ?');
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() === false) {
            return $code;
        }
    }

    throw new RuntimeException('Gagal membuat kode pesanan unik.');
}

/**
 * Membuat satu order per toko dalam SATU transaksi database.
 *
 * @param array<int,array> $cartItems baris dari cart_items() milik pembeli
 * @param array{handover?:string,pickup_point?:string,delivery_notes?:string,payment_method?:string} $options
 * @return array{payment_ref:string,orders:array<int,array{id:int,code:string,store_name:string,total:int}>,total:int}
 */
function create_orders_from_cart(int $buyerId, array $cartItems, array $options): array
{
    if ($cartItems === []) {
        throw new RuntimeException('Keranjang masih kosong. Tambahkan produk dulu, ya.');
    }

    $handover = ($options['handover'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
    $pickupPoint = trim((string) ($options['pickup_point'] ?? ''));
    $deliveryNotes = trim((string) ($options['delivery_notes'] ?? ''));
    $paymentMethod = trim((string) ($options['payment_method'] ?? 'QRIS'));
    if (!in_array($paymentMethod, ['QRIS', 'Virtual Account', 'E-Wallet'], true)) {
        $paymentMethod = 'QRIS';
    }
    $shippingFee = $handover === 'delivery' ? 3000 : 0;
    $serviceFee = 1000;

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $groups = cart_group_by_store($cartItems);
        $paymentRef = 'PAY-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $created = [];
        $grandTotal = 0;

        foreach ($groups as $group) {
            $storeId = (int) $group['store_id'];

            $stmt = $pdo->prepare('SELECT * FROM stores WHERE id = ?');
            $stmt->execute([$storeId]);
            $store = $stmt->fetch();
            if (!$store || $store['verified_status'] !== 'verified') {
                throw new RuntimeException('Toko ' . $group['store_name'] . ' belum terverifikasi, produknya belum bisa dipesan.');
            }

            $subtotal = 0;
            $lines = [];
            foreach ($group['items'] as $item) {
                $stmt = $pdo->prepare("SELECT id, name, price, stock, status FROM products WHERE id = ?");
                $stmt->execute([(int) $item['product_id']]);
                $product = $stmt->fetch();
                if (!$product || $product['status'] !== 'active') {
                    throw new RuntimeException('Produk "' . $item['name'] . '" sudah tidak dijual.');
                }
                $qty = (int) $item['qty'];
                if ((int) $product['stock'] < $qty) {
                    throw new RuntimeException('Stok "' . $product['name'] . '" tinggal ' . (int) $product['stock'] . '. Kurangi jumlahnya dulu.');
                }
                $lineTotal = (int) $product['price'] * $qty;
                $subtotal += $lineTotal;
                $lines[] = [
                    'product_id' => (int) $product['id'],
                    'name'       => (string) $product['name'],
                    'price'      => (int) $product['price'],
                    'variant'    => (string) $item['variant'],
                    'note'       => (string) $item['note'],
                    'qty'        => $qty,
                ];
            }

            $total = $subtotal + $serviceFee + $shippingFee;
            $code = generate_order_code($pdo);
            $nowTs = now();

            $stmt = $pdo->prepare("INSERT INTO orders (code, payment_ref, buyer_id, store_id, status, escrow_status, handover_method, pickup_point, delivery_notes, subtotal, service_fee, shipping_fee, total, commission, payment_method, paid_at, created_at)
                                   VALUES (?,?,?,?,'placed','held',?,?,?,?,?,?,?,0,?,?,?)");
            $stmt->execute([
                $code, $paymentRef, $buyerId, $storeId, $handover,
                $pickupPoint !== '' ? $pickupPoint : ($store['pickup_point'] ?? ''),
                $deliveryNotes !== '' ? $deliveryNotes : null,
                $subtotal, $serviceFee, $shippingFee, $total, $paymentMethod, $nowTs, $nowTs,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name_snapshot, price_snapshot, variant, note, qty) VALUES (?,?,?,?,?,?,?)');
            $updateStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($lines as $line) {
                $insertItem->execute([$orderId, $line['product_id'], $line['name'], $line['price'], $line['variant'], $line['note'], $line['qty']]);
                $updateStock->execute([$line['qty'], $line['product_id'], $line['qty']]);
                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException('Stok "' . $line['name'] . '" berubah saat checkout. Coba lagi.');
                }
            }

            $insertEvent = $pdo->prepare('INSERT INTO order_events (order_id, status, title, note, actor_id, created_at) VALUES (?,?,?,?,?,?)');
            $insertEvent->execute([
                $orderId, 'placed', 'Pesanan dibuat',
                'Pembayaran simulasi berhasil (' . $paymentMethod . '). Dana ditahan escrow sampai pesanan dikonfirmasi diterima.',
                $buyerId, $nowTs,
            ]);

            $buyerName = (string) (current_user()['name'] ?? 'Pembeli');
            notify($buyerId, 'order', 'Pesanan ' . $code . ' telah dipesan',
                'Pesanan dari ' . $store['name'] . ' berhasil dibuat. Pantau statusnya di halaman Pesanan Saya.',
                'orders.php?highlight=' . $orderId, $pdo);
            $sellerId = (int) $store['owner_id'];
            notify($sellerId, 'order', 'Pesanan baru masuk di ' . $store['name'],
                $buyerName . ' memesan ' . count($lines) . ' produk senilai ' . rupiah($total) . '. Segera terima pesanan.',
                'seller/orders.php?status=placed', $pdo);

            $created[] = [
                'id'         => $orderId,
                'code'       => $code,
                'store_id'   => $storeId,
                'store_name' => (string) $store['name'],
                'total'      => $total,
            ];
            $grandTotal += $total;
        }

        $deleteCart = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        foreach ($cartItems as $item) {
            $deleteCart->execute([(int) $item['cart_id'], $buyerId]);
        }

        $pdo->commit();

        return ['payment_ref' => $paymentRef, 'orders' => $created, 'total' => $grandTotal];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function order_by_code(string $code): ?array
{
    $stmt = db()->prepare('SELECT o.*, s.name AS store_name, s.slug AS store_slug, s.icon AS store_icon, u.name AS buyer_name
                           FROM orders o JOIN stores s ON s.id = o.store_id JOIN users u ON u.id = o.buyer_id
                           WHERE o.code = ?');
    $stmt->execute([$code]);
    $order = $stmt->fetch();

    return $order ?: null;
}

function order_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT o.*, s.name AS store_name, s.slug AS store_slug, s.icon AS store_icon, s.owner_id AS store_owner,
                                  s.pickup_point AS store_pickup, s.plan AS store_plan, s.pro_until AS store_pro_until,
                                  u.name AS buyer_name, u.initials AS buyer_initials, u.email AS buyer_email
                           FROM orders o
                           JOIN stores s ON s.id = o.store_id
                           JOIN users u ON u.id = o.buyer_id
                           WHERE o.id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    return $order ?: null;
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT oi.*, c.slug AS category_slug, c.id AS category_id, c.name AS category_name, c.icon AS category_icon
                           FROM order_items oi
                           JOIN products p ON p.id = oi.product_id
                           JOIN categories c ON c.id = p.category_id
                           WHERE oi.order_id = ? ORDER BY oi.id');
    $stmt->execute([$orderId]);

    return $stmt->fetchAll();
}

function order_events(int $orderId): array
{
    $stmt = db()->prepare('SELECT e.*, u.name AS actor_name FROM order_events e LEFT JOIN users u ON u.id = e.actor_id WHERE e.order_id = ? ORDER BY e.created_at, e.id');
    $stmt->execute([$orderId]);

    return $stmt->fetchAll();
}

/**
 * Ambil pesanan + item + event + status ulasan sekaligus (menghindari N+1).
 *
 * @param array<int,array> $orders baris orders (harus punya kolom id)
 */
function attach_order_details(array $orders): array
{
    if ($orders === []) {
        return [];
    }

    $ids = array_map(static fn (array $o): int => (int) $o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare("SELECT oi.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
                           FROM order_items oi
                           JOIN products p ON p.id = oi.product_id
                           JOIN categories c ON c.id = p.category_id
                           WHERE oi.order_id IN ({$placeholders}) ORDER BY oi.id");
    $stmt->execute($ids);
    $itemsByOrder = [];
    $itemCount = [];
    foreach ($stmt->fetchAll() as $item) {
        $itemsByOrder[(int) $item['order_id']][] = $item;
        $itemCount[(int) $item['order_id']] = ($itemCount[(int) $item['order_id']] ?? 0) + (int) $item['qty'];
    }

    $stmt = db()->prepare("SELECT * FROM order_events WHERE order_id IN ({$placeholders}) ORDER BY created_at, id");
    $stmt->execute($ids);
    $eventsByOrder = [];
    foreach ($stmt->fetchAll() as $event) {
        $eventsByOrder[(int) $event['order_id']][] = $event;
    }

    $stmt = db()->prepare("SELECT order_id, product_id FROM reviews WHERE order_id IN ({$placeholders})");
    $stmt->execute($ids);
    $reviewed = [];
    foreach ($stmt->fetchAll() as $row) {
        $reviewed[(int) $row['order_id']][(int) $row['product_id']] = true;
    }

    foreach ($orders as &$order) {
        $oid = (int) $order['id'];
        $order['items'] = $itemsByOrder[$oid] ?? [];
        $order['events'] = $eventsByOrder[$oid] ?? [];
        $order['reviewed'] = $reviewed[$oid] ?? [];
        $order['item_qty'] = $itemCount[$oid] ?? 0;
    }
    unset($order);

    return $orders;
}

function buyer_orders(int $buyerId, string $status = 'all'): array
{
    $sql = 'SELECT o.*, s.name AS store_name, s.slug AS store_slug, s.icon AS store_icon, s.plan AS store_plan
            FROM orders o JOIN stores s ON s.id = o.store_id
            WHERE o.buyer_id = ?';
    $params = [$buyerId];
    if ($status !== 'all' && isset(ORDER_TRANSITIONS[$status])) {
        $sql .= ' AND o.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return attach_order_details($stmt->fetchAll());
}

function seller_orders(int $storeId, string $status = 'all'): array
{
    $sql = 'SELECT o.*, u.name AS buyer_name, u.initials AS buyer_initials, s.name AS store_name, s.icon AS store_icon
            FROM orders o
            JOIN users u ON u.id = o.buyer_id
            JOIN stores s ON s.id = o.store_id
            WHERE o.store_id = ?';
    $params = [$storeId];
    if ($status !== 'all' && isset(ORDER_TRANSITIONS[$status])) {
        $sql .= ' AND o.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return attach_order_details($stmt->fetchAll());
}

function orders_by_payment_ref(string $ref, int $buyerId): array
{
    $stmt = db()->prepare('SELECT o.*, s.name AS store_name, s.slug AS store_slug, s.icon AS store_icon
                           FROM orders o JOIN stores s ON s.id = o.store_id
                           WHERE o.payment_ref = ? AND o.buyer_id = ? ORDER BY o.id');
    $stmt->execute([$ref, $buyerId]);

    return attach_order_details($stmt->fetchAll());
}

/**
 * Validasi + eksekusi transisi status pesanan.
 *
 * @param array $actor baris users pelaku (id, role)
 * @return array{ok:bool,message:string}
 */
function change_order_status(int $orderId, string $to, array $actor, ?string $note = null): array
{
    $pdo = db();
    $order = order_by_id($orderId);

    if (!$order) {
        return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
    }

    $from = (string) $order['status'];
    $role = (string) $actor['role'];
    $actorId = (int) $actor['id'];
    $isBuyer = $role === 'buyer' && $actorId === (int) $order['buyer_id'];
    $isSeller = $role === 'seller' && $actorId === (int) $order['store_owner'];

    if (!$isBuyer && !$isSeller) {
        return ['ok' => false, 'message' => 'Kamu tidak punya akses ke pesanan ini.'];
    }
    if (!in_array($to, ORDER_TRANSITIONS[$from] ?? [], true)) {
        return ['ok' => false, 'message' => 'Perubahan status dari "' . order_status_label($from) . '" ke "' . order_status_label($to) . '" tidak diizinkan.'];
    }
    if ($isBuyer && $to !== 'completed' && $to !== 'cancelled') {
        return ['ok' => false, 'message' => 'Aksi ini hanya bisa dilakukan penjual.'];
    }
    if ($isSeller && $to === 'completed') {
        return ['ok' => false, 'message' => 'Pesanan hanya bisa diselesaikan pembeli.'];
    }
    if ($isBuyer && $to === 'cancelled' && $from !== 'placed') {
        return ['ok' => false, 'message' => 'Pesanan yang sudah diproses penjual tidak bisa dibatalkan sendiri.'];
    }

    $note = trim((string) $note);
    $nowTs = now();

    $titles = [
        'processing' => ['Pesanan diterima penjual', 'Penjual mulai menyiapkan pesananmu.'],
        'ready'      => $order['handover_method'] === 'delivery'
            ? ['Pesanan sedang diantar', 'Mitra antar sedang menuju lokasi pengantaran.']
            : ['Pesanan siap diambil', 'Silakan ambil pesanan di ' . ($order['pickup_point'] ?: $order['store_pickup'] ?: 'titik ambil kampus') . '.'],
        'completed'  => ['Pesanan selesai', 'Pembeli mengonfirmasi pesanan diterima. Escrow dilepas ke penjual.'],
        'cancelled'  => ['Pesanan dibatalkan', $note !== '' ? $note : 'Pesanan dibatalkan.'],
    ];
    [$title, $defaultNote] = $titles[$to];

    $pdo->beginTransaction();
    try {
        if ($to === 'completed') {
            $commission = (int) round((int) $order['subtotal'] * commission_rate([
                'plan'      => (string) $order['store_plan'],
                'pro_until' => $order['store_pro_until'],
            ]) / 100);
            $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', escrow_status = 'released', completed_at = ?, commission = ? WHERE id = ? AND status = ?");
            $stmt->execute([$nowTs, $commission, $orderId, $from]);

            $items = order_items($orderId);
            $bumpSold = $pdo->prepare('UPDATE products SET sold_count = sold_count + ? WHERE id = ?');
            foreach ($items as $item) {
                $bumpSold->execute([(int) $item['qty'], (int) $item['product_id']]);
            }
        } elseif ($to === 'cancelled') {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', escrow_status = 'refunded', cancel_reason = ? WHERE id = ? AND status = ?");
            $stmt->execute([$note !== '' ? $note : $defaultNote, $orderId, $from]);

            $items = order_items($orderId);
            $restore = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($items as $item) {
                $restore->execute([(int) $item['qty'], (int) $item['product_id']]);
            }
        } else {
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND status = ?');
            $stmt->execute([$to, $orderId, $from]);
        }

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Status pesanan sudah berubah. Muat ulang halaman ini.');
        }

        $insertEvent = $pdo->prepare('INSERT INTO order_events (order_id, status, title, note, actor_id, created_at) VALUES (?,?,?,?,?,?)');
        $insertEvent->execute([$orderId, $to, $title, $note !== '' ? $note : $defaultNote, $actorId, $nowTs]);

        $link = $isBuyer ? 'seller/orders.php?status=' . $to : 'orders.php?highlight=' . $orderId;
        if ($isBuyer) {
            $sellerBody = $to === 'completed'
                ? 'Pembeli mengonfirmasi pesanan diterima. Escrow ' . rupiah((int) $order['subtotal']) . ' sudah dilepas ke tokomu.'
                : 'Pesanan ' . $order['code'] . ' dari ' . $order['buyer_name'] . ' kini berstatus ' . order_status_label($to, $order['handover_method']) . '.';
            notify((int) $order['store_owner'], 'order', $title . ' — ' . $order['code'], $sellerBody, $link, $pdo);
        } else {
            notify((int) $order['buyer_id'], 'order', $title . ' — ' . $order['code'],
                'Pesanan dari ' . $order['store_name'] . ' berstatus ' . order_status_label($to, $order['handover_method']) . '.',
                'orders.php?highlight=' . $orderId, $pdo);
        }

        $pdo->commit();

        return ['ok' => true, 'message' => 'Status pesanan diperbarui menjadi ' . order_status_label($to, $order['handover_method']) . '.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'message' => 'Gagal memperbarui pesanan: ' . $e->getMessage()];
    }
}

/** Ulasan hanya untuk order completed milik pembeli, satu per produk per order. */
function submit_review(int $orderId, int $productId, int $buyerId, int $rating, string $comment): array
{
    $order = order_by_id($orderId);
    if (!$order || (int) $order['buyer_id'] !== $buyerId) {
        return ['ok' => false, 'message' => 'Pesanan tidak ditemukan untuk akun ini.'];
    }
    if ($order['status'] !== 'completed') {
        return ['ok' => false, 'message' => 'Ulasan hanya bisa diberikan setelah pesanan selesai.'];
    }

    $items = order_items($orderId);
    $productName = null;
    foreach ($items as $item) {
        if ((int) $item['product_id'] === $productId) {
            $productName = (string) $item['name_snapshot'];
            break;
        }
    }
    if ($productName === null) {
        return ['ok' => false, 'message' => 'Produk ini bukan bagian dari pesanan tersebut.'];
    }
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'message' => 'Pilih rating 1 sampai 5 bintang.'];
    }

    $stmt = db()->prepare('SELECT 1 FROM reviews WHERE order_id = ? AND product_id = ?');
    $stmt->execute([$orderId, $productId]);
    if ($stmt->fetchColumn() !== false) {
        return ['ok' => false, 'message' => 'Ulasan untuk produk ini sudah pernah dikirim.'];
    }

    $stmt = db()->prepare('INSERT INTO reviews (order_id, product_id, buyer_id, store_id, rating, comment, created_at) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$orderId, $productId, $buyerId, (int) $order['store_id'], $rating, trim($comment), now()]);

    notify((int) $order['store_owner'], 'review', 'Ulasan baru untuk ' . $productName,
        $order['buyer_name'] . ' memberi ' . $rating . ' bintang pada pesanan ' . $order['code'] . '.',
        'seller/reviews.php');

    return ['ok' => true, 'message' => 'Terima kasih! Ulasanmu sudah tayang di halaman produk.'];
}

function reply_review(int $reviewId, int $storeId, string $reply): array
{
    $reply = trim($reply);
    if ($reply === '') {
        return ['ok' => false, 'message' => 'Tulis balasan dulu, ya.'];
    }

    $stmt = db()->prepare('SELECT r.*, p.name AS product_name, u.name AS buyer_name FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.buyer_id WHERE r.id = ? AND r.store_id = ?');
    $stmt->execute([$reviewId, $storeId]);
    $review = $stmt->fetch();
    if (!$review) {
        return ['ok' => false, 'message' => 'Ulasan tidak ditemukan di tokomu.'];
    }
    if (!empty($review['seller_reply'])) {
        return ['ok' => false, 'message' => 'Ulasan ini sudah pernah dibalas (satu balasan per ulasan).'];
    }

    $stmt = db()->prepare('UPDATE reviews SET seller_reply = ? WHERE id = ?');
    $stmt->execute([$reply, $reviewId]);

    notify((int) $review['buyer_id'], 'review', 'Balasan ulasan dari toko',
        'Penjual membalas ulasanmu untuk ' . $review['product_name'] . '.',
        'product.php?id=' . (int) $review['product_id'] . '#ulasan', null);

    return ['ok' => true, 'message' => 'Balasan ulasan sudah dikirim ke pembeli.'];
}

/* ------------------------------------------------------------- statistik */

function seller_summary(int $storeId): array
{
    $pdo = db();
    $stmt = $pdo->prepare("SELECT
        SUM(CASE WHEN status = 'placed' THEN 1 ELSE 0 END) AS new_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS ready_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN subtotal ELSE 0 END), 0) AS revenue,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN commission ELSE 0 END), 0) AS commission
        FROM orders WHERE store_id = ?");
    $stmt->execute([$storeId]);
    $row = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare('SELECT COALESCE(ROUND(AVG(rating), 1), 0) AS rating, COUNT(*) AS review_count FROM reviews WHERE store_id = ?');
    $stmt->execute([$storeId]);
    $review = $stmt->fetch() ?: ['rating' => 0, 'review_count' => 0];

    return [
        'new_orders'        => (int) ($row['new_orders'] ?? 0),
        'processing_orders' => (int) ($row['processing_orders'] ?? 0),
        'ready_orders'      => (int) ($row['ready_orders'] ?? 0),
        'completed_orders'  => (int) ($row['completed_orders'] ?? 0),
        'revenue'           => (int) ($row['revenue'] ?? 0),
        'commission'        => (int) ($row['commission'] ?? 0),
        'rating'            => (float) $review['rating'],
        'review_count'      => (int) $review['review_count'],
    ];
}

function seller_tracking(int $storeId): array
{
    $pdo = db();
    $orders = seller_orders($storeId);

    $funnel = ['placed' => 0, 'processing' => 0, 'ready' => 0, 'completed' => 0, 'cancelled' => 0];
    $durations = ['accept' => [], 'prepare' => [], 'handover' => []];
    $daily = [];
    for ($i = 6; $i >= 0; $i--) {
        $daily[date('Y-m-d', strtotime("-{$i} days"))] = 0;
    }
    $timelineOrders = [];

    foreach ($orders as $order) {
        $funnel[$order['status']] = ($funnel[$order['status']] ?? 0) + 1;
        $timestamps = [];
        foreach ($order['events'] as $event) {
            $timestamps[$event['status']] = strtotime((string) $event['created_at']);
        }
        if ($order['status'] === 'completed') {
            if (isset($timestamps['placed'], $timestamps['processing'])) {
                $durations['accept'][] = $timestamps['processing'] - $timestamps['placed'];
            }
            if (isset($timestamps['processing'], $timestamps['ready'])) {
                $durations['prepare'][] = $timestamps['ready'] - $timestamps['processing'];
            }
            if (isset($timestamps['ready'], $timestamps['completed'])) {
                $durations['handover'][] = $timestamps['completed'] - $timestamps['ready'];
            }
            $day = date('Y-m-d', strtotime((string) $order['completed_at']));
            if (isset($daily[$day])) {
                $daily[$day] += (int) $order['subtotal'];
            }
        }
        $timelineOrders[] = [
            'id'        => (int) $order['id'],
            'code'      => (string) $order['code'],
            'buyer'     => (string) ($order['buyer_name'] ?? ''),
            'status'    => (string) $order['status'],
            'total'     => (int) $order['total'],
            'created_at'=> (string) $order['created_at'],
            'events'    => $order['events'],
        ];
    }

    $avg = static function (array $values): int {
        return $values === [] ? 0 : (int) round(array_sum($values) / count($values));
    };

    $stmt = $pdo->prepare("SELECT oi.name_snapshot, SUM(oi.qty) AS qty, SUM(oi.qty * oi.price_snapshot) AS revenue
                           FROM order_items oi JOIN orders o ON o.id = oi.order_id
                           WHERE o.store_id = ? AND o.status = 'completed'
                           GROUP BY oi.name_snapshot ORDER BY qty DESC LIMIT 5");
    $stmt->execute([$storeId]);
    $topProducts = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT p.id, p.name, p.view_count, p.boost_until,
                                  (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi JOIN orders o ON o.id = oi.order_id
                                   WHERE oi.product_id = p.id AND o.store_id = ? AND o.created_at >= COALESCE(b.starts_at, p.created_at)) AS orders_since_boost,
                                  b.starts_at, b.ends_at, b.source, b.price AS boost_price
                           FROM products p
                           LEFT JOIN boosts b ON b.id = (SELECT id FROM boosts WHERE product_id = p.id ORDER BY ends_at DESC LIMIT 1)
                           WHERE p.store_id = ? AND (p.boost_until IS NOT NULL OR b.id IS NOT NULL)
                           ORDER BY p.boost_until DESC LIMIT 5");
    $stmt->execute([$storeId, $storeId]);
    $boostRows = $stmt->fetchAll();

    $totalOrders = array_sum($funnel);
    $settled = $funnel['completed'] + $funnel['cancelled'];

    return [
        'orders'         => $orders,
        'funnel'         => $funnel,
        'funnel_total'   => $totalOrders,
        'avg_accept'     => $avg($durations['accept']),
        'avg_prepare'    => $avg($durations['prepare']),
        'avg_handover'   => $avg($durations['handover']),
        'daily'          => $daily,
        'top_products'   => $topProducts,
        'boosts'         => $boostRows,
        'timeline'       => $timelineOrders,
        'completion_rate'=> $settled > 0 ? round($funnel['completed'] / max(1, $totalOrders) * 100) : 0,
    ];
}
