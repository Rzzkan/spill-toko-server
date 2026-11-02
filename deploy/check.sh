#!/usr/bin/env bash
# Pemeriksaan pasca-deploy:  APP_URL=https://spill.delova.id bash deploy/check.sh
APP_URL="${APP_URL:?isi APP_URL}"
ok=0; bad=0
t() { local name="$1" want="$2" got="$3"; if [ "$got" = "$want" ]; then printf '  ✓ %s\n' "$name"; ok=$((ok+1)); else printf '  ✗ %s (harapan %s, dapat %s)\n' "$name" "$want" "$got"; bad=$((bad+1)); fi; }
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$@"; }
echo "Memeriksa $APP_URL"
t "API plans"                   200 "$(code "$APP_URL/api/index.php?r=plans")"
t "API butuh login (me)"        401 "$(code "$APP_URL/api/index.php?r=me")"
t "Admin minta password"        401 "$(code "$APP_URL/admin/")"
t "Webhook tolak tanpa tanda"   403 "$(code -XPOST -d '{}' "$APP_URL/api/index.php?r=midtrans/notify")"
t "config.php tidak terbuka"    404 "$(code "$APP_URL/config.php")"
t "src/ tidak terbuka"          404 "$(code "$APP_URL/src/bootstrap.php")"
t "schema.sql tidak terbuka"    404 "$(code "$APP_URL/schema.sql")"
echo "Hasil: $ok lulus, $bad gagal"
[ "$bad" -eq 0 ]
