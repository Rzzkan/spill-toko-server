#!/usr/bin/env bash
# Perbarui aplikasi dari repo git (sebagai root di folder repo):
#   cd ~/spill-toko-server && git pull --ff-only && sudo bash deploy/update.sh
# .env, APP_KEY, kunci lisensi, dan data (termasuk semua pengaturan dashboard) tidak berubah.
set -euo pipefail
APP_DIR="${APP_DIR:-/var/www/spilltoko}"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[ -f "$APP_DIR/.env" ] || { echo ".env tidak ada di $APP_DIR, jalankan setup.sh dulu" >&2; exit 1; }
rsync -a --delete --exclude '.env' --exclude '.git' --exclude 'storage/' --exclude 'vendor/' --exclude 'node_modules/' --exclude 'tests/' "$SRC/" "$APP_DIR/"
cd "$APP_DIR"
php artisan down --retry=10 >/dev/null 2>&1 || true
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress
php artisan migrate --force --no-interaction
php artisan optimize:clear >/dev/null
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R root:www-data "$APP_DIR"; chmod 640 "$APP_DIR/.env"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod +x "$APP_DIR"/deploy/*.sh
php artisan up >/dev/null 2>&1 || true
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
systemctl reload "php${PHPV}-fpm"
echo "Update selesai ($(date '+%F %T'))."
