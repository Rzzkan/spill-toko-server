# Spill Toko — Server (Laravel + TailAdmin)

Backend SaaS untuk extension Chrome **Spill Toko**: login Google, langganan (trial 3 hari · Bulanan · Lifetime), pembayaran Midtrans, analisis AI lewat kenari.id, dan panel admin bergaya TailAdmin.

- **Laravel 12** (PHP 8.2+), MySQL/MariaDB, Blade + Alpine + Tailwind v4 (TailAdmin free), ApexCharts.
- **Semua konfigurasi provider diatur dari dashboard admin** (Pengaturan): Google Client ID, Midtrans (Server Key, mode), kenari.id (API key, model, kuota, prompt). Disimpan di database; kunci rahasia dienkripsi dengan `APP_KEY`. File `.env` hanya berisi koneksi database & `APP_KEY`.
- Kontrak API untuk extension tidak berubah: `/api/index.php?r=plans|auth|me|checkout|orders|ai/analyze|midtrans/notify` (juga tersedia rute bersih `/api/plans`, dst).

## Portal pengguna (web)
`/masuk` login dengan akun Google (akun yang sama dengan extension) → `/akun`: status langganan (Trial/Bulanan/Lifetime, tanggal berakhir, sisa hari, kuota AI hari ini), tombol **Berlangganan / Perpanjang** (Midtrans) dan riwayat pembayaran. Perpanjangan Bulanan ditambahkan setelah masa aktif berjalan. Butuh *Authorized JavaScript origins* = domain situs di OAuth client Google.

## Halaman admin (`/admin`)
Dashboard (statistik + grafik) · Pengguna (cari/filter, tambah hari, Lifetime, cabut akses, riwayat) · Order · Pemakaian AI (grafik, pengguna teratas, log galat, kosongkan cache) · **Pengaturan** (Langganan, Google Login, Midtrans, AI kenari.id, Prompt AI, Extension) dengan tombol **Tes** untuk Google/Midtrans/kenari.id dan **Muat daftar model** · Profil admin.

## Pasang cepat (lokal)
```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* di .env, lalu:
php artisan spill:install                                     # migrasi + seed + kunci lisensi; admin awal: rzzkan
php artisan serve                                     # http://127.0.0.1:8000/admin
```
Aset front-end sudah di-build ke `public/build` (tidak perlu Node di server). Untuk mengubah tampilan: `npm install && npm run build`.

## Deploy VPS
Lihat [`deploy/README.md`](deploy/README.md) — satu perintah `deploy/setup.sh` (nginx + PHP-FPM + MariaDB + SSL).

## Tes
```bash
vendor/bin/phpunit      # 22 tes: API, webhook, AI (cache/kuota/fallback), admin, pengaturan
```

## Perintah
| Perintah | Fungsi |
|---|---|
| `php artisan spill:install --admin=EMAIL` | Migrasi, buat kunci lisensi/JWT, admin pertama |
| `php artisan spill:admin EMAIL [--password=…]` | Buat admin / reset kata sandi |
| `php artisan schedule:run` (cron tiap menit) | Bersihkan log AI >90 hari & cache AI >30 hari |

## Penting
- **Jangan ganti `APP_KEY` setelah ada data**: nilai rahasia di Pengaturan tidak bisa didekripsi lagi.
- **Kunci lisensi (Ed25519)** dibuat otomatis sekali. Menggantinya (Pengaturan → Extension) membuat semua lisensi lama tak valid sampai extension dirilis ulang dengan kunci publik baru.
- Dibuat di atas template [TailAdmin Laravel](https://tailadmin.com) (MIT, lihat `LICENSE`).
