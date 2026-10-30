<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];
$isPro = store_is_pro($store);

$tracking = seller_tracking($storeId);

$formatDuration = static function (int $seconds): string {
    if ($seconds <= 0) {
        return '—';
    }
    if ($seconds < 60) {
        return 'kurang dari 1 mnt';
    }
    $minutes = (int) round($seconds / 60);
    if ($minutes < 60) {
        return $minutes . ' mnt';
    }
    $hours = intdiv($minutes, 60);
    $rest = $minutes % 60;

    return $hours . ' j' . ($rest > 0 ? ' ' . $rest . ' mnt' : '');
};

$shortRupiah = static function (int $value): string {
    if ($value >= 1000000) {
        return 'Rp' . number_format($value / 1000000, 1, ',', '.') . ' jt';
    }
    if ($value >= 1000) {
        return 'Rp' . number_format($value / 1000, 0, ',', '.') . ' rb';
    }

    return 'Rp' . number_format($value, 0, ',', '.');
};

// Timeline pesanan terpilih
$selectedOrderId = (int) ($_GET['order'] ?? 0);
$selected = null;
foreach ($tracking['timeline'] as $item) {
    if ($selectedOrderId === $item['id']) {
        $selected = $item;
        break;
    }
}
if ($selected === null && $tracking['timeline'] !== []) {
    $selected = $tracking['timeline'][0];
}

$page_title = 'Data Tracking';
$active_nav = 'tracking';
$shell_title = 'Data Tracking';
$shell_subtitle = $isPro ? 'Analitik pesanan khusus seller PRO' : 'Fitur khusus PRO';
require __DIR__ . '/../includes/seller-layout.php';
?>

<?php if (!$isPro): ?>
    <div class="stat-grid">
        <?= stat_card('Funnel Pesanan', '—', 'filter', 'blue', 'Khusus PRO') ?>
        <?= stat_card('Waktu Proses', '—', 'clock', 'yellow', 'Khusus PRO') ?>
        <?= stat_card('Omzet 7 Hari', '—', 'wallet', 'mint', 'Khusus PRO') ?>
        <?= stat_card('Performa Boost', '—', 'sparkles', 'indigo', 'Khusus PRO') ?>
    </div>

    <div class="pro-locked">
        <div class="pro-locked__preview" aria-hidden="true">
            <div class="metric-row">
                <div class="metric"><div class="metric__label">Pesanan selesai</div><div class="metric__value">42</div></div>
                <div class="metric"><div class="metric__label">Rata-rata diterima</div><div class="metric__value">11 mnt</div></div>
                <div class="metric"><div class="metric__label">Omzet</div><div class="metric__value">Rp1,2 jt</div></div>
            </div>
            <div class="chart-box">
                <div class="funnel" style="margin-top:8px">
                    <?php foreach ([['Dipesan', 80], ['Diproses', 62], ['Siap', 48], ['Selesai', 34], ['Dibatalkan', 12]] as [$label, $width]): ?>
                        <div class="funnel__row"><span class="funnel__label"><?= e($label) ?></span><span class="funnel__track"><span class="funnel__fill" style="width:<?= $width ?>%"></span></span><span class="funnel__value">—</span></div>
                    <?php endforeach; ?>
                </div>
                <div class="skeleton" style="height:120px;margin-top:16px"></div>
            </div>
        </div>
        <div class="pro-locked__overlay">
            <span class="pro-locked__icon"><?= icon('lock', 26) ?></span>
            <h2 class="pro-locked__title">Data Tracking khusus seller PRO</h2>
            <p class="pro-locked__text">Lihat funnel pesanan, waktu proses rata-rata antar tahap, timeline tiap pesanan, grafik omzet 7 hari, produk terlaris, dan performa Boost. Upgrade sekali, langsung terbuka.</p>
            <div class="btn-row" style="justify-content:center">
                <a class="btn btn--yellow" href="<?= e(url('seller/pro.php')) ?>"><?= icon('sparkles', 16) ?>Upgrade ke PRO — Rp25.000</a>
                <a class="btn btn--outline" href="<?= e(url('seller/orders.php')) ?>"><?= icon('box', 16) ?>Kelola pesanan dulu</a>
            </div>
            <span class="tiny muted">Pembayaran simulasi · tanpa biaya nyata</span>
        </div>
    </div>
<?php else: ?>
    <div class="metric-row" style="grid-template-columns:repeat(4,minmax(0,1fr))">
        <div class="metric">
            <div class="metric__label">Pesanan selesai</div>
            <div class="metric__value"><?= (int) $tracking['funnel']['completed'] ?> / <?= (int) $tracking['funnel_total'] ?></div>
            <div class="tiny muted"><?= (int) $tracking['completion_rate'] ?>% dari total pesanan</div>
        </div>
        <div class="metric">
            <div class="metric__label">Rata-rata diterima</div>
            <div class="metric__value"><?= e($formatDuration((int) $tracking['avg_accept'])) ?></div>
            <div class="tiny muted">Dipesan → Diproses</div>
        </div>
        <div class="metric">
            <div class="metric__label">Rata-rata disiapkan</div>
            <div class="metric__value"><?= e($formatDuration((int) $tracking['avg_prepare'])) ?></div>
            <div class="tiny muted">Diproses → Siap</div>
        </div>
        <div class="metric">
            <div class="metric__label">Rata-rata diserahkan</div>
            <div class="metric__value"><?= e($formatDuration((int) $tracking['avg_handover'])) ?></div>
            <div class="tiny muted">Siap → Selesai</div>
        </div>
    </div>

    <div class="dash-grid">
        <div>
            <div class="chart-box" style="margin-bottom:16px">
                <div class="chart-box__head">
                    <div>
                        <div class="panel__title"><?= icon('chart', 17) ?>Funnel pesanan</div>
                        <div class="panel__sub">Jumlah pesanan pada setiap tahap (semua waktu).</div>
                    </div>
                    <span class="badge badge--outline"><?= (int) $tracking['funnel_total'] ?> pesanan</span>
                </div>
                <div class="funnel">
                    <?php
                    $maxFunnel = max(1, ...array_values($tracking['funnel']));
                    $funnelMeta = [
                        'placed'     => ['Dipesan', 'warning', 'clock'],
                        'processing' => ['Diproses', 'info', 'refresh'],
                        'ready'      => ['Siap', 'cyan', 'check-circle'],
                        'completed'  => ['Selesai', 'success', 'check'],
                        'cancelled'  => ['Dibatalkan', 'neutral', 'x'],
                    ];
                    foreach ($funnelMeta as $key => [$label, $tone, $iconName]):
                        $count = (int) $tracking['funnel'][$key];
                        $width = $count > 0 ? max(2, ($count / $maxFunnel) * 100) : 0;
                        ?>
                        <div class="funnel__row">
                            <span class="funnel__label"><?= icon($iconName, 12) ?><?= e($label) ?></span>
                            <span class="funnel__track"><span class="funnel__fill funnel__fill--<?= $tone ?>" style="width:<?= round($width, 1) ?>%"></span></span>
                            <span class="funnel__value"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="tiny muted" style="margin-top:10px">Funnel dihitung dari status pesanan saat ini. Pesanan selesai dan dibatalkan tidak lagi berubah status.</p>
            </div>

            <div class="chart-box">
                <div class="chart-box__head">
                    <div>
                        <div class="panel__title"><?= icon('trend', 17) ?>Omzet 7 hari terakhir</div>
                        <div class="panel__sub">Subtotal pesanan selesai per hari (sebelum komisi).</div>
                    </div>
                    <div class="chart-legend"><span><i></i>Omzet harian</span></div>
                </div>
                <?php
                $daily = $tracking['daily'];
                $maxDaily = max(1, ...array_values($daily));
                $days = array_keys($daily);
                $barW = 54;
                $gap = 26;
                $chartW = count($days) * ($barW + $gap);
                $baseY = 150;
                $maxH = 120;
                $dayNames = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];
                ?>
                <svg class="chart-svg" viewBox="0 0 <?= $chartW + 20 ?> 190" role="img" aria-label="Grafik omzet tujuh hari terakhir">
                    <?php foreach ([0, 25, 50, 75, 100] as $pct): ?>
                        <?php $y = $baseY - ($maxH * $pct / 100); ?>
                        <line x1="0" y1="<?= $y ?>" x2="<?= $chartW ?>" y2="<?= $y ?>" stroke="#E7EEF9" stroke-width="1"/>
                    <?php endforeach; ?>
                    <?php foreach ($days as $index => $day): ?>
                        <?php
                        $value = (int) ($daily[$day] ?? 0);
                        $height = $value > 0 ? max(4, ($value / $maxDaily) * $maxH) : 0;
                        $x = 10 + $index * ($barW + $gap);
                        $y = $baseY - $height;
                        $isToday = $day === date('Y-m-d');
                        ?>
                        <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= $height ?>" rx="7" fill="<?= $isToday ? '#0B2FC9' : '#5B8DEF' ?>"/>
                        <?php if ($value > 0): ?>
                            <text x="<?= $x + $barW / 2 ?>" y="<?= $y - 6 ?>" text-anchor="middle" font-size="10" font-weight="600" fill="#33415C" font-family="Inter, sans-serif"><?= e($shortRupiah($value)) ?></text>
                        <?php endif; ?>
                        <text x="<?= $x + $barW / 2 ?>" y="172" text-anchor="middle" font-size="10" fill="#8B97AC" font-family="Inter, sans-serif"><?= e($dayNames[date('D', strtotime($day))] ?? date('D', strtotime($day))) ?></text>
                    <?php endforeach; ?>
                </svg>
                <?php if (array_sum($daily) === 0): ?>
                    <p class="small muted" style="margin-top:8px">Belum ada pesanan selesai dalam 7 hari terakhir. Grafik akan terisi setelah pembeli mengonfirmasi pesanan.</p>
                <?php else: ?>
                    <p class="tiny muted" style="margin-top:8px">Total 7 hari: <strong><?= rupiah((int) array_sum($daily)) ?></strong></p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div class="panel__title"><?= icon('sparkles', 17) ?>Performa Boost</div>
                </div>
                <?php if ($tracking['boosts'] === []): ?>
                    <p class="small muted">Belum ada produk yang di-Boost.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table table--compact">
                            <thead><tr><th>Produk</th><th>Kunjungan</th><th>Pesanan</th></tr></thead>
                            <tbody>
                                <?php foreach ($tracking['boosts'] as $boost): ?>
                                    <tr>
                                        <td>
                                            <div style="font-size:11.5px;font-weight:600;line-height:1.3"><?= e($boost['name']) ?></div>
                                            <div class="tiny muted"><?= $boost['boost_until'] && strtotime((string) $boost['boost_until']) > time() ? 'Aktif s/d ' . tanggal((string) $boost['boost_until']) : 'Boost berakhir' ?></div>
                                        </td>
                                        <td class="tabular"><?= number_format((int) $boost['view_count'], 0, ',', '.') ?></td>
                                        <td class="tabular"><?= (int) $boost['orders_since_boost'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="tiny muted" style="margin-top:8px">Kunjungan adalah total view produk; pesanan dihitung sejak Boost dimulai.</p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="panel" style="margin-bottom:16px">
                <div class="panel__head">
                    <div class="panel__title"><?= icon('clock', 17) ?>Timeline pesanan</div>
                </div>
                <?php if ($tracking['timeline'] === []): ?>
                    <p class="small muted">Belum ada pesanan untuk ditampilkan.</p>
                <?php else: ?>
                    <form method="get" action="<?= e(url('seller/tracking.php')) ?>" style="margin-bottom:12px">
                        <label class="label" for="trackingOrder">Pilih pesanan</label>
                        <select class="select" id="trackingOrder" name="order" data-autosubmit style="margin-top:6px">
                            <?php foreach ($tracking['timeline'] as $timelineOrder): ?>
                                <option value="<?= (int) $timelineOrder['id'] ?>" <?= $selected && $selected['id'] === $timelineOrder['id'] ? 'selected' : '' ?>>
                                    <?= e($timelineOrder['code']) ?> · <?= e($timelineOrder['buyer']) ?> · <?= e(order_status_label((string) $timelineOrder['status'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($selected): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding-bottom:10px;border-bottom:1px solid var(--border);margin-bottom:12px">
                            <div>
                                <div style="font-size:12.5px;font-weight:600"><?= e($selected['code']) ?></div>
                                <div class="tiny muted"><?= e($selected['buyer']) ?> · <?= rupiah((int) $selected['total']) ?></div>
                            </div>
                            <?= status_badge((string) $selected['status']) ?>
                        </div>
                        <div class="timeline">
                            <?php foreach ($selected['events'] as $i => $event): ?>
                                <?php $isLastEvent = $i === count($selected['events']) - 1; ?>
                                <div class="timeline__item <?= $isLastEvent ? 'is-active' : 'is-done' ?>">
                                    <span class="timeline__dot"></span>
                                    <div class="timeline__title"><?= e($event['title']) ?></div>
                                    <div class="timeline__time"><?= e(tanggal((string) $event['created_at'], true)) ?> · <?= e(time_ago((string) $event['created_at'])) ?></div>
                                    <?php if (!empty($event['note'])): ?><div class="timeline__note"><?= e((string) $event['note']) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="panel" style="margin-bottom:16px">
                <div class="panel__head">
                    <div class="panel__title"><?= icon('flame', 17) ?>Produk terlaris</div>
                </div>
                <?php if ($tracking['top_products'] === []): ?>
                    <p class="small muted">Belum ada produk terjual.</p>
                <?php else: ?>
                    <?php foreach ($tracking['top_products'] as $index => $product): ?>
                        <div style="display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px dashed var(--border)">
                            <span class="badge badge--neutral" style="flex-shrink:0">#<?= $index + 1 ?></span>
                            <div style="flex:1;min-width:0">
                                <div style="font-size:12px;font-weight:600;line-height:1.35"><?= e($product['name_snapshot']) ?></div>
                                <div class="tiny muted"><?= (int) $product['qty'] ?> terjual · <?= rupiah((int) $product['revenue']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>

