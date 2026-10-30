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
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
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
        <a class="brand" href="<?= e(url('index.php')) ?>" style="justify-content:center;margin-bottom:18px" aria-label="NeMart">
            <img class="brand__logo" src="<?= e(asset('img/logo.png')) ?>" alt="NeMart" width="643" height="200">
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
