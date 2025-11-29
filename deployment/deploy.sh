#!/bin/bash

################################################################################
# Laravel Deployment Script
# For: Delivery Dispatch Application
# Usage: ./deploy.sh [branch_name]
# Default branch: main
################################################################################

set -e  # Exit on error

# Configuration
APP_DIR="/var/www/delivery-dispatch"
REPO_URL="https://github.com/abduljabbar786/delivery-dispatch-laravel.git"
BRANCH="${1:-main}"
PHP_VERSION="8.2"

echo "========================================="
echo "Laravel Deployment Script"
echo "========================================="
echo "Branch: $BRANCH"
echo "Directory: $APP_DIR"
echo ""

# Check if running as deploy user
if [ "$USER" != "deploy" ]; then
    echo "Error: This script must be run as the 'deploy' user"
    echo "Run: sudo -u deploy bash deploy.sh"
    exit 1
fi

# Navigate to app directory
cd $APP_DIR

echo "Step 1: Enabling maintenance mode..."
php artisan down || true

echo "Step 2: Pulling latest code from Git..."
if [ ! -d ".git" ]; then
    echo "Git repository not found. Cloning..."
    cd ..
    sudo rm -rf delivery-dispatch
    git clone -b $BRANCH $REPO_URL delivery-dispatch
    cd delivery-dispatch
else
    git fetch origin
    git reset --hard origin/$BRANCH
fi

echo "Step 3: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Step 4: Installing NPM dependencies..."
npm ci --production=false

echo "Step 5: Building frontend assets..."
npm run build

echo "Step 6: Running database migrations..."
php artisan migrate --force

echo "Step 7: Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Step 8: Optimizing application..."
php artisan optimize

echo "Step 9: Setting proper permissions..."
sudo chown -R deploy:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

echo "Step 10: Restarting services..."
# Restart PHP-FPM
sudo systemctl restart php${PHP_VERSION}-fpm

# Restart queue workers and Reverb via Supervisor
sudo supervisorctl restart delivery-dispatch:*

# Reload Nginx
sudo systemctl reload nginx

echo "Step 11: Disabling maintenance mode..."
php artisan up

echo "Step 12: Running post-deployment checks..."
php artisan --version
php artisan config:show app.env
php artisan queue:monitor redis

echo ""
echo "========================================="
echo "Deployment completed successfully!"
echo "========================================="
echo "Application: $(php artisan --version)"
echo "Environment: $(php artisan config:show app.env)"
echo ""
echo "Useful commands:"
echo "  Check queue status: php artisan queue:monitor"
echo "  View logs: tail -f storage/logs/laravel.log"
echo "  Check Reverb: sudo supervisorctl status delivery-dispatch-reverb"
echo "  Check workers: sudo supervisorctl status delivery-dispatch-queue-worker:*"
echo ""
