#!/usr/bin/env bash
set -euo pipefail
HOST="${NEXA_DB_HOST:-127.0.0.1}"; PORT="${NEXA_DB_PORT:-3306}"; DB="${NEXA_DB_NAME:-nexa_group_finance}"; USER="${NEXA_DB_USER:-root}"; PASS="${NEXA_DB_PASS:-}"
OUT="${1:-backups/${DB}-$(date +%Y%m%d-%H%M%S).sql.gz}"
mkdir -p "$(dirname "$OUT")"
TMP="${OUT%.gz}.tmp.sql"; trap 'rm -f "$TMP"' EXIT
MYSQL_PWD="$PASS" mysqldump -h"$HOST" -P"$PORT" -u"$USER" --single-transaction --routines --triggers --events --hex-blob --set-gtid-purged=OFF "$DB" > "$TMP"
gzip -c "$TMP" > "$OUT"
sha256sum "$OUT" > "$OUT.sha256"
printf 'BACKUP_OK %s\n' "$OUT"
cat "$OUT.sha256"
