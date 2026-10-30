# PRD NeMart v2: Demo MVP (Lomba ICT Recursion 2.0)

| Item | Keterangan |
|---|---|
| **Produk** | NeMart, marketplace usaha mahasiswa Polinema |
| **Tagline** | "Connecting Student Businesses, One Click at a Time" |
| **Tim** | Pengen Juara |
| **Versi** | 2.0 (Demo MVP, menggantikan v1.0) |
| **Tujuan dokumen** | Cukup lengkap untuk membangun ulang aplikasi dari nol dan untuk dipresentasikan sebagai demo di depan juri |
| **Bahasa antarmuka** | Bahasa Indonesia |
| **Pembayaran** | **Disimulasikan.** Tidak memakai Midtrans atau payment gateway apa pun |

---

## 1. Prinsip Demo MVP

Aplikasi ini dibuat untuk **demo yang meyakinkan**, bukan produksi. Tiga prinsip yang mengatur semua keputusan scope:

1. **Satu alur inti harus mulus dan benar-benar tersambung antar peran.** Pesanan yang dibuat pembeli harus benar-benar muncul di sisi penjual, dan perubahan status dari penjual harus benar-benar terlihat oleh pembeli. Tidak boleh ada data statis yang berpura-pura.
2. **Fitur lain boleh ringan atau ditandai roadmap**, asal tidak mengganggu alur inti.
3. **Admin seminimal mungkin.** Cukup untuk membuktikan bahwa platform ada yang mengawasi.

### 1.1 Alur inti (acuan utama seluruh dokumen)

```
Login → Katalog / Cari → Detail Produk → Keranjang → Checkout
→ Bayar (simulasi) → Halaman "Pesanan Telah Dipesan"
→ Pesanan muncul di Seller Centre (notifikasi + daftar pesanan)
→ Seller ubah status (Diproses → Siap) → Pembeli melihat tracking + notifikasi
→ Pembeli konfirmasi diterima (Selesai, escrow dilepas) → Pembeli beri rating & ulasan
→ Seller PRO melihat Data Tracking (funnel, waktu proses, omzet, performa Boost)
```

### 1.2 Prioritas fitur

| Prioritas | Fitur | Catatan |
|---|---|---|
| P0 | Login simulasi per peran | Pembeli, Penjual Free, Penjual PRO, Admin |
| P0 | Katalog, kategori, pencarian, halaman toko | Prioritas pembeli #1 |
| P0 | Keranjang dan checkout | Prioritas pembeli #2 |
| P0 | Order tersimpan di database dan muncul di seller | Inti alur |
| P0 | Tracking status pesanan dan notifikasi | Prioritas pembeli #3 |
| P0 | Rating dan ulasan | Prioritas pembeli #4 |
| P0 | Tampilan Featured dari Boost | Prioritas pembeli #5 |
| P1 | Seller Centre: dashboard, pesanan, produk, Boost, PRO | Dibutuhkan agar alur inti lengkap |
| P1 | Data Tracking khusus seller PRO | Pembeda Free vs PRO |
| P1 | Admin: verifikasi penjual, monitor pesanan, reset demo | Dibatasi 3 menu |
| P2 | Pre Order | Hanya label dan countdown di seed data, tanpa logika kuota |

### 1.3 Di luar scope demo (cukup disebut di slide roadmap)

Sengketa penuh, referral, broadcast promosi, chat, pengantaran mandiri, pencairan dana nyata, verifikasi KTM nyata, upload foto ulasan, pembayaran nyata, aplikasi native, multi kampus.

---

## 2. Peran dan Akun Demo

Login adalah **pemilih akun demo** (satu klik, tanpa kata sandi), supaya juri bisa berpindah peran dengan cepat. Setiap akun terhubung ke baris nyata di tabel `users`.

| Akun | Role | Toko | Keterangan |
|---|---|---|---|
| Marvelino R. | `buyer` | - | Pembeli utama untuk demo. Keranjang **kosong** saat awal |
| Aditya P. | `seller` | Dapur Mahasiswa (Free) | Penjual terverifikasi, paket Free |
| Salsa N. | `seller` | Kopi Kampus (PRO) | Penjual terverifikasi, paket PRO |
| Kemahasiswaan Polinema | `admin` | - | Admin |
| (opsional) Rina T. | `seller` | Toko baru (status `pending`) | Untuk demo verifikasi admin |

Aturan akses: halaman `/seller/*` hanya untuk role `seller`, `/admin/*` hanya untuk `admin`. Akses yang salah diarahkan ke `login.php?error=unauthorized`. Penjual yang tokonya belum `verified` tidak tampil di katalog.

---

## 3. Tech Stack dan Arsitektur

Mengikuti stack yang sudah ada di repo, dengan satu perubahan penting: **data disimpan di database, bukan di `$_SESSION` atau file array statis**.

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Bahasa / server | PHP 8.2+ native, tanpa framework | Sudah dipakai di repo, mudah dijelaskan |
| Database | **SQLite** (PDO), file `data/nemart.sqlite` | Tanpa instalasi, dipakai bersama oleh semua sesi sehingga pembeli dan penjual melihat data yang sama |
| Sesi | `$_SESSION['user']` hanya untuk identitas login | Data bisnis tidak boleh di sesi |
| Frontend | HTML server-rendered + CSS variabel + JS vanilla secukupnya | Cepat, tanpa build step |
| Ikon | Helper `icon('nama', ukuran)` (SVG inline, satu set konsisten) | Sudah ada di repo |
| Grafik | Chart.js via CDN, atau SVG buatan sendiri | Untuk dashboard seller |
| Deploy | Docker + Render (`Dockerfile`, `render.yaml` sudah ada) | Seed otomatis saat container start |

Catatan deploy: disk Render gratis bersifat sementara, jadi database dibuat ulang dari `db/seed.php` setiap start. Ini justru menguntungkan untuk demo karena data selalu bersih. Admin punya tombol **Reset Data Demo** untuk mengembalikan ke kondisi awal sebelum presentasi.

Keamanan minimum yang tetap dijaga: semua query memakai prepared statement, semua output lewat `e()` (htmlspecialchars), form POST memakai token CSRF sederhana, dan pemeriksaan kepemilikan (seller hanya bisa mengubah pesanan tokonya sendiri, pembeli hanya melihat pesanannya sendiri).

---

## 4. Struktur File Target

```
nemart/
├── index.php              # Beranda marketplace
├── search.php             # Pencarian + kategori + filter + urutan (?q= &cat= &sort=)
├── product.php            # Detail produk (?id=)
├── store.php              # Halaman toko (?id= atau ?slug=)
├── cart.php               # Keranjang
├── cart-action.php        # POST: add, update_qty, delete, clear
├── checkout.php           # Checkout + proses pembayaran simulasi
├── order-success.php      # "Pesanan Telah Dipesan" (?code=)
├── orders.php             # Pesanan saya + tracking + aksi
├── order-action.php       # POST: confirm_received, cancel, submit_review
├── notifications.php      # Daftar notifikasi + tandai dibaca
├── login.php / logout.php
├── seller/
│   ├── dashboard.php
│   ├── orders.php         # Daftar pesanan masuk + update status
│   ├── order-action.php   # POST: accept, ready, cancel
│   ├── products.php       # Daftar produk
│   ├── product-form.php   # Tambah / edit produk
│   ├── reviews.php        # Ulasan + balas
│   ├── boost.php          # Beli Boost
│   ├── pro.php            # Paket PRO (upgrade simulasi)
│   └── tracking.php       # Data Tracking (khusus PRO, Free melihat upsell)
├── admin/
│   ├── dashboard.php      # Statistik ringkas + tombol reset demo
│   ├── sellers.php        # Verifikasi penjual
│   └── orders.php         # Monitor semua pesanan (read-only)
├── includes/
│   ├── bootstrap.php      # session_start, load db, helper, guard
│   ├── db.php             # koneksi PDO SQLite
│   ├── helpers.php        # e(), rupiah(), icon(), csrf, flash, time_ago
│   ├── auth.php           # require_login(), require_role()
│   ├── repo-products.php  # query katalog, search, featured
│   ├── repo-orders.php    # create_order(), change_status(), timeline
│   ├── repo-notif.php     # notify(), unread_count()
│   ├── head.php / header.php / footer.php
│   └── seller-layout.php / admin-layout.php
├── db/
│   ├── schema.sql
│   └── seed.php           # Membuat tabel + data contoh (idempotent)
├── assets/
│   ├── css/               # tokens.css, base.css, components.css, pages.css
│   ├── js/                # app.js (modal, countdown, stepper, tabs)
│   └── img/products/      # foto produk (fallback: ikon)
├── data/                  # nemart.sqlite (di-ignore git)
├── Dockerfile  render.yaml  .dockerignore  .gitignore
└── NeMart_PRD.md
```

---

## 5. Model Data

Semua id integer autoincrement. Waktu disimpan sebagai `TEXT` ISO 8601 (`datetime('now','localtime')`).

**users**: `id, name, email, role (buyer|seller|admin), prodi, initials, status (active|blocked), created_at`

**stores**: `id, owner_id → users, name, slug, description, department, pickup_point, icon, verified_status (pending|verified|rejected), plan (free|pro), pro_until, joined_at`
Rating toko dihitung dari `reviews` (rata-rata dan jumlah), tidak disimpan ganda.

**categories**: `id, name, slug, icon`
Isi: Makanan, Minuman, Jasa Desain, Laundry, Merch dan Jaket, Print dan Fotokopi, Les dan Jasa Akademik, Lainnya.

**products**: `id, store_id, category_id, name, description, price, stock, variants (JSON array), image, status (active|inactive), sold_count, view_count, boost_until (NULL jika tidak aktif), is_po (0/1), po_deadline, created_at`

**cart_items**: `id, user_id, product_id, variant, note, qty, created_at`
Satu baris per kombinasi (user, produk, varian). Disimpan di DB agar tahan reload dan konsisten antar halaman.

**orders**: `id, code (NM-YYMMDD-XXXX), payment_ref, buyer_id, store_id, status, escrow_status (held|released|refunded), handover_method (pickup|delivery), pickup_point, delivery_notes, subtotal, service_fee, shipping_fee, total, commission, payment_method, paid_at, completed_at, created_at`
Checkout dengan produk dari beberapa toko menghasilkan **satu order per toko**, semuanya berbagi `payment_ref`.

**order_items**: `id, order_id, product_id, name_snapshot, price_snapshot, variant, note, qty`
Nama dan harga disalin saat checkout agar riwayat tidak berubah ketika produk diedit.

**order_events** (sumber data tracking): `id, order_id, status, title, note, actor_id, created_at`
Satu baris setiap perubahan status. Timeline pembeli dan Data Tracking PRO dibaca dari tabel ini.

**notifications**: `id, user_id, type (order|review|system), title, body, link, is_read, created_at`

**reviews**: `id, order_id, product_id, buyer_id, store_id, rating (1-5), comment, seller_reply, created_at` dengan `UNIQUE(order_id, product_id)`

**boosts**: `id, store_id, product_id, starts_at, ends_at, price, source (paid|pro_free), created_at`

---

## 6. Aturan Pesanan

### 6.1 Status dan transisi

| Kode | Label pembeli | Siapa yang memicu | Escrow |
|---|---|---|---|
| `placed` | Dipesan | Sistem, setelah pembayaran simulasi | held |
| `processing` | Diproses | Seller (Terima Pesanan) | held |
| `ready` | Siap Diambil / Sedang Diantar | Seller | held |
| `completed` | Selesai | Pembeli (Konfirmasi Diterima) | released |
| `cancelled` | Dibatalkan | Seller (dengan alasan) atau pembeli selama masih `placed` | refunded |

Transisi yang sah hanya: `placed → processing → ready → completed`, serta `placed → cancelled` dan `processing → cancelled` (oleh seller). Semua transisi lain ditolak di server. Setiap transisi menulis satu baris `order_events` dan mengirim satu `notification` ke pihak lawan.

Tidak ada status "Belum Bayar" karena pembayaran disimulasikan dan langsung berhasil. Ini menghapus sumber bug pada versi sebelumnya (tombol "Bayar Sekarang" pada pesanan yang belum dibayar).

### 6.2 Biaya

| Komponen | Aturan |
|---|---|
| Subtotal | Jumlah `harga × qty` |
| Biaya layanan escrow | Rp1.000 per order (ditampilkan konsisten di keranjang, checkout, dan detail pesanan) |
| Ongkos antar | Rp0 untuk ambil di titik kampus, **Rp3.000** untuk diantar mitra. Harus ikut masuk ke total |
| Total | Subtotal + biaya layanan + ongkos antar |
| Komisi platform | 5% (Free) atau 3% (PRO) dari subtotal, dihitung saat `completed`, ditampilkan di dashboard seller sebagai potongan |

### 6.3 Aturan lain

1. Stok berkurang saat order dibuat dan dikembalikan jika `cancelled`. Checkout ditolak jika stok tidak cukup.
2. Ulasan hanya bisa dibuat untuk order `completed`, satu ulasan per produk per order.
3. Seller bisa membalas ulasan satu kali.
4. Pembeli tidak bisa membeli dari toko yang berstatus bukan `verified`.
5. Akun `blocked` tidak bisa login.

---

## 7. Spesifikasi Halaman Pembeli (urut prioritas)

### 7.1 Katalog, kategori, pencarian (`index.php`, `search.php`)

**Beranda** (tanpa landing page, gaya marketplace), urutan blok:

1. Top bar kecil dan **header sticky**: logo, kolom pencarian lebar dengan tombol cari, ikon keranjang dengan badge jumlah, ikon notifikasi dengan badge belum dibaca, menu akun.
2. Hero banner (carousel 2 sampai 3 slide statis, auto-slide 5 detik).
3. Ikon kategori (8 kategori, masing-masing menuju `search.php?cat=slug`).
4. **Produk Unggulan (Boost)**: produk dengan `boost_until > sekarang`, label "Featured". Lihat bagian 10.
5. Toko Mahasiswa Pilihan: kartu toko (nama, jurusan, rating, jumlah produk, tombol Kunjungi).
6. Rekomendasi untuk Kamu: grid produk (5 kolom desktop, 3 tablet, 2 mobile) dengan tombol "Lihat Lebih Banyak" (paginasi sederhana, tanpa infinite scroll).
7. Footer.

**Pencarian dan kategori** (`search.php`):
- Parameter: `q` (kata kunci pada nama dan deskripsi produk serta nama toko), `cat`, `min`, `max`, `rating`, `sort` (`terlaris`, `terbaru`, `termurah`, `termahal`, `rating`), `page`.
- Produk Boost aktif **selalu di urutan teratas** pada hasil yang cocok, diberi label "Featured", lalu sisanya mengikuti urutan pilihan.
- Keadaan kosong: pesan "Tidak ada hasil untuk '...'" plus tautan kategori populer.
- Ringkasan: "Hasil untuk 'kata' (N produk)".

**Kartu produk**: foto 1:1 (fallback ikon), nama maksimal 2 baris, harga (tebal, warna primary), rating dan jumlah terjual, nama toko, label status (Open hijau, PO kuning, Habis abu-abu), badge "Escrow Aman", label "Featured" bila Boost aktif.

### 7.2 Detail produk (`product.php`)

Galeri atau gambar utama, nama, rating dan jumlah ulasan, terjual, harga, pilihan varian, catatan, pemilih jumlah (maks sebesar stok). Tombol **Tambah ke Keranjang** dan **Beli Sekarang** (tambah lalu langsung ke checkout). Kotak jaminan escrow. Kartu penjual dengan tautan ke toko. Tab: Deskripsi, Ulasan (rata-rata, daftar ulasan, balasan seller), Produk Lain dari Toko. Setiap kunjungan menaikkan `view_count`.

Jika belum login, tombol keranjang mengarahkan ke login lalu kembali ke produk.

### 7.3 Halaman toko (`store.php`)

Banner dan logo, nama usaha, jurusan, **rating toko** (rata-rata semua ulasan), jumlah produk, bergabung sejak, lencana Terverifikasi dan PRO. Tab: Semua Produk, Ulasan, Tentang. Produk Boost toko tampil lebih dulu.

### 7.4 Keranjang (`cart.php`)

Item dikelompokkan per toko. Ubah jumlah (minimal 1, ada tombol hapus terpisah), hapus, kosongkan. Ringkasan sticky: jumlah item, subtotal, tombol Checkout. Jika kosong: ilustrasi dan tombol "Mulai Belanja". Keranjang **kosong untuk akun baru**. Biaya layanan dan ongkos antar tidak ditampilkan sebagai "Gratis"; tulis "dihitung di checkout".

Kotak centang per item **harus berfungsi** (hanya item tercentang yang dibawa ke checkout) atau dihapus. Jangan tampilkan kontrol yang tidak bekerja.

### 7.5 Checkout (`checkout.php`)

Satu halaman, tiga blok kiri dan ringkasan sticky kanan:

1. **Serah terima**: ambil di titik kampus (gratis) atau diantar mitra (+Rp3.000), pilihan titik kampus, catatan lokasi. Pilihan ini mengubah rincian biaya secara langsung.
2. **Ringkasan pesanan per toko**, dengan tombol ubah jumlah (kembali ke keranjang) agar pembeli tidak terjebak.
3. **Metode pembayaran (simulasi)**: QRIS, Virtual Account, E-Wallet sebagai pilihan tampilan. Label jelas: "Mode demo: pembayaran disimulasikan".

Tombol **Bayar Sekarang** membuka layar konfirmasi simulasi (QR dummy dan tombol "Saya Sudah Bayar"). Setelah dikonfirmasi, server dalam **satu transaksi database**:
1. memvalidasi stok dan toko aktif,
2. membuat satu `orders` per toko beserta `order_items`,
3. mengurangi stok dan menambah `sold_count` saat `completed` (atau saat order, pilih satu dan konsisten),
4. menulis `order_events` pertama (`placed`),
5. membuat `notifications` untuk pembeli (pesanan dibuat) dan untuk pemilik toko (pesanan baru),
6. mengosongkan item yang dibeli dari `cart_items`,
7. redirect ke `order-success.php?code=...`.

Jika keranjang kosong, `checkout.php` mengarahkan ke `cart.php`.

### 7.6 Pesanan telah dipesan (`order-success.php`)

Halaman konfirmasi: ikon sukses, teks **"Pesanan Telah Dipesan"**, kode pesanan, ringkasan item dan total, catatan "Dana ditahan escrow hingga kamu konfirmasi pesanan diterima", serta dua tombol: **Lacak Pesanan** (ke `orders.php`) dan **Lanjut Belanja**. Jika pembeli membuat beberapa order (beberapa toko), semuanya ditampilkan.

### 7.7 Pesanan saya dan tracking (`orders.php`)

Tab: Semua, Dipesan, Diproses, Siap, Selesai, Dibatalkan. Kartu pesanan: toko, kode, tanggal, item, total, label status, **timeline vertikal dari `order_events`** (judul, waktu, catatan). Aksi menurut status:

| Status | Aksi pembeli |
|---|---|
| `placed` | Batalkan Pesanan |
| `processing` | (tidak ada) |
| `ready` | **Konfirmasi Diterima** |
| `completed` | **Beri Ulasan** (jika belum), Beli Lagi |
| `cancelled` | Beli Lagi |

Data dibaca dari database, difilter `buyer_id = user login`.

### 7.8 Notifikasi (`notifications.php` dan dropdown di header)

Dibuat otomatis oleh sistem pada: pesanan dibuat, status berubah, pesanan selesai, ulasan baru (untuk seller), balasan ulasan (untuk pembeli). Badge angka di header, daftar dengan penanda belum dibaca, klik membuka halaman terkait dan menandai dibaca.

### 7.9 Rating dan ulasan

Modal "Beri Ulasan" per produk pada order `completed`: bintang 1 sampai 5 (wajib) dan komentar. Upload foto **tidak** diwajibkan pada demo (boleh dihilangkan). Setelah dikirim, rating produk dan rating toko langsung ter-update, ulasan muncul di detail produk dan halaman toko, serta seller menerima notifikasi.

---

## 8. Seller Centre

Layout: sidebar kiri tetap (Dashboard, Pesanan, Produk, Ulasan, Promosi Boost, Langganan PRO, Data Tracking), header berisi nama toko, notifikasi, dan tombol "Lihat Toko".

### 8.1 Dashboard
Kartu ringkasan: Pesanan Baru (status `placed`), Perlu Diproses, Omzet (order `completed`), Rating Toko. Daftar 5 pesanan terbaru dengan tombol cepat. Banner paket (Free atau PRO).

### 8.2 Pesanan
Tab status seperti pembeli. Setiap pesanan menampilkan pembeli, item, catatan, metode serah terima dan lokasi. Tombol sesuai status: `placed` → **Terima & Proses** atau **Tolak** (alasan); `processing` → **Tandai Siap**; `ready` → menunggu pembeli. Setiap klik menulis `order_events` dan notifikasi ke pembeli. Pesanan yang dibuat pembeli **harus muncul di sini tanpa reload data manual** (cukup muat ulang halaman).

### 8.3 Produk
Tabel produk (foto, nama, harga, stok, status, aksi edit, nonaktifkan). Formulir tambah dan edit: nama, kategori, deskripsi, harga, stok, varian (teks dipisah koma), gambar (opsional, fallback ikon).

### 8.4 Ulasan
Daftar ulasan, filter bintang, tombol balas (sekali).

### 8.5 Promosi Boost
Pilih produk dan durasi (24 jam Rp5.000, 3 hari Rp12.000, 7 hari Rp25.000). Pembayaran disimulasikan. Hasil: `products.boost_until` diperbarui dan produk tampil Featured. Penjual PRO punya kuota 3 Boost gratis per bulan (`source = pro_free`).

### 8.6 Langganan PRO
Tabel perbandingan, tombol **Upgrade (simulasi) Rp25.000 per bulan**, hasilnya `plan = pro` dan `pro_until = +30 hari`.

| Fitur | Free | PRO |
|---|---|---|
| Komisi | 5% | 3% |
| Boost gratis | Tidak ada | 3 per bulan |
| **Data Tracking** | Terkunci (tampilan upsell) | **Terbuka** |
| Lencana PRO | Tidak ada | Ada |

### 8.7 Data Tracking (khusus PRO, `seller/tracking.php`)

> **Asumsi yang perlu dikonfirmasi tim:** "seller PRO bisa melihat tracking data" diartikan sebagai panel analitik pelacakan pesanan. Semua seller melihat pesanan masuk dan mengubah status. PRO mendapat tambahan tampilan data tracking di bawah.

Seller Free yang membuka halaman ini melihat kartu "Fitur khusus PRO" dengan pratinjau buram dan tombol upgrade. Seller PRO melihat:

1. **Funnel pesanan**: jumlah pesanan di tiap tahap (Dipesan, Diproses, Siap, Selesai, Dibatalkan).
2. **Waktu proses rata-rata** antar tahap, dihitung dari selisih waktu `order_events`.
3. **Timeline per pesanan**: pilih pesanan, lihat seluruh riwayat status dengan waktunya.
4. **Grafik omzet** 7 hari terakhir.
5. **Produk terlaris** (5 teratas).
6. **Performa Boost**: kunjungan produk sejak Boost aktif (`view_count`) dan jumlah pesanan.

Penjaga akses dilakukan di server (`plan = pro` dan `pro_until` belum lewat), bukan hanya disembunyikan di tampilan.

---

## 9. Admin (sengaja minimal, 3 menu)

| Menu | Fungsi |
|---|---|
| **Dashboard** | Kartu: jumlah pengguna, jumlah toko terverifikasi, total pesanan, GMV. Tombol **Reset Data Demo** (konfirmasi dulu, menjalankan ulang seed) |
| **Verifikasi Penjual** | Daftar toko `pending` dengan data singkat. Tombol Setujui atau Tolak (alasan). Toko yang disetujui langsung tampil di katalog dan penjual diberi notifikasi |
| **Monitor Pesanan** | Tabel semua pesanan (kode, pembeli, toko, total, status, escrow), read-only, dengan filter status |

Tidak ada manajemen sengketa, kampanye, atau moderasi konten pada demo. Blokir pengguna dihapus dari scope.

---

## 10. Boost dan Tampilan Featured

- Produk Featured = `boost_until` lebih besar dari waktu sekarang dan toko `verified`.
- Tampil di: seksi "Produk Unggulan" beranda (maksimal 6, acak atau urut waktu mulai), posisi teratas hasil pencarian dan kategori, serta label "Featured" pada kartu produk.
- Boost kedaluwarsa otomatis karena pengecekan berbasis waktu pada query, tanpa cron.
- Pembeli tidak melihat kata "Iklan"; gunakan label "Featured" yang konsisten.

---

## 11. Design System

Gaya: bersih, padat informasi seperti marketplace, ramah mahasiswa, tidak generik. Nama token CSS mengikuti repo (`--blue-700`, `--blue-500`, `--blue-100`, `--cyan-100`, `--surface`, `--surface-2`, `--border`, `--text`, `--text-muted`, `--r-sm`, `--r-md`, `--r-lg`, `--r-pill`, `--sh-pop`, `--font-display`, `--fs-*`, serta pasangan `--success|warning|danger|info` dengan sufiks `-bg`, `-fg`, `-solid`). Nilai heks di bawah mengikuti palet PRD v1 dan harus dicocokkan dengan `assets/css/tokens.css`.

### 11.1 Warna

| Peran | Hex | Dipakai untuk |
|---|---|---|
| Primary (Royal Blue) | `#0B2FC9` | Header, tombol utama, harga |
| Secondary (Periwinkle) | `#5B8DEF` | Link, ikon, elemen aktif |
| Accent (Sky Cyan) | `#A8E6F7` | Latar ikon, badge, highlight |
| Deep (Indigo) | `#3F3BA8` | Hover, penekanan, grafik |
| Navy | `#2F4A7A` | Footer, sidebar seller dan admin |
| Highlight (Sale Yellow) | `#FFD23F` | Tombol CTA kedua, label Featured/Boost |
| Highlight 2 (Orange) | `#F5A623` | Bintang rating, notifikasi |
| Success (Mint) | `#3DBE8B` | Selesai, escrow aman |
| Danger | `#D64545` | Dibatalkan, error |
| Background | `#EEF3FC` | Latar halaman |
| Surface | `#FFFFFF` | Kartu, form |
| Text / Muted | `#1A1A2E` / `#64748B` | Teks utama / pendukung |

Proporsi: sekitar 60% latar terang, 30% biru, 10% aksen.

### 11.2 Tipografi, bentuk, jarak

- Font: Poppins untuk judul (`--font-display`), Inter untuk isi. Skala: judul halaman 24 sampai 28px, judul seksi 18 sampai 20px, isi 14px, keterangan 12px.
- Angka harga memakai `font-variant-numeric: tabular-nums`.
- Radius 12px untuk kartu, 8px untuk input dan tombol kecil, pill untuk badge.
- Jarak memakai skala kelipatan 4px (4, 8, 12, 16, 24, 32, 48).
- Bayangan lembut satu tingkat untuk kartu, satu tingkat lebih kuat untuk modal.

### 11.3 Komponen wajib

Tombol (primary, secondary, outline, kuning), kartu produk, kartu toko, badge status pesanan (warna sesuai peta status), badge Featured, input dan select, tab, tabel, modal, toast/flash, stepper jumlah, timeline vertikal, kartu statistik, kartu terkunci PRO, empty state, breadcrumb.

### 11.4 Peta warna status pesanan

| Status | Warna |
|---|---|
| Dipesan | Kuning (warning) |
| Diproses | Biru (info) |
| Siap | Cyan atau biru tua |
| Selesai | Hijau (success) |
| Dibatalkan | Abu-abu atau merah (neutral/danger) |

### 11.5 Aturan agar tidak terlihat generik

1. Satu set ikon SVG yang konsisten, tidak memakai emoji sebagai ikon.
2. Foto atau ilustrasi produk yang nyata dan seragam rasio. Jika memakai ikon sebagai pengganti, bungkus dalam kotak berwarna seragam dan jangan bercampur dengan foto.
3. Teks asli berbahasa Indonesia untuk semua label, pesan kosong, dan pesan galat. Tidak ada "Lorem ipsum", tidak ada teks Inggris yang tersisa.
4. Gradien dipakai hanya pada hero, bukan pada kartu atau tombol.
5. Setiap halaman punya keadaan kosong, keadaan galat, dan umpan balik sukses (flash message) yang konsisten.
6. Tidak ada tombol atau kontrol yang tidak berfungsi. Jika fitur tidak ada di demo, hapus kontrolnya.
7. Responsif dari 360px sampai desktop, area sentuh minimal 44px.

---

## 12. Data Seed (demo yang meyakinkan)

Seed harus idempotent dan dijalankan dari `db/seed.php`.

- **Pengguna:** sesuai tabel pada bagian 2.
- **Toko:** minimal 6 toko terverifikasi (termasuk Dapur Mahasiswa Free dan Kopi Kampus PRO), 1 toko `pending`. Toko memiliki jurusan dan titik ambil yang realistis di Polinema.
- **Produk:** minimal 24 produk tersebar di semua kategori, harga realistis (misalnya Es Kopi Susu Rp12.000, Ayam Geprek Rp15.000, Jasa Desain Poster Rp35.000, Laundry Kiloan Rp7.000 per kg, Jaket Angkatan Rp65.000 berlabel PO). Setiap produk punya stok, varian, dan deskripsi singkat.
- **Boost:** 3 sampai 4 produk Featured aktif dengan `boost_until` berjarak beberapa hari ke depan.
- **Riwayat:** 5 sampai 8 order `completed` dengan ulasan beragam agar rating, jumlah terjual, dan Data Tracking PRO tidak kosong. Beberapa `order_events` dengan jeda waktu realistis agar waktu proses rata-rata bermakna.
- **Kondisi awal akun demo:** keranjang Marvelino kosong, tidak ada order `placed` yang menggantung.

---

## 13. Skenario Demo dan Kriteria Penerimaan

Skrip demo sekitar 3 menit. Setiap langkah adalah kriteria penerimaan yang harus lulus.

| # | Skenario | Hasil yang diharapkan |
|---|---|---|
| 1 | Login sebagai Marvelino (pembeli) | Masuk ke beranda, keranjang menunjukkan 0 |
| 2 | Cari "kopi", buka kategori Minuman | Hasil relevan, produk Featured di urutan teratas dengan label |
| 3 | Buka produk, pilih varian dan jumlah, tambah ke keranjang | Badge keranjang naik, item muncul di keranjang |
| 4 | Buka keranjang, ubah jumlah, lanjut checkout | Jumlah berubah, total benar |
| 5 | Pilih "Diantar" | Ongkos Rp3.000 masuk ke total |
| 6 | Bayar (simulasi), konfirmasi | Halaman **"Pesanan Telah Dipesan"** dengan kode pesanan. Keranjang kosong |
| 7 | Logout, login sebagai Aditya (seller Free) | Notifikasi pesanan baru, pesanan tampil di daftar dan dashboard |
| 8 | Klik Terima & Proses, lalu Tandai Siap | Status berubah, pembeli menerima notifikasi |
| 9 | Buka Data Tracking sebagai seller Free | Halaman terkunci dengan tombol upgrade |
| 10 | Pembeli membuka Pesanan Saya | Timeline menampilkan semua perubahan status dengan waktu |
| 11 | Pembeli klik Konfirmasi Diterima, lalu Beri Ulasan | Status Selesai, escrow dilepas, ulasan muncul di produk dan toko, seller diberi notifikasi |
| 12 | Login sebagai Salsa (seller PRO), pesan dari pembeli ke tokonya lewat langkah 1 sampai 8 | Data Tracking menampilkan funnel, waktu proses, timeline, omzet, performa Boost, dengan data pesanan yang baru dibuat |
| 13 | Seller membeli Boost untuk sebuah produk | Produk muncul di Produk Unggulan beranda dan teratas pencarian |
| 14 | Login sebagai admin, setujui toko `pending` | Toko tampil di katalog |
| 15 | Admin menekan Reset Data Demo | Data kembali ke kondisi seed |

Kriteria teknis tambahan: tidak ada galat PHP pada log, semua halaman tidak menampilkan teks Inggris atau placeholder, semua halaman terbuka tanpa galat di layar 360px.

---

## 14. Kebutuhan Non-Fungsional (versi demo)

- **Performa:** halaman utama termuat di bawah 2 detik pada jaringan lokal atau Render.
- **Keandalan demo:** seed otomatis saat start, tombol reset, tanpa ketergantungan layanan eksternal (kecuali CDN font/grafik, sediakan fallback).
- **Keamanan dasar:** prepared statement, escaping output, CSRF token, pemeriksaan role dan kepemilikan di server.
- **Aksesibilitas:** kontras WCAG AA, label pada kontrol form, fokus terlihat.

---

## 15. Pemetaan ke Pitch Deck dan BMC

> Pitch deck dan BMC belum dilampirkan saat dokumen ini disusun. Pemetaan di bawah memakai isi PRD v1 yang merujuk keduanya (survei, model pendapatan, strategi GROW, SDGs). Cocokkan ulang dengan slide dan kanvas final sebelum lomba.

| Elemen deck / BMC | Wujud di demo |
|---|---|
| Masalah: informasi tersebar, penjual sulit dinilai | Katalog terpusat, rating dan ulasan, halaman toko |
| Masalah: risiko transaksi | Escrow yang dilepas saat pembeli konfirmasi (simulasi) |
| Masalah: pencatatan pesanan berantakan | Order tersimpan, tracking status, Seller Centre |
| Value proposition: Discovery, Transaction, Retention | Katalog dan pencarian, checkout dan tracking, rating |
| Pendapatan: komisi 5%/3% | Dihitung dan ditampilkan di dashboard seller |
| Pendapatan: langganan PRO Rp25.000 | Halaman PRO dan Data Tracking terkunci |
| Pendapatan: Paid Boost mulai Rp5.000 | Halaman Boost dan tampilan Featured |
| Customer segments: pembeli, penjual, admin | Tiga peran pada login |
| Roadmap: sengketa, referral, broadcast, kampus lain | Slide roadmap saja, tidak dibangun |
| SDGs 8, 9, 12 | Slide dampak, tidak ada fitur khusus |

---

## 16. Asumsi dan Pertanyaan Terbuka

1. **Arti "seller PRO melihat tracking data":** diasumsikan sebagai panel Data Tracking (bagian 8.7). Konfirmasi dengan tim apakah maksudnya hanya timeline status pesanan, atau analitik lengkap seperti di atas.
2. `sold_count` dinaikkan saat order `completed` (lebih jujur). Ubah ke saat order dibuat bila ingin angka bergerak langsung saat demo.
3. Pre Order hanya label di seed data. Jika juri menanyakan, jawab bahwa PO ada di fase 2.
4. Foto produk: pakai foto sendiri atau berlisensi bebas. Jika tidak ada waktu, gunakan ikon seragam dan jangan campur dengan foto.
5. Verifikasi mahasiswa dilakukan manual oleh admin pada versi nyata. Pada demo hanya status toko `pending` ke `verified`.

---

## 17. Urutan Pembangunan (rebuild dari nol)

| Tahap | Isi | Selesai bila |
|---|---|---|
| 1 | `schema.sql`, `seed.php`, `db.php`, `bootstrap.php`, helper, token CSS dan komponen dasar | Seed berjalan, halaman kosong bergaya benar |
| 2 | Login simulasi, guard role, header dengan badge keranjang dan notifikasi | Skenario 1 |
| 3 | Beranda, search, kategori, produk, toko | Skenario 2, 3 |
| 4 | Keranjang, checkout, `create_order()`, halaman Pesanan Telah Dipesan | Skenario 4 sampai 6 |
| 5 | Seller: dashboard, pesanan, perubahan status, notifikasi | Skenario 7, 8 |
| 6 | Pesanan saya dengan timeline, konfirmasi, ulasan | Skenario 10, 11 |
| 7 | Boost dan tampilan Featured | Skenario 13 |
| 8 | PRO, Data Tracking | Skenario 9, 12 |
| 9 | Admin (3 menu) dan reset demo | Skenario 14, 15 |
| 10 | Pemolesan: responsif, empty state, teks, uji skenario penuh dua kali berturut-turut | Semua skenario lulus |

---

## Lampiran A. Hasil Audit Kode vs PRD v1 (repo `unkpell0/ne-mart`)

Audit dilakukan pada file yang dapat dibaca: `NeMart_PRD.md`, `checkout.php`, `cart.php`, `orders.php`, `login.php`. Isi folder `admin/`, `seller/`, `includes/`, `data/`, `assets/` tidak dapat dibuka (GitHub memblokir pembacaan direktori), sehingga tidak diaudit.

| Temuan | Bukti | Dampak | Perbaikan di v2 |
|---|---|---|---|
| Checkout tidak membuat pesanan | `checkout.php` hanya mengosongkan `$_SESSION['cart']` lalu redirect | Alur inti putus, seller tidak menerima apa pun | `create_order()` transaksional (7.5) |
| Pesanan berasal dari data statis | `orders.php` memuat `data/orders.php` | Pesanan baru pembeli tidak pernah muncul | Tabel `orders` di SQLite (5) |
| Tombol aksi hanya pesan palsu | Ulasan, sengketa, konfirmasi hanya menyetel `$feedback_msg` | Tidak ada efek nyata | Aksi menulis ke DB dan `order_events` |
| Pesanan "Belum Bayar" menautkan ke checkout | `orders.php` → `checkout.php`, yang redirect ke keranjang jika kosong | Tombol tidak berfungsi | Status "Belum Bayar" dihapus (6.1) |
| Ongkos antar tidak terhitung | Radio "delivery" tidak dibaca, `shipping_fee = 0` tetap | Total salah saat memilih antar | Ongkos dinamis (6.2, 7.5) |
| Biaya layanan tidak konsisten | Keranjang: "GRATIS"; checkout: Rp1.000 | Total berubah tanpa penjelasan | Rp1.000 konsisten, "dihitung di checkout" |
| Kotak centang keranjang tidak berfungsi | Checkbox tanpa logika | Kontrol menipu | Berfungsi atau dihapus (7.4) |
| Countdown dibuat dari tanggal tetap | `data-countdown="2026-10-28 23:59:59"` | Waktu bayar selalu salah | Countdown dihapus (bayar simulasi) |
| Data demo tersimpan di sesi | `login.php` memakai `$_SESSION['demo']['users']` | Tidak dibagi antar peran dan antar perangkat | Database SQLite (3) |
| Login hard-coded 3 akun | `login.php` | Tidak ada seller PRO vs Free | 4 sampai 5 akun demo (2) |
| PRD v1 memuat pembayaran penyedia nyata | Bagian 7.7, 13, 21 | Bertentangan dengan keputusan tanpa gateway | Pembayaran simulasi |

### Kelengkapan PRD v1 untuk rebuild dari nol

PRD v1 kuat pada spesifikasi halaman, aturan bisnis, dan palet warna, tetapi **belum cukup** untuk rebuild karena tidak memuat: tech stack, struktur file, model data, mesin status pesanan, daftar rute dan aksi, kriteria penerimaan, data seed, atau skrip demo. Scope-nya juga lebih luas dari yang bisa didemokan (sengketa, referral, broadcast, PO penuh), dan pertanyaan terbuka tentang penyedia pembayaran belum terjawab. Dokumen ini menutup celah tersebut.
