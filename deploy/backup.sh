#!/usr/bin/env bash
# Cadangan harian database + pembersihan data lama. Dipasang cron oleh setup.sh (02:30).
set -euo pipefail
DB_NAME=spilltoko
DEST=/var/backups/spilltoko
mkdir -p "$DEST"; chmod 700 "$DEST"
mysqldump --single-transaction --routines "$DB_NAME" | gzip > "$DEST/$DB_NAME-$(date +%F).sql.gz"
find "$DEST" -name '*.sql.gz' -mtime +14 -delete
# log AI > 90 hari & cache AI > 30 hari tidak diperlukan lagi
mysql "$DB_NAME" -e "DELETE FROM ai_log WHERE created_at < UTC_TIMESTAMP() - INTERVAL 90 DAY; DELETE FROM ai_cache WHERE created_at < UTC_TIMESTAMP() - INTERVAL 30 DAY;"
echo "$(date '+%F %T') backup ok"
