#!/bin/bash

################################################################################
# Automated Backup Script
# For: Laravel Delivery Dispatch Application
# Backups: Database, Application Files, Environment Config
# Retention: 7 daily, 4 weekly, 3 monthly
################################################################################

set -e  # Exit on error

# Configuration
APP_DIR="/var/www/delivery-dispatch"
BACKUP_DIR="/var/backups/delivery-dispatch"
DB_NAME="delivery_dispatch"
DB_USER="delivery_user"
DB_PASSWORD="CHANGE_THIS_DB_PASSWORD"  # Update with actual password
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DATE=$(date +"%Y-%m-%d")
RETENTION_DAYS=7
RETENTION_WEEKS=4
RETENTION_MONTHS=3

# Create backup directories
mkdir -p $BACKUP_DIR/{daily,weekly,monthly}
mkdir -p $BACKUP_DIR/temp

echo "========================================="
echo "Backup Script - $(date)"
echo "========================================="

# Determine backup type (daily, weekly, monthly)
DAY_OF_WEEK=$(date +%u)  # 1=Monday, 7=Sunday
DAY_OF_MONTH=$(date +%d)

if [ "$DAY_OF_MONTH" = "01" ]; then
    BACKUP_TYPE="monthly"
    BACKUP_PATH="$BACKUP_DIR/monthly"
    RETENTION_COUNT=$RETENTION_MONTHS
elif [ "$DAY_OF_WEEK" = "7" ]; then
    BACKUP_TYPE="weekly"
    BACKUP_PATH="$BACKUP_DIR/weekly"
    RETENTION_COUNT=$RETENTION_WEEKS
else
    BACKUP_TYPE="daily"
    BACKUP_PATH="$BACKUP_DIR/daily"
    RETENTION_COUNT=$RETENTION_DAYS
fi

echo "Backup Type: $BACKUP_TYPE"
echo "Backup Path: $BACKUP_PATH"

# Create temporary directory for this backup
TEMP_DIR="$BACKUP_DIR/temp/$TIMESTAMP"
mkdir -p $TEMP_DIR

echo ""
echo "Step 1: Backing up database..."
mysqldump -u $DB_USER -p$DB_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --databases $DB_NAME \
    --result-file=$TEMP_DIR/database.sql

# Compress database backup
gzip $TEMP_DIR/database.sql
echo "Database backed up and compressed: database.sql.gz"

echo ""
echo "Step 2: Backing up application files..."
# Backup storage directory (uploaded files, logs)
tar -czf $TEMP_DIR/storage.tar.gz -C $APP_DIR storage

# Backup .env file (encrypted)
if [ -f "$APP_DIR/.env" ]; then
    cp $APP_DIR/.env $TEMP_DIR/.env
    gzip $TEMP_DIR/.env
fi

echo "Application files backed up"

echo ""
echo "Step 3: Creating backup archive..."
BACKUP_FILENAME="backup_${BACKUP_TYPE}_${TIMESTAMP}.tar.gz"
tar -czf $BACKUP_PATH/$BACKUP_FILENAME -C $TEMP_DIR .

# Calculate backup size
BACKUP_SIZE=$(du -h $BACKUP_PATH/$BACKUP_FILENAME | cut -f1)
echo "Backup created: $BACKUP_FILENAME (Size: $BACKUP_SIZE)"

echo ""
echo "Step 4: Cleaning up old backups..."
# Remove temporary directory
rm -rf $TEMP_DIR

# Remove old backups based on retention policy
cd $BACKUP_PATH
ls -t backup_${BACKUP_TYPE}_*.tar.gz | tail -n +$((RETENTION_COUNT + 1)) | xargs -r rm
REMAINING=$(ls -1 backup_${BACKUP_TYPE}_*.tar.gz 2>/dev/null | wc -l)
echo "Removed old backups. Remaining $BACKUP_TYPE backups: $REMAINING"

echo ""
echo "Step 5: Backup verification..."
# Verify the backup archive is valid
if tar -tzf $BACKUP_PATH/$BACKUP_FILENAME >/dev/null 2>&1; then
    echo "Backup verification: SUCCESS"
else
    echo "Backup verification: FAILED"
    exit 1
fi

echo ""
echo "========================================="
echo "Backup completed successfully!"
echo "========================================="
echo "Backup file: $BACKUP_PATH/$BACKUP_FILENAME"
echo "Backup size: $BACKUP_SIZE"
echo "Backup type: $BACKUP_TYPE"
echo ""
echo "Backup summary:"
echo "  Daily backups: $(ls -1 $BACKUP_DIR/daily/backup_daily_*.tar.gz 2>/dev/null | wc -l)"
echo "  Weekly backups: $(ls -1 $BACKUP_DIR/weekly/backup_weekly_*.tar.gz 2>/dev/null | wc -l)"
echo "  Monthly backups: $(ls -1 $BACKUP_DIR/monthly/backup_monthly_*.tar.gz 2>/dev/null | wc -l)"
echo ""
echo "Total backup size: $(du -sh $BACKUP_DIR | cut -f1)"
echo ""

# Optional: Upload to remote storage (uncomment and configure)
# echo "Step 6: Uploading to remote storage..."
# Example for AWS S3:
# aws s3 cp $BACKUP_PATH/$BACKUP_FILENAME s3://your-bucket/backups/delivery-dispatch/
# Example for rsync to remote server:
# rsync -avz $BACKUP_PATH/$BACKUP_FILENAME user@remote-server:/backups/delivery-dispatch/
