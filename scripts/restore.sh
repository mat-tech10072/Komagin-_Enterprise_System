#!/usr/bin/env bash
#
# Komagin HR — Restore Script
# Phase 6, Stage 6.6 (Backup & Disaster Recovery)
#
# Restores a database backup (and optionally the matching files backup)
# produced by backup.sh. Refuses to run against the live database name
# unless --confirm is passed, to prevent an accidental overwrite while
# testing a restore.
#
# Usage:
#   restore.sh <db_backup.sql> [files_backup.tar.gz] [--target-db NAME] [--confirm]
#
set -euo pipefail

DB_BACKUP_FILE=""
FILES_BACKUP_FILE=""
TARGET_DB=""
CONFIRM=0

for arg in "$@"; do
    case "$arg" in
        --confirm) CONFIRM=1 ;;
        --target-db) NEXT_IS_TARGET=1 ;;
        *)
            if [ "${NEXT_IS_TARGET:-0}" = "1" ]; then
                TARGET_DB="$arg"; NEXT_IS_TARGET=0
            elif [ -z "$DB_BACKUP_FILE" ]; then
                DB_BACKUP_FILE="$arg"
            elif [ -z "$FILES_BACKUP_FILE" ]; then
                FILES_BACKUP_FILE="$arg"
            fi
            ;;
    esac
done

if [ -z "$DB_BACKUP_FILE" ] || [ ! -f "$DB_BACKUP_FILE" ]; then
    echo "Usage: $0 <db_backup.sql> [files_backup.tar.gz] [--target-db NAME] [--confirm]" >&2
    exit 1
fi

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-komagin_hr}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
UPLOADS_DIR="${UPLOADS_DIR:-$APP_ROOT/uploads}"
MYSQL_BIN="${MYSQL_BIN:-mysql}"

TARGET_DB="${TARGET_DB:-$DB_NAME}"

if [ "$TARGET_DB" = "$DB_NAME" ] && [ "$CONFIRM" != "1" ]; then
    echo "REFUSING to restore over the live database '$DB_NAME' without --confirm." >&2
    echo "To test a restore safely, use: --target-db ${DB_NAME}_restore_test" >&2
    echo "To actually overwrite the live database, re-run with --confirm." >&2
    exit 1
fi

echo "[$(date -Iseconds)] Restoring database backup '$DB_BACKUP_FILE' into '$TARGET_DB'..."
MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" --host="$DB_HOST" --user="$DB_USER" \
    -e "CREATE DATABASE IF NOT EXISTS \`$TARGET_DB\`"
MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" --host="$DB_HOST" --user="$DB_USER" \
    "$TARGET_DB" < "$DB_BACKUP_FILE"
echo "[$(date -Iseconds)] Database restore complete."

if [ -n "$FILES_BACKUP_FILE" ]; then
    if [ ! -f "$FILES_BACKUP_FILE" ]; then
        echo "WARNING: files backup '$FILES_BACKUP_FILE' not found, skipping file restore." >&2
    else
        RESTORE_DIR="${UPLOADS_DIR}_restore_$(date +%s)"
        mkdir -p "$RESTORE_DIR"
        tar -xzf "$FILES_BACKUP_FILE" -C "$RESTORE_DIR"
        echo "[$(date -Iseconds)] Files backup extracted to $RESTORE_DIR (not overwriting live uploads/ automatically — move into place manually after review)."
    fi
fi

echo "[$(date -Iseconds)] Done."
