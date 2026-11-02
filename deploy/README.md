# Deploy Spill Toko ke VPS (Ubuntu/Debian)

Paket ini memasang backend di **subdomain baru** pada VPS yang sama dengan situs Delova lain (mis. `spill.delova.id`, di samping `agent.` dan `threads.`). Skrip hanya **menambah** satu vhost nginx — situs lain tidak diubah. Hasil uji: nginx + PHP-FPM 8.3 + MariaDB, 7 pemeriksaan keamanan lulus.

> Nama subdomain `spill.delova.id` hanya contoh; ganti sesuka hati.

## Prasyarat
- VPS Ubuntu 22.04/24.04 atau Debian 12, akses root (`sudo -i`), **nginx** sebagai web server di port 80/443.
  Kalau VPS memakai Apache/aaPanel/CloudPanel/cPanel, lihat bagian **Manual** di bawah.
- Satu A record: `spill` → IP VPS (di Cloudflare boleh proxied/oranye).
- Akun Google Cloud, akun Midtrans, API key kenari.id.

## 1. Taruh kode di VPS
```bash
# opsi A: dari GitHub (repo privat butuh token/deploy key)
git clone https://github.com/Rzzkan/spill-toko-server.git ~/spill-toko-server
# opsi B: dari komputer lokal (PowerShell)
scp -r "C:\Users\Aditya Nur Riskan N\Delova\Delova Wardrobe\Chrome Extension\spill-toko-server" root@IP-VPS:~/
```

## 2. Jalankan setup (sekali; aman diulang)
```bash
cd ~/spill-toko-server
DOMAIN=spill.delova.id EMAIL=emailmu@gmail.com ADMIN_PASS='password-admin-kuat' bash deploy/setup.sh
```
Skrip akan: memasang paket yang belum ada → membuat database & user (password acak) → menyalin app ke `/var/www/spilltoko` → membuat `config.php` (kunci lisensi Ed25519 + JWT secret acak) → vhost nginx + rate limit → SSL Let's Encrypt → cadangan harian jam 02:30 → pemeriksaan akhir.

Di akhir tercetak **LICENSE_PUBKEY**, URL admin, Notification URL Midtrans, dan `API_BASE`. Catat (juga tersimpan di `/root/spilltoko-deploy-info.txt`).

> Kunci lisensi **tidak akan berubah** saat skrip dijalankan ulang. Jangan hapus `config.php` — kalau kunci berganti, public key di semua extension yang sudah terpasang tidak cocok lagi.

## 3. Google login
1. console.cloud.google.com → OAuth consent screen (External) → buat **OAuth client ID** tipe *Web application*.
2. Authorized redirect URI: `https://<ID-EXTENSION>.chromiumapp.org/`
   - Saat uji (unpacked): ID = `ecngchlgljjgclfjhljppkmmoamdfpjl`.
   - Setelah rilis Web Store: ID baru dari Web Store (field `key` dihapus saat build).
3. Isi ke server (jalankan ulang setup, nilai lain dipertahankan):
```bash
GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com DOMAIN=spill.delova.id EMAIL=emailmu@gmail.com bash deploy/setup.sh
```

## 4. Midtrans
1. Mulai di **Sandbox**: ambil Server Key → `MIDTRANS_SERVER_KEY=SB-Mid-server-... DOMAIN=... EMAIL=... bash deploy/setup.sh`
2. Dashboard Midtrans → Settings → Payment Notification URL: `https://spill.delova.id/api/index.php?r=midtrans/notify`
3. Uji satu pembayaran sandbox sampai akun ter-upgrade otomatis.
4. Production: pakai Server Key production + `MIDTRANS_PROD=1`.

## 5. AI kenari.id
Buka `https://spill.delova.id/admin/` → **Pengaturan → Analisis AI**: isi API key → Simpan → **Tes koneksi** → **Muat daftar model** → pilih model. Atur kuota harian & cache di halaman yang sama.

## 6. Build extension
Di komputer Windows (Node 18+):
```powershell
cd "C:\Users\Aditya Nur Riskan N\Delova\Delova Wardrobe\Chrome Extension\spill-toko"
node tools/build.mjs --api https://spill.delova.id/api/index.php --client-id XXXX.apps.googleusercontent.com --pubkey LICENSE_PUBKEY
```
Hasil: `dist/spill-toko-1.0.0.zip` → upload ke Chrome Web Store. Untuk uji lokal, cukup edit `src/config.js` dengan tiga nilai yang sama dan "Load unpacked".

## 7. Checklist go-live
- [ ] `bash deploy/check.sh` semua ✓ (`APP_URL=https://spill.delova.id bash deploy/check.sh`)
- [ ] Login Google dari extension → trial 7 hari aktif
- [ ] Bayar Bulanan di Midtrans **sandbox** → status Aktif otomatis
- [ ] Admin: Tes koneksi AI ✓, satu analisis di halaman produk berhasil
- [ ] Ganti Midtrans ke production + Notification URL production
- [ ] Simpan `extension-key.pem` & `/var/www/spilltoko/config.php` di tempat aman (password manager / backup terenkripsi)

## Operasional
| Perlu | Perintah |
|---|---|
| Update kode | `cd ~/spill-toko-server && git pull --ff-only && sudo bash deploy/update.sh` |
| Cek kesehatan | `APP_URL=https://spill.delova.id bash /var/www/spilltoko/deploy/check.sh` |
| Cadangan manual | `sudo /var/www/spilltoko/deploy/backup.sh` → `/var/backups/spilltoko/` (14 hari) |
| Restore | `gunzip -c /var/backups/spilltoko/spilltoko-TANGGAL.sql.gz \| mysql spilltoko` |
| Log error PHP/nginx | `tail -f /var/log/nginx/error.log` |
| Ubah rate limit | edit `/etc/nginx/conf.d/spilltoko-ratelimit.conf` (default 60/menit per IP) → `nginx -t && systemctl reload nginx` |

Salin juga cadangan DB ke luar VPS (mis. `rclone`/`scp` mingguan); cadangan di disk yang sama tidak melindungi dari VPS rusak.

## Manual (Apache / cPanel / hosting bersama)
Syarat: PHP 8.1+ dengan `pdo_mysql`, `curl`, `sodium`, `mbstring`; MySQL/MariaDB; HTTPS.
1. Buat database + user di panel, impor `schema.sql`.
2. Upload isi repo **di luar** document root; arahkan document root (atau subdomain) ke folder `public/`. `config.php`, `src/`, `tools/` harus berada di luar `public/`.
3. Buat `config.php`: `DB_NAME=... DB_USER=... DB_PASS=... APP_URL=https://... ADMIN_PASS=... php deploy/make-config.php /path/config.php` (baris terakhir output = LICENSE_PUBKEY).
4. `public/.htaccess` sudah menyertakan aturan agar header `Authorization` sampai ke PHP; kalau admin/login minta ulang terus, pastikan `mod_rewrite` aktif.
5. Ikuti langkah 3–7 di atas. Tambahkan cron `backup.sh` bila ada akses shell.

## Troubleshooting
| Gejala | Penyebab umum |
|---|---|
| `certbot gagal` | A record belum mengarah ke VPS / port 80 tertutup firewall (`ufw allow 80,443/tcp`) |
| Login Google: `redirect_uri_mismatch` | ID extension di redirect URI Google berbeda dengan ID yang dipakai |
| `Nonce/Client ID tidak cocok` | `GOOGLE_CLIENT_ID` di server ≠ yang dipakai extension |
| Bayar sukses tapi tidak aktif | Notification URL Midtrans salah, atau Server Key berbeda mode (sandbox vs production); lihat log nginx |
| Admin: `Unauthorized` terus | `ADMIN_PASS` belum diisi saat setup; jalankan ulang setup dengan `ADMIN_PASS=...` |
| AI: "Layanan AI sedang bermasalah" | API key salah/saldo kenari habis — lihat sebabnya di Admin → Pemakaian AI → Log |
| Banyak `429` | Rate limit nginx terlalu ketat untuk jaringan kantor (banyak pengguna 1 IP); naikkan `rate=` |
