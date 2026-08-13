#!/bin/bash

# Directory path for archived logs
# Resolves storage/logs/archived relative to script location by default, or accepts path as parameter
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
LOG_DIR="${1:-$SCRIPT_DIR/storage/logs/archived}"
DAYS_TO_KEEP=14

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting log cleanup for directory: $LOG_DIR (retention: $DAYS_TO_KEEP days)..."

if [ -d "$LOG_DIR" ]; then
    # Delete log files modified more than DAYS_TO_KEEP days ago
    DELETED_COUNT=$(find "$LOG_DIR" -type f -name "*.log" -mtime +$DAYS_TO_KEEP -print -delete | wc -l)
    
    # Remove empty directories left behind
    find "$LOG_DIR" -mindepth 1 -type d -empty -delete
    
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Cleanup completed. Removed $DELETED_COUNT archived log file(s)."
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Directory $LOG_DIR does not exist."
fi
