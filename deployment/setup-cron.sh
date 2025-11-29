#!/bin/bash

################################################################################
# Laravel Scheduler Cron Setup Script
# This script adds the Laravel scheduler to the deploy user's crontab
################################################################################

set -e

APP_DIR="/var/www/delivery-dispatch"
CRON_ENTRY="* * * * * cd $APP_DIR && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"

echo "========================================="
echo "Laravel Scheduler Cron Setup"
echo "========================================="
echo ""

# Check if running as deploy user
if [ "$USER" != "deploy" ]; then
    echo "Error: This script must be run as the 'deploy' user"
    echo "Run: sudo -u deploy bash setup-cron.sh"
    exit 1
fi

# Check if cron entry already exists
if crontab -l 2>/dev/null | grep -q "$APP_DIR"; then
    echo "Cron entry already exists for Laravel scheduler."
    echo ""
    echo "Current crontab:"
    crontab -l | grep "$APP_DIR"
    echo ""
    read -p "Do you want to update it? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Skipping cron setup."
        exit 0
    fi

    # Remove old entry
    crontab -l | grep -v "$APP_DIR" | crontab -
fi

# Add new cron entry
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -

echo "Cron entry added successfully!"
echo ""
echo "Current crontab:"
crontab -l
echo ""
echo "The Laravel scheduler will now run every minute."
echo "Scheduled tasks configured in routes/console.php will execute automatically."
echo ""
echo "Scheduled tasks:"
echo "  - Daily: Cleanup rider locations (4:30 AM)"
echo "  - Monthly: Cleanup old orders (1st of month, 5:00 AM)"
echo ""
