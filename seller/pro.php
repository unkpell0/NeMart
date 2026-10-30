<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

if (is_post()) {
    require_csrf();
    if (($_POST['action'] ?? '') === 'upgrade') {
        $baseTs = time();
        if (store_is_pro($store)) {
            $baseTs = strtotime((string) $store['pro_until']);
        }
        $until = date('Y-m-d H:i:s', strtotime('+30 days', $baseTs));

        $stmt = db()->prepare("UPDATE stores SET plan = 'pro', pro_until = ? WHERE id = ?");
        $stmt->execute([$until, $storeId]);

        notify((int) current_user()['id'], 'system', 'Paket PRO aktif',
            'Langganan PRO ' . $store['name'] . ' aktif sampai ' . tanggal($until) . '. Data Tracking sudah terbuka.',
            'seller/tracking.php', null);

        flash('success', 'Paket PRO aktif sampai ' . tanggal($until) . ' (pembayaran simulasi). Data Tracking sekarang terbuka.');
        redirect('seller/pro.php');
    }
}

$isPro = store_is_pro($store);
$daysLeft = $isPro ? max(0, (int) ceil((strtotime((string) $store['pro_until']) - time()) / 86400)) : 0;
$commissionRate = $isPro ? 3 : 5;

$page_title = 'Langganan PRO';
$active_nav = 'pro';
$shell_title = 'Langganan PRO';
$shell_subtitle = $isPro ? 'PRO aktif · ' . $daysLeft . ' hari tersisa' : 'Paket Free · komisi 5%';
require __DIR__ . '/../includes/seller-layout.php';
?>

<?php if ($isPro): ?>
    <div class="pro-hero" style="margin-bottom:16px">
        <div class="pro-hero__body">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="badge badge--pro"><?= icon('sparkles', 12) ?>PRO</span>
                <h2 class="pro-hero__title">Paket PRO aktif</h2>
            </div>
            <p class="pro-hero__text">
                Aktif sampai <strong style="color:#fff"><?= e(tanggal((string) $store['pro_until'], true)) ?></strong> (<?= $daysLeft ?> hari lagi).
                Komisi platform 3%, 3 Boost gratis per bulan, dan Data Tracking pesanan terbuka.
            </p>
        </div>
        <form method="post" action="<?= e(url('seller/pro.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upgrade">
            <button type="submit" class="btn btn--yellow"><?= icon('refresh', 16) ?>Perpanjang 30 Hari</button>
        </form>
    </div>
<?php else: ?>
    <div class="pro-hero" style="margin-bottom:16px">
        <div class="pro-hero__body">
            <span class="badge badge--warning"><?= icon('info', 12) ?>Paket Free</span>
            <h2 class="pro-hero__title" style="margin-top:8px">Buka Data Tracking dan komisi lebih kecil</h2>
            <p class="pro-hero__text">
                Upgrade ke PRO seharga <strong style="color:#fff">Rp25.000 per bulan</strong> (simulasi). Komisi turun dari 5% ke 3%,
                kamu mendapat 3 Boost gratis setiap bulan, lencana PRO di toko, dan panel Data Tracking pesanan.
            </p>
        </div>
        <form method="post" action="<?= e(url('seller/pro.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upgrade">
            <button type="submit" class="btn btn--yellow" data-confirm="Aktifkan PRO selama 30 hari? Pembayaran disimulasikan dan tidak ada biaya nyata."><?= icon('sparkles', 16) ?>Upgrade ke PRO</button>
        </form>
    </div>
<?php endif; ?>

<div class="dash-grid">
    <div class="panel panel--flush">
        <div class="panel__head" style="padding:16px 18px;margin:0;border-bottom:1px solid var(--border)">
            <div>
                <div class="panel__title"><?= icon('layers', 17) ?>Perbandingan paket</div>
                <div class="panel__sub">Semua fitur transaksi inti tetap tersedia di paket Free.</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="pro-table">
                <thead>
                    <tr>
                        <th>Fitur</th>
                        <th style="text-align:center">Free</th>
                        <th class="is-pro" style="text-align:center">PRO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Komisi platform</td>
                        <td style="text-align:center">5%</td>
                        <td class="is-pro" style="text-align:center">3%</td>
                    </tr>
                    <tr>
                        <td>Boost gratis</td>
                        <td style="text-align:center">Tidak ada</td>
                        <td class="is-pro" style="text-align:center">3 per bulan</td>
                    </tr>
                    <tr>
                        <td>Data Tracking</td>
                        <td style="text-align:center"><?= icon('lock', 14) ?> Terkunci</td>
                        <td class="is-pro" style="text-align:center"><?= icon('check-circle', 14) ?> Terbuka</td>
                    </tr>
                    <tr>
                        <td>Lencana PRO di toko</td>
                        <td style="text-align:center"><?= icon('x', 14) ?></td>
                        <td class="is-pro" style="text-align:center"><?= icon('check', 14) ?> Ada</td>
                    </tr>
                    <tr>
                        <td>Pesanan, produk, ulasan, Boost berbayar</td>
                        <td style="text-align:center"><?= icon('check', 14) ?></td>
                        <td class="is-pro" style="text-align:center"><?= icon('check', 14) ?></td>
                    </tr>
                    <tr>
                        <td>Komisi saat pesanan selesai</td>
                        <td style="text-align:center">5% dari subtotal</td>
                        <td class="is-pro" style="text-align:center">3% dari subtotal</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel__title"><?= icon('wallet', 17) ?>Status langganan</div>
            <div class="summary-row" style="margin-top:10px"><span>Paket saat ini</span><strong><?= $isPro ? 'PRO' : 'Free' ?></strong></div>
            <div class="summary-row"><span>Komisi platform</span><strong><?= $commissionRate ?>%</strong></div>
            <div class="summary-row"><span>Boost gratis bulan ini</span><strong><?= $isPro ? '3 kuota' : 'Tidak tersedia' ?></strong></div>
            <div class="summary-row"><span>Masa aktif</span><strong><?= $isPro ? e(tanggal((string) $store['pro_until'])) : '—' ?></strong></div>
            <div class="summary-note" style="margin-top:10px">Pembayaran langganan disimulasikan. Tidak ada tagihan atau uang nyata pada demo ini.</div>
            <a class="btn btn--outline btn--full btn--sm" href="<?= e(url('seller/tracking.php')) ?>" style="margin-top:12px"><?= icon('chart', 14) ?><?= $isPro ? 'Buka Data Tracking' : 'Lihat Data Tracking' ?></a>
        </div>

        <div class="panel">
            <div class="panel__title"><?= icon('info', 17) ?>Yang berubah setelah upgrade</div>
            <ul class="small muted" style="display:grid;gap:8px;margin-top:8px">
                <li>• Setiap pesanan selesai dipotong 3% (bukan 5%).</li>
                <li>• Panel Data Tracking: funnel, waktu proses, omzet, performa Boost.</li>
                <li>• Lencana PRO tampil di halaman toko dan produk.</li>
                <li>• Kuota 3 Boost gratis per bulan mempercepat produk terlihat.</li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>
