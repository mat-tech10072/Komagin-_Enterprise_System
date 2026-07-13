#!/usr/bin/env bash
#
# Komagin HR — Backup Script
# Phase 6, Stage 6.6 (Backup & Disaster Recovery)
#
# Backs up the database (mysqldump) and the uploads/ directory (tar) to a
# single timestamped pair of files, then applies simple retention rotation
# per tier (daily/weekly/monthly). Intended to be run by system cron/systemd
# timers on the production droplet (see the deployment guide's §10) — also
# runs as-is on this local dev environment for testing.
#
# Usage: backup.sh <daily|weekly|monthly> [manual-label]
#
set -euo pipefail

TIER="${1:-daily}"
LABEL="${2:-}"

# ── Configuration (override via environment variables; sensible defaults
#    match the DigitalOcean deployment guide's paths) ──────────────────────
APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_ROOT="${BACKUP_ROOT:-$APP_ROOT/database/backups}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-komagin_hr}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
UPLOADS_DIR="${UPLOADS_DIR:-$APP_ROOT/uploads}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-mysqldump}"

# Retention: how many backups of each tier to keep before rotating out the
# oldest. Daily backups feed the weekly/monthly tiers (run separately by
# cron with a different $1), so each tier's own count is independent.
case "$TIER" in
    daily)   KEEP=14 ;;   # 2 weeks of daily backups
    weekly)  KEEP=8  ;;   # ~2 months of weekly backups
    monthly) KEEP=12 ;;   # 1 year of monthly backups
    manual)  KEEP=0  ;;   # manual backups are never auto-rotated out
    *) echo "Usage: $0 <daily|weekly|monthly|manual> [label]" >&2; exit 1 ;;
esac

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
SUFFIX="${TIER}_${TIMESTAMP}${LABEL:+_$LABEL}"
DB_BACKUP_FILE="$BACKUP_ROOT/${SUFFIX}_db.sql"
FILES_BACKUP_FILE="$BACKUP_ROOT/${SUFFIX}_files.tar.gz"

mkdir -p "$BACKUP_ROOT"

echo "[$(date -Iseconds)] Starting $TIER backup..."

# ── Database ────────────────────────────────────────────────────────────
MYSQL_PWD="$DB_PASS" "$MYSQLDUMP_BIN" \
    --host="$DB_HOST" --user="$DB_USER" \
    --single-transaction --routines --triggers --events \
    "$DB_NAME" > "$DB_BACKUP_FILE"

if [ ! -s "$DB_BACKUP_FILE" ]; then
    echo "[$(date -Iseconds)] ERROR: database backup is empty — aborting, not rotating retention." >&2
    rm -f "$DB_BACKUP_FILE"
    exit 1
fi

# ── Uploaded files (documents, branding assets: letterheads/logos/
#    signatures/stamps/watermarks, avatars, photos, contracts) ───────────
tar -czf "$FILES_BACKUP_FILE" -C "$(dirname "$UPLOADS_DIR")" "$(basename "$UPLOADS_DIR")"

if [ ! -s "$FILES_BACKUP_FILE" ]; then
    echo "[$(date -Iseconds)] ERROR: files backup is empty — aborting, not rotating retention." >&2
    rm -f "$FILES_BACKUP_FILE"
    exit 1
fi

echo "[$(date -Iseconds)] Backup complete: $DB_BACKUP_FILE ($(du -h "$DB_BACKUP_FILE" | cut -f1)), $FILES_BACKUP_FILE ($(du -h "$FILES_BACKUP_FILE" | cut -f1))"

# ── Retention rotation (skip for manual backups — KEEP=0 means unlimited) ─
if [ "$KEEP" -gt 0 ]; then
    for pattern in "${TIER}_*_db.sql" "${TIER}_*_files.tar.gz"; do
        # shellcheck disable=SC2012
        ls -1t "$BACKUP_ROOT"/$pattern 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r old; do
            echo "[$(date -Iseconds)] Rotating out old $TIER backup: $old"
            rm -f "$old"
        done
    done
fi

echo "[$(date -Iseconds)] Done."
