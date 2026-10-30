<?php
/** Kepala halaman publik. Variabel: $page_title, $page_desc (opsional), $body_class (opsional). */
$page_title = $page_title ?? 'Beranda';
$page_desc = $page_desc ?? 'NeMart — marketplace usaha mahasiswa Politeknik Negeri Malang. Temukan makanan, minuman, jasa, dan merch dari toko mahasiswa dengan pembayaran escrow yang aman.';
$body_class = $body_class ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$shareImage = (!empty($_SERVER['HTTP_HOST']))
    ? (($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . asset('img/logo.png'))
    : asset('img/logo.png');
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> · NeMart</title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta name="theme-color" content="#0B2FC9">
    <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NeMart">
    <meta property="og:title" content="<?= e($page_title) ?> · NeMart">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <meta property="og:image" content="<?= e($shareImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/pages.css')) ?>">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="<?= e($body_class) ?>">
<a class="skip-link" href="#main">Lewati ke konten utama</a>
