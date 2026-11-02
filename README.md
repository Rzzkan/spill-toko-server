# Spill Toko — Backend SaaS (PHP + MySQL)

Backend langganan untuk extension Spill Toko: login Google, trial 7 hari, paket **Bulanan Rp25.000 / 30 hari** dan **Lifetime Rp200.000**, pembayaran **Midtrans**, **analisis AI lewat kenari.id**, dan halaman admin (harga, trial, kuota, dan konfigurasi AI diubah dari web).

Tanpa framework — cukup PHP 8.1+ (ekstensi `pdo_mysql`, `curl`, `sodium`) dan MySQL/MariaDB. Jalan di shared hosting (cPanel) maupun VPS.

## Deploy cepat
Untuk VPS Ubuntu/Debian dengan nginx: `DOMAIN=spill.delova.id EMAIL=kamu@gmail.com ADMIN_PASS='…' bash deploy/setup.sh` — panduan lengkap (DNS, Google, Midtrans, build extension, update, backup) ada di [`deploy/README.md`](deploy/README.md).

## Struktur
```
config.php            ← buat sendiri dari config.sample.php (di luar folder public/!)
schema.sql            ← tabel users, orders, admin_log, settings, ai_log, ai_cache (aman dijalankan ulang)
src/bootstrap.php     ← helper, JWT, verifikasi Google, lisensi bertanda tangan, Midtrans, tabel settings
src/ai.php            ← integrasi AI: prompt, kuota, cache, panggilan ke kenari.id
public/               ← ini yang jadi document root (public_html)
  index.php           ← landing page + harga
  thanks.php          ← halaman setelah bayar
  api/index.php       ← API (dipanggil extension & webhook Midtrans)
  admin/index.php     ← dashboard admin (HTTP Basic Auth)
tools/keygen.php      ← generate kunci lisensi & JWT secret (setup.sh melakukannya otomatis)
deploy/               ← setup.sh, update.sh, backup.sh, check.sh, template nginx, panduan deploy
tools/extension-key.pem ← kunci ID extension (JANGAN dimasukkan ke paket extension)
```

## Cara kerja singkat
1. Extension → **Masuk dengan Google** → mendapat `id_token` → `POST /api/index.php?r=auth`. Server memverifikasi token (aud, iss, exp, nonce, email_verified), membuat akun, dan mengaktifkan **trial 7 hari** sekali saja per akun Google.
2. Server mengembalikan **lisensi bertanda tangan Ed25519** (berlaku maks. 72 jam, atau sampai masa aktif habis). Extension memverifikasinya dengan public key, jadi data lisensi di storage tidak bisa diedit begitu saja, dan extension tetap jalan offline sampai 72 jam.
3. Pilih paket → `POST ?r=checkout` → server membuat transaksi Midtrans Snap → extension membuka halaman bayar Midtrans (QRIS, VA, e-wallet, kartu).
4. Setelah bayar, Midtrans memanggil `?r=midtrans/notify`. Server memeriksa **signature**, **memverifikasi ulang ke API Midtrans**, mencocokkan **nominal**, lalu mengaktifkan paket (idempoten — notifikasi ganda tidak menambah masa aktif dua kali). Extension mengecek tiap 8 detik dan aktif otomatis.
5. Bulanan = **prabayar**: membeli lagi menambah 30 hari dari sisa masa aktif. Tidak ada auto-debit (Midtrans Subscription butuh tokenisasi kartu/GoPay dan persetujuan khusus; bisa ditambah nanti).

## Setup (urut)

### 1. Database
```sql
CREATE DATABASE spilltoko CHARACTER SET utf8mb4;
CREATE USER 'spilltoko'@'localhost' IDENTIFIED BY 'PASSWORD-KUAT';
GRANT ALL ON spilltoko.* TO 'spilltoko'@'localhost';
```
```bash
mysql -u spilltoko -p spilltoko < schema.sql
```

### 2. Kunci
```bash
php tools/keygen.php
```
- `license_sign_key` dan `jwt_secret` → ke `config.php` (**rahasia, hanya di server**)
- `LICENSE_PUBKEY` → ke extension `src/config.js` (aman dipublikasikan)

### 3. Google Cloud Console (login Google)
1. https://console.cloud.google.com → buat project → **APIs & Services → OAuth consent screen** (External, isi nama app & email).
2. **Credentials → Create credentials → OAuth client ID → tipe “Web application”**.
3. **Authorized redirect URIs** tambahkan:
   - `https://ecngchlgljjgclfjhljppkmmoamdfpjl.chromiumapp.org/` ← ID extension saat dev (dari `key` di manifest)
   - `https://<ID-DARI-CHROME-WEB-STORE>.chromiumapp.org/` ← setelah dipublikasikan (ID-nya akan berbeda)
4. Salin **Client ID** → `config.php` (`google_client_id`) dan extension `src/config.js` (`GOOGLE_CLIENT_ID`).

### 4. Midtrans
1. Daftar di https://dashboard.midtrans.com, mulai dengan **Sandbox**.
2. **Settings → Access Keys** → salin **Server Key** → `config.php` (`midtrans.server_key`).
3. **Settings → Configuration → Payment Notification URL**:
   `https://DOMAINMU/api/index.php?r=midtrans/notify`
4. Uji di sandbox, lalu ganti ke Server Key production dan set `'is_production' => true`.

### 5. Config & deploy
```bash
cp config.sample.php config.php   # lalu isi semua nilai
php -r "echo password_hash('PASSWORD-ADMIN', PASSWORD_DEFAULT);"   # tempel ke admin.pass_hash
```
Arahkan document root domain ke folder `public/`. Pastikan `config.php`, `src/`, dan `tools/` berada **di luar** document root. Wajib HTTPS.

### 6. Extension
Isi `src/config.js`:
```js
API_BASE: 'https://DOMAINMU/api/index.php',
GOOGLE_CLIENT_ID: '…apps.googleusercontent.com',
LICENSE_PUBKEY: '…dari keygen…',
```
Selama tiga nilai itu kosong, lisensi **nonaktif** dan semua fitur terbuka (mode pengembangan).

### 7. Publikasi di Chrome Web Store
- Hapus field `"key"` dari `manifest.json` sebelum upload (Web Store memberi ID sendiri), lalu daftarkan redirect URI ID baru itu di Google Cloud (langkah 3).
- Update extension (termasuk untuk pelanggan Lifetime) otomatis lewat Web Store.

## Analisis AI (kenari.id)
Data produk/toko/pencarian yang di-scrape extension dikirim ke server (`POST ?r=ai/analyze`), server menyusun prompt lalu memanggil **kenari.id** (`/v1/chat/completions`, format OpenAI). **API key hanya ada di server** (tersimpan terenkripsi di tabel `settings`), tidak pernah dikirim ke extension.

**Alur:** extension kirim data → server cek langganan aktif → cek cache (data identik = jawaban tersimpan, **tidak memakai kuota**) → cek laju & kuota harian → panggil model utama (lalu model cadangan bila gagal) → rapikan JSON → simpan cache → kirim ke extension, tampil di tab **✨ Analisa AI**.

**Setup (semua dari web):**
1. Buat API key di dashboard kenari.id (format `kn-…`) dan isi saldo / paket.
2. Buka `https://DOMAINMU/admin/?tab=settings` → bagian **Analisis AI**: isi API key, klik **Simpan**, lalu **Tes koneksi**.
3. Klik **Muat daftar model** untuk memilih model (default `deepseek-v4-flash`); isi model cadangan bila perlu.
4. Atur kuota harian per paket (default Trial 20, Bulanan 100, Lifetime 100; 0 = tanpa batas), batas per menit, lama cache, dan apakah analisis jalan **otomatis** setelah scan.
5. Prompt bisa diubah di halaman yang sama (kosong = bawaan). Keluaran AI harus tetap JSON dengan kunci `ringkasan, skor, label, temuan, peluang, risiko, rekomendasi, kata_kunci, saran_judul`.

**Upgrade dari versi sebelumnya:** jalankan ulang `mysql … < schema.sql` (hanya menambah tabel baru), timpa file backend, lalu build/muat ulang extension.

**Catatan:**
- Biaya token dibayar dari saldo kenari.id milikmu. Kuota harian + cache adalah pengaman utama; pantau di tab **Pemakaian AI** (token, error, pengguna teraktif) dan dashboard kenari.
- Data di dalam halaman Shopee diperlakukan sebagai data mentah (prompt menyuruh AI mengabaikan instruksi di dalamnya) dan hasil AI ditampilkan sebagai teks biasa, bukan HTML.
- Pesan teknis (saldo habis, key ditolak) hanya tampil di log admin; pengguna melihat pesan umum.
- Memilih "Paksa mode JSON" mengirim `response_format`; bila model menolaknya, server otomatis mengulang tanpa itu.

## Admin
`https://DOMAINMU/admin/` (Basic Auth) punya empat tab:
- **Pengguna** — filter Trial/Bulanan/Lifetime/Kedaluwarsa, cari email, tambah hari, jadikan lifetime, cabut akses (berguna untuk pembayaran manual/transfer), pemakaian AI per pengguna.
- **Order** — riwayat pembayaran Midtrans.
- **Pengaturan** — lama trial, harga & masa aktif paket (berlaku untuk transaksi berikutnya), dan seluruh konfigurasi AI.
- **Pemakaian AI** — grafik tabel 14 hari, pengguna teraktif, log permintaan terbaru, tombol kosongkan cache.

## Endpoint
| Method | `?r=` | Auth | Fungsi |
|---|---|---|---|
| GET | `plans` | – | daftar paket & harga |
| POST | `auth` | – | tukar Google id_token → sesi + lisensi |
| GET | `me` | Bearer | lisensi terbaru |
| POST | `checkout` | Bearer | buat transaksi Midtrans `{plan}` |
| GET | `orders` | Bearer | riwayat order |
| POST | `ai/analyze` | Bearer | analisis AI `{kind: product\|shop\|search, data, force?}` |
| POST | `midtrans/notify` | signature | webhook Midtrans |

## Catatan keamanan
- Pengecekan lisensi berjalan di sisi extension, jadi orang yang paham bisa mem-patch kode extension. Tanda tangan Ed25519 + masa berlaku 72 jam mencegah pemalsuan data dan pembagian akun secara mudah, tetapi bukan perlindungan mutlak. Perlindungan terkuat adalah memindahkan sebagian nilai ke server (mis. riwayat tren/agregasi pasar) di tahap berikutnya.
- Endpoint `ai/analyze` dilindungi langganan aktif, kuota harian, batas per menit, dan batas ukuran data (60 KB). Tetap pasang rate limiting di Cloudflare/Nginx.
- Jangan commit `config.php` dan `tools/extension-key.pem`.
- Pertimbangkan rate limiting di level web server (Cloudflare/Nginx) untuk `?r=auth` dan `?r=checkout`.
