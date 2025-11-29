#!/bin/bash

################################################################################
# Hetzner Server Provisioning Script
# For: Laravel Delivery Dispatch Application
# Server: Hetzner CPX31 (4 vCPU, 8GB RAM, 160GB NVMe SSD)
# OS: Ubuntu 24.04 LTS
################################################################################

set -e  # Exit on error

echo "========================================="
echo "Starting Server Provisioning..."
echo "========================================="

# Update system
echo "Step 1: Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "Step 2: Installing essential packages..."
sudo apt install -y \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    supervisor \
    nginx \
    ufw \
    certbot \
    python3-certbot-nginx \
    htop \
    redis-server \
    gnupg2

# Install PHP 8.2 and required extensions
echo "Step 3: Installing PHP 8.2 and extensions..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-redis \
    php8.2-intl \
    php8.2-tokenizer \
    php8.2-fileinfo

# Install Composer
echo "Step 4: Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install MySQL 8.0
echo "Step 5: Installing MySQL 8.0..."
sudo apt install -y mysql-server mysql-client

# Secure MySQL installation
echo "Step 6: Securing MySQL..."
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'CHANGE_THIS_ROOT_PASSWORD';"
sudo mysql -e "DELETE FROM mysql.user WHERE User='';"
sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
sudo mysql -e "DROP DATABASE IF EXISTS test;"
sudo mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Create application database and user
echo "Step 7: Creating application database..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS delivery_dispatch;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'delivery_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_DB_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON delivery_dispatch.* TO 'delivery_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Configure Redis
echo "Step 8: Configuring Redis..."
sudo sed -i 's/# maxmemory <bytes>/maxmemory 1gb/' /etc/redis/redis.conf
sudo sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
sudo sed -i 's/appendonly no/appendonly yes/' /etc/redis/redis.conf
sudo systemctl restart redis-server
sudo systemctl enable redis-server

# Configure PHP-FPM
echo "Step 9: Optimizing PHP-FPM..."
sudo sed -i 's/pm.max_children = .*/pm.max_children = 50/' /etc/php/8.2/fpm/pool.d/www.conf
sudo sed -i 's/pm.start_servers = .*/pm.start_servers = 10/' /etc/php/8.2/fpm/pool.d/www.conf
sudo sed -i 's/pm.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php/8.2/fpm/pool.d/www.conf
sudo sed -i 's/pm.max_spare_servers = .*/pm.max_spare_servers = 15/' /etc/php/8.2/fpm/pool.d/www.conf
sudo sed -i 's/;pm.max_requests = .*/pm.max_requests = 500/' /etc/php/8.2/fpm/pool.d/www.conf

# Increase PHP memory limit and execution time
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.2/fpm/php.ini

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm

# Install Node.js (for asset compilation)
echo "Step 10: Installing Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Create deployment user
echo "Step 11: Creating deployment user..."
sudo useradd -m -s /bin/bash deploy || true
sudo usermod -aG www-data deploy

# Create application directory
echo "Step 12: Setting up application directory..."
sudo mkdir -p /var/www/delivery-dispatch
sudo chown -R deploy:www-data /var/www/delivery-dispatch
sudo chmod -R 755 /var/www/delivery-dispatch

# Configure firewall
echo "Step 13: Configuring firewall..."
sudo ufw --force enable
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw allow 8080/tcp  # Laravel Reverb

# Create log directory
sudo mkdir -p /var/log/delivery-dispatch
sudo chown -R deploy:www-data /var/log/delivery-dispatch

echo "========================================="
echo "Server provisioning completed!"
echo "========================================="
echo ""
echo "IMPORTANT: Please update the following:"
echo "1. MySQL root password (currently: CHANGE_THIS_ROOT_PASSWORD)"
echo "2. Database user password (currently: CHANGE_THIS_DB_PASSWORD)"
echo "3. Configure your .env file with these credentials"
echo ""
echo "Next steps:"
echo "1. Copy your application code to /var/www/delivery-dispatch"
echo "2. Configure Nginx (see nginx-config.conf)"
echo "3. Configure Supervisor (see supervisor-config.conf)"
echo "4. Run the deployment script (deploy.sh)"
echo ""
echo "MySQL Database created: delivery_dispatch"
echo "MySQL User created: delivery_user@localhost"
echo ""
