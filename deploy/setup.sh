#!/usr/bin/env bash
# Penyiapan server Spill Toko di VPS Ubuntu/Debian (nginx + PHP-FPM + MariaDB + SSL Let's Encrypt).
# Aman dijalankan ulang (idempoten): tidak mengubah situs lain, tidak mengganti kunci lisensi yang sudah ada.
#
# Pemakaian (sebagai root):
#   DOMAIN=spill.delova.id EMAIL=kamu@gmail.com ADMIN_PASS='password-admin' bash deploy/setup.sh
# Opsional: GOOGLE_CLIENT_ID, MIDTRANS_SERVER_KEY, MIDTRANS_PROD=1, APP_DIR, DB_NAME, DB_USER, SKIP_SSL=1, CHROME_STORE_URL
set -euo pipefail

DOMAIN="${DOMAIN:?isi DOMAIN, mis. DOMAIN=spill.delova.id}"
EMAIL="${EMAIL:-}"
APP_DIR="${APP_DIR:-/var/www/spilltoko}"
DB_NAME="${DB_NAME:-spilltoko}"
DB_USER="${DB_USER:-spilltoko}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
say() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
die() { printf '\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "jalankan sebagai root (sudo -i)"
[[ "$DOMAIN" =~ ^[a-zA-Z0-9.-]+$ ]] || die "DOMAIN tidak valid"
[[ "$DB_NAME$DB_USER" =~ ^[a-zA-Z0-9_]+$ ]] || die "DB_NAME/DB_USER hanya boleh huruf, angka, underscore"
[ -f "$SRC/schema.sql" ] || die "jalankan dari folder spill-toko-server (schema.sql tidak ditemukan)"
[ "${SKIP_SSL:-0}" = "1" ] || [ -n "$EMAIL" ] || die "isi EMAIL untuk sertifikat SSL (atau SKIP_SSL=1)"

say "Memeriksa web server di port 80"
LISTENER="$(ss -ltnp 2>/dev/null | awk '$4 ~ /:80$/ {print $NF}' | head -1 || true)"
if [ -n "$LISTENER" ] && ! grep -q nginx <<<"$LISTENER"; then
  die "port 80 dipakai bukan nginx ($LISTENER). Pakai panduan manual Apache/cPanel di deploy/README.md agar situs lain tidak terganggu."
fi

say "Memasang paket yang belum ada"
export DEBIAN_FRONTEND=noninteractive
NEED=()
for p in nginx mariadb-server php-fpm php-mysql php-curl php-mbstring rsync curl; do dpkg -s "$p" >/dev/null 2>&1 || NEED+=("$p"); done
[ "${SKIP_SSL:-0}" = "1" ] || for p in certbot python3-certbot-nginx; do dpkg -s "$p" >/dev/null 2>&1 || NEED+=("$p"); done
if [ ${#NEED[@]} -gt 0 ]; then apt-get update -qq && apt-get install -y -qq "${NEED[@]}"; fi
systemctl enable --now mariadb nginx >/dev/null 2>&1 || true

PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION,"8.1.0",">=")?0:1);' || die "PHP $PHPV terlalu lama, butuh 8.1+"
php -m | grep -qi sodium || die "ekstensi PHP sodium tidak ada"
PHP_SOCK="/run/php/php${PHPV}-fpm.sock"
systemctl enable --now "php${PHPV}-fpm" >/dev/null 2>&1 || true
[ -S "$PHP_SOCK" ] || die "socket PHP-FPM tidak ditemukan ($PHP_SOCK)"

say "Menyalin aplikasi ke $APP_DIR"
mkdir -p "$APP_DIR"
rsync -a --delete --exclude 'config.php' --exclude '.git' --exclude 'deploy' --exclude 'tools/*.pem' "$SRC/" "$APP_DIR/"
mkdir -p "$APP_DIR/deploy" && cp "$SRC"/deploy/*.sh "$SRC"/deploy/make-config.php "$APP_DIR/deploy/" && chmod +x "$APP_DIR"/deploy/*.sh

say "Database"
if [ -f "$APP_DIR/config.php" ]; then
  DB_PASS="$(php -r '$c=require $argv[1]; echo $c["db"]["pass"];' "$APP_DIR/config.php")"
else
  DB_PASS="$(openssl rand -hex 16)"
fi
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4;
          CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"
mysql "$DB_NAME" < "$APP_DIR/schema.sql"

say "Membuat config.php"
SCHEME=https; [ "${SKIP_SSL:-0}" = "1" ] && SCHEME=http
ADMIN_GENERATED=""
if [ ! -f "$APP_DIR/config.php" ] && [ -z "${ADMIN_PASS:-}" ]; then ADMIN_PASS="$(openssl rand -base64 12 | tr -d '/+=')"; ADMIN_GENERATED=1; fi
PUBKEY="$(APP_URL="$SCHEME://$DOMAIN" DB_NAME="$DB_NAME" DB_USER="$DB_USER" DB_PASS="$DB_PASS" ADMIN_PASS="${ADMIN_PASS:-}" \
  php "$APP_DIR/deploy/make-config.php" "$APP_DIR/config.php" | tail -1)"
chown -R root:www-data "$APP_DIR"; chmod 640 "$APP_DIR/config.php"; find "$APP_DIR" -type d -exec chmod 750 {} +

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

say "Cadangan harian & pembersihan"
cat > /etc/cron.d/spilltoko <<CRON
30 2 * * * root $APP_DIR/deploy/backup.sh >> /var/log/spilltoko-backup.log 2>&1
CRON
chmod 644 /etc/cron.d/spilltoko
sed -i "s#^DB_NAME=.*#DB_NAME=$DB_NAME#" "$APP_DIR/deploy/backup.sh"

say "Pemeriksaan akhir"
APP_URL="$SCHEME://$DOMAIN" bash "$APP_DIR/deploy/check.sh" || true

cat <<INFO

======================= SELESAI =======================
URL situs        : $SCHEME://$DOMAIN
Admin            : $SCHEME://$DOMAIN/admin/   (user: admin${ADMIN_GENERATED:+, password: $ADMIN_PASS  <-- catat, hanya tampil sekali})
LICENSE_PUBKEY   : $PUBKEY      (isi ke extension, aman dipublikasikan)
Redirect URI Google (Authorized redirect URIs):
   https://<ID-EXTENSION>.chromiumapp.org/
Notification URL Midtrans:
   $SCHEME://$DOMAIN/api/index.php?r=midtrans/notify
Extension -> API_BASE: $SCHEME://$DOMAIN/api/index.php

Lanjut: isi Google Client ID & Midtrans Server Key lewat ulang skrip ini
   (GOOGLE_CLIENT_ID=... MIDTRANS_SERVER_KEY=... DOMAIN=$DOMAIN EMAIL=$EMAIL bash deploy/setup.sh)
 lalu API key kenari.id di Admin > Pengaturan > Analisis AI.
INFO
{ echo "LICENSE_PUBKEY=$PUBKEY"; echo "DOMAIN=$DOMAIN"; echo "APP_DIR=$APP_DIR"; } > /root/spilltoko-deploy-info.txt; chmod 600 /root/spilltoko-deploy-info.txt
