<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}

require_csrf();

logout_user();
flash('info', 'Kamu sudah keluar. Sampai jumpa lagi!');
redirect('index.php');
