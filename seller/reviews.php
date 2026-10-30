<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_login();
$store = require_seller();
$storeId = (int) $store['id'];

if (is_post()) {
    require_csrf();
    if (($_POST['action'] ?? '') === 'reply') {
        $result = reply_review((int) ($_POST['review_id'] ?? 0), $storeId, (string) ($_POST['reply'] ?? ''));
        flash($result['ok'] ? 'success' : 'danger', $result['message']);
    }
    redirect('seller/reviews.php');
}

$filter = (int) ($_GET['rating'] ?? 0);
$allReviews = store_reviews($storeId);
$reviews = array_values(array_filter($allReviews, static fn (array $r): bool => $filter === 0 || (int) $r['rating'] === $filter));

$counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$sum = 0;
foreach ($allReviews as $review) {
    $counts[(int) $review['rating']] = ($counts[(int) $review['rating']] ?? 0) + 1;
    $sum += (int) $review['rating'];
}
$avg = $allReviews === [] ? 0 : round($sum / count($allReviews), 1);
$unreplied = 0;
foreach ($allReviews as $review) {
    if (empty($review['seller_reply'])) {
        $unreplied++;
    }
}

$page_title = 'Ulasan Pembeli';
$active_nav = 'reviews';
$shell_title = 'Ulasan Pembeli';
$shell_subtitle = count($allReviews) . ' ulasan · ' . $unreplied . ' belum dibalas';
require __DIR__ . '/../includes/seller-layout.php';
?>

<div class="dash-grid">
    <div>
        <div class="panel panel--flush">
            <div class="panel__head" style="padding:14px 16px;margin:0;border-bottom:1px solid var(--border)">
                <div>
                    <div class="panel__title"><?= icon('star', 17) ?>Semua ulasan</div>
                    <div class="panel__sub">Balasan hanya bisa dikirim satu kali per ulasan.</div>
                </div>
                <form method="get" action="<?= e(url('seller/reviews.php')) ?>" class="catalog-toolbar__sort">
                    <label for="ratingFilter">Filter</label>
                    <select class="select" id="ratingFilter" name="rating" data-autosubmit>
                        <option value="0">Semua bintang</option>
                        <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                            <option value="<?= $star ?>" <?= $filter === $star ? 'selected' : '' ?>><?= $star ?> bintang (<?= (int) $counts[$star] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($reviews === []): ?>
                <div style="padding:16px">
                    <?= empty_state('star', 'Belum ada ulasan', 'Ulasan muncul otomatis setelah pembeli menyelesaikan pesanan dan menilainya.') ?>
                </div>
            <?php else: ?>
                <div style="padding:0 18px">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-item">
                            <div class="review-item__head">
                                <?= avatar((string) $review['buyer_name'], (string) $review['buyer_initials'], 'avatar--sm') ?>
                                <div style="flex:1">
                                    <div class="review-item__name"><?= e($review['buyer_name']) ?></div>
                                    <div class="review-item__date">
                                        <?= e(tanggal((string) $review['created_at'])) ?> ·
                                        <a href="<?= e(url('product.php?id=' . (int) $review['product_id'])) ?>" style="color:var(--blue-700)"><?= e($review['product_name']) ?></a>
                                    </div>
                                </div>
                                <?= rating_stars((float) $review['rating'], null, 14) ?>
                            </div>
                            <div class="review-item__body"><?= e((string) $review['comment']) ?></div>

                            <?php if (!empty($review['seller_reply'])): ?>
                                <div class="review-reply"><strong>Balasanmu:</strong> <?= e((string) $review['seller_reply']) ?></div>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('seller/reviews.php')) ?>" style="margin-top:10px">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                                    <div class="field">
                                        <label class="label" for="reply-<?= (int) $review['id'] ?>">Balas ulasan</label>
                                        <textarea class="textarea" id="reply-<?= (int) $review['id'] ?>" name="reply" rows="2" maxlength="300" required placeholder="Contoh: Terima kasih, kak! Kami tunggu pesanan berikutnya."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn--primary btn--sm" style="margin-top:8px"><?= icon('message', 14) ?>Kirim Balasan</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel__title"><?= icon('star', 17) ?>Ringkasan rating</div>
            <div style="text-align:center;padding:12px 0 6px">
                <div style="font-family:var(--font-display);font-size:40px;font-weight:700;line-height:1"><?= number_format($avg, 1, ',', '.') ?></div>
                <div class="small muted">dari 5 · <?= count($allReviews) ?> ulasan</div>
                <div style="margin-top:6px;display:flex;justify-content:center"><?= rating_stars((float) $avg, null, 16, false) ?></div>
            </div>
            <div class="review-bars" style="margin-top:10px">
                <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                    <?php $pct = count($allReviews) > 0 ? ($counts[$star] / count($allReviews)) * 100 : 0; ?>
                    <div class="review-bar">
                        <span><?= $star ?> <span aria-hidden="true">★</span></span>
                        <span class="review-bar__track"><span class="review-bar__fill" style="width:<?= round($pct, 1) ?>%"></span></span>
                        <span style="text-align:right"><?= (int) $counts[$star] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="notice notice--info" style="margin-top:14px;font-size:11px">
                <?= icon('info', 14) ?>
                <span>Rating toko dihitung dari seluruh ulasan produk. Membalas ulasan mengirim notifikasi ke pembeli.</span>
            </div>
        </div>

        <?php if ($unreplied > 0): ?>
            <div class="panel">
                <div class="panel__title"><?= icon('message', 17) ?>Perlu dibalas</div>
                <p class="small muted" style="margin-top:6px">Ada <?= $unreplied ?> ulasan yang belum kamu balas. Balasan cepat membuat pembeli percaya.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/seller-layout-end.php'; ?>
