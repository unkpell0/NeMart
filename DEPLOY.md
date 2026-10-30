# Deploy NeMart

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/unkpell0/NeMart/tree/v2-demo)

Aplikasi ini adalah PHP 8.2 + SQLite tanpa layanan eksternal. Cara paling mudah dan gratis: **Render (Docker) + GitHub**. Panduan lain disertakan di bawah.

> **Penting:** root repository harus folder yang berisi `index.php`, `Dockerfile`, dan `render.yaml` (yaitu isi folder `nemart/`). Jangan menaruhnya satu level terlalu dalam, kecuali kamu menyesuaikan `dockerfilePath` dan `dockerContext` di Render.

---

## Opsi A — Render + GitHub (disarankan, gratis)

Kode sudah tersedia di branch **`v2-demo`** pada repo <https://github.com/unkpell0/NeMart>. Cara tercepat: klik tombol **Deploy to Render** di atas, login Render dengan GitHub, lalu **Apply** Blueprint. Render membaca `render.yaml`, memakai branch `v2-demo`, dan membuat service `nemart`.

Jika ingin melakukannya manual:

### 1. Repository GitHub

Branch yang dipakai: `v2-demo` (main tidak diubah). Untuk repo baru:

```powershell
git init -b main
git add .
git commit -m "NeMart v2 demo MVP"
git remote add origin https://github.com/USERNAME/nemart.git
git push -u origin main
```

Ganti `USERNAME` dengan username GitHub-mu. Saat `push`, Git akan meminta login (browser atau Personal Access Token).

### 2. Deploy lewat Blueprint Render

1. Buka <https://dashboard.render.com> → **New +** → **Blueprint**.
2. Hubungkan GitHub, pilih repo `nemart`.
3. Render membaca `render.yaml` dan menampilkan service `nemart`.
4. Klik **Apply** / **Create**.
5. Tunggu build selesai (3–5 menit). URL akan berbentuk `https://nemart-xxxx.onrender.com`.

Alternatif manual bila Blueprint tidak dipakai: **New + → Web Service → repo → Runtime: Docker → Dockerfile Path: `./Dockerfile` → Health Check Path: `/healthz.php` → Plan: Free**.

### 3. Setelah deploy

1. Buka `https://<domain>/healthz.php` → harus muncul `{"status":"ok",...}`.
2. Buka beranda, login sebagai **Kemahasiswaan Polinema**, lalu **Reset Data Demo** sekali agar data bersih.
3. Uji cepat: login Marvelino → tambah produk → checkout → login Aditya → terima pesanan.

### Catatan plan Free Render

| Hal | Dampak | Solusi untuk lomba |
|---|---|---|
| Service tidur setelah ~15 menit idle | Request pertama bisa lambat 30–60 detik | Buka URL 10 menit sebelum presentasi |
| Disk gratis bersifat sementara | Database kembali ke seed setiap deploy/restart | Sudah sesuai PRD; reset manual juga tersedia |
| Build ulang saat push | Data hilang | Jangan push saat hari-H |
| Butuh database persisten | Perlu disk berbayar | Attach disk Render ke `/var/www/html/data` (plan Starter) |

---

## Opsi B — Railway

1. Push repo ke GitHub seperti Opsi A.
2. <https://railway.app> → **New Project** → **Deploy from GitHub repo** → pilih repo.
3. Railway mendeteksi `Dockerfile` otomatis. Bila tidak, set **Builder: Dockerfile**.
4. **Settings → Networking → Generate Domain**.
5. Variabel `PORT` diisi otomatis oleh Railway; entrypoint sudah memakainya.
6. Agar database tidak hilang saat redeploy, tambahkan **Volume** dan mount ke `/var/www/html/data`.

Health check: `/healthz.php`.

---

## Opsi C — VPS dengan Docker

Di server (butuh Docker + port 80 terbuka):

```bash
git clone https://github.com/USERNAME/nemart.git
cd nemart
docker build -t nemart .
docker run -d --restart unless-stopped --name nemart \
  -p 80:10000 -e PORT=10000 \
  -v nemart_data:/var/www/html/data \
  nemart
```

Buka `http://IP-SERVER/`. Untuk HTTPS, pasang reverse proxy (Caddy/Nginx) atau Cloudflare di depan server.

---

## Opsi D — Shared hosting / cPanel

Syarat: PHP 8.2+, ekstensi `pdo_sqlite`, `mbstring`, `session` aktif.

1. Upload isi folder `nemart/` ke `public_html` (atau subfolder).
2. Pastikan folder `data/` dapat ditulis (permission 775/777 sesuai host).
3. Buka domainnya — skema dan seed dibuat otomatis pada request pertama.
4. Bila diletakkan di subfolder, aplikasi mendeteksi base URL sendiri; tidak perlu ubah konfigurasi.
5. Bila muncul error 500, cek **PHP Error Log** di cPanel; biasanya `pdo_sqlite` belum aktif.

---

## Pemeriksaan setelah deploy

- [ ] `https://domain/healthz.php` → `status: ok`
- [ ] Beranda tampil, gambar ikon kategori muncul
- [ ] Login 5 akun demo berhasil
- [ ] Keranjang Marvelino kosong di kondisi awal
- [ ] Checkout membuat pesanan dan muncul di Seller Centre
- [ ] Data Tracking PRO terbuka; versi Free terkunci
- [ ] Admin bisa reset data demo

## Troubleshooting

| Gejala | Penyebab umum | Perbaikan |
|---|---|---|
| Halaman putih / 500 | `pdo_sqlite` tidak ada | Aktifkan ekstensi atau pakai image Docker kami |
| `could not find driver` | PDO SQLite belum aktif | Sama seperti di atas |
| Data hilang tiap restart | Disk ephemeral (Render Free) | Gunakan volume/disk atau reset sebelum demo |
| Sesi tidak bertahan | `data/sessions` tidak writable | `chown -R www-data:www-data data` |
| Aset CSS 404 di subfolder | Document root salah | Arahkan document root ke folder aplikasi |
| Port tidak merespons | Container tidak listen `$PORT` | Pastikan pakai `Dockerfile`/entrypoint bawaan |

## Membuat perubahan setelah deploy

```powershell
git add .
git commit -m "perbaikan ..."
git push
```

Render/Railway akan otomatis build ulang. Ingat: pada plan gratis, database ikut ter-reset.
