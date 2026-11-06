# TemanSeller — Server (Laravel + TailAdmin)

Backend SaaS untuk extension Chrome **TemanSeller**: login Google, langganan (trial 3 hari · Bulanan · Lifetime), pembayaran Midtrans, analisis AI lewat kenari.id, dan panel admin bergaya TailAdmin.

- **Laravel 12** (PHP 8.2+), MySQL/MariaDB, Blade + Alpine + Tailwind v4 (TailAdmin free), ApexCharts.
- **Semua konfigurasi provider diatur dari dashboard admin** (Pengaturan): Google Client ID, Midtrans (Server Key, mode), kenari.id (API key, model, kuota, prompt). Disimpan di database; kunci rahasia dienkripsi dengan `APP_KEY`. File `.env` hanya berisi koneksi database & `APP_KEY`.
- Kontrak API untuk extension tidak berubah: `/api/index.php?r=plans|auth|me|checkout|orders|ai/analyze|midtrans/notify` (juga tersedia rute bersih `/api/plans`, dst).

## Portal pengguna (web)
`/login` login dengan akun Google (akun yang sama dengan extension) → `/akun`: status langganan (Trial/Bulanan/Lifetime, tanggal berakhir, sisa hari, kuota AI hari ini), tombol **Berlangganan / Perpanjang** (Midtrans) dan riwayat pembayaran. Perpanjangan Bulanan ditambahkan setelah masa aktif berjalan. Butuh *Authorized JavaScript origins* = domain situs di OAuth client Google.

## Layanan Kelola Instagram & Threads (v1.7)
Layanan jasa berlangganan (mirip urusin.co), terpisah dari aplikasi ber-Midtrans seperti Shofee.
- **Publik**: tab **Layanan** di beranda → `/layanan` (paket per jenis, portofolio, FAQ, tombol WhatsApp). Beranda juga memuat seksi layanan; Shofee ditandai **Extension Chrome**.
- **Admin** (`/admin/layanan`): **Paket & Harga** (jenis paket dan paket dinamis: nama, harga, periode, fitur, lama aktif, aktif/draf, unggulan), **Portofolio** (tambah/ubah/hapus + unggah gambar), **Langganan Pelanggan** (aktifkan pesanan, atur masa aktif, tautan folder Drive, pesan ke pelanggan, tambah manual lewat email). **Pengaturan → Layanan Sosmed**: nomor WhatsApp, template pesan, info pembayaran, saklar tampil/sembunyi.
- **Akun pelanggan** (`/akun/layanan`): status langganan, jenis paket, masa berlaku, dan tautan folder Drive; konfirmasi pesanan dari tombol "Pesan paket ini".
- Alur bayar **manual**: pelanggan memesan → status *Menunggu konfirmasi* → bayar sesuai info pembayaran + konfirmasi WhatsApp → admin mengubah ke *Aktif*.
- Tiga paket awal dibuat sebagai **draf nonaktif berharga 0**; tidak tampil sampai admin mengisi harga dan mengaktifkan. Portofolio awal kosong.
- Gambar portofolio disimpan di disk lokal (`storage/app/private/layanan`, volume `storage` Docker) dan disajikan lewat rute `/layanan/gambar/{id}/{v}` (tanpa `storage:link`).

## Halaman admin (`/admin`)
Dashboard (statistik + grafik) · Pengguna (cari/filter, tambah hari, Lifetime, cabut akses, riwayat) · Order · Pemakaian AI (grafik, pengguna teratas, log galat, kosongkan cache) · **Pengaturan** (Langganan, Google Login, Midtrans, AI kenari.id, Prompt AI, Extension) dengan tombol **Tes** untuk Google/Midtrans/kenari.id dan **Muat daftar model** · Profil admin.

## Pasang cepat (lokal)
```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* di .env, lalu:
php artisan temanseller:install                                     # migrasi + seed + kunci lisensi; admin awal: rzzkan
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
| `php artisan temanseller:install --admin=EMAIL` | Migrasi, buat kunci lisensi/JWT, admin pertama |
| `php artisan temanseller:admin EMAIL [--password=…]` | Buat admin / reset kata sandi |
| `php artisan schedule:run` (cron tiap menit) | Bersihkan log AI >90 hari & cache AI >30 hari |

## Penting
- **Jangan ganti `APP_KEY` setelah ada data**: nilai rahasia di Pengaturan tidak bisa didekripsi lagi.
- **Kunci lisensi (Ed25519)** dibuat otomatis sekali. Menggantinya (Pengaturan → Extension) membuat semua lisensi lama tak valid sampai extension dirilis ulang dengan kunci publik baru.
- Dibuat di atas template [TailAdmin Laravel](https://tailadmin.com) (MIT, lihat `LICENSE`).

## Marketplace Audit (v1.8.0)

Aplikasi `audit` (Shopee): pengguna login Google di temanseller.com, buka `/audit`, tempel tautan toko/produk. Data dikumpulkan di **browser pengguna** oleh extension (sesi Shopee pengguna tidak pernah sampai ke server), dikirim ke `POST /api/audit/scans/{id}/data` (header `X-Audit-Token`, sekali pakai), lalu server menjalankan pemeriksaan aturan + audit AI (kenari.id).

- Kuota: jatah gratis per bulan (WIB) dan batas harian pelanggan diatur di Admin → Pengaturan → Marketplace Audit. Harga paket unlimited (bulanan/tahunan) di Admin → Aplikasi → Marketplace Audit (harga 0 = belum dijual).
- Scan hanya terhitung bila audit selesai. Satu pemindaian aktif per pengguna.
- Tautan, data, dan hasil dihapus otomatis setelah masa simpan (scheduler `audit-purge`, tiap jam); baris hitungan tetap.
- Halaman: `/marketplace-audit` (publik), `/audit` (login), `/admin/audit`.
- Kontrak extension↔halaman↔server: lihat `docs/AUDIT-EXTENSION.md` di repo extension.
