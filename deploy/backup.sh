#!/usr/bin/env bash
#
# Nightly backup for Admin-Panel-2.2.
#
#   sudo cp deploy/backup.sh /usr/local/bin/admin-panel-backup.sh
#   sudo chmod +x /usr/local/bin/admin-panel-backup.sh
#   # then uncomment the cron line in deploy/crontab.example
#
# Reads DB credentials from the app's .env so they live in exactly one place.
#
# The database is the ONLY copy of the schema -- this repo ships 381 migrations
# and no .sql dump. storage/app/public/uploads is user-uploaded data that exists
# nowhere else. Both need to leave this box: add an offsite rsync/S3 step at the
# bottom, or a backup is only protecting you from your own mistakes, not from
# losing the server.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/admin-panel}"
DEST="${DEST:-/var/backups/admin-panel}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%F)"

env_get() {
    # Strips optional surrounding quotes; first match wins
    sed -n "s/^$1=\(.*\)$/\1/p" "$APP_DIR/.env" | head -1 | sed -e 's/^"//' -e 's/"$//'
}

DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"
DB_HOST="$(env_get DB_HOST)"

mkdir -p "$DEST"

# MYSQL_PWD keeps the password out of the process list that `ps` shows
MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="${DB_HOST:-127.0.0.1}" \
    --user="$DB_USERNAME" \
    --single-transaction --routines --triggers --events \
    --default-character-set=utf8mb4 \
    "$DB_DATABASE" | gzip > "$DEST/db-$STAMP.sql.gz"

rsync -a --delete "$APP_DIR/storage/app/public/" "$DEST/uploads/"

find "$DEST" -name 'db-*.sql.gz' -mtime "+$KEEP_DAYS" -delete

echo "Backup complete: $DEST/db-$STAMP.sql.gz ($(du -h "$DEST/db-$STAMP.sql.gz" | cut -f1))"

# TODO offsite copy, e.g.:
# rsync -az "$DEST/" backup@elsewhere:/backups/admin-panel/
