<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
require_admin();

if (is_post()) {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $storeId = (int) ($_POST['store_id'] ?? 0);
    $reason = trim((string) ($_POST['reason'] ?? ''));

    $stmt = db()->prepare('SELECT s.*, u.name AS owner_name FROM stores s JOIN users u ON u.id = s.owner_id WHERE s.id = ?');
    $stmt->execute([$storeId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('danger', 'Toko tidak ditemukan.');
        redirect('admin/sellers.php');
    }

    if ($action === 'approve') {
        $stmt = db()->prepare("UPDATE stores SET verified_status = 'verified' WHERE id = ?");
        $stmt->execute([$storeId]);
        notify((int) $target['owner_id'], 'system', 'Toko ' . $target['name'] . ' telah terverifikasi',
            'Selamat! Toko kamu sudah tampil di katalog NeMart. Pastikan produk aktif dan stok selalu diperbarui.',
            'seller/dashboard.php', null);
        flash('success', 'Toko “' . $target['name'] . '” disetujui dan sekarang tampil di katalog.');
        redirect('admin/sellers.php');
    }

    if ($action === 'reject') {
        if ($reason === '') {
            flash('warning', 'Tulis alasan penolakan agar penjual bisa memperbaiki berkasnya.');
            redirect('admin/sellers.php');
        }
        $stmt = db()->prepare("UPDATE stores SET verified_status = 'rejected' WHERE id = ?");
        $stmt->execute([$storeId]);
        notify((int) $target['owner_id'], 'system', 'Verifikasi toko ' . $target['name'] . ' ditolak',
            'Alasan: ' . $reason . '. Perbaiki data toko lalu hubungi admin kemahasiswaan.',
            'seller/dashboard.php', null);
        flash('info', 'Toko “' . $target['name'] . '” ditolak dengan alasan yang sudah dikirim ke penjual.');
        redirect('admin/sellers.php');
    }

    flash('danger', 'Aksi verifikasi tidak dikenal.');
    redirect('admin/sellers.php');
}

$pending = db()->query("SELECT s.*, u.name AS owner_name, u.email AS owner_email, u.prodi AS owner_prodi,
                               (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS product_count
                        FROM stores s JOIN users u ON u.id = s.owner_id
                        WHERE s.verified_status = 'pending' ORDER BY s.joined_at DESC")->fetchAll();

$allStores = db()->query("SELECT s.*, u.name AS owner_name,
                                 (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS product_count,
                                 (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) AS order_count
                          FROM stores s JOIN users u ON u.id = s.owner_id
                          ORDER BY CASE s.verified_status WHEN 'pending' THEN 0 WHEN 'verified' THEN 1 ELSE 2 END, s.name")->fetchAll();

$page_title = 'Verifikasi Penjual';
$active_nav = 'sellers';
$shell_title = 'Verifikasi Penjual';
$shell_subtitle = count($pending) . ' toko menunggu keputusan';
require __DIR__ . '/../includes/admin-layout.php';
?>

<div class="panel" style="margin-bottom:16px">
    <div class="panel__head">
        <div>
            <div class="panel__title"><?= icon('shield', 17) ?>Menunggu verifikasi</div>
            <div class="panel__sub">Toko yang disetujui langsung tampil di katalog dan penjual menerima notifikasi.</div>
        </div>
        <span class="badge badge--warning"><?= count($pending) ?> pending</span>
    </div>

    <?php if ($pending === []): ?>
        <?= empty_state('check-circle', 'Semua toko sudah ditinjau', 'Tidak ada pengajuan verifikasi yang menunggu. Toko baru akan muncul di sini.') ?>
    <?php else: ?>
        <?php foreach ($pending as $store): ?>
            <div class="pending-store">
                <span class="avatar avatar--lg avatar--seller"><?= icon((string) $store['icon'], 24) ?></span>
                <div class="pending-store__body">
                    <div class="pending-store__name">
                        <?= e($store['name']) ?>
                        <span class="badge badge--warning"><?= icon('clock', 11) ?>Pending</span>
                    </div>
                    <div class="pending-store__meta">
                        <?= e($store['owner_name']) ?> · <?= e($store['department']) ?> · <?= e($store['pickup_point']) ?> · <?= (int) $store['product_count'] ?> produk
                    </div>
                    <div class="pending-store__meta">Diajukan <?= e(tanggal((string) $store['joined_at'], true)) ?> · <?= e($store['owner_email']) ?></div>
                </div>
                <div class="admin-actions">
                    <button type="button" class="btn btn--danger-outline btn--sm" data-reject-open data-store-id="<?= (int) $store['id'] ?>" data-store-name="<?= e($store['name']) ?>"><?= icon('x', 13) ?>Tolak</button>
                    <form method="post" action="<?= e(url('admin/sellers.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="store_id" value="<?= (int) $store['id'] ?>">
                        <button type="submit" class="btn btn--primary btn--sm" data-confirm="Setujui toko <?= e($store['name']) ?>? Toko langsung tampil di katalog."><?= icon('check', 13) ?>Setujui</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="panel panel--flush">
    <div class="panel__head" style="padding:16px 18px;margin:0;border-bottom:1px solid var(--border)">
        <div>
            <div class="panel__title"><?= icon('store', 17) ?>Semua toko</div>
            <div class="panel__sub"><?= count($allStores) ?> toko terdaftar</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Toko</th><th>Pemilik</th><th>Jurusan</th><th>Produk</th><th>Pesanan</th><th>Paket</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allStores as $store): ?>
                    <tr>
                        <td>
                            <div class="cell-product">
                                <span class="avatar avatar--sm avatar--seller"><?= icon((string) $store['icon'], 14) ?></span>
                                <div>
                                    <div style="font-weight:600;font-size:12.5px"><?= e($store['name']) ?></div>
                                    <div class="tiny muted"><?= e($store['slug']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= e($store['owner_name']) ?></td>
                        <td class="small muted"><?= e($store['department']) ?></td>
                        <td class="small tabular"><?= (int) $store['product_count'] ?></td>
                        <td class="small tabular"><?= (int) $store['order_count'] ?></td>
                        <td><span class="badge <?= $store['plan'] === 'pro' ? 'badge--pro' : 'badge--neutral' ?>"><?= $store['plan'] === 'pro' ? 'PRO' : 'Free' ?></span></td>
                        <td>
                            <?php if ($store['verified_status'] === 'verified'): ?>
                                <span class="badge badge--success"><?= icon('check-circle', 11) ?>Terverifikasi</span>
                            <?php elseif ($store['verified_status'] === 'pending'): ?>
                                <span class="badge badge--warning"><?= icon('clock', 11) ?>Pending</span>
                            <?php else: ?>
                                <span class="badge badge--danger"><?= icon('x', 11) ?>Ditolak</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<dialog class="modal" id="rejectDialog" aria-labelledby="rejectTitle">
    <form method="post" action="<?= e(url('admin/sellers.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="store_id" id="rejectStoreId" value="">
        <div class="modal__head">
            <div>
                <h2 class="modal__title" id="rejectTitle">Tolak verifikasi toko</h2>
                <p class="modal__sub" id="rejectStoreName">Toko</p>
            </div>
            <button type="button" class="modal__close" data-dialog-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="modal__body">
            <div class="field">
                <label class="label" for="rejectReason">Alasan penolakan <span style="color:var(--danger)">*</span></label>
                <textarea class="textarea" id="rejectReason" name="reason" rows="3" maxlength="200" required placeholder="Contoh: titik ambil belum jelas, deskripsi toko kosong, atau data pemilik tidak sesuai."></textarea>
                <span class="field__hint">Alasan akan dikirim ke penjual lewat notifikasi.</span>
            </div>
        </div>
        <div class="modal__foot">
            <button type="button" class="btn btn--ghost" data-dialog-close>Kembali</button>
            <button type="submit" class="btn btn--danger"><?= icon('x', 15) ?>Tolak Verifikasi</button>
        </div>
    </form>
</dialog>

<?php require __DIR__ . '/../includes/admin-layout-end.php'; ?>
