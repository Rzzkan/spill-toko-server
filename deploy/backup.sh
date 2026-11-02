#!/usr/bin/env bash
# Cadangan harian database (cron 02:30 oleh setup.sh). Pembersihan log/cache AI lama dilakukan scheduler Laravel.
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
env_val() { grep -E "^$1=" "$APP_DIR/.env" | head -1 | cut -d= -f2-; }
DB_NAME="$(env_val DB_DATABASE)"
DEST=/var/backups/spilltoko
mkdir -p "$DEST"; chmod 700 "$DEST"
mysqldump --single-transaction --routines "$DB_NAME" | gzip > "$DEST/$DB_NAME-$(date +%F).sql.gz"
find "$DEST" -name '*.sql.gz' -mtime +14 -delete
echo "$(date '+%F %T') backup ok"
