<?php
/** Kepala halaman publik. Variabel: $page_title, $page_desc (opsional), $body_class (opsional). */
$page_title = $page_title ?? 'Beranda';
$page_desc = $page_desc ?? 'NeMart — marketplace usaha mahasiswa Politeknik Negeri Malang. Temukan makanan, minuman, jasa, dan merch dari toko mahasiswa dengan pembayaran escrow yang aman.';
$body_class = $body_class ?? '';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> · NeMart</title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta name="theme-color" content="#0B2FC9">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%230B2FC9'/%3E%3Cpath d='M20 46V18h5l14 19V18h5v28h-5L25 27v19z' fill='%23fff'/%3E%3C/svg%3E">
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
