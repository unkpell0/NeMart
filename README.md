# NeMart — Marketplace Usaha Mahasiswa Polinema

Demo MVP untuk Lomba ICT Recursion 2.0 (Tim **Pengen Juara**). Dibangun persis mengikuti `NeMart_PRD_v2_Demo_MVP.md`: PHP 8.2 native, SQLite, HTML server-rendered, tanpa build step.

> **Tagline:** Connecting Student Businesses, One Click at a Time
> **Pembayaran disimulasikan.** Tidak ada payment gateway, tidak ada uang nyata.

---

## 1. Menjalankan aplikasi

### Cara tercepat (PHP CLI)

```bash
cd nemart
php -S 127.0.0.1:8000
```

Buka <http://127.0.0.1:8000>. Database `data/nemart.sqlite` beserta data demo dibuat otomatis pada request pertama.

Syarat: PHP 8.2+ dengan ekstensi `pdo_sqlite`, `mbstring`, `session`. Tidak perlu Composer, Node, atau MySQL.

### XAMPP / Apache

Letakkan folder `nemart/` di `htdocs`, lalu buka `http://localhost/nemart/`. Aplikasi mendeteksi base URL subfolder secara otomatis.

### Docker / Render

```bash
docker build -t nemart .
docker run -p 8000:10000 -e PORT=10000 nemart
```

`render.yaml` sudah disiapkan untuk Render (Docker, free plan). Disk gratis Render bersifat sementara, jadi database dibuat ulang dari seed setiap container start — data demo selalu bersih.

### Mereset data demo

- Login sebagai **Kemahasiswaan Polinema** (admin) → Dashboard → **Reset Data Demo**, atau
- CLI: `php db/seed.php --force`

---

## 2. Akun demo (tanpa kata sandi)

| Akun | Peran | Toko / Keterangan |
|---|---|---|
| **Marvelino R.** | Pembeli | Keranjang kosong saat awal |
| **Aditya P.** | Penjual Free | Dapur Mahasiswa (makanan) |
| **Salsa N.** | Penjual PRO | Kopi Kampus (minuman) |
| **Kemahasiswaan Polinema** | Admin | Verifikasi penjual, monitor, reset |
| **Rina T.** | Penjual (pending) | Toko baru untuk demo verifikasi admin |

Login adalah pemilih akun satu klik di `/login.php`. Setelah login, role diarahkan ke beranda, Seller Centre, atau Panel Admin.

---

## 3. Alur inti yang benar-benar tersambung

1. Pembeli mencari produk → detail → keranjang → checkout.
2. Pembayaran simulasi (QR dummy) → server membuat **satu order per toko** dalam satu transaksi database, mengurangi stok, menulis `order_events`, dan mengirim notifikasi ke pembeli + penjual.
3. Halaman **“Pesanan Telah Dipesan”** menampilkan kode pesanan.
4. Penjual melihat pesanan di Seller Centre, menekan **Terima & Proses**, lalu **Tandai Siap**. Setiap aksi menulis event dan notifikasi.
5. Pembeli melihat timeline, menekan **Konfirmasi Diterima** → status Selesai, escrow dilepas, komisi dihitung.
6. Pembeli memberi **rating & ulasan**; ulasan langsung muncul di produk dan toko, penjual menerima notifikasi dan bisa membalas sekali.
7. Seller PRO membuka **Data Tracking**: funnel, waktu proses rata-rata, timeline per pesanan, grafik omzet 7 hari, produk terlaris, performa Boost.

Semua state disimpan di SQLite — bukan di sesi atau file statis — sehingga pembeli dan penjual melihat data yang sama.

---

## 4. Fitur per peran

**Pembeli**
- Beranda: hero carousel, 8 kategori, Produk Unggulan (Boost/Featured), toko pilihan, rekomendasi.
- Pencarian & filter: kata kunci (produk + toko), kategori, rentang harga, rating minimum, 5 opsi urutan, paginasi. Produk Featured selalu teratas.
- Detail produk: varian, catatan, stepper jumlah, escrow, ulasan + balasan, produk lain dari toko.
- Keranjang: dikelompokkan per toko, checkbox berfungsi, stepper jumlah, hapus/kosongkan.
- Checkout: ambil sendiri (Rp0) vs diantar (+Rp3.000/toko), biaya layanan Rp1.000/toko, metode bayar simulasi, modal QR.
- Pesanan Saya: 6 tab status, timeline vertikal, batalkan, konfirmasi diterima, beli lagi, beri ulasan.
- Notifikasi: dropdown header + halaman penuh, badge unread, tandai dibaca.

**Penjual**
- Dashboard: kartu pesanan baru, perlu diproses, omzet selesai, rating, pesanan terbaru dengan aksi cepat.
- Pesanan: tab status, data pembeli, item, catatan, serah terima, terima/proses/siap/tolak dengan alasan.
- Produk: tabel + form tambah/edit (nama, kategori, deskripsi, harga, stok, varian, status).
- Ulasan: filter bintang, balas sekali.
- Boost: 24 jam Rp5.000 / 3 hari Rp12.000 / 7 hari Rp25.000, kuota 3 gratis untuk PRO.
- PRO: perbandingan paket, upgrade simulasi Rp25.000/30 hari.
- Data Tracking: khusus PRO, terkunci dengan upsell untuk Free (guard di server).

**Admin (3 menu)**
- Dashboard: pengguna, toko terverifikasi, total pesanan, GMV, reset demo.
- Verifikasi Penjual: setujui/tolak dengan alasan + notifikasi.
- Monitor Pesanan: read-only, filter status, escrow.

---

## 5. Aturan bisnis penting

| Aturan | Implementasi |
|---|---|
| Satu order per toko | `create_orders_from_cart()` mengelompokkan item, berbagi `payment_ref` |
| Transisi sah | `placed → processing → ready → completed`, `placed/processing → cancelled`; selain itu ditolak server |
| Biaya | Subtotal + Rp1.000/toko + ongkir Rp3.000 (jika antar) |
| Komisi | 5% Free / 3% PRO, dihitung saat `completed` |
| Stok | Berkurang saat order dibuat, dikembalikan saat batal |
| Terjual | Naik saat `completed` |
| Escrow | `held → released` (selesai) / `refunded` (batal) |
| Ulasan | Hanya order `completed`, satu per produk per order, balasan sekali |
| Boost | `boost_until > now` + toko verified → Featured; kedaluwarsa tanpa cron |
| Akses | `/seller/*` khusus seller, `/admin/*` khusus admin, cek kepemilikan di server |

---

## 6. Struktur file

```
nemart/
├── index.php · search.php · product.php · store.php
├── cart.php · cart-action.php · checkout.php · order-success.php
├── orders.php · order-action.php · notifications.php
├── login.php · logout.php
├── seller/   dashboard, orders, order-action, products, product-form,
│             reviews, boost, pro, tracking
├── admin/    dashboard, sellers, orders
├── includes/ bootstrap, db, helpers, auth, ui, repo-products, repo-orders,
│             repo-notif, head, header, footer, seller-layout(+end),
│             admin-layout(+end), error-page
├── db/       schema.sql, seed.php
├── assets/   css/{tokens,base,components,pages}.css, js/app.js
├── data/     nemart.sqlite (dibuat otomatis, di-ignore git)
├── tools/    smoke.php (uji alur end-to-end)
├── Dockerfile · render.yaml · docker-entrypoint.sh
└── README.md
```

- **Tanpa framework & tanpa build step.** CSS memakai variabel token, ikon berupa satu set SVG inline (`icon()`), grafik Data Tracking adalah SVG buatan sendiri.
- **Foto produk** memakai kotak ikon kategori yang seragam (sesuai PRD 11.5) agar tidak terlihat seperti placeholder acak.
- **Keamanan minimum:** prepared statement, escaping output via `e()`, CSRF token di semua POST, cek role & kepemilikan.

---

## 7. Pengujian

```bash
# jalankan server dulu di port 8123
php -S 127.0.0.1:8123

# di terminal lain
php tools/smoke.php
```

`tools/smoke.php` menguji 96 assertion: seluruh halaman publik, login per peran, alur keranjang → checkout multi-toko → seller memproses → pembeli konfirmasi → ulasan, pembatalan oleh pembeli, penolakan oleh penjual, penjagaan transisi status, Data Tracking PRO vs Free, Boost, verifikasi admin, guard akses, dan reset demo. Semua lulus tanpa galat PHP, dan skrip bisa dijalankan berulang kali karena diakhiri reset data.

---

## 8. Batasan demo (roadmap)

Sengketa penuh, referral, broadcast promosi, chat, pengantaran mandiri, pencairan dana nyata, verifikasi KTM nyata (hanya status toko), foto ulasan, pembayaran nyata, aplikasi native, dan multi kampus berada di luar scope demo dan disebut sebagai roadmap pada pitch deck.

---

## 9. Kredit

NeMart v2 — Tim **Pengen Juara**, Politeknik Negeri Malang. Dibangun untuk demo Lomba ICT Recursion 2.0 berdasarkan PRD v2.
