# Storyboard NeMart

**Versi 1.0 · Prototype Klik · ITC Business Plan 2026**

Dokumen ini adalah blueprint visual untuk 14 layar NeMart. Dipakai untuk rebuild di Figma frame-by-frame, atau sebagai acuan dev. Setiap layar punya wireframe ASCII, spec layout, elemen UI, state, interaksi, data dummy, dan mode (Klik/Logika) sesuai PRD §6.

**Device:** iPhone 14 — 390 × 844 px
**Grid:** 8pt · 4 kolom · gutter 16 · margin 20
**Mode:** Klik = hanya tampilan · Logika = data dummy dengan perilaku sungguhan

---

## 1. Design Tokens

### Warna

| Token | Hex | Pemakaian |
| --- | --- | --- |
| `primary` | `#0E7C66` | Tombol utama, brand, angka harga |
| `primary-dark` | `#0A5A4A` | Hover/pressed state |
| `accent` | `#F5A623` | Boost, rating bintang, badge Featured |
| `success` | `#1FA971` | Status Open, dana cair, verifikasi disetujui |
| `warning` | `#E8A33D` | Menunggu verifikasi, dana tertahan |
| `danger` | `#E5484D` | Laporan, tolak, status sengketa |
| `neutral-900` | `#111827` | Judul |
| `neutral-700` | `#374151` | Body teks |
| `neutral-500` | `#6B7280` | Teks sekunder |
| `neutral-300` | `#D1D5DB` | Border, divider |
| `neutral-100` | `#F3F4F6` | Background isian, chip |
| `surface` | `#FFFFFF` | Card, sheet |

### Tipografi — Plus Jakarta Sans

| Style | Ukuran / Weight | Line-height |
| --- | --- | --- |
| Display | 28 / 700 | 34 |
| H1 | 22 / 700 | 28 |
| H2 | 18 / 600 | 24 |
| H3 | 16 / 700 | 22 |
| Body | 14 / 400 | 20 |
| Body-strong | 14 / 600 | 20 |
| Caption | 12 / 400 | 16 |
| Button | 15 / 600 | 20 |
| Price | 20 / 800 | 24 |

### Radius & Spacing

| Token | Nilai |
| --- | --- |
| radius-card | 16 |
| radius-button/input | 12 |
| radius-chip | 999 |
| radius-sheet | 24 (atas saja) |
| space unit | 4 / 8 / 12 / 16 / 20 / 24 / 32 |
| padding layar | 20 horizontal · 16 vertikal |
| jarak antar-card | 12 |
| tinggi bottom nav | 74 (+ safe area) |

---

## 2. Library Komponen

| # | Komponen | Varian |
| --- | --- | --- |
| 1 | Button | primary · secondary · outline · danger · amber · sm/lg · disabled |
| 2 | PO Card | open · closed · featured · kuota penuh |
| 3 | Badge Status | Open · Closed · Dibayar · Diproses · Siap/Dikirim · Selesai · Sengketa · Menunggu |
| 4 | Input | text · password · number · textarea · select · focus · error |
| 5 | Search Bar | placeholder · terisi |
| 6 | Category Pill | default · active |
| 7 | Filter Chip | default · active |
| 8 | Rating Stars | read-only · interaktif · 1–5 |
| 9 | Bottom Nav | buyer (5 item + FAB) · seller |
| 10 | Top Bar | back · judul · +ikon kanan |
| 11 | Timeline Status | dot done · current · upcoming |
| 12 | Saldo Card | tertahan · cair · net |
| 13 | Empty State | ikon · judul · deskripsi · CTA |

---

## 3. Peta Alur

### Pembeli
```
Login ──► Beranda ──► Cari/Filter ──► Detail PO ──► Checkout ──► QRIS
                                                                   │
              Rating ◄── Konfirmasi ◄── Lacak Status ◄─────────────┘
                 │            │
                 │            └──► [Laporkan Masalah] ──► Sengketa (Admin)
                 └──► tampil di Profil Penjual
```

### Penjual
```
Daftar + Unggah KTM ──► Menunggu Verifikasi ──► (Admin setujui) ──► Dasbor
                                                                      │
     Buat PO ──► PO Open ──► Pesanan Masuk ──► Ubah Status ──► Saldo Cair
        │                                                         ▲
        └──► Boost PO (Rp5rb/24j · Rp10rb/3hr) ──► Slot Featured   │
                              Paket Free vs Pro ─────────────────┘
```

### Admin
```
Verifikasi Penjual ──► [Setujui] ──► Penjual aktif
                   └─► [Tolak] ──► Notifikasi penjual
Laporan Masalah ──► [Refund ke Pembeli]  atau  [Cair ke Penjual]
```

---

## 4. 14 Storyboard Layar

---

### FRAME 01 — Login & Daftar
**ID PRD:** PB-01 · **Mode:** Klik

**Tujuan:** Masuk dengan akun kampus untuk mengakses beranda.

```
┌──────────────────────────────────┐
│  9:41                        ▮▮  │ status bar
├──────────────────────────────────┤
│                                  │
│           ┌────┐                 │
│           │ N  │                 │  logo mark 52×52
│           └────┘                 │
│        NeMart                    │  Display 28/700
│  Usaha mahasiswa, satu tempat,   │  caption
│  transaksi aman                  │
│                                  │
│  Email Kampus                    │  label 13/600
│  ┌────────────────────────────┐  │
│  │ rizki@student.polinema...  │  │  input
│  └────────────────────────────┘  │
│  Kata Sandi                      │
│  ┌────────────────────────────┐  │
│  │ ••••••••          👁        │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │         Masuk              │  │  button primary
│  └────────────────────────────┘  │
│   Belum punya akun? Daftar       │
│  ─────────────────────────────   │
│   Atau masuk cepat dengan        │
│  ┌────────────────────────────┐  │
│  │  SSO Polinema              │  │  button outline
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🔒 Dana ditahan (escrow)   │  │  info card biru
│  │ Pembayaran kamu aman...    │  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Elemen:** logo mark, judul + tagline, input email, input sandi (+toggle mata), tombol **Masuk**, link Daftar, tombol SSO, info card escrow.
**State:** validasi email kampus (harus `@student.polinema.ac.id`), error inline merah.
**Interaksi:** tap **Masuk** → Beranda. Tap **Daftar** → Frame 01b.
**Catatan:** HP-first, tombol min 48px. Jangan tutupi escrow messaging — ini poin jual utama.
**Data dummy:** `rizki@student.polinema.ac.id` / `demo1234`

---

### FRAME 01b — Daftar Akun
**ID PRD:** PB-01 · **Mode:** Klik

```
┌──────────────────────────────────┐
│  ‹   Daftar Akun                 │
├──────────────────────────────────┤
│  Nama Lengkap                    │
│  ┌────────────────────────────┐  │
│  └────────────────────────────┘  │
│  Email Kampus                    │
│  ┌────────────────────────────┐  │
│  └────────────────────────────┘  │
│  Kata Sandi (min 8)              │
│  ┌────────────────────────────┐  │
│  └────────────────────────────┘  │
│  Konfirmasi Sandi                │
│  ┌────────────────────────────┐  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │          Daftar            │  │
│  └────────────────────────────┘  │
│   Sudah punya akun? Masuk        │
└──────────────────────────────────┘
```

**Interaksi:** submit → toast "Akun dibuat" → Login.

---

### FRAME 02 — Beranda (PO Aktif + Featured)
**ID PRD:** PB-02, PB-03 · **Mode:** Logika

**Tujuan:** Titik masuk utama — lihat semua PO aktif dengan status jelas.

```
┌──────────────────────────────────┐
│  [N] NeMart            🔔   👤   │  top bar logo
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ 🔍 Cari PO, makanan...     │  │  search bar → Frame 03
│  └────────────────────────────┘  │
│  ┌───┐┌────┐┌────┐┌────┐┌────┐  │  category pills
│  │All││🍜  ││✈️  ││🧺  ││🖨️  │  │  horizontal scroll
│  └───┘└────┘└────┘└────┘└────┘  │
│                                  │
│  ⭐ PO Pilihan (Featured)        │  sec header
│  ← ┌──────────┐ ┌──────────┐    │  horizontal scroll
│    │  🍜      │ │  ✈️      │    │  card 200px
│    │ ● Open   │ │ ● Open   │    │
│    │ Mie Gaco │ │ Jastip   │    │
│    │ Rp18.000 │ │ Rp25.000 │    │
│    └──────────┘ └──────────┘    │
│                                  │
│  PO Aktif            Lihat →     │
│  ┌────────────────────────────┐  │
│  │ 🧺  ● Open        ⭐Featured│  │  PO card
│  │ Laundry Kiloan Hemat       │  │
│  │ Budi Santoso               │  │
│  │ Rp7.000                    │  │
│  │ 📦 28/50      ★4.7 (35)    │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🍜  Closed                 │  │  closed tampak beda
│  │ PO Seblak Ceker Level 5    │  │  (opacity turun)
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🌱 Dampak NeMart           │  │  info card amber
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  🏠      🔍    [+]     📦    👤  │  bottom nav
│  Home   Cari   Jual  Pesanan Profil
└──────────────────────────────────┘
```

**Elemen:** logo, ikon notif, ikon profil, search bar, category pills, rail Featured (rotasi 3–5), daftar PO aktif, info card dampak, bottom nav.
**State:** PO Closed tampak redup + label "Closed" + tidak bisa dipesan. Featured rotasi.
**Interaksi:** tap search → Frame 03 · tap pill → filter beranda · tap card → Frame 04.
**Data dummy:** 7 PO, 2 Featured, 1 Closed.

---

### FRAME 03 — Cari & Filter
**ID PRD:** PB-04 · **Mode:** Logika

**Tujuan:** Temukan PO dengan cepat lewat kata kunci + filter.

```
┌──────────────────────────────────┐
│  ‹   Cari & Filter               │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ 🔍 cari PO...         ✕    │  │  input aktif
│  └────────────────────────────┘  │
│  ┌───┐┌────┐┌────┐┌────┐┌────┐  │  pills (cat aktif)
│  │All││🍜  ││✈️  ││🧺  ││🖨️  │  │
│  └───┘└────┘└────┘└────┘└────┘  │
│  ┌──────────┐ ┌──────────────┐  │  filter chips
│  │ ⊙ Hanya  │ │ $ Max Rp100k │  │
│  │   Open   │ │              │  │  → sheet slider
│  └──────────┘ └──────────────┘  │
│  7 PO ditemukan                  │
│  ┌────────────────────────────┐  │
│  │ 🍜 ● Open  PO Mie Gacoan   │  │
│  │ Rp18.000    ★4.8 (42)      │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ... hasil terfilter ...    │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  🏠      🔍    [+]     📦    👤  │
└──────────────────────────────────┘
```

**State:** normal (hasil ada) · **empty** → ikon 🔍 + "Tidak ada hasil, coba ubah filter".
**Interaksi:** ketik → hasil live · tap pill → filter kategori · "Hanya Open" toggle · "Max Rp" → sheet slider harga.
**Catatan:** hasil berubah instan tanpa reload.

---

### FRAME 04 — Detail PO
**ID PRD:** PB-05 · **Mode:** Logika

**Tujuan:** Info lengkap PO + profil penjual sebelum pesan.

```
┌──────────────────────────────────┐
│  ‹                            ↗  │  overlay top bar
│         ┌──────────────┐         │
│         │      🍜      │         │  hero 220px
│         │   ● Open     │         │
│         └──────────────┘         │
├──────────────────────────────────┤
│  PO Mie Gacoan Level 2           │  H1
│                        ● Bisa    │
│  Rp18.000                        │  price besar
│  ┌───┐ Andi Pratama [PRO]        │  penjual → Frame 05
│  │ A │ ★4.7 · 42 ulasan   Profil→│
│  └───┘                           │
│  ─────────────────────────────   │
│  Deskripsi                       │
│  PO mie gacoan level 2, isi 10   │
│  porsi. Pick up di parkiran FT.. │
│  ┌────────────────────────────┐  │
│  │ 🌱 Opsi ramah lingkungan   │  │  info card hijau
│  │ Bawa wadah sendiri diskon  │  │  (jika eco=true)
│  └────────────────────────────┘  │
│  ─────────────────────────────   │
│  Kuota & Deadline                │
│  ┌────────────────────────────┐  │
│  │ Kuota terisi    18/30      │  │
│  │ ▓▓▓▓▓▓▓▓░░░░░░░░           │  │  progress bar
│  │ ⏰ Deadline     7 Nov      │  │
│  │ 📦 Tersisa      12 slot    │  │
│  └────────────────────────────┘  │
│  ─────────────────────────────   │
│  Ulasan                          │
│  ┌────────────────────────────┐  │
│  │ R Rizki        2 Nov  ★5   │  │
│  │ PO cepat, penjual ramah... │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│ ┌──────────────────────────────┐ │  sticky CTA
│ │ Pesan Sekarang · Rp18.000    │ │  (disabled jika
│ └──────────────────────────────┘ │   kuota habis/closed)
└──────────────────────────────────┘
```

**State:** tombol pesan **disabled** jika kuota habis atau PO Closed.
**Interaksi:** tap penjual → Frame 05 · tap **Pesan** → Frame 06.

---

### FRAME 05 — Profil / Etalase Penjual
**ID PRD:** PB-12 · **Mode:** Klik

```
┌──────────────────────────────────┐
│  ‹   Profil Penjual              │
├──────────────────────────────────┤
│           ┌────┐                 │
│           │ A  │                 │  avatar 80px
│           └────┘                 │
│      Andi Pratama [PRO]          │
│      Bergabung Sep 2026          │
│  ┌──────────┐ ┌──────────┐       │  stat grid
│  │ ⭐ 4.7   │ │ 📦 84    │       │
│  │ Rating   │ │Transaksi │       │
│  └──────────┘ └──────────┘       │
│  ┌────────────────────────────┐  │
│  │ 📊 Dampak ekonomi (SDG 8)  │  │
│  │ Total omzet: Rp1.240.000   │  │
│  └────────────────────────────┘  │
│  PO dari Andi                    │
│  ┌────────────────────────────┐  │
│  │ 🍜 PO Mie Gacoan   ● Open  │  │
│  └────────────────────────────┘  │
│  Ulasan pembeli                  │
│  ┌────────────────────────────┐  │
│  │ R Rizki           ★5.0     │  │
│  │ "Aman banget, dana ditahan"│  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Elemen:** avatar, nama + badge PRO, rating, total transaksi, rekap omzet (SDG 8), daftar PO, ulasan.
**Interaksi:** tap PO → Frame 04.

---

### FRAME 06 — Checkout
**ID PRD:** PB-06 · **Mode:** Logika

**Tujuan:** Tentukan jumlah + catatan, total dihitung otomatis.

```
┌──────────────────────────────────┐
│  ‹   Checkout                    │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ PO Mie Gacoan Level 2      │  │  mini recap card
│  │ Andi Pratama · Rp18.000/item│ │
│  └────────────────────────────┘  │
│  Jumlah Pesanan                  │
│  ┌──┐ ┌───┐ ┌──┐   Maks 12      │  qty stepper
│  │ −│ │ 2 │ │ +│                 │
│  └──┘ └───┘ └──┘                 │
│  Catatan untuk Penjual           │
│  ┌────────────────────────────┐  │
│  │ Misal: level pedas...      │  │  textarea
│  └────────────────────────────┘  │
│  ─────────────────────────────   │
│  Ringkasan Pembayaran            │
│  ┌────────────────────────────┐  │
│  │ Subtotal (2 × Rp18.000)     │  │
│  │                  Rp36.000   │  │
│  │ Biaya QRIS (MDR 0.7%)       │  │
│  │                    Rp252    │  │
│  │ ─────────────────────────── │  │
│  │ Total Bayar     Rp36.252    │  │  bold hijau
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🔒 Dana ditahan (escrow)   │  │
│  │ Dana Rp36.252 ditahan...   │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ Bayar QRIS · Rp36.252      │  │  button primary
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Logika:** total = (qty × harga) + (subtotal × 0.7%). Qty dibatasi kuota tersisa.
**Interaksi:** +/− → hitung ulang · **Bayar** → Frame 07.

---

### FRAME 07 — Pembayaran QRIS (Simulasi)
**ID PRD:** PB-07 · **Mode:** Klik

```
┌──────────────────────────────────┐
│  ‹   Pembayaran QRIS             │
├──────────────────────────────────┤
│                                  │
│         ┌──────────┐             │
│         │ ▓▓▓▓▓▓▓▓ │             │  QR code
│         │ ▓▓ ▓▓ ▓▓ │             │  160×160
│         │ ▓▓▓▓▓▓▓▓ │             │
│         └──────────┘             │
│      Scan untuk membayar         │
│   Nomor VA: 8801 1024 9988  📋  │
│  ┌────────────────────────────┐  │
│  │ Total Bayar    Rp36.252    │  │
│  │ Metode      QRIS (simulasi)│  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🔒 Dana akan ditahan       │  │
│  │ Cair setelah dikonfirmasi  │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ✓ Saya Sudah Bayar (Demo)  │  │  button primary
│  └────────────────────────────┘  │
│  QRIS simulasi — tidak ada uang  │
│  sungguhan yang diproses         │
└──────────────────────────────────┘
```

**Interaksi:** tap **Saya Sudah Bayar** → order dibuat (status Dibayar, dana ditahan) → Frame 08.
**Catatan:** jelas labeli "simulasi" — hindari klaim memproses uang nyata.

---

### FRAME 08 — Status Pesanan Pembeli
**ID PRD:** PB-08, PB-09, PB-10 · **Mode:** Logika

**Tujuan:** Pantau status, konfirmasi terima, atau laporkan masalah.

```
┌──────────────────────────────────┐
│  ‹   Pesanan Saya                │
├──────────────────────────────────┤
│  ┌─────────────┐┌─────────────┐  │  tabs
│  │ Aktif (1)   ││ Selesai (1) │  │
│  └─────────────┘└─────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 📦 NM-1024                 │  │  order card
│  │ PO Mie Gacoan Level 2      │  │
│  │ 2 × Rp18.000  Rp36.000     │  │
│  │                  ● Diproses│  │
│  │ 4 Nov              Lacak → │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  ← DETAIL PESANAN (Frame 08b)    │
│  ┌────────────────────────────┐  │
│  │ Status Pesanan             │  │
│  │ ● Dibayar       Selesai    │  │  timeline
│  │ ● Diproses   Berlangsung   │  │
│  │ ○ Siap/Dikirim  Menunggu   │  │
│  │ ○ Selesai       Menunggu   │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🔒 Dana Rp36.000 ditahan   │  │
│  │ Cair setelah konfirmasi    │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ Konfirmasi Pesanan Diterima│  │  → Selesai + dana cair
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ Laporkan Masalah           │  │  → Sengketa (sheet)
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**State timeline:** Dibayar → Diproses → Siap/Dikirim → Selesai (mengikuti perubahan penjual).
**Interaksi:** 08b **Konfirmasi** → status Selesai · **Laporkan** → sheet alasan → status Sengketa.
**Sheet laporan:** radio alasan (tidak sesuai, rusak, tidak respons, telat, lainnya) + textarea + kirim.

---

### FRAME 09 — Rating & Ulasan
**ID PRD:** PB-11 · **Mode:** Logika

**Tujuan:** Beri rating hanya untuk pesanan yang selesai.

```
┌──────────────────────────────────┐
│  ‹   Beri Rating                 │
├──────────────────────────────────┤
│           ┌────┐                 │
│           │ B  │                 │  avatar penjual
│           └────┘                 │
│      Laundry Kiloan Hemat        │
│      Budi Santoso · NM-1023      │
│                                  │
│   Bagaimana pengalamanmu?        │
│      ☆  ☆  ☆  ☆  ☆              │  stars interaktif
│   Klik bintang untuk nilai       │
│                                  │
│  Ulasan                          │
│  ┌────────────────────────────┐  │
│  │ Bagikan pengalamanmu...    │  │  textarea
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 📊 Rating adil & terverifikasi│
│  │ Hanya dari transaksi NeMart│  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │       Kirim Rating         │  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Logika:** hanya bisa dibuka jika pesanan berstatus Selesai. Setelah kirim → review muncul di profil penjual (Frame 05).
**Catatan:** rating terkunci ke transaksi platform (mitigasi bocor ke WA, PRD §13).

---

### FRAME 10 — Pendaftaran Penjual & Status Verifikasi
**ID PRD:** PJ-01 · **Mode:** Klik

```
┌──────────────────────────────────┐
│  ‹   Daftar Jadi Penjual         │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ 🏪 Mulai jualan di NeMart  │  │  banner hijau
│  │ Bebas komisi bulan 1 + boost│ │
│  └────────────────────────────┘  │
│  Nama Usaha                      │
│  ┌────────────────────────────┐  │
│  │ Andi Snack                 │  │
│  └────────────────────────────┘  │
│  Kategori Usaha                  │
│  ┌────────────────────────────┐  │
│  │ Makanan & Minuman       ▼  │  │  select
│  └────────────────────────────┘  │
│  Nomor WA                        │
│  ┌────────────────────────────┐  │
│  │ 08123456789                │  │
│  └────────────────────────────┘  │
│  Unggah Bukti Mahasiswa (KTM)    │
│  ┌────────────────────────────┐  │
│  │        📄                  │  │  dropzone dashed
│  │ Ketuk untuk unggah          │  │
│  │ JPG/PNG/PDF maks 2MB        │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │  Daftar Sebagai Penjual    │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  ← STATUS VERIFIKASI (Frame 10b) │
│        ┌────┐                    │
│        │ ⏳ │                    │
│        └────┘                    │
│    Menunggu Verifikasi           │
│   Biasanya 1×24 jam              │
│  ┌────────────────────────────┐  │
│  │ Nama     Andi Snack        │  │
│  │ Status   ⏳ Menunggu        │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ Simulasi: Verifikasi Sekarang│
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Interaksi:** submit → status "Menunggu". Tombol simulasi untuk demo → langsung disetujui → Frame 11.

---

### FRAME 11 — Dasbor Penjual
**ID PRD:** PJ-04, PJ-05 · **Mode:** Logika

**Tujuan:** Lihat pesanan masuk + saldo tertahan vs cair.

```
┌──────────────────────────────────┐
│  ‹   Dasbor Penjual       ⚡     │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ Saldo bisa ditarik         │  │  saldo card hijau
│  │ Rp71.250                   │  │  gradient
│  │ ─────────────────────────  │  │
│  │ Tertahan      Sudah cair   │  │
│  │ Rp36.000      Rp75.000     │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 💸 Tarik Saldo             │  │
│  └────────────────────────────┘  │
│  Statistik                       │
│  ┌──────────┐ ┌──────────┐       │
│  │ 📦 7     │ │ 🛍️ 2     │       │
│  │ Pesanan  │ │ PO aktif │       │
│  └──────────┘ └──────────┘       │
│  ┌──────────┐ ┌──────────┐       │
│  │ ⭐ 4.7   │ │ 👤 42    │       │
│  │ Rating   │ │ Pembeli  │       │
│  └──────────┘ └──────────┘       │
│  ┌────────────────────────────┐  │
│  │ 📊 Dampak (SDG 8)          │  │
│  │ Omzet Rp125.000 · Komisi 5%│  │
│  │ Rp6.250 · Bersih Rp118.750 │  │
│  └────────────────────────────┘  │
│  Pesanan Masuk        Lihat →    │
│  ┌────────────────────────────┐  │
│  │ 📦 NM-1024 · 2 item  Diproses│
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ➕ Buat PO Baru            │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ⚡ Beli Boost              │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  🏠   🔍   [+]   📦   👤        │
└──────────────────────────────────┘
```

**Logika:** saldo bisa ditarik = (cair − komisi). Saldo tertahan **tidak naik** sebelum konfirmasi (SY-02).
**Interaksi:** tap pesanan → kelola status · **Buat PO** → Frame 12 · **Boost** → Frame 13.

---

### FRAME 12 — Buat / Kelola Listing PO
**ID PRD:** PJ-02, PJ-03 · **Mode:** Logika

```
┌──────────────────────────────────┐
│  ‹   Buat PO Baru                │
├──────────────────────────────────┤
│  Nama PO                         │
│  ┌────────────────────────────┐  │
│  │ PO Seblak Special          │  │
│  └────────────────────────────┘  │
│  Kategori                        │
│  ┌────────────────────────────┐  │
│  │ 🍜 Makanan              ▼  │  │
│  └────────────────────────────┘  │
│  Deskripsi                       │
│  ┌────────────────────────────┐  │
│  │ Jelaskan PO kamu...        │  │
│  └────────────────────────────┘  │
│  Harga per item (Rp)             │
│  ┌────────────────────────────┐  │
│  │ 15000                      │  │
│  └────────────────────────────┘  │
│  Kuota                           │
│  ┌────────────────────────────┐  │
│  │ 20                         │  │
│  └────────────────────────────┘  │
│  Tanggal Tutup PO                │
│  ┌────────────────────────────┐  │
│  │ 📅 pilih tanggal           │  │
│  └────────────────────────────┘  │
│  Foto PO (opsional)              │
│  ┌────────────────────────────┐  │
│  │        📷 Ketuk unggah     │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ℹ️ Komisi & escrow         │  │
│  │ Free 5% · cair setelah     │  │
│  │ konfirmasi pembeli         │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │          Buka PO           │  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Logika:** PO otomatis **Closed** saat kuota habis atau deadline lewat (PJ-03). Bisa buka/tutup manual.
**Post-create:** listing langsung muncul di Beranda (Frame 02).

---

### FRAME 13 — Boost & Paket Free/Pro
**ID PRD:** PJ-06, PJ-07, PJ-08 · **Mode:** Klik

```
┌──────────────────────────────────┐
│  ‹   Boost PO                    │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ ⚡ Tingkatkan jangkauan PO  │  │
│  │ Tampil di slot Featured    │  │
│  └────────────────────────────┘  │
│  Pilih PO untuk di-boost         │
│  ┌────────────────────────────┐  │
│  │ PO Mie Gacoan Level 2      │  │  selectable
│  └────────────────────────────┘  │
│  Pilih Paket Boost               │
│  ┌────────────────────────────┐  │
│  │ ⚡ Boost 24 Jam   Rp5.000  │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ⚡ Boost 3 Hari   Rp10.000 │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 🎁 Boost gratis penjual baru│ │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  ← PAKET (Frame 13b)             │
│  ┌────────────────────────────┐  │
│  │ 🌱 Paket Free    Rp0       │  │
│  │ • Komisi 5%                │  │
│  │ • Buat PO tanpa batas      │  │
│  │ • Boost berbayar           │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ ⭐ Paket Pro  Rp25.000/bln │  │  highlight
│  │ • Komisi 3% (dari 5%)      │  │
│  │ • 3× boost gratis/bulan    │  │
│  │ • Dasbor lengkap           │  │
│  │ • Badge Penjual Pro        │  │
│  │ [ Upgrade ke Pro ]         │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ 💡 Order Rp20.000: komisi  │  │
│  │ Free Rp1.000 · Pro Rp600   │  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Aturan:** Boost Rp5rb/24j, Rp10rb/3hr. Slot Featured dibatasi 3–5, dirotasi, syarat rating minimum.
**Logika hemat:** 63 order/bulan → balik biaya Pro.

---

### FRAME 14 — Panel Admin
**ID PRD:** AD-01, AD-02, SY-01, SY-02 · **Mode:** Klik

**Tujuan:** Verifikasi penjual + tangani laporan masalah.

```
┌──────────────────────────────────┐
│  ‹   Panel Admin                 │
├──────────────────────────────────┤
│  ┌────────────────────────────┐  │
│  │ 🛡️ Tim NeMart             │  │  banner ungu
│  └────────────────────────────┘  │
│  ┌─────────────┐┌─────────────┐  │  tabs
│  │Verifikasi(2)││ Laporan (1) │  │
│  └─────────────┘└─────────────┘  │
│  Permintaan Verifikasi           │
│  ┌────────────────────────────┐  │
│  │ E Eka Putra                │  │
│  │ Makanan · 3 Nov            │  │
│  │ 📄 KTM_2025.pdf            │  │
│  │    [✕ Tolak]  [✓ Setujui]  │  │
│  └────────────────────────────┘  │
│  ┌────────────────────────────┐  │
│  │ L Lina Marlina             │  │
│  │ Jastip · 2 Nov             │  │
│  │    [✕ Tolak]  [✓ Setujui]  │  │
│  └────────────────────────────┘  │
├──────────────────────────────────┤
│  ← TAB LAPORAN                   │
│  ┌────────────────────────────┐  │
│  │ ⚠️ RP-01 · NM-1022         │  │
│  │ Rizki vs Budi Santoso      │  │
│  │ Barang tidak sesuai        │  │
│  │ Dana ditahan: Rp21.000     │  │
│  │ [↩ Refund ] [Cair ke Penjual]│
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

**Interaksi:** Setujui/Tolak → status penjual berubah, penjual dapat notifikasi (SY-01). Refund/Cair → keputusan escrow final (AD-02, SY-02).

---

## 5. Skrip Demo 2–3 Menit (untuk Pitch)

| Waktu | Adegan | Layar | Narasi |
| --- | --- | --- | --- |
| 0:00–0:20 | Masalah | — | "PO mahasiswa tersebar di grup WA & IG, mudah terlewat, dan penjual sering kena cancel sepihak." |
| 0:20–0:40 | Masuk | 01 → 02 | "NeMart satukan semua PO di satu tempat, dengan status jelas." |
| 0:40–1:05 | Cari & pilih | 03 → 04 | "Filter hanya yang Open, buka detail, lihat rating penjual." |
| 1:05–1:30 | Pesan & bayar | 06 → 07 | "Checkout otomatis, bayar QRIS. Dana **ditahan** — penjual tidak bisa kabur." |
| 1:30–1:55 | Konfirmasi | 08 | "Pembeli pantau status. Setelah konfirmasi terima, baru dana **cair** ke penjual." |
| 1:55–2:15 | Rating | 09 | "Rating hanya dari transaksi platform — membangun kepercayaan." |
| 2:15–2:45 | Sisi penjual | 11 → 13 | "Penjual lihat saldo tertahan vs cair, komisi transparan, dan bisa boost PO." |
| 2:45–3:00 | Dampak | 05 | "Setiap transaksi = omzet UMKM kampus naik (SDG 8), plus PO terjadwal & nota digital (SDG 12)." |

---

## 6. Mapping Layar → ID Kebutuhan PRD

| # Frame | Layar | ID PRD | Prioritas | Mode |
| --- | --- | --- | --- | --- |
| 01 | Login & Daftar | PB-01 | Must | Klik |
| 02 | Beranda | PB-02, PB-03 | Must/Should | Logika |
| 03 | Cari & Filter | PB-04 | Must | Logika |
| 04 | Detail PO | PB-05 | Must | Logika |
| 05 | Profil Penjual | PB-12 | Should | Klik |
| 06 | Checkout | PB-06 | Must | Logika |
| 07 | QRIS | PB-07 | Must | Klik |
| 08 | Status Pesanan | PB-08, PB-09, PB-10 | Must/Should | Logika |
| 09 | Rating & Ulasan | PB-11 | Must | Logika |
| 10 | Daftar Penjual & Verifikasi | PJ-01 | Must | Klik |
| 11 | Dasbor Penjual | PJ-04, PJ-05 | Must | Logika |
| 12 | Buat/Ubah Listing PO | PJ-02, PJ-03 | Must | Logika |
| 13 | Boost & Paket | PJ-06, PJ-07, PJ-08 | Should/Could | Klik |
| 14 | Panel Admin | AD-01, AD-02, SY-01, SY-02 | Must/Should | Klik |

---

## 7. Checklist Setup Figma

- [ ] Pages: `00 Cover` · `01 Tokens` · `02 Components` · `03 Screens Buyer` · `04 Screens Seller` · `05 Screens Admin` · `06 Prototype Flow`
- [ ] Buat text styles & color styles dari Bagian 1
- [ ] Buat 13 komponen (Bagian 2) + variants
- [ ] Frame 390 × 844, grid 8pt/4 kolom
- [ ] Nama frame: `NN_NamaLayar` (mis. `04_DetailPO`)
- [ ] Wire alur sesuai Bagian 3 (Prototype mode → On click)
- [ ] Tandai mode Klik vs Logika di tiap frame (badge kecil)

---

*Storyboard v1.0 · Prototype Klik · Semua data dummy · Pembayaran & escrow disimulasikan*
