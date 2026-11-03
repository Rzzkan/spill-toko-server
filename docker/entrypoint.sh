#!/bin/sh
# Persiapan saat container start: tunggu DB, migrasi, pasang instalasi awal (sekali), cache, scheduler, lalu jalankan web server.
set -e
cd /app
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ -z "$APP_KEY" ]; then echo "[temanseller] APP_KEY kosong. Isi di .env (php artisan key:generate --show)." >&2; exit 1; fi

echo "[temanseller] menunggu database ${DB_HOST:-db}:${DB_PORT:-3306} ..."
i=0
until php -r '
try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT")?:3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
  i=$((i+1)); [ "$i" -gt 60 ] && { echo "[temanseller] database tidak siap" >&2; exit 1; }
  sleep 2
done

php artisan migrate --force
if [ ! -f storage/.installed ]; then
  php artisan spill:install ${ADMIN_EMAIL:+--admin="$ADMIN_EMAIL"} && touch storage/.installed
fi
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# scheduler Laravel (pembersihan harian) di latar belakang
php artisan schedule:work >/dev/null 2>&1 &

exec "$@"
