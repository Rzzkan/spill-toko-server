#!/usr/bin/env bash
# Pemeriksaan pasca-deploy:  APP_URL=https://spill.delova.id bash deploy/check.sh
APP_URL="${APP_URL:?isi APP_URL}"
ok=0; bad=0
t() { local name="$1" want="$2" got="$3"; if [ "$got" = "$want" ]; then printf '  ✓ %s\n' "$name"; ok=$((ok+1)); else printf '  ✗ %s (harapan %s, dapat %s)\n' "$name" "$want" "$got"; bad=$((bad+1)); fi; }
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$@"; }
echo "Memeriksa $APP_URL"
t "Halaman depan"               200 "$(code "$APP_URL/")"
t "API plans"                   200 "$(code "$APP_URL/api/index.php?r=plans")"
t "API butuh login (me)"        401 "$(code "$APP_URL/api/index.php?r=me")"
t "Admin: halaman login"        200 "$(code "$APP_URL/admin/login")"
t "Admin: tertutup tanpa login" 302 "$(code "$APP_URL/admin")"
t "Webhook tolak tanpa tanda"   403 "$(code -XPOST -H 'Content-Type: application/json' -d '{}' "$APP_URL/api/index.php?r=midtrans/notify")"
t ".env tidak terbuka"          403 "$(code "$APP_URL/.env")"
t "artisan tidak terbuka"       404 "$(code "$APP_URL/artisan")"
t "storage/ tidak terbuka"      404 "$(code "$APP_URL/storage/logs/laravel.log")"
echo "Hasil: $ok lulus, $bad gagal"
[ "$bad" -eq 0 ]
