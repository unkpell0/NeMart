# Demo NeMart via Cloudflare Tunnel (sementara)

Cloudflare Pages/Workers **tidak bisa** menjalankan PHP + SQLite. Karena itu demo ini memakai **Cloudflare Quick Tunnel**: server PHP tetap berjalan di laptop, lalu Cloudflare memberi URL HTTPS publik `https://xxxx.trycloudflare.com`.

Cocok untuk presentasi besok, **bukan** untuk produksi permanen.

## Menjalankan

1. Pastikan laptop terhubung internet dan PHP tersedia (XAMPP).
2. Buka folder `nemart\tools`.
3. Klik dua kali **`Demo-Presentasi.bat`**.
4. Tunggu 10–20 detik. Skrip akan:
   - menyalakan server PHP di `127.0.0.1:8123`,
   - mengunduh `cloudflared` bila belum ada,
   - membuat tunnel Cloudflare,
   - membuka URL di browser,
   - menyimpan URL ke `Desktop\NeMart-URL-Presentasi.txt`.
5. Catat URL tersebut. URL **berubah** setiap kali tunnel dijalankan ulang.

Hentikan demo:

```powershell
powershell -ExecutionPolicy Bypass -File nemart\tools\presentasi-stop.ps1
```

Atau tutup jendela/hidupkan ulang laptop (server ikut berhenti).

## Selama presentasi

- **Jangan tidurkan laptop.** Atur Power & Sleep ke “Never” saat demo.
- Tetap terhubung internet; jangan ganti jaringan Wi-Fi.
- Jangan menutup proses PHP/cloudflared.
- Buka URL 5–10 menit sebelum tampil untuk memastikan masih hidup.
- URL ini tidak ada jaminan uptime (quick tunnel). Jika mati, jalankan ulang `Demo-Presentasi.bat` dan pakai URL baru.

## Keamanan

- Demo ini tanpa kata sandi dan siapa pun yang punya URL bisa mengubah data demo.
- Jangan bagikan URL ke publik luas; cukup ke juri.
- Reset data sebelum presentasi: login admin → **Reset Data Demo**, atau `php db/seed.php --force`.
- Data demo berisi transaksi simulasi, bukan data pribadi.

## Batasan teknis

| Hal | Keterangan |
|---|---|
| Uptime | Tidak dijamin; bergantung laptop & koneksi |
| URL | Acak dan berubah saat tunnel dijalankan ulang |
| Performa | Tergantung upload internet kampus/rumah |
| SQLite | Tetap di laptop; aman untuk demo satu instance |
| HTTPS | Otomatis dari Cloudflare |

Untuk URL permanen, gunakan Render (lihat `DEPLOY.md`) atau Named Tunnel Cloudflare dengan domain sendiri.

## Jika bermasalah

| Gejala | Perbaikan |
|---|---|
| Browser 502/1033 | Server PHP mati. Jalankan ulang `Demo-Presentasi.bat` |
| URL tidak muncul | Lihat `nemart\data\tunnel-presentasi.log` |
| Port 8123 dipakai | Hentikan proses lain atau `presentasi-stop.ps1` lalu ulang |
| `cloudflared` gagal unduh | Periksa internet/firewall, lalu ulang |
| Halaman lambat | Jaringan upload lambat; tetap pakai laptop yang sama |

## Uji cepat setelah URL aktif

```powershell
php tools/uji-alur.php --base=https://xxxx.trycloudflare.com
```

Skrip akan mereset data demo sebelum dan sesudah uji.
