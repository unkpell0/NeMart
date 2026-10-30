<?php
/** Halaman galat mandiri (dipakai untuk 403, 404, 419). */
$error_code = $error_code ?? '404';
$error_title = $error_title ?? 'Halaman tidak ditemukan';
$error_message = $error_message ?? 'Tautan yang kamu buka mungkin sudah berubah atau tidak tersedia lagi.';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($error_title) ?> · NeMart</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%230B2FC9'/%3E%3Cpath d='M20 46V18h5l14 19V18h5v28h-5L25 27v19z' fill='%23fff'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/pages.css')) ?>">
</head>
<body>
<main class="error-page" id="main">
    <div class="error-card">
        <a class="brand" href="<?= e(url('index.php')) ?>" style="justify-content:center;margin-bottom:18px">
            <span class="brand__mark"><span>N</span></span>
            <span class="brand__text"><span class="brand__name">NeMart</span></span>
        </a>
        <div class="error-code"><?= e($error_code) ?></div>
        <h1><?= e($error_title) ?></h1>
        <p><?= e($error_message) ?></p>
        <div class="btn-row">
            <a class="btn btn--primary" href="<?= e(url('index.php')) ?>"><?= icon('home', 16) ?>Ke Beranda</a>
            <?php if (!is_logged_in()): ?>
                <a class="btn btn--outline" href="<?= e(url('login.php')) ?>"><?= icon('user', 16) ?>Masuk Akun Demo</a>
            <?php else: ?>
                <a class="btn btn--outline" href="<?= e(url('orders.php')) ?>"><?= icon('box', 16) ?>Pesanan Saya</a>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
