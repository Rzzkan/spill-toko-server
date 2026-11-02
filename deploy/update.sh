#!/usr/bin/env bash
# Memperbarui aplikasi di server dari repo git (dijalankan sebagai root di folder repo spill-toko-server):
#   cd ~/spill-toko-server && git pull --ff-only && sudo bash deploy/update.sh
# config.php, kunci lisensi, dan data tidak berubah. Skema DB hanya menambah tabel yang belum ada.
set -euo pipefail
APP_DIR="${APP_DIR:-/var/www/spilltoko}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[ -f "$APP_DIR/config.php" ] || { echo "config.php tidak ada di $APP_DIR, jalankan setup.sh dulu" >&2; exit 1; }
DB_NAME="$(php -r '$c=require $argv[1]; preg_match("/dbname=([^;]+)/",$c["db"]["dsn"],$m); echo $m[1];' "$APP_DIR/config.php")"
rsync -a --delete --exclude 'config.php' --exclude '.git' --exclude 'deploy' --exclude 'tools/*.pem' "$SRC/" "$APP_DIR/"
cp "$SRC"/deploy/*.sh "$SRC"/deploy/make-config.php "$APP_DIR/deploy/" && chmod +x "$APP_DIR"/deploy/*.sh
mysql "$DB_NAME" < "$APP_DIR/schema.sql"
chown -R root:www-data "$APP_DIR"; chmod 640 "$APP_DIR/config.php"
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
systemctl reload "php${PHPV}-fpm"
echo "Update selesai ($(date '+%F %T'))."
