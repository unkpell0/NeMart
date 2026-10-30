<?php
/**
 * NeMart v2 — pembuat tabel + data demo.
 * Idempotent: aman dipanggil setiap request. Gunakan --force untuk reset penuh.
 *
 * CLI: php db/seed.php --force
 */

declare(strict_types=1);

function nemart_drop_all(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = OFF');
    foreach ([
        'boosts', 'reviews', 'notifications', 'order_events', 'order_items',
        'orders', 'cart_items', 'products', 'categories', 'stores', 'users',
    ] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
    }
    $pdo->exec('PRAGMA foreign_keys = ON');
}

function nemart_schema(PDO $pdo): void
{
    $pdo->exec((string) file_get_contents(__DIR__ . '/schema.sql'));
}

function nemart_is_seeded(PDO $pdo): bool
{
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function nemart_seed(PDO $pdo, bool $force = false): void
{
    if (!$force && nemart_is_seeded($pdo)) {
        return;
    }

    if ($force) {
        nemart_drop_all($pdo);
    }
    nemart_schema($pdo);

    $pdo->beginTransaction();

    $ts = static fn (string $expr): string => date('Y-m-d H:i:s', strtotime($expr));
    $now = $ts('now');

    // ------------------------------------------------------------------ users
    $users = [
        [1, 'Marvelino R.', 'marvelino@student.polinema.ac.id', 'buyer', 'Teknik Informatika', 'MR', $ts('-120 days')],
        [2, 'Aditya P.', 'aditya@student.polinema.ac.id', 'seller', 'Teknik Informatika', 'AP', $ts('-210 days')],
        [3, 'Salsa N.', 'salsa@student.polinema.ac.id', 'seller', 'Administrasi Niaga', 'SN', $ts('-240 days')],
        [4, 'Kemahasiswaan Polinema', 'kemahasiswaan@polinema.ac.id', 'admin', 'Bagian Kemahasiswaan', 'KP', $ts('-400 days')],
        [5, 'Rina T.', 'rina@student.polinema.ac.id', 'seller', 'Teknik Elektro', 'RT', $ts('-9 days')],
        [6, 'Nadia K.', 'nadia@student.polinema.ac.id', 'seller', 'Teknik Kimia', 'NK', $ts('-180 days')],
        [7, 'Bagas W.', 'bagas@student.polinema.ac.id', 'seller', 'Teknik Mesin', 'BW', $ts('-160 days')],
        [8, 'Ayu L.', 'ayu@student.polinema.ac.id', 'seller', 'Akuntansi', 'AL', $ts('-150 days')],
        [9, 'Fajar R.', 'fajar@student.polinema.ac.id', 'seller', 'Teknik Informatika', 'FR', $ts('-190 days')],
        [10, 'Yoga P.', 'yoga@student.polinema.ac.id', 'seller', 'Teknik Sipil', 'YP', $ts('-170 days')],
        [11, 'Intan S.', 'intan@student.polinema.ac.id', 'seller', 'Teknik Elektro', 'IS', $ts('-140 days')],
        [12, 'Dimas S.', 'dimas@student.polinema.ac.id', 'buyer', 'Teknik Sipil', 'DS', $ts('-130 days')],
        [13, 'Nabila P.', 'nabila@student.polinema.ac.id', 'buyer', 'Akuntansi', 'NP', $ts('-110 days')],
        [14, 'Fikri A.', 'fikri@student.polinema.ac.id', 'buyer', 'Teknik Elektro', 'FA', $ts('-95 days')],
    ];
    $stmt = $pdo->prepare('INSERT INTO users (id, name, email, role, prodi, initials, status, created_at) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $u[5], 'active', $u[6]]);
    }

    // ----------------------------------------------------------------- stores
    $stores = [
        [1, 2, 'Dapur Mahasiswa', 'dapur-mahasiswa', 'Masakan rumahan porsi mahasiswa. Geprek, nasi goreng, dan rice bowl dibuat dadakan setelah pesanan masuk.', 'Teknik Informatika', 'Kantin Gedung A, lantai 1', 'utensils', 'verified', 'free', null, $ts('-210 days')],
        [2, 3, 'Kopi Kampus', 'kopi-kampus', 'Kedai kopi mahasiswa Administrasi Niaga. Biji lokal Gayo, diseduh per gelas, bisa pesan tanpa gula.', 'Administrasi Niaga', 'Kantin Gedung B, dekat lift', 'coffee', 'verified', 'pro', $ts('+26 days'), $ts('-240 days')],
        [3, 6, 'Sweet Bites Polinema', 'sweet-bites', 'Kue rumahan untuk oleh-oleh dan rapat organisasi. Dipanggang setiap pagi, tanpa pengawet.', 'Teknik Kimia', 'Gedung C, lobi utama', 'cake', 'verified', 'free', null, $ts('-180 days')],
        [4, 7, 'Print Express', 'print-express', 'Cetak, fotokopi, dan jilid dengan harga per lembar yang jelas. Bisa pesan lewat WhatsApp setelah checkout.', 'Teknik Mesin', 'Gedung D, lantai 2 (sebelah laboratorium)', 'print', 'verified', 'free', null, $ts('-160 days')],
        [5, 8, 'Laundry Bersih', 'laundry-bersih', 'Laundry kiloan dan satuan untuk mahasiswa asrama. Antar-jemput gratis di area kampus.', 'Akuntansi', 'Asrama Mahasiswa, blok B', 'laundry', 'verified', 'free', null, $ts('-150 days')],
        [6, 9, 'Kreatif Design Studio', 'kreatif-design', 'Jasa desain grafis mahasiswa DKV dan Informatika. Poster, feed Instagram, logo, sampai video reels.', 'Teknik Informatika', 'Gedung A, ruang 2.04', 'pen', 'verified', 'pro', $ts('+18 days'), $ts('-190 days')],
        [7, 5, 'Rina Craft', 'rina-craft', 'Merch dan aksesori rajut buatan tangan. Sedang melengkapi berkas verifikasi mahasiswa.', 'Teknik Elektro', 'Gedung E, lantai 1', 'shirt', 'pending', 'free', null, $ts('-9 days')],
        [8, 10, 'Polinema Merch', 'polinema-merch', 'Kaos, jaket angkatan, dan hoodie resmi himpunan. Produksi terbatas setiap batch.', 'Teknik Sipil', 'Sekretariat BEM, Gedung A lantai 1', 'shirt', 'verified', 'free', null, $ts('-170 days')],
        [9, 11, 'Ruang Belajar', 'ruang-belajar', 'Les privat dan bantuan tugas untuk mata kuliah dasar. Jadwal fleksibel, bisa online.', 'Teknik Elektro', 'Perpustakaan lantai 2, pojok diskusi', 'book', 'verified', 'free', null, $ts('-140 days')],
    ];
    $stmt = $pdo->prepare('INSERT INTO stores (id, owner_id, name, slug, description, department, pickup_point, icon, verified_status, plan, pro_until, joined_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($stores as $s) {
        $stmt->execute($s);
    }

    // ------------------------------------------------------------- categories
    $categories = [
        [1, 'Makanan', 'makanan', 'utensils'],
        [2, 'Minuman', 'minuman', 'coffee'],
        [3, 'Jasa Desain', 'jasa-desain', 'pen'],
        [4, 'Laundry', 'laundry', 'laundry'],
        [5, 'Merch dan Jaket', 'merch-jaket', 'shirt'],
        [6, 'Print dan Fotokopi', 'print-fotokopi', 'print'],
        [7, 'Les dan Jasa Akademik', 'les-akademik', 'book'],
        [8, 'Lainnya', 'lainnya', 'tag'],
    ];
    $stmt = $pdo->prepare('INSERT INTO categories (id, name, slug, icon) VALUES (?,?,?,?)');
    foreach ($categories as $c) {
        $stmt->execute($c);
    }

    // --------------------------------------------------------------- products
    // store, kategori, nama, harga, stok, varian, terjual, dilihat, boost(hari), po, po(hari), deskripsi
    $products = [
        [1, 'Makanan', 'Ayam Geprek Sambal Bawang', 15000, 24, ['Level 1', 'Level 2', 'Level 3', 'Level 4'], 128, 864, 0, 0, 0,
            'Ayam crispy digeprek dengan sambal bawang fresh, disajikan bersama nasi hangat dan lalapan. Pedasnya bisa dipilih sesuai selera.'],
        [1, 'Makanan', 'Nasi Goreng Kampung', 13000, 20, ['Original', 'Pedas'], 96, 641, 0, 0, 0,
            'Nasi goreng dengan bumbu kampung, telur mata sapi, dan kerupuk. Dimasak setelah pesanan masuk agar tetap panas.'],
        [1, 'Makanan', 'Mie Ayam Bakso', 14000, 18, ['Original', 'Extra Bakso'], 74, 512, 0, 0, 0,
            'Mie ayam kenyal dengan topping ayam kecap dan bakso sapi. Kuah kaldu direbus pagi hari.'],
        [1, 'Makanan', 'Rice Bowl Chicken Katsu', 17000, 16, ['Saus Teriyaki', 'Saus Blackpepper'], 51, 388, 0, 0, 0,
            'Nasi putih dengan chicken katsu renyah dan saus pilihan, cocok untuk makan siang di sela kelas.'],
        [1, 'Minuman', 'Es Teh Jumbo', 5000, 50, ['Manis', 'Tawar'], 210, 720, 0, 0, 0,
            'Es teh manis ukuran jumbo 500 ml, teman wajib makan siang. Bisa minta tanpa gula.'],
        [1, 'Lainnya', 'Paket Katering Rapat', 45000, 12, ['10 porsi', '20 porsi'], 32, 240, 0, 0, 0,
            'Paket makan rapat berisi nasi, lauk, sayur, dan air mineral. Pesan minimal H-1 untuk acara organisasi.'],
        [2, 'Minuman', 'Es Kopi Susu Gula Aren', 12000, 40, ['Regular', 'Less Sugar', 'Extra Shot'], 186, 1248, 4, 0, 0,
            'Espresso double shot dengan susu segar dan gula aren cair. Menu paling sering dipesan di Kopi Kampus.'],
        [2, 'Minuman', 'Matcha Latte', 16000, 25, ['Iced', 'Hot'], 92, 702, 0, 0, 0,
            'Matcha grade ceremonial dikocok dengan susu, tidak terlalu manis. Tersedia dingin atau panas.'],
        [2, 'Minuman', 'Americano Dingin', 13000, 30, ['Iced', 'Hot'], 64, 430, 0, 0, 0,
            'Dua shot espresso dan air mineral dingin. Pilihan tepat kalau butuh fokus untuk tugas besar.'],
        [2, 'Minuman', 'Cold Brew Botol 500 ml', 18000, 20, ['Original', 'Vanilla'], 38, 296, 0, 0, 0,
            'Kopi seduh dingin 18 jam, dikemas botol kaca. Tahan tiga hari di kulkas.'],
        [2, 'Makanan', 'Croissant Butter', 14000, 12, [], 27, 215, 0, 0, 0,
            'Croissant mentega buatan sendiri, dipanggang ulang sebelum diserahkan agar renyah.'],
        [2, 'Minuman', 'Paket Ngopi Hemat', 20000, 15, ['2 Kopi Susu', '2 Americano'], 55, 380, 0, 0, 0,
            'Dua gelas kopi dengan harga lebih hemat, cocok untuk belajar berdua atau rapat tim kecil.'],
        [3, 'Makanan', 'Brownies Fudgy Loyang Kecil', 25000, 15, ['Original', 'Keju', 'Almond'], 61, 470, 2, 0, 0,
            'Brownies cokelat pekat dengan tekstur fudgy, satu loyang ukuran 15x10 cm. Bisa dipotong 9 bagian.'],
        [3, 'Makanan', 'Cookies Almond', 18000, 20, ['250 gram'], 39, 288, 0, 0, 0,
            'Cookies renyah dengan almond panggang dan chocolate chunk. Dikemas toples kaca mini.'],
        [3, 'Makanan', 'Bolu Kukus Pandan', 12000, 18, ['6 pcs'], 45, 310, 0, 0, 0,
            'Bolu kukus pandan lembut dengan aroma daun pandan asli, tanpa pewarna sintetis.'],
        [3, 'Makanan', 'Cheesecake Slice', 22000, 8, ['Original', 'Strawberry'], 22, 198, 0, 0, 0,
            'Cheesecake baked dengan basis biskuit, satu potong tebal. Stok terbatas setiap hari.'],
        [4, 'Print dan Fotokopi', 'Print Hitam Putih', 300, 2000, ['HVS 70gr', 'HVS 80gr'], 1450, 2100, 0, 0, 0,
            'Cetak hitam putih per lembar, file dikirim setelah checkout. Hasil bisa diambil di tempat atau diantar.'],
        [4, 'Print dan Fotokopi', 'Fotokopi KTM', 500, 1000, ['Bolak-balik', 'Satu sisi'], 980, 860, 0, 0, 0,
            'Fotokopi kartu tanda mahasiswa untuk keperluan berkas beasiswa dan organisasi.'],
        [4, 'Print dan Fotokopi', 'Jilid Spiral', 5000, 120, ['Maks 50 lembar', 'Maks 100 lembar'], 160, 340, 0, 0, 0,
            'Jilid spiral dengan cover plastik bening dan kertas buffalo. Rapi untuk laporan praktikum.'],
        [4, 'Print dan Fotokopi', 'Cetak Foto 4R', 3000, 300, ['Glossy', 'Matte'], 210, 290, 0, 0, 0,
            'Cetak foto ukuran 4R dengan kertas kualitas foto. Cocok untuk lampiran berkas atau kenang-kenangan.'],
        [4, 'Print dan Fotokopi', 'Print Warna A3', 5000, 200, ['HVS', 'Art Paper'], 120, 260, 0, 0, 0,
            'Cetak warna ukuran A3 untuk poster kegiatan atau tugas mata kuliah.'],
        [5, 'Laundry', 'Laundry Kiloan', 7000, 999, ['Cuci Kering', 'Cuci Setrika', 'Express 6 Jam'], 240, 690, 0, 0, 0,
            'Cuci kiloan dengan deterjen lembut, minimal 2 kg. Express 6 jam tersedia untuk kebutuhan mendadak.'],
        [5, 'Laundry', 'Cuci Sepatu', 20000, 40, ['Sneakers', 'Kanvas'], 88, 410, 0, 0, 0,
            'Cuci sepatu dengan sikat lembut dan sabun khusus, termasuk pengeringan. Estimasi selesai 1 hari.'],
        [5, 'Laundry', 'Setrika Saja per Kg', 5000, 200, [], 120, 350, 0, 0, 0,
            'Layanan setrika untuk pakaian yang sudah dicuci sendiri. Disetrika rapi dan dilipat.'],
        [5, 'Laundry', 'Bed Cover dan Selimut', 25000, 25, ['Single', 'Queen'], 31, 160, 0, 0, 0,
            'Pencucian bed cover dan selimut ukuran single sampai queen, termasuk pengeringan.'],
        [6, 'Jasa Desain', 'Jasa Desain Poster', 35000, 60, ['A3', 'A2', 'Digital'], 74, 610, 5, 0, 0,
            'Desain poster kegiatan kampus, revisi dua kali. File dikirim dalam PDF dan PNG siap cetak.'],
        [6, 'Jasa Desain', 'Desain Feed Instagram', 25000, 80, ['3 Post', '6 Post', '9 Post'], 105, 720, 0, 0, 0,
            'Paket desain feed Instagram yang konsisten untuk usaha mahasiswa, termasuk caption singkat.'],
        [6, 'Jasa Desain', 'Logo Usaha Mahasiswa', 75000, 25, ['Basic', 'Premium'], 28, 380, 0, 0, 0,
            'Logo usaha dengan dua konsep awal, file vektor, dan panduan warna dasar.'],
        [6, 'Jasa Desain', 'Edit Video Reels', 45000, 30, ['30 detik', '60 detik'], 42, 330, 0, 0, 0,
            'Editing video vertikal untuk reels atau TikTok, termasuk subtitle dan musik bebas royalti.'],
        [6, 'Jasa Desain', 'Paket Branding UMKM', 150000, 10, ['Basic', 'Lengkap'], 9, 210, 0, 0, 0,
            'Logo, kartu nama, dan template feed dalam satu paket untuk usaha yang baru mulai.'],
        [6, 'Lainnya', 'Jasa Fotografi Produk', 50000, 20, ['10 Foto', '20 Foto'], 15, 190, 0, 0, 0,
            'Foto produk dengan latar bersih dan pencahayaan studio mini, cocok untuk katalog marketplace.'],
        [8, 'Merch dan Jaket', 'Jaket Angkatan 2025', 65000, 60, ['M', 'L', 'XL', 'XXL'], 140, 980, 6, 1, 12,
            'Jaket angkatan dengan bahan drill premium dan bordir nama angkatan. Pre order batch kedua dibuka sampai akhir bulan.'],
        [8, 'Merch dan Jaket', 'Kaos Polinema Basic', 55000, 45, ['M', 'L', 'XL'], 88, 560, 0, 0, 0,
            'Kaos katun combed 30s dengan sablon logo kampus, nyaman untuk kuliah sehari-hari.'],
        [8, 'Merch dan Jaket', 'Tote Bag Kanvas', 35000, 30, ['Natural', 'Hitam'], 40, 280, 0, 0, 0,
            'Tote bag kanvas tebal dengan sablon satu sisi, muat laptop 14 inci.'],
        [8, 'Merch dan Jaket', 'Hoodie Polinema', 120000, 20, ['M', 'L', 'XL'], 26, 340, 0, 0, 0,
            'Hoodie fleece dengan bordir logo, hangat untuk kelas pagi dan kegiatan malam.'],
        [8, 'Merch dan Jaket', 'Pin Kampus', 10000, 100, ['Logo', 'Angkatan'], 175, 420, 0, 0, 0,
            'Pin enamel ukuran 3 cm dengan pengait besi, cocok untuk tas dan jaket.'],
        [8, 'Lainnya', 'Sewa Kostum Wisuda', 75000, 25, ['Standar', 'Plus Foto'], 34, 260, 0, 0, 0,
            'Sewa toga dan aksesori wisuda untuk foto bersama keluarga, sudah termasuk pengeringan.'],
        [9, 'Les dan Jasa Akademik', 'Les Privat Kalkulus', 40000, 40, ['Online', 'Offline'], 36, 310, 0, 0, 0,
            'Les privat kalkulus satu jam bersama mahasiswa semester atas, fokus latihan soal UTS dan UAS.'],
        [9, 'Les dan Jasa Akademik', 'Bimbingan Tugas Akhir', 60000, 20, ['Online', 'Offline'], 18, 240, 0, 0, 0,
            'Pendampingan menyusun bab dan olah data, bukan jasa pembuatan tugas akhir.'],
        [9, 'Les dan Jasa Akademik', 'Jasa Ketik Cepat', 15000, 100, ['1-3 halaman', '4-6 halaman'], 62, 330, 0, 0, 0,
            'Pengetikan rapi dengan format sesuai pedoman kampus, selesai dalam sehari.'],
        [7, 'Merch dan Jaket', 'Tas Rajut Handmade', 45000, 20, ['Natural', 'Hitam', 'Hijau'], 0, 0, 0, 0, 0,
            'Tas rajut buatan tangan dengan benang katun, kuat untuk buku dan laptop kecil. Dikerjakan sendiri oleh pemilik toko.'],
        [7, 'Merch dan Jaket', 'Gantungan Kunci Rajut', 12000, 60, ['Bunga', 'Hati'], 0, 0, 0, 0, 0,
            'Gantungan kunci rajut mini, cocok untuk hadiah teman satu angkatan. Bisa pesan warna custom.'],
        [7, 'Merch dan Jaket', 'Bucket Hat Rajut', 55000, 15, ['Cream', 'Navy'], 0, 0, 0, 0, 0,
            'Bucket hat rajut dengan lapisan kain dalam, nyaman dipakai untuk kegiatan luar ruangan.'],
    ];
    $stmt = $pdo->prepare('INSERT INTO products (id, store_id, category_id, name, description, price, stock, variants, image, status, sold_count, view_count, boost_until, is_po, po_deadline, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $catIdBySlug = [];
    foreach ($categories as $c) {
        $catIdBySlug[$c[1]] = $c[0];
    }
    $productIdByName = [];
    $productMeta = [];
    $pid = 0;
    foreach ($products as $p) {
        $pid++;
        [$storeId, $catName, $name, $price, $stock, $variants, $sold, $views, $boostDays, $isPo, $poDays, $desc] = $p;
        if (!isset($catIdBySlug[$catName])) {
            throw new RuntimeException("Kategori tidak dikenal: {$catName}");
        }
        $boostUntil = $boostDays > 0 ? $ts("+{$boostDays} days") : null;
        $poDeadline = $poDays > 0 ? $ts("+{$poDays} days") : null;
        $createdAt = $ts('-' . (30 + $pid) . ' days');
        $stmt->execute([
            $pid, $storeId, $catIdBySlug[$catName], $name, $desc, $price, $stock,
            json_encode($variants, JSON_UNESCAPED_UNICODE), null, 'active', $sold, $views,
            $boostUntil, $isPo, $poDeadline, $createdAt,
        ]);
        $productIdByName[$name] = $pid;
        $productMeta[$pid] = ['name' => $name, 'price' => $price, 'store_id' => $storeId];
    }

    // ----------------------------------------------------------------- boosts
    $boosts = [
        [1, 2, 7, $ts('-3 days'), $ts('+4 days'), 5000, 'paid'],
        [2, 13, 3, $ts('-5 days'), $ts('+2 days'), 5000, 'paid'],
        [3, 26, 6, $ts('-2 days'), $ts('+5 days'), 12000, 'paid'],
        [4, 32, 8, $ts('-1 days'), $ts('+6 days'), 12000, 'paid'],
    ];
    $stmt = $pdo->prepare('INSERT INTO boosts (store_id, product_id, starts_at, ends_at, price, source, created_at) VALUES (?,?,?,?,?,?,?)');
    foreach ($boosts as $b) {
        $stmt->execute([$b[2], $b[1], $b[3], $b[4], $b[5], $b[6], $b[3]]);
    }

    // ----------------------------------------------------------------- orders
    // buyer, store, dasar waktu, status, serah terima, metode bayar, item [[nama, varian, qty]], rating, komentar, balasan
    $orders = [
        [
            'buyer' => 1, 'store' => 2, 'base' => '-3 days 08:12', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'QRIS', 'rating' => 5,
            'comment' => 'Kopinya pas, gula arennya tidak terlalu manis. Pesanan juga cepat siap walau jam istirahat.',
            'reply' => 'Terima kasih, kak! Next order bisa minta extra shot gratis kok.',
            'items' => [['Es Kopi Susu Gula Aren', 'Less Sugar', 2], ['Croissant Butter', '', 1]],
        ],
        [
            'buyer' => 1, 'store' => 1, 'base' => '-6 days 11:42', 'status' => 'completed',
            'handover' => 'delivery', 'payment' => 'E-Wallet', 'rating' => 4,
            'comment' => 'Gepreknya enak dan porsinya banyak. Pengantaran sedikit lebih lambat dari perkiraan.',
            'reply' => 'Makasih ulasannya, kak. Kami sedang tambah satu kurir untuk jam ramai.',
            'items' => [['Ayam Geprek Sambal Bawang', 'Level 2', 2], ['Es Teh Jumbo', 'Manis', 2]],
        ],
        [
            'buyer' => 1, 'store' => 6, 'base' => '-9 days 15:20', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'Virtual Account', 'rating' => 5,
            'comment' => 'Desain posternya rapi, revisi cepat, dan file siap cetak. Sangat membantu untuk acara himpunan.',
            'reply' => 'Senang bisa bantu acaranya, kak. Sukses terus!',
            'items' => [['Jasa Desain Poster', 'A2', 1], ['Desain Feed Instagram', '6 Post', 1]],
        ],
        [
            'buyer' => 12, 'store' => 2, 'base' => '-2 days 09:05', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'QRIS', 'rating' => 5,
            'comment' => 'Cold brew-nya mantap, tidak asam berlebihan. Botolnya bisa dipakai ulang.',
            'reply' => null,
            'items' => [['Cold Brew Botol 500 ml', 'Original', 1], ['Matcha Latte', 'Iced', 1]],
        ],
        [
            'buyer' => 13, 'store' => 1, 'base' => '-4 days 12:30', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'QRIS', 'rating' => 5,
            'comment' => 'Nasi gorengnya masih hangat waktu diambil, telurnya matang sempurna.',
            'reply' => null,
            'items' => [['Nasi Goreng Kampung', 'Pedas', 1], ['Es Teh Jumbo', 'Tawar', 1]],
        ],
        [
            'buyer' => 13, 'store' => 3, 'base' => '-7 days 10:15', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'E-Wallet', 'rating' => 4,
            'comment' => 'Browniesnya fudgy dan cokelatnya terasa. Kemasannya lucu, cocok untuk hadiah.',
            'reply' => 'Terima kasih, kak! Varian keju juga baru tersedia.',
            'items' => [['Brownies Fudgy Loyang Kecil', 'Original', 1], ['Cookies Almond', '250 gram', 1]],
        ],
        [
            'buyer' => 14, 'store' => 8, 'base' => '-8 days 16:40', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'Virtual Account', 'rating' => 4,
            'comment' => 'Jaketnya tebal dan bordirnya rapi. Ukuran L agak longgar, mungkin next time ambil M.',
            'reply' => 'Betul kak, potongannya memang oversize. Kami kirim panduan ukuran di deskripsi.',
            'items' => [['Jaket Angkatan 2025', 'L', 1]],
        ],
        [
            'buyer' => 12, 'store' => 9, 'base' => '-5 days 13:22', 'status' => 'completed',
            'handover' => 'pickup', 'payment' => 'QRIS', 'rating' => 3,
            'comment' => 'Penjelasannya jelas, tapi jadwalnya sempat digeser satu jam.',
            'reply' => 'Mohon maaf atas perubahan jadwalnya, kak. Kami sudah perbaiki pengingatnya.',
            'items' => [['Les Privat Kalkulus', 'Offline', 1]],
        ],
        [
            'buyer' => 12, 'store' => 5, 'base' => '-11 days 08:30', 'status' => 'cancelled',
            'handover' => 'pickup', 'payment' => 'QRIS', 'rating' => null,
            'comment' => null, 'reply' => null, 'cancel' => 'Mesin pengering sedang diperbaiki, pesanan tidak bisa diselesaikan hari ini.',
            'items' => [['Laundry Kiloan', 'Express 6 Jam', 2]],
        ],
        [
            'buyer' => 14, 'store' => 4, 'base' => '-12 days 14:05', 'status' => 'cancelled',
            'handover' => 'pickup', 'payment' => 'E-Wallet', 'rating' => null,
            'comment' => null, 'reply' => null, 'cancel' => 'Pembeli membatalkan karena file belum siap.',
            'items' => [['Print Warna A3', 'HVS', 5]],
        ],
    ];

    $insertOrder = $pdo->prepare('INSERT INTO orders (code, payment_ref, buyer_id, store_id, status, escrow_status, handover_method, pickup_point, delivery_notes, subtotal, service_fee, shipping_fee, total, commission, payment_method, paid_at, completed_at, cancel_reason, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name_snapshot, price_snapshot, variant, note, qty) VALUES (?,?,?,?,?,?,?)');
    $insertEvent = $pdo->prepare('INSERT INTO order_events (order_id, status, title, note, actor_id, created_at) VALUES (?,?,?,?,?,?)');
    $insertReview = $pdo->prepare('INSERT INTO reviews (order_id, product_id, buyer_id, store_id, rating, comment, seller_reply, created_at) VALUES (?,?,?,?,?,?,?,?)');
    $insertNotif = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at) VALUES (?,?,?,?,?,?,?)');

    $storeOwner = [1 => 2, 2 => 3, 3 => 6, 4 => 7, 5 => 8, 6 => 9, 7 => 5, 8 => 10, 9 => 11];
    $storePickup = [1 => 'Kantin Gedung A, lantai 1', 2 => 'Kantin Gedung B, dekat lift', 3 => 'Gedung C, lobi utama', 4 => 'Gedung D, lantai 2', 5 => 'Asrama Mahasiswa, blok B', 6 => 'Gedung A, ruang 2.04', 7 => 'Gedung E, lantai 1', 8 => 'Sekretariat BEM, Gedung A lantai 1', 9 => 'Perpustakaan lantai 2'];
    $planRate = [1 => 5, 2 => 3, 3 => 5, 4 => 5, 5 => 5, 6 => 3, 7 => 5, 8 => 5, 9 => 5];

    $orderId = 0;
    foreach ($orders as $o) {
        $orderId++;
        $base = $ts($o['base']);
        $baseTs = strtotime($base);
        $storeId = $o['store'];
        $subtotal = 0;
        $items = [];
        foreach ($o['items'] as $it) {
            $pmeta = $productMeta[$productIdByName[$it[0]]];
            $line = $pmeta['price'] * $it[2];
            $subtotal += $line;
            $items[] = [$productIdByName[$it[0]], $it[0], $pmeta['price'], $it[1], $it[2]];
        }
        $serviceFee = 1000;
        $shippingFee = $o['handover'] === 'delivery' ? 3000 : 0;
        $total = $subtotal + $serviceFee + $shippingFee;
        $commission = (int) round($subtotal * $planRate[$storeId] / 100);
        $completedAt = $o['status'] === 'completed' ? date('Y-m-d H:i:s', $baseTs + 51 * 60 + ($orderId % 4) * 7 * 60) : null;
        $code = 'NM-' . date('ymd', $baseTs) . '-' . sprintf('%04d', 120 + $orderId);
        $paymentRef = 'PAY-' . date('ymd', $baseTs) . '-' . strtoupper(substr(md5((string) $orderId), 0, 6));
        $escrow = $o['status'] === 'completed' ? 'released' : ($o['status'] === 'cancelled' ? 'refunded' : 'held');

        $insertOrder->execute([
            $code, $paymentRef, $o['buyer'], $storeId, $o['status'], $escrow,
            $o['handover'], $storePickup[$storeId],
            $o['handover'] === 'delivery' ? 'Diantar ke Gedung B, lantai 3, ruang B-302.' : null,
            $subtotal, $serviceFee, $shippingFee, $total, $commission, $o['payment'],
            $base, $completedAt, $o['cancel'] ?? null, $base,
        ]);
        $oid = (int) $pdo->lastInsertId();

        foreach ($items as $it) {
            $insertItem->execute([$oid, $it[0], $it[1], $it[2], $it[3], '', $it[4]]);
        }

        $insertEvent->execute([$oid, 'placed', 'Pesanan dibuat', 'Pembayaran simulasi berhasil. Dana ditahan escrow.', $o['buyer'], $base]);

        if ($o['status'] === 'cancelled') {
            $cancelAt = date('Y-m-d H:i:s', $baseTs + 25 * 60);
            $insertEvent->execute([$oid, 'cancelled', 'Pesanan dibatalkan', $o['cancel'], $storeOwner[$storeId], $cancelAt]);
        } else {
            $acceptAt = date('Y-m-d H:i:s', $baseTs + 11 * 60);
            $readyAt = date('Y-m-d H:i:s', $baseTs + 28 * 60);
            $insertEvent->execute([$oid, 'processing', 'Pesanan diterima penjual', 'Penjual mulai menyiapkan pesanan.', $storeOwner[$storeId], $acceptAt]);
            $insertEvent->execute([$oid, 'ready', $o['handover'] === 'delivery' ? 'Pesanan sedang diantar' : 'Pesanan siap diambil', $o['handover'] === 'delivery' ? 'Mitra antar menuju lokasi pembeli.' : 'Silakan ambil di ' . $storePickup[$storeId] . '.', $storeOwner[$storeId], $readyAt]);
            $insertEvent->execute([$oid, 'completed', 'Pesanan selesai', 'Pembeli mengonfirmasi pesanan diterima. Escrow dilepas ke penjual.', $o['buyer'], $completedAt]);
        }

        if ($o['status'] === 'completed' && $o['rating'] !== null) {
            $reviewAt = date('Y-m-d H:i:s', $baseTs + 70 * 60);
            foreach ($items as $idx => $it) {
                $rating = (int) $o['rating'];
                if ($idx > 0 && $rating > 3) {
                    $rating = max(1, $rating - ($idx === 1 ? 1 : 0));
                }
                $insertReview->execute([
                    $oid, $it[0], $o['buyer'], $storeId, $rating,
                    $idx === 0 ? $o['comment'] : 'Sesuai deskripsi dan dikemas rapi.',
                    $idx === 0 ? $o['reply'] : null, $reviewAt,
                ]);
            }
        }

        // Notifikasi historis
        if ($o['status'] === 'completed') {
            $insertNotif->execute([
                $o['buyer'], 'order', 'Pesanan ' . $code . ' selesai',
                'Terima kasih sudah berbelanja di ' . ($stores[$storeId - 1][2]) . '. Escrow sudah dilepas ke penjual.',
                'orders.php?status=completed', 1, $completedAt,
            ]);
        }
        if ($o['status'] === 'cancelled') {
            $insertNotif->execute([
                $o['buyer'], 'order', 'Pesanan ' . $code . ' dibatalkan',
                $o['cancel'], 'orders.php?status=cancelled', 1, date('Y-m-d H:i:s', $baseTs + 25 * 60),
            ]);
        }
    }

    // Notifikasi unread yang relevan untuk demo
    $insertNotif->execute([2, 'review', 'Ulasan baru untuk Ayam Geprek Sambal Bawang', 'Marvelino R. memberi 4 bintang: "Gepreknya enak dan porsinya banyak."', 'seller/reviews.php', 0, $ts('-5 hours')]);
    $insertNotif->execute([3, 'review', 'Ulasan baru untuk Es Kopi Susu Gula Aren', 'Marvelino R. memberi 5 bintang: "Kopinya pas, gula arennya tidak terlalu manis."', 'seller/reviews.php', 0, $ts('-2 hours')]);
    $insertNotif->execute([1, 'review', 'Balasan ulasan dari Kopi Kampus', '"Terima kasih, kak! Next order bisa minta extra shot gratis kok."', 'product.php?id=7#ulasan', 0, $ts('-90 minutes')]);

    $pdo->commit();

    // View counter seed tidak perlu; produk sudah punya angka awal.
    if (!empty($GLOBALS['nemart_seed_verbose'])) {
        echo "Seed selesai: " . count($users) . " pengguna, " . count($products) . " produk, " . count($orders) . " riwayat pesanan." . PHP_EOL;
    }
}

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    require_once __DIR__ . '/../includes/db.php';
    $force = in_array('--force', $argv ?? [], true);
    $GLOBALS['nemart_seed_verbose'] = true;
    nemart_seed(db(), $force);
    echo "Database: " . db_path() . PHP_EOL;
}
