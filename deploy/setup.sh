#!/usr/bin/env bash
# Penyiapan TemanSeller (Laravel + TailAdmin) di VPS Ubuntu/Debian: nginx + PHP-FPM + MariaDB + SSL Let's Encrypt.
# Aman dijalankan ulang (idempoten): tidak mengubah situs lain, tidak mengganti APP_KEY / kunci lisensi yang sudah ada.
#
# Pemakaian (sebagai root):
#   DOMAIN=spill.delova.id EMAIL=kamu@gmail.com ADMIN_EMAIL=kamu@gmail.com ADMIN_PASS='password-admin' bash deploy/setup.sh
# Opsional: APP_DIR, DB_NAME, DB_USER, SKIP_SSL=1
# Google / Midtrans / kenari.id TIDAK diisi di sini — semuanya diatur dari dashboard admin (Pengaturan).
set -euo pipefail

DOMAIN="${DOMAIN:?isi DOMAIN, mis. DOMAIN=spill.delova.id}"
EMAIL="${EMAIL:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-$EMAIL}"
APP_DIR="${APP_DIR:-/var/www/spilltoko}"
DB_NAME="${DB_NAME:-spilltoko}"
DB_USER="${DB_USER:-spilltoko}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
say() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
die() { printf '\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "jalankan sebagai root (sudo -i)"
[[ "$DOMAIN" =~ ^[a-zA-Z0-9.-]+$ ]] || die "DOMAIN tidak valid"
[[ "$DB_NAME$DB_USER" =~ ^[a-zA-Z0-9_]+$ ]] || die "DB_NAME/DB_USER hanya boleh huruf, angka, underscore"
[ -f "$SRC/artisan" ] || die "jalankan dari folder repo (file artisan tidak ditemukan)"
[ "${SKIP_SSL:-0}" = "1" ] || [ -n "$EMAIL" ] || die "isi EMAIL untuk sertifikat SSL (atau SKIP_SSL=1)"

say "Memeriksa web server di port 80"
LISTENER="$(ss -ltnp 2>/dev/null | awk '$4 ~ /:80$/ {print $NF}' | head -1 || true)"
if [ -n "$LISTENER" ] && ! grep -q nginx <<<"$LISTENER"; then
  die "port 80 dipakai bukan nginx ($LISTENER). Pakai panduan manual di deploy/README.md agar situs lain tidak terganggu."
fi

say "Memasang paket yang belum ada"
export DEBIAN_FRONTEND=noninteractive
NEED=()
for p in nginx mariadb-server php-fpm php-cli php-mysql php-curl php-mbstring php-xml php-zip unzip rsync curl openssl; do dpkg -s "$p" >/dev/null 2>&1 || NEED+=("$p"); done
[ "${SKIP_SSL:-0}" = "1" ] || for p in certbot python3-certbot-nginx; do dpkg -s "$p" >/dev/null 2>&1 || NEED+=("$p"); done
if [ ${#NEED[@]} -gt 0 ]; then apt-get update -qq && apt-get install -y -qq "${NEED[@]}"; fi
systemctl enable --now mariadb nginx >/dev/null 2>&1 || true

PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);' || die "PHP $PHPV terlalu lama, Laravel 12 butuh PHP 8.2+ (Ubuntu 24.04 membawa 8.3)"
php -m | grep -qi sodium || die "ekstensi PHP sodium tidak ada"
PHP_SOCK="/run/php/php${PHPV}-fpm.sock"
systemctl enable --now "php${PHPV}-fpm" >/dev/null 2>&1 || true
[ -S "$PHP_SOCK" ] || die "socket PHP-FPM tidak ditemukan ($PHP_SOCK)"

if ! command -v composer >/dev/null 2>&1; then
  say "Memasang Composer"
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php && php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer && rm -f /tmp/composer-setup.php \
    || die "gagal memasang composer (cek koneksi ke getcomposer.org)"
fi

say "Menyalin aplikasi ke $APP_DIR"
mkdir -p "$APP_DIR"
rsync -a --delete --exclude '.env' --exclude '.git' --exclude 'storage/' --exclude 'vendor/' --exclude 'node_modules/' --exclude 'tests/' "$SRC/" "$APP_DIR/"
mkdir -p "$APP_DIR"/storage/{app/public,framework/{cache/data,sessions,views},logs} "$APP_DIR/bootstrap/cache"

say "Database"
if [ -f "$APP_DIR/.env" ]; then
  DB_PASS="$(grep -E '^DB_PASSWORD=' "$APP_DIR/.env" | head -1 | cut -d= -f2-)"
else
  DB_PASS="$(openssl rand -hex 16)"
fi
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
          CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

say "Membuat .env (hanya koneksi database & APP_KEY)"
SCHEME=https; [ "${SKIP_SSL:-0}" = "1" ] && SCHEME=http
NEWENV=0
if [ ! -f "$APP_DIR/.env" ]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"; NEWENV=1
  sed -i -e "s#^APP_URL=.*#APP_URL=$SCHEME://$DOMAIN#" -e "s#^DB_DATABASE=.*#DB_DATABASE=$DB_NAME#" \
         -e "s#^DB_USERNAME=.*#DB_USERNAME=$DB_USER#" -e "s#^DB_PASSWORD=.*#DB_PASSWORD=$DB_PASS#" "$APP_DIR/.env"
fi

say "Memasang dependensi (composer, tanpa paket dev)"
cd "$APP_DIR"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress
[ "$NEWENV" = "1" ] && php artisan key:generate --force --no-interaction

say "Migrasi database, kunci lisensi, admin"
ADMIN_GENERATED=""
ADMIN_ARGS=()
if [ -n "$ADMIN_EMAIL" ] && [[ "$ADMIN_EMAIL" == *@* ]]; then
  if [ -z "${ADMIN_PASS:-}" ] && [ "$NEWENV" = "1" ]; then ADMIN_PASS="$(openssl rand -base64 12 | tr -d '/+=')"; ADMIN_GENERATED=1; fi
  ADMIN_ARGS=(--admin="$ADMIN_EMAIL")
fi
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction   # admin awal: username rzzkan
PUBKEY="$(php artisan tinker --execute='echo App\Services\License::publicKey();' 2>/dev/null | tail -1)"
if [ ${#ADMIN_ARGS[@]} -gt 0 ] && [ -n "${ADMIN_PASS:-}" ]; then
  php artisan temanseller:admin "$ADMIN_EMAIL" --name=Admin --password="$ADMIN_PASS" >/dev/null
fi

say "Cache & izin"
php artisan optimize:clear >/dev/null
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R root:www-data "$APP_DIR"; chmod 640 "$APP_DIR/.env"
find "$APP_DIR" -type d -exec chmod 750 {} +
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"; chmod -R u+rwX,g+rX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod +x "$APP_DIR"/deploy/*.sh

say "Konfigurasi nginx"
cp "$SRC/deploy/nginx-ratelimit.conf" /etc/nginx/conf.d/spilltoko-ratelimit.conf
LISTEN6=""; [ -e /proc/net/if_inet6 ] && LISTEN6="listen [::]:80;"   # VPS tanpa IPv6 akan gagal bila baris ini ada
sed -e "s#__LISTEN6__#$LISTEN6#g" -e "s#__DOMAIN__#$DOMAIN#g" -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__PHP_SOCK__#$PHP_SOCK#g" "$SRC/deploy/nginx.conf.template" > "/etc/nginx/sites-available/spilltoko-$DOMAIN"
ln -sf "/etc/nginx/sites-available/spilltoko-$DOMAIN" "/etc/nginx/sites-enabled/spilltoko-$DOMAIN"
nginx -t
systemctl reload nginx

if [ "${SKIP_SSL:-0}" != "1" ]; then
  say "SSL Let's Encrypt (pastikan DNS A record $DOMAIN sudah mengarah ke IP VPS ini)"
  certbot --nginx -d "$DOMAIN" -m "$EMAIL" --agree-tos --no-eff-email --non-interactive --redirect \
    || die "certbot gagal. Cek DNS $DOMAIN lalu jalankan ulang skrip ini."
fi

say "Scheduler Laravel & cadangan harian"
cat > /etc/cron.d/spilltoko <<CRON
* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1
30 2 * * * root $APP_DIR/deploy/backup.sh >> /var/log/spilltoko-backup.log 2>&1
CRON
chmod 644 /etc/cron.d/spilltoko

say "Pemeriksaan akhir"
APP_URL="$SCHEME://$DOMAIN" bash "$APP_DIR/deploy/check.sh" || true

cat <<INFO

======================= SELESAI =======================
Admin            : $SCHEME://$DOMAIN/admin   (username awal: rzzkan — segera ganti kata sandi di Profil Admin)${ADMIN_EMAIL:+; admin tambahan: $ADMIN_EMAIL${ADMIN_GENERATED:+ / password: $ADMIN_PASS}}
LICENSE_PUBKEY   : $PUBKEY      (isi ke extension, aman dipublikasikan)
Extension -> API_BASE: $SCHEME://$DOMAIN/api/index.php

Langkah berikut ada di dashboard admin → Pengaturan:
  • Google Login  → Client ID + Redirect URI yang ditampilkan di halaman itu
  • Midtrans      → Server Key + Notification URL
  • AI (kenari.id)→ API key → Tes koneksi → pilih model
INFO
{ echo "LICENSE_PUBKEY=$PUBKEY"; echo "DOMAIN=$DOMAIN"; echo "APP_DIR=$APP_DIR"; } > /root/spilltoko-deploy-info.txt; chmod 600 /root/spilltoko-deploy-info.txt
