# Deploy TemanSeller dengan Docker (port 127.0.0.1:3300 -> 3000)

Container web (FrankenPHP/Caddy) + MariaDB. Web hanya dibuka di `127.0.0.1:3300`; domain publik dilayani nginx host (reverse proxy + SSL).

## 1. Ambil kode
```bash
git clone https://github.com/Rzzkan/spill-toko-server.git ~/temanseller && cd ~/temanseller
```

## 2. Siapkan .env
```bash
cp .env.docker.example .env
nano .env        # isi password DB, ADMIN_EMAIL, APP_URL
```

## 3. Buat APP_KEY
```bash
docker compose build
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
# salin hasilnya (base64:...) ke APP_KEY di .env
```

## 4. Jalankan
```bash
docker compose up -d
docker compose logs -f app      # tunggu "migrate" selesai & LICENSE_PUBKEY tercetak
curl -I http://127.0.0.1:3300/robots.txt    # harus 200
```
Saat pertama start, container otomatis: migrasi DB, membuat kunci lisensi + admin awal (`ADMIN_EMAIL`), cache konfigurasi, dan menjalankan scheduler.

## 5. Nginx host (reverse proxy)
`/etc/nginx/sites-available/temanseller.com`:
```nginx
server {
    server_name temanseller.com www.temanseller.com;
    client_max_body_size 10m;
    location / {
        proxy_pass http://127.0.0.1:3300;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }
    listen 80;
}
```
```bash
sudo ln -s /etc/nginx/sites-available/temanseller.com /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d temanseller.com -d www.temanseller.com
```
(DNS: A record `temanseller.com` -> IP VPS lebih dulu. Cloudflare boleh proxied.)

## 6. Ambil LICENSE_PUBKEY & masuk admin
```bash
docker compose exec app php artisan temanseller:install | grep LICENSE_PUBKEY
```
Buka `https://temanseller.com/admin` -> Pengaturan (Google, Midtrans, AI, Affiliate). Isi `LICENSE_PUBKEY` ke `src/config.js` extension.

## Operasional
| Perlu | Perintah |
|---|---|
| Update kode | `cd ~/temanseller && git pull --ff-only && docker compose up -d --build` |
| Log | `docker compose logs -f app` |
| Artisan | `docker compose exec app php artisan <perintah>` |
| Cadangan DB | `docker compose exec db sh -c 'mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' > backup-$(date +%F).sql` |
| Restart | `docker compose restart app` |
| Cek kesehatan | `docker compose ps` (app: healthy) |

Data tersimpan di volume `storage` (kunci lisensi, log) dan `dbdata` (MariaDB) - tidak hilang saat rebuild. Jangan jalankan `docker compose down -v`.
