<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_admin();

$status = (string) ($_GET['status'] ?? 'all');
if (!isset(order_status_map()[$status])) {
    $status = 'all';
}

$counts = ['all' => 0];
foreach (order_status_map() as $key => $meta) {
    $counts[$key] = 0;
}
foreach (db()->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll() as $row) {
    $counts[(string) $row['status']] = (int) $row['total'];
    $counts['all'] += (int) $row['total'];
}

$sql = "SELECT o.*, s.name AS store_name, u.name AS buyer_name, u.initials AS buyer_initials
        FROM orders o JOIN stores s ON s.id = o.store_id JOIN users u ON u.id = o.buyer_id";
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$held = (int) db()->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE escrow_status = 'held'")->fetchColumn();
$released = (int) db()->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE escrow_status = 'released'")->fetchColumn();

$tabs = [
    'all'        => ['label' => 'Semua', 'count' => $counts['all']],
    'placed'     => ['label' => 'Dipesan', 'count' => $counts['placed']],
    'processing' => ['label' => 'Diproses', 'count' => $counts['processing']],
    'ready'      => ['label' => 'Siap', 'count' => $counts['ready']],
    'completed'  => ['label' => 'Selesai', 'count' => $counts['completed']],
    'cancelled'  => ['label' => 'Dibatalkan', 'count' => $counts['cancelled']],
];

$page_title = 'Monitor Pesanan';
$active_nav = 'orders';
$shell_title = 'Monitor Pesanan';
$shell_subtitle = 'Semua transaksi platform · read-only';
require __DIR__ . '/../includes/admin-layout.php';
?>

<div class="stat-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <?= stat_card('Total Pesanan', number_format($counts['all'], 0, ',', '.'), 'box', 'blue') ?>
    <?= stat_card('Escrow Ditahan', rupiah($held), 'shield', 'yellow', 'Belum dikonfirmasi pembeli') ?>
    <?= stat_card('Escrow Dilepas', rupiah($released), 'check-circle', 'mint', 'Pesanan selesai') ?>
</div>

<div class="notice notice--info" style="margin-bottom:14px">
    <?= icon('lock', 16) ?>
    <span><strong>Read-only.</strong> Admin hanya memantau. Perubahan status pesanan dilakukan penjual (Diproses, Siap) dan pembeli (Konfirmasi Diterima).</span>
</div>

<?= tabs_nav($tabs, $status, url('admin/orders.php')) ?>

<div class="panel panel--flush">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pembeli</th>
                    <th>Toko</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Escrow</th>
                    <th>Serah terima</th>
                    <th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders === []): ?>
                    <tr><td colspan="8" style="text-align:center;padding:36px 12px" class="muted">Belum ada pesanan pada filter ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="small strong nowrap"><?= e($order['code']) ?><div class="tiny muted"><?= e((string) $order['payment_method']) ?> · <?= e((string) $order['payment_ref']) ?></div></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:7px">
                                <?= avatar((string) $order['buyer_name'], (string) $order['buyer_initials'], 'avatar--sm') ?>
                                <span class="small"><?= e($order['buyer_name']) ?></span>
                            </div>
                        </td>
                        <td class="small"><?= e($order['store_name']) ?></td>
                        <td class="small tabular strong"><?= rupiah((int) $order['total']) ?></td>
                        <td><?= status_badge((string) $order['status'], (string) $order['handover_method']) ?></td>
                        <td><span class="badge <?= $order['escrow_status'] === 'held' ? 'badge--warning' : ($order['escrow_status'] === 'released' ? 'badge--success' : 'badge--neutral') ?>"><?= $order['escrow_status'] === 'held' ? 'Ditahan' : ($order['escrow_status'] === 'released' ? 'Dilepas' : 'Refund') ?></span></td>
                        <td class="small muted"><?= $order['handover_method'] === 'delivery' ? 'Diantar' : 'Ambil' ?></td>
                        <td class="small muted nowrap"><?= e(tanggal((string) $order['created_at'], true)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-layout-end.php'; ?>
