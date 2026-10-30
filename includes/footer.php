<?php
/** Footer publik + pemuatan JavaScript. */
?>
<footer class="site-footer">
    <div class="container footer__grid">
        <div>
            <div class="footer__brand">
                <span class="brand__mark"><span>N</span></span>
                <span class="brand__name" style="font-family:var(--font-display);font-weight:700;">NeMart</span>
            </div>
            <p class="footer__desc">Connecting Student Businesses, One Click at a Time. Marketplace yang menghubungkan usaha mahasiswa Polinema dengan pembeli di kampus.</p>
            <div class="footer__chips" style="margin-top:14px">
                <span class="footer__chip"><?= icon('check-circle', 11) ?>Demo MVP</span>
                <span class="footer__chip"><?= icon('shield', 11) ?>Escrow simulasi</span>
            </div>
        </div>
        <div>
            <h3 class="footer__title">Jelajahi</h3>
            <ul class="footer__links">
                <li><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
                <li><a href="<?= e(url('search.php')) ?>">Semua Produk</a></li>
                <li><a href="<?= e(url('search.php?sort=terlaris')) ?>">Produk Terlaris</a></li>
                <li><a href="<?= e(url('index.php#produk-unggulan')) ?>">Produk Unggulan</a></li>
            </ul>
        </div>
        <div>
            <h3 class="footer__title">Akun</h3>
            <ul class="footer__links">
                <li><a href="<?= e(url('login.php')) ?>">Masuk akun demo</a></li>
                <li><a href="<?= e(url('orders.php')) ?>">Pesanan saya</a></li>
                <li><a href="<?= e(url('notifications.php')) ?>">Notifikasi</a></li>
                <li><a href="<?= e(url('cart.php')) ?>">Keranjang</a></li>
            </ul>
        </div>
        <div>
            <h3 class="footer__title">Untuk Penjual</h3>
            <ul class="footer__links">
                <li><a href="<?= e(url('seller/dashboard.php')) ?>">Seller Centre</a></li>
                <li><a href="<?= e(url('seller/boost.php')) ?>">Promosi Boost</a></li>
                <li><a href="<?= e(url('seller/pro.php')) ?>">Paket PRO</a></li>
                <li><a href="<?= e(url('seller/tracking.php')) ?>">Data Tracking</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer__bottom">
        <span>&copy; <?= date('Y') ?> NeMart — Tim Pengen Juara, Politeknik Negeri Malang.</span>
        <span>Demo kompetisi ICT Recursion 2.0 · Pembayaran disimulasikan.</span>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
