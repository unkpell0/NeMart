<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_admin();

if (is_post()) {
    require_csrf();
    if (($_POST['action'] ?? '') === 'reset_demo') {
        nemart_seed(db(), true);
        flash('success', 'Data demo sudah direset ke kondisi awal. Keranjang, pesanan baru, dan perubahan status selama presentasi dibersihkan.');
        redirect('admin/dashboard.php');
    }
    flash('danger', 'Aksi admin tidak dikenal.');
    redirect('admin/dashboard.php');
}

$counts = [
    'users'    => (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'sellers'  => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
    'stores'   => (int) db()->query("SELECT COUNT(*) FROM stores WHERE verified_status = 'verified'")->fetchColumn(),
    'pending'  => (int) db()->query("SELECT COUNT(*) FROM stores WHERE verified_status = 'pending'")->fetchColumn(),
    'orders'   => (int) db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'products' => (int) db()->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
    'gmv'      => (int) db()->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'held'     => (int) db()->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE escrow_status = 'held'")->fetchColumn(),
];

$recentOrders = db()->query("SELECT o.*, s.name AS store_name, u.name AS buyer_name, u.initials AS buyer_initials
                             FROM orders o JOIN stores s ON s.id = o.store_id JOIN users u ON u.id = o.buyer_id
                             ORDER BY o.created_at DESC LIMIT 6")->fetchAll();
$pendingStores = db()->query("SELECT s.*, u.name AS owner_name FROM stores s JOIN users u ON u.id = s.owner_id WHERE s.verified_status = 'pending' ORDER BY s.joined_at DESC")->fetchAll();

$page_title = 'Dashboard Admin';
$active_nav = 'dashboard';
$shell_title = 'Dashboard Admin';
$shell_subtitle = 'Ringkasan platform dan kontrol demo';
require __DIR__ . '/../includes/admin-layout.php';
?>

<?php if ($counts['pending'] > 0): ?>
    <div class="notice notice--warning" style="margin-bottom:16px">
        <?= icon('shield', 18) ?>
        <div style="flex:1"><strong><?= (int) $counts['pending'] ?> toko menunggu verifikasi.</strong> Toko belum tampil di katalog sampai disetujui.</div>
        <a class="btn btn--yellow btn--sm" href="<?= e(url('admin/sellers.php')) ?>">Verifikasi Sekarang</a>
    </div>
<?php endif; ?>

<div class="admin-stat-grid">
    <?= stat_card('Pengguna', number_format($counts['users'], 0, ',', '.'), 'user', 'blue', $counts['sellers'] . ' penjual terdaftar') ?>
    <?= stat_card('Toko Terverifikasi', number_format($counts['stores'], 0, ',', '.'), 'store', 'mint', $counts['pending'] . ' menunggu verifikasi') ?>
    <?= stat_card('Total Pesanan', number_format($counts['orders'], 0, ',', '.'), 'box', 'indigo', 'Seluruh riwayat demo') ?>
    <?= stat_card('GMV Selesai', rupiah($counts['gmv']), 'wallet', 'cyan', 'Escrow sudah dilepas') ?>
</div>

<div class="dash-grid">
    <div class="panel panel--flush">
        <div class="panel__head" style="padding:16px 18px;margin:0;border-bottom:1px solid var(--border)">
            <div>
                <div class="panel__title"><?= icon('clipboard', 17) ?>Pesanan terbaru</div>
                <div class="panel__sub">Read-only. Perubahan status hanya bisa dilakukan penjual dan pembeli terkait.</div>
            </div>
            <a class="link-arrow" href="<?= e(url('admin/orders.php')) ?>">Monitor semua<?= icon('arrow-right', 14) ?></a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Kode</th><th>Pembeli</th><th>Toko</th><th>Total</th><th>Status</th><th>Escrow</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td class="small strong"><?= e($order['code']) ?><div class="tiny muted"><?= e(time_ago((string) $order['created_at'])) ?></div></td>
                            <td class="small"><?= e($order['buyer_name']) ?></td>
                            <td class="small"><?= e($order['store_name']) ?></td>
                            <td class="small tabular"><?= rupiah((int) $order['total']) ?></td>
                            <td><?= status_badge((string) $order['status'], (string) $order['handover_method']) ?></td>
                            <td><span class="badge <?= $order['escrow_status'] === 'held' ? 'badge--warning' : ($order['escrow_status'] === 'released' ? 'badge--success' : 'badge--neutral') ?>"><?= $order['escrow_status'] === 'held' ? 'Ditahan' : ($order['escrow_status'] === 'released' ? 'Dilepas' : 'Refund') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="panel" style="margin-bottom:16px">
            <div class="panel__head">
                <div class="panel__title"><?= icon('store', 17) ?>Menunggu verifikasi</div>
                <a class="link-arrow" href="<?= e(url('admin/sellers.php')) ?>">Kelola<?= icon('arrow-right', 13) ?></a>
            </div>
            <?php if ($pendingStores === []): ?>
                <p class="small muted">Semua toko sudah diverifikasi.</p>
            <?php else: ?>
                <?php foreach ($pendingStores as $pending): ?>
                    <div class="quick-item">
                        <span class="avatar avatar--sm avatar--seller"><?= icon((string) $pending['icon'], 15) ?></span>
                        <div class="quick-item__body">
                            <div class="quick-item__title"><?= e($pending['name']) ?></div>
                            <div class="quick-item__meta"><?= e($pending['owner_name']) ?> · <?= e($pending['department']) ?></div>
                        </div>
                        <a class="btn btn--outline btn--sm" href="<?= e(url('admin/sellers.php')) ?>">Tinjau</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="reset-box">
            <div class="panel__title" style="color:var(--danger-fg)"><?= icon('refresh', 17) ?>Reset Data Demo</div>
            <p class="small" style="margin-top:6px;color:var(--text-muted)">
                Mengembalikan seluruh data (pengguna, toko, produk, pesanan, ulasan, Boost) ke kondisi seed awal. Berguna sebelum presentasi.
            </p>
            <form method="post" action="<?= e(url('admin/dashboard.php')) ?>" style="margin-top:12px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_demo">
                <button type="submit" class="btn btn--danger btn--full" data-confirm="Reset semua data demo ke kondisi awal? Pesanan dan perubahan selama presentasi akan hilang.">
                    <?= icon('refresh', 16) ?>Reset Data Demo
                </button>
            </form>
            <p class="tiny muted" style="margin-top:8px">Data saat ini: <?= $counts['orders'] ?> pesanan · <?= $counts['products'] ?> produk aktif · dana escrow ditahan <?= rupiah($counts['held']) ?>.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin-layout-end.php'; ?>
