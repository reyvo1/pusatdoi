#!/usr/bin/env bash
set -euo pipefail
FILE="${1:?Usage: mysql-restore.sh backup.sql.gz [target_db]}"
HOST="${NEXA_DB_HOST:-127.0.0.1}"; PORT="${NEXA_DB_PORT:-3306}"; TARGET="${2:-${NEXA_DB_NAME:-nexa_group_finance_restore}}"; USER="${NEXA_DB_USER:-root}"; PASS="${NEXA_DB_PASS:-}"
[ -f "$FILE" ] || { echo "Backup file not found: $FILE" >&2; exit 2; }
[ -f "$FILE.sha256" ] && (cd "$(dirname "$FILE")" && sha256sum -c "$(basename "$FILE").sha256")
[[ "$TARGET" =~ ^[A-Za-z0-9_]+$ ]] || { echo 'Invalid target database name' >&2; exit 2; }
MYSQL_PWD="$PASS" mysql -h"$HOST" -P"$PORT" -u"$USER" -e "CREATE DATABASE IF NOT EXISTS \`$TARGET\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gzip -dc "$FILE" | MYSQL_PWD="$PASS" mysql -h"$HOST" -P"$PORT" -u"$USER" "$TARGET"
printf 'RESTORE_OK %s -> %s\n' "$FILE" "$TARGET"
