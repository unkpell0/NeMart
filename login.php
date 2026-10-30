<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$errorCode = (string) ($_GET['error'] ?? '');
$next = (string) ($_GET['next'] ?? '');
$accounts = [];
$stmt = db()->query("SELECT u.*, s.name AS store_name, s.plan, s.verified_status, s.icon AS store_icon
                     FROM users u LEFT JOIN stores s ON s.owner_id = u.id
                     WHERE u.id IN (1, 2, 3, 4, 5)
                     ORDER BY CASE u.id WHEN 1 THEN 1 WHEN 2 THEN 2 WHEN 3 THEN 3 WHEN 4 THEN 4 ELSE 5 END");
$accounts = $stmt->fetchAll();

if (is_post()) {
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir kedaluwarsa. Silakan pilih akun sekali lagi.');
        redirect('login.php');
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    $target = (string) ($_POST['next'] ?? '');
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $account = $stmt->fetch();

    if (!$account) {
        flash('danger', 'Akun demo tidak ditemukan.');
        redirect('login.php');
    }
    if ($account['status'] !== 'active') {
        flash('danger', 'Akun ini sedang diblokir dan tidak bisa masuk.');
        redirect('login.php');
    }

    login_user((int) $account['id']);
    unset($_SESSION['intended']);

    $home = match ($account['role']) {
        'seller' => 'seller/dashboard.php',
        'admin'  => 'admin/dashboard.php',
        default  => 'index.php',
    };

    if ($target !== '' && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
        flash('success', 'Selamat datang, ' . $account['name'] . '.');
        header('Location: ' . $target);
        exit;
    }

    flash('success', 'Masuk sebagai ' . $account['name'] . '.');
    redirect($home);
}

if (is_logged_in() && $errorCode === '' && !is_post()) {
    $user = current_user();
    redirect(match ($user['role']) {
        'seller' => 'seller/dashboard.php',
        'admin'  => 'admin/dashboard.php',
        default  => 'index.php',
    });
}

$page_title = 'Masuk akun demo';
$page_desc = 'Pilih akun demo NeMart untuk masuk sebagai pembeli, penjual, atau admin.';
require __DIR__ . '/includes/head.php';
?>
<div class="auth">
    <section class="auth__brand" aria-label="Tentang NeMart">
        <div class="auth__brand-inner">
            <div class="auth__logo">
                <span class="brand__mark"><span>N</span></span>
                <span class="brand__text">
                    <span class="brand__name">NeMart</span>
                    <span class="brand__tag">Polinema</span>
                </span>
            </div>
            <h1 class="auth__headline">Satu klik untuk<br><em>usaha mahasiswa</em><br>yang lebih rapi.</h1>
            <p class="auth__text">NeMart menghubungkan pembeli kampus dengan toko mahasiswa: katalog rapi, escrow yang menahan dana sampai pesanan diterima, dan pencatatan pesanan yang benar-benar tersambung antara pembeli dan penjual.</p>

            <div class="auth__points">
                <div class="auth__point">
                    <span class="auth__point-icon"><?= icon('shield', 15) ?></span>
                    <span><strong style="color:#fff">Escrow aman.</strong> Dana ditahan platform sampai kamu menekan Konfirmasi Diterima.</span>
                </div>
                <div class="auth__point">
                    <span class="auth__point-icon"><?= icon('store', 15) ?></span>
                    <span><strong style="color:#fff">Toko terverifikasi.</strong> Setiap penjual dicek oleh admin kampus sebelum tampil di katalog.</span>
                </div>
                <div class="auth__point">
                    <span class="auth__point-icon"><?= icon('chart', 15) ?></span>
                    <span><strong style="color:#fff">Data untuk penjual.</strong> Seller PRO melihat funnel, waktu proses, dan omzet di Data Tracking.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="auth__aside">
        <div class="auth__panel">
            <h2 class="auth__title">Masuk sebagai siapa?</h2>
            <p class="auth__sub">Akun demo tanpa kata sandi. Pilih satu kartu untuk langsung masuk.</p>

            <?= flash_render() ?>

            <?php if ($errorCode === 'unauthorized'): ?>
                <div class="flash flash--warning" style="margin-bottom:16px">
                    <?= icon('lock', 18) ?>
                    <span>Halaman itu hanya bisa dibuka oleh peran tertentu. Masuk dengan akun yang sesuai, ya.</span>
                </div>
            <?php endif; ?>

            <div class="account-list">
                <?php foreach ($accounts as $account): ?>
                    <?php
                    $role = (string) $account['role'];
                    $roleLabel = $role === 'buyer' ? 'Pembeli' : ($role === 'seller' ? 'Penjual' : 'Admin');
                    $meta = match ($role) {
                        'seller' => ($account['store_name'] ?: 'Belum punya toko') . ' · ' . ($account['plan'] === 'pro' ? 'PRO' : 'Free') . ' · ' . $account['prodi']
                            . ($account['verified_status'] === 'pending' ? ' · Pending' : ''),
                        'admin'  => 'Admin kemahasiswaan',
                        default  => 'Pembeli · ' . $account['prodi'],
                    };
                    ?>
                    <form method="post" action="<?= e(url('login.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>">
                        <input type="hidden" name="next" value="<?= e($next) ?>">
                        <button type="submit" class="account-card">
                            <?= avatar((string) $account['name'], (string) $account['initials'], $role === 'seller' ? 'avatar--seller' : '') ?>
                            <span class="account-card__body">
                                <span class="account-card__name">
                                    <?= e($account['name']) ?>
                                    <span class="role-chip role-chip--<?= e($role) ?>"><?= e($roleLabel) ?></span>
                                </span>
                                <span class="account-card__meta"><?= e($meta) ?></span>
                            </span>
                            <span class="account-card__go"><?= icon('arrow-right', 18) ?></span>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <p class="auth__note">
                <?= icon('info', 14) ?>
                <span>Mode demo: pembayaran disimulasikan dan tidak ada transaksi uang nyata. Data tersimpan di database SQLite yang sama untuk semua peran.</span>
            </p>

            <a class="auth__back" href="<?= e(url('index.php')) ?>"><?= icon('arrow-left', 15) ?>Kembali ke beranda</a>
        </div>
    </section>
</div>
</body>
</html>
