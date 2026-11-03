# Deploy Spill Toko (Laravel) ke VPS Ubuntu/Debian

Memasang backend di **subdomain baru** pada VPS yang sama dengan situs lain (mis. `spill.delova.id`). Skrip hanya **menambah** satu vhost nginx — situs lain tidak diubah. Hasil uji: nginx + PHP-FPM 8.3 + MariaDB, 9 pemeriksaan lulus.

## Prasyarat
- Ubuntu 22.04/24.04 atau Debian 12 dengan **PHP 8.2+** (Ubuntu 24.04 membawa 8.3), akses root, **nginx** di port 80/443. (Apache/cPanel: lihat bagian Manual.)
- A record `spill` → IP VPS (Cloudflare boleh proxied).
- Akun Google Cloud, Midtrans, dan API key kenari.id — **diisi nanti di dashboard**, bukan di server.

## 1. Taruh kode di VPS
```bash
git clone https://github.com/Rzzkan/spill-toko-server.git ~/spill-toko-server
```

## 2. Jalankan setup (sekali; aman diulang)
```bash
cd ~/spill-toko-server
DOMAIN=spill.delova.id EMAIL=emailmu@gmail.com ADMIN_EMAIL=emailmu@gmail.com ADMIN_PASS='password-admin-kuat' bash deploy/setup.sh
```
Skrip: memasang paket yang kurang (+ Composer) → database & user (password acak) → salin app ke `/var/www/spilltoko` → `.env` (hanya DB + `APP_KEY`) → `composer install --no-dev` → migrasi, kunci lisensi, akun admin → cache → vhost nginx + rate limit → SSL Let's Encrypt → cron scheduler & cadangan harian 02:30 → pemeriksaan akhir.

Di akhir tercetak **LICENSE_PUBKEY**, URL admin, dan `API_BASE` (juga di `/root/spilltoko-deploy-info.txt`). Skrip dijalankan ulang tidak mengganti `APP_KEY`, kunci lisensi, maupun pengaturan dashboard.

## 3. Isi konfigurasi di dashboard
Masuk `https://spill.delova.id/admin` → **Pengaturan**:
1. **Google Login** — buat OAuth client (Web application) di Google Cloud Console; tempel Redirect URI yang tertera di halaman → isi Client ID → Simpan → *Tes Google*.
2. **Midtrans** — Server Key (mulai dari Sandbox), salin *Notification URL* & *Finish URL* dari halaman itu ke dashboard Midtrans → *Tes Midtrans*. Production: nyalakan **Mode Production** + Server Key production.
3. **AI (kenari.id)** — API key → Simpan → *Tes koneksi* → *Muat daftar model* → pilih model; atur kuota/cache. Prompt bisa disesuaikan di tab **Prompt AI**.
4. **Extension** — salin `API_BASE` dan `LICENSE_PUBKEY`; isi **ID extension** (dari Chrome Web Store setelah publish) agar Redirect URI Google yang ditampilkan benar.

## 4. Build extension
```powershell
cd "...\Chrome Extension\spill-toko"
node tools/build.mjs --api https://spill.delova.id/api/index.php --client-id XXXX.apps.googleusercontent.com --pubkey LICENSE_PUBKEY
```

## Operasional
| Perlu | Perintah |
|---|---|
| Update kode | `cd ~/spill-toko-server && git pull --ff-only && sudo bash deploy/update.sh` |
| Cek kesehatan | `APP_URL=https://spill.delova.id bash /var/www/spilltoko/deploy/check.sh` |
| Reset kata sandi admin | `cd /var/www/spilltoko && sudo -u www-data php artisan spill:admin EMAIL --password='baru-12345'` |
| Cadangan manual | `sudo /var/www/spilltoko/deploy/backup.sh` → `/var/backups/spilltoko/` (14 hari) |
| Restore | `gunzip -c /var/backups/spilltoko/spilltoko-TANGGAL.sql.gz \| mysql spilltoko` |
| Log aplikasi | `tail -f /var/www/spilltoko/storage/logs/laravel-*.log` · `/var/log/nginx/error.log` |

> **Simpan `APP_KEY`** (di `/var/www/spilltoko/.env`) di tempat aman. Kalau hilang, API key/Server Key di Pengaturan tidak bisa didekripsi (isi ulang saja) — tapi kunci lisensi juga ikut tak terbaca dan harus dibuat ulang (extension perlu rilis ulang). Salin cadangan DB ke luar VPS secara berkala.

## Manual (Apache / cPanel / hosting bersama)
Syarat: PHP 8.2+ (`pdo_mysql`, `curl`, `mbstring`, `xml`, `sodium`), MySQL/MariaDB, HTTPS, SSH + Composer.
1. Upload repo di luar document root; arahkan document root/subdomain ke folder `public/`.
2. `composer install --no-dev -o`, `cp .env.example .env`, isi `DB_*` + `APP_URL`, `php artisan key:generate`.
3. `php artisan spill:install --admin=EMAIL` lalu `php artisan config:cache route:cache view:cache`.
4. Izin tulis untuk `storage/` dan `bootstrap/cache/`. Cron: `* * * * * php /path/artisan schedule:run`.
5. `public/.htaccess` bawaan Laravel sudah meneruskan header `Authorization`.

## Troubleshooting
| Gejala | Penyebab umum |
|---|---|
| `certbot gagal` | A record belum mengarah ke VPS / port 80 tertutup (`ufw allow 80,443/tcp`) |
| Login Google `redirect_uri_mismatch` | ID extension di Redirect URI Google ≠ ID yang dipakai extension |
| "Login Google belum dikonfigurasi" | Client ID belum diisi di Pengaturan → Google Login |
| Bayar sukses tapi tidak aktif | Notification URL Midtrans salah, atau Server Key beda mode (sandbox/production) |
| "Layanan AI sedang bermasalah" | API key salah / saldo kenari habis — sebab tepatnya ada di Admin → Pemakaian AI → Log |
| Halaman 500 | `storage/` tidak writable oleh `www-data`, atau `.env` rusak; lihat `storage/logs` |
| Banyak `429` | Rate limit nginx terlalu ketat untuk jaringan kantor; naikkan `rate=` di `/etc/nginx/conf.d/spilltoko-ratelimit.conf` |
