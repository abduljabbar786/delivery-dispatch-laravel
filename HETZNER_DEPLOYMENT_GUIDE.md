# Hetzner Server Deployment Guide
# Delivery Dispatch Laravel Application

## Table of Contents
1. [Cost Analysis & Server Recommendation](#cost-analysis--server-recommendation)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Server Provisioning](#server-provisioning)
4. [Initial Server Setup](#initial-server-setup)
5. [Installing Dependencies](#installing-dependencies)
6. [Application Deployment](#application-deployment)
7. [Database Setup](#database-setup)
8. [Redis Configuration](#redis-configuration)
9. [Laravel Reverb Setup](#laravel-reverb-setup)
10. [Queue Workers Setup](#queue-workers-setup)
11. [Nginx Configuration](#nginx-configuration)
12. [SSL/TLS Setup](#ssltls-setup)
13. [Security Hardening](#security-hardening)
14. [Monitoring & Logging](#monitoring--logging)
15. [Backup Configuration](#backup-configuration)
16. [Performance Optimization](#performance-optimization)
17. [Troubleshooting](#troubleshooting)

---

## Cost Analysis & Server Recommendation

### Application Requirements Summary
Based on deep analysis of your Laravel application:

**Traffic & Scale:**
- 2 Supervisors + 50 Riders
- 500+ Orders per day
- 10,000-20,000 GPS location points per day
- Real-time WebSocket connections (50+ concurrent)
- ~500 MB - 1 GB traffic per day

**Resource Requirements:**
- **CPU:** 4 vCPUs (for PHP-FPM, MySQL, Redis, Reverb, Queue Workers)
- **RAM:** 8 GB (PHP: 2.5GB, MySQL: 2GB, Redis: 512MB, Reverb: 500MB, Queues: 1.3GB, OS: 1GB)
- **Storage:** 60 GB SSD minimum (80GB+ recommended for logs and growth)
- **I/O Performance:** High (due to GPS location tracking with 20k inserts/day)
- **Network:** 1-2 TB/month bandwidth

---

### Hetzner Server Options Comparison

#### Option 1: **CPX31 (RECOMMENDED)** ⭐
```
Specifications:
- 4 vCPU (AMD EPYC / Intel Xeon, shared)
- 8 GB RAM
- 160 GB NVMe SSD
- 20 TB traffic included
- Location: Germany, Finland, USA

Price: €13.15/month (~$14-15 USD/month)

Performance Score: 9/10
Value Score: 10/10
```

**Why CPX31 is the Best Choice:**
✅ **Perfect Resource Match** - Exactly 4 vCPU + 8 GB RAM (matches recommendation)
✅ **Best Value** - $14/month vs DigitalOcean $48/month for similar specs
✅ **Generous Storage** - 160 GB (2.5x what you need)
✅ **Massive Bandwidth** - 20 TB (20x what you need)
✅ **NVMe SSD** - Fastest storage for high I/O operations (GPS tracking)
✅ **Scalable** - Easy upgrade path to CPX41/CPX51

**Capacity:**
- Can handle: **100+ riders**, **1000+ orders/day**
- WebSocket connections: **200+ concurrent**
- Database: **500 MB - 2 GB** with room to grow

---

#### Option 2: CPX21 (Budget Option)
```
Specifications:
- 3 vCPU (shared)
- 4 GB RAM
- 80 GB NVMe SSD
- 20 TB traffic

Price: €7.05/month (~$8 USD/month)

Performance Score: 6/10
Value Score: 9/10
```

**Pros:**
✅ Cheapest option
✅ Good for development/staging
✅ Still includes 20 TB traffic

**Cons:**
❌ Only 4 GB RAM (tight for production)
❌ 3 vCPU may struggle under peak load
❌ Limited headroom for scaling

**Verdict:** Good for staging, NOT recommended for production with 50 riders.

---

#### Option 3: CX31 (Alternative Shared)
```
Specifications:
- 2 vCPU (shared, older generation)
- 8 GB RAM
- 80 GB SSD (not NVMe)
- 20 TB traffic

Price: €10.52/month (~$12 USD/month)

Performance Score: 7/10
Value Score: 8/10
```

**Pros:**
✅ 8 GB RAM
✅ Slightly cheaper than CPX31
✅ Good enough for moderate load

**Cons:**
❌ Only 2 vCPU (may bottleneck during peaks)
❌ Older CPU generation
❌ Standard SSD (slower than NVMe)

**Verdict:** Not recommended - spend $2 more for CPX31 with 2x CPU + NVMe.

---

#### Option 4: CCX13 (Dedicated CPU)
```
Specifications:
- 2 dedicated vCPU (AMD EPYC)
- 8 GB RAM
- 80 GB NVMe SSD
- 20 TB traffic

Price: €18.90/month (~$21 USD/month)

Performance Score: 8/10
Value Score: 6/10
```

**Pros:**
✅ Dedicated CPU (no sharing)
✅ Consistent performance
✅ Better for CPU-intensive tasks

**Cons:**
❌ 50% more expensive than CPX31
❌ Only 2 vCPU (vs 4 shared on CPX31)
❌ Shared vCPU is sufficient for this workload

**Verdict:** Overkill - CPX31 shared vCPU is more than adequate.

---

#### Option 5: CPX41 (High Scale)
```
Specifications:
- 8 vCPU (shared)
- 16 GB RAM
- 240 GB NVMe SSD
- 20 TB traffic

Price: €24.40/month (~$27 USD/month)

Performance Score: 10/10
Value Score: 7/10
```

**When to Use:**
- 100+ riders
- 2000+ orders/day
- Multiple locations/branches
- High availability requirements

**Verdict:** Overkill for current needs, but good upgrade path.

---

### Comparison with Other Providers

| Provider | Specs | Price | Value |
|----------|-------|-------|-------|
| **Hetzner CPX31** | 4 vCPU, 8GB, 160GB NVMe | $14/mo | ⭐⭐⭐⭐⭐ |
| DigitalOcean | 4 vCPU, 8GB, 160GB SSD | $48/mo | ⭐⭐ |
| Linode | 4 vCPU, 8GB, 160GB SSD | $48/mo | ⭐⭐ |
| Vultr | 4 vCPU, 8GB, 160GB SSD | $48/mo | ⭐⭐ |
| AWS Lightsail | 2 vCPU, 8GB, 160GB SSD | $40/mo | ⭐⭐ |
| AWS EC2 t3.large | 2 vCPU, 8GB, +EBS | $60+/mo | ⭐ |

**Hetzner CPX31 saves you $408/year compared to DigitalOcean!**

---

### Final Recommendation

## **HETZNER CPX31** 🏆

**Price:** €13.15/month (~$158/year)

**Perfect for:**
- 50 riders (can scale to 100+)
- 500+ orders/day (can handle 1000+)
- Real-time GPS tracking
- WebSocket connections
- Production workload

**Upgrade Path:**
- Start: CPX31 ($14/mo)
- Growth (100+ riders): CPX41 ($27/mo)
- Enterprise (200+ riders): CCX23 Dedicated ($40/mo)

**Total First Year Cost Estimate:**
```
Server (CPX31):        €157.80 ($175/year)
Domain (.com):         €12.00 ($13/year)
Backups (optional):    €2.63/mo ($31/year)
Total:                 €189.36 ($219/year)
```

---

## Pre-Deployment Checklist

### 1. Domain & DNS
- [ ] Purchase domain name (e.g., deliverydispatch.com)
- [ ] Access to DNS management (Cloudflare recommended)
- [ ] Plan subdomain structure:
  - `api.deliverydispatch.com` - API backend
  - `app.deliverydispatch.com` - Dashboard frontend (optional)
  - `ws.deliverydispatch.com` - WebSocket endpoint (optional, can use same domain)

### 2. Hetzner Account
- [ ] Create account at https://www.hetzner.com/
- [ ] Verify email address
- [ ] Add payment method (credit card or PayPal)
- [ ] Enable 2FA (two-factor authentication)

### 3. SSH Key Preparation
Generate SSH key on your local machine:

```bash
# On your local machine
ssh-keygen -t ed25519 -C "your_email@example.com" -f ~/.ssh/hetzner_delivery_dispatch

# Copy public key (you'll need this during server creation)
cat ~/.ssh/hetzner_delivery_dispatch.pub
```

### 4. Git Repository Access
- [ ] Ensure code is pushed to Git repository (GitHub/GitLab/Bitbucket)
- [ ] Generate deployment SSH key (if private repo):

```bash
ssh-keygen -t ed25519 -C "deploy@deliverydispatch" -f ~/.ssh/deploy_key
# Add public key to GitHub/GitLab deploy keys
```

### 5. Environment Variables Preparation
Create production `.env` file locally (DO NOT commit to Git):

```bash
cp .env.example .env.production

# Edit with production values:
# - Database credentials
# - Redis configuration
# - POS webhook API key
# - Reverb credentials
# - Mail settings
```

### 6. Third-Party Services
- [ ] Email service (Mailgun, SendGrid, or AWS SES)
- [ ] SMS service if needed (Twilio, Vonage)
- [ ] Error tracking (Sentry, Bugsnag) - optional
- [ ] Uptime monitoring (UptimeRobot, Pingdom) - optional

---

## Server Provisioning

### Step 1: Create Hetzner Cloud Project

1. **Login to Hetzner Cloud Console**
   - Go to https://console.hetzner.cloud/
   - Click "New Project"
   - Name: `delivery-dispatch-production`

2. **Add SSH Key**
   - Go to "Security" → "SSH Keys"
   - Click "Add SSH Key"
   - Paste your public key from earlier
   - Name: `your-name-laptop`

### Step 2: Create Server

1. **Click "Add Server"**

2. **Location:**
   - 🇩🇪 **Germany (Nuremberg or Falkenstein)** - Best for EU
   - 🇫🇮 **Finland (Helsinki)** - Good for EU + Middle East
   - 🇺🇸 **USA (Ashburn)** - Best for US

   **Choose based on your primary user location.**

3. **Image:**
   - Select: **Ubuntu 24.04 LTS** (or Ubuntu 22.04 LTS)

4. **Type:**
   - Select: **Shared vCPU**
   - Choose: **CPX31** (4 vCPU, 8 GB RAM, 160 GB NVMe)

5. **Networking:**
   - ✅ Public IPv4
   - ✅ Public IPv6
   - Leave "Private Networks" unchecked (not needed for single server)

6. **Firewall (Configure Now):**
   Click "Create Firewall" and add these rules:

   **Inbound Rules:**
   ```
   SSH (22)      - TCP - Source: Your IP (e.g., 203.0.113.5/32)
   HTTP (80)     - TCP - Source: 0.0.0.0/0, ::/0
   HTTPS (443)   - TCP - Source: 0.0.0.0/0, ::/0
   ```

   **Outbound Rules:**
   ```
   All traffic allowed (default)
   ```

   **⚠️ IMPORTANT:** Restrict SSH to your IP for security!

7. **SSH Keys:**
   - Select your SSH key added earlier

8. **Volumes:**
   - Skip (not needed)

9. **Additional Features:**
   - ✅ **Backups** (€2.63/month) - HIGHLY RECOMMENDED
   - ❌ IPv4 (already included)

10. **Cloud Config (User Data) - Optional:**

    Paste this to automate initial updates:

    ```yaml
    #cloud-config
    package_update: true
    package_upgrade: true
    packages:
      - curl
      - git
      - ufw
      - fail2ban
    runcmd:
      - timedatectl set-timezone UTC
    ```

11. **Name:**
    ```
    delivery-dispatch-prod
    ```

12. **Labels (Optional):**
    ```
    env: production
    app: delivery-dispatch
    ```

13. **Click "Create & Buy Now"**

### Step 3: Note Server Details

After creation (takes ~1 minute), note:

```
Server Name: delivery-dispatch-prod
IPv4: <YOUR_SERVER_IP>
IPv6: <YOUR_SERVER_IPV6>
Root Password: <Sent via email - ignore if using SSH key>
```

### Step 4: Configure DNS

Point your domain to the server:

**A Record:**
```
Type: A
Name: @ (or api)
Value: <YOUR_SERVER_IP>
TTL: 300
```

**AAAA Record (IPv6):**
```
Type: AAAA
Name: @ (or api)
Value: <YOUR_SERVER_IPV6>
TTL: 300
```

**Wildcard (optional):**
```
Type: A
Name: *
Value: <YOUR_SERVER_IP>
TTL: 300
```

---

## Initial Server Setup

### Step 1: Connect to Server

```bash
# From your local machine
ssh -i ~/.ssh/hetzner_delivery_dispatch root@<YOUR_SERVER_IP>
```

If using Hetzner Cloud Console (alternative):
- Click on your server → "Console" button

### Step 2: Update System

```bash
# Update package lists
apt update

# Upgrade all packages
apt upgrade -y

# Install essential packages
apt install -y curl git wget unzip zip software-properties-common \
    ufw fail2ban htop build-essential supervisor
```

### Step 3: Set Timezone

```bash
# Set to UTC (recommended for servers)
timedatectl set-timezone UTC

# Verify
timedatectl
```

### Step 4: Create Non-Root User

```bash
# Create user
adduser deployer
# Set strong password when prompted

# Add to sudo group
usermod -aG sudo deployer

# Setup SSH for deployer
mkdir -p /home/deployer/.ssh
cp ~/.ssh/authorized_keys /home/deployer/.ssh/
chown -R deployer:deployer /home/deployer/.ssh
chmod 700 /home/deployer/.ssh
chmod 600 /home/deployer/.ssh/authorized_keys
```

### Step 5: Configure SSH Security

```bash
# Edit SSH config
nano /etc/ssh/sshd_config
```

Make these changes:

```
# Disable root login
PermitRootLogin no

# Disable password authentication (SSH key only)
PasswordAuthentication no

# Disable empty passwords
PermitEmptyPasswords no

# Disable X11 forwarding (not needed)
X11Forwarding no

# Allow only deployer user
AllowUsers deployer
```

Save and restart SSH:

```bash
systemctl restart sshd
```

**⚠️ IMPORTANT:** Test new connection before closing current session:

```bash
# From another terminal on your local machine
ssh -i ~/.ssh/hetzner_delivery_dispatch deployer@<YOUR_SERVER_IP>
```

### Step 6: Configure Firewall (UFW)

```bash
# Default policies
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (IMPORTANT: Do this first!)
ufw allow 22/tcp

# Allow HTTP and HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Enable firewall
ufw enable

# Verify
ufw status verbose
```

Expected output:
```
Status: active

To                         Action      From
--                         ------      ----
22/tcp                     ALLOW       Anywhere
80/tcp                     ALLOW       Anywhere
443/tcp                     ALLOW       Anywhere
```

### Step 7: Configure Fail2Ban (Brute Force Protection)

```bash
# Copy default config
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit config
nano /etc/fail2ban/jail.local
```

Find and modify:

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = 22
logpath = /var/log/auth.log
```

Start Fail2Ban:

```bash
systemctl enable fail2ban
systemctl start fail2ban

# Check status
fail2ban-client status sshd
```

---

## Installing Dependencies

### Step 1: Install PHP 8.3

```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.3 and extensions
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-redis php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
    php8.3-bcmath php8.3-opcache php8.3-readline

# Verify installation
php -v
# Should show: PHP 8.3.x
```

### Step 2: Configure PHP

```bash
# Edit PHP-FPM pool config
nano /etc/php/8.3/fpm/pool.d/www.conf
```

Find and modify:

```ini
; Change user/group to deployer
user = deployer
group = deployer

; Process manager settings
pm = dynamic
pm.max_children = 25
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500

; Listen on socket (faster than TCP)
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

Edit PHP-FPM php.ini:

```bash
nano /etc/php/8.3/fpm/php.ini
```

Modify these values:

```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 60
max_input_time = 60

; OPcache settings (IMPORTANT for performance)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.validate_timestamps=1
```

Restart PHP-FPM:

```bash
systemctl restart php8.3-fpm
systemctl enable php8.3-fpm

# Verify
systemctl status php8.3-fpm
```

### Step 3: Install Composer

```bash
# Download installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Verify installer (optional but recommended)
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# Install globally
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Clean up
php -r "unlink('composer-setup.php');"

# Verify
composer --version
```

### Step 4: Install Node.js (for Reverb)

```bash
# Install Node.js 20 LTS (required for Laravel Reverb)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Verify
node -v   # Should be v20.x
npm -v    # Should be 10.x
```

### Step 5: Install MySQL 8.0

```bash
# Install MySQL server
apt install -y mysql-server

# Verify installation
mysql --version
# Should show: mysql  Ver 8.0.x
```

Secure MySQL installation:

```bash
mysql_secure_installation
```

Answer prompts:
```
Validate password component? Y
Password strength: 2 (strong)
New root password: <STRONG_PASSWORD>
Remove anonymous users? Y
Disallow root login remotely? Y
Remove test database? Y
Reload privilege tables? Y
```

### Step 6: Install Redis

```bash
# Install Redis server
apt install -y redis-server

# Configure Redis
nano /etc/redis/redis.conf
```

Find and modify:

```conf
# Bind to localhost only (security)
bind 127.0.0.1 ::1

# Set max memory (512 MB for this application)
maxmemory 512mb
maxmemory-policy allkeys-lru

# Enable persistence
save 900 1
save 300 10
save 60 10000

# Disable protected mode (only if bind is set correctly)
protected-mode yes

# No password needed (localhost only)
# requirepass <leave commented>
```

Restart Redis:

```bash
systemctl restart redis-server
systemctl enable redis-server

# Test connection
redis-cli ping
# Should return: PONG
```

Verify Redis PHP extension:

```bash
php -m | grep redis
# Should show: redis
```

### Step 7: Install Nginx

```bash
# Install Nginx
apt install -y nginx

# Start and enable
systemctl start nginx
systemctl enable nginx

# Verify
systemctl status nginx
```

Test in browser:
- Visit: `http://<YOUR_SERVER_IP>`
- Should see: "Welcome to nginx!"

---

## Application Deployment

### Step 1: Setup Application Directory

```bash
# Switch to deployer user
su - deployer

# Create web directory
sudo mkdir -p /var/www/delivery-dispatch
sudo chown -R deployer:deployer /var/www/delivery-dispatch
cd /var/www/delivery-dispatch
```

### Step 2: Clone Repository

**For Private Repository (recommended):**

```bash
# On server, generate deployment SSH key
ssh-keygen -t ed25519 -C "deploy@server" -f ~/.ssh/deploy_key -N ""

# Display public key
cat ~/.ssh/deploy_key.pub
# Copy this and add to GitHub/GitLab as deploy key
```

Add to GitHub:
- Go to repo → Settings → Deploy keys → Add deploy key
- Paste public key, name: "Production Server"
- ✅ Allow write access (only if you want to push from server)

Configure SSH:

```bash
nano ~/.ssh/config
```

Add:

```
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/deploy_key
    StrictHostKeyChecking no
```

Clone repository:

```bash
cd /var/www/delivery-dispatch
git clone git@github.com:YOUR_USERNAME/delivery-dispatch-laravel.git .

# Verify
ls -la
# Should see Laravel files
```

**For Public Repository:**

```bash
cd /var/www/delivery-dispatch
git clone https://github.com/YOUR_USERNAME/delivery-dispatch-laravel.git .
```

### Step 3: Install Dependencies

```bash
cd /var/www/delivery-dispatch

# Install PHP dependencies (production only, optimized)
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm ci --production
```

### Step 4: Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Edit environment file
nano .env
```

Configure production values:

```env
APP_NAME="Delivery Dispatch"
APP_ENV=production
APP_KEY=  # Will generate in next step
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_dispatch
DB_USERNAME=delivery_user
DB_PASSWORD=<STRONG_DB_PASSWORD>

BROADCAST_CONNECTION=reverb
CACHE_STORE=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Reverb Configuration
REVERB_APP_ID=<GENERATE_RANDOM_ID>
REVERB_APP_KEY=<GENERATE_RANDOM_KEY>
REVERB_APP_SECRET=<GENERATE_RANDOM_SECRET>
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com

# POS Integration
POS_WEBHOOK_API_KEY=<GENERATE_STRONG_API_KEY>

# Mail Configuration (example: Mailgun)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Generate secure keys:

```bash
# Generate APP_KEY
php artisan key:generate

# Generate Reverb credentials
php artisan reverb:install
# Answer: yes to update .env

# Generate POS API key (strong random string)
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
# Copy output to POS_WEBHOOK_API_KEY in .env
```

### Step 5: Set Permissions

```bash
cd /var/www/delivery-dispatch

# Set ownership
sudo chown -R deployer:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Storage and cache writable
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R deployer:www-data storage bootstrap/cache
```

---

## Database Setup

### Step 1: Create Database and User

```bash
# Login to MySQL as root
sudo mysql
```

In MySQL prompt:

```sql
-- Create database
CREATE DATABASE delivery_dispatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace <STRONG_DB_PASSWORD> with actual password from .env)
CREATE USER 'delivery_user'@'localhost' IDENTIFIED BY '<STRONG_DB_PASSWORD>';

-- Grant privileges
GRANT ALL PRIVILEGES ON delivery_dispatch.* TO 'delivery_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify
SHOW DATABASES;
SELECT user, host FROM mysql.user WHERE user = 'delivery_user';

-- Exit
EXIT;
```

### Step 2: Test Database Connection

```bash
cd /var/www/delivery-dispatch

# Test connection
php artisan tinker
```

In Tinker:

```php
DB::connection()->getPdo();
// Should show: PDO object
exit
```

### Step 3: Run Migrations

```bash
cd /var/www/delivery-dispatch

# Run migrations
php artisan migrate --force

# Verify tables
php artisan tinker
```

In Tinker:

```php
DB::select('SHOW TABLES');
// Should show all tables
exit
```

### Step 4: Optimize Database for Spatial Queries

```bash
sudo mysql delivery_dispatch
```

In MySQL:

```sql
-- Verify spatial indexes exist
SHOW INDEX FROM riders WHERE Key_name LIKE '%pos%';
SHOW INDEX FROM rider_locations WHERE Key_name LIKE '%pos%';
SHOW INDEX FROM orders WHERE Key_name LIKE '%pos%';

-- If missing, add them
ALTER TABLE riders ADD SPATIAL INDEX idx_latest_pos(latest_pos);
ALTER TABLE rider_locations ADD SPATIAL INDEX idx_pos(pos);
ALTER TABLE orders ADD SPATIAL INDEX idx_dest_pos(dest_pos);

-- Optimize tables
OPTIMIZE TABLE riders, rider_locations, orders;

EXIT;
```

### Step 5: Configure MySQL Performance

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/modify under `[mysqld]`:

```ini
# InnoDB settings
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query cache (disabled in MySQL 8.0, but good to note)
# query_cache_size = 0

# Connection settings
max_connections = 150
wait_timeout = 600

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1
```

Restart MySQL:

```bash
sudo systemctl restart mysql

# Verify settings
sudo mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
```

---

## Redis Configuration

### Step 1: Verify Redis is Running

```bash
# Check status
sudo systemctl status redis-server

# Test connection
redis-cli ping
# Should return: PONG

# Check memory
redis-cli INFO memory | grep used_memory_human
```

### Step 2: Test Laravel Redis Connection

```bash
cd /var/www/delivery-dispatch

php artisan tinker
```

In Tinker:

```php
use Illuminate\Support\Facades\Redis;

// Test connection
Redis::ping();
// Should return: "+PONG"

// Set test value
Redis::set('test_key', 'Hello from Laravel');

// Get test value
Redis::get('test_key');
// Should return: "Hello from Laravel"

// Clean up
Redis::del('test_key');

exit
```

### Step 3: Monitor Redis (Optional)

```bash
# Watch Redis commands in real-time
redis-cli monitor

# In another terminal, test Laravel cache
cd /var/www/delivery-dispatch
php artisan cache:remember test_cache 60 "return 'cached value';"

# Should see SET command in monitor
# Press Ctrl+C to exit monitor
```

---

## Laravel Reverb Setup

### Step 1: Install Reverb

Already installed via composer, verify:

```bash
cd /var/www/delivery-dispatch

# Check if Reverb is installed
composer show laravel/reverb
# Should show version info
```

### Step 2: Configure Reverb

Verify `.env` has Reverb settings:

```bash
nano .env
```

Ensure these exist:

```env
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://yourdomain.com
```

Check `config/reverb.php`:

```bash
cat config/reverb.php
```

### Step 3: Create Supervisor Config for Reverb

```bash
sudo nano /etc/supervisor/conf.d/reverb.conf
```

Add:

```ini
[program:reverb]
process_name=%(program_name)s
command=php /var/www/delivery-dispatch/artisan reverb:start --host=127.0.0.1 --port=8080
user=deployer
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/delivery-dispatch/storage/logs/reverb.log
stopwaitsecs=60
```

Update Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb

# Check status
sudo supervisorctl status reverb
# Should show: RUNNING
```

### Step 4: Test Reverb

```bash
# Check if Reverb is listening
sudo netstat -tulpn | grep 8080
# Should show: tcp  0  0 127.0.0.1:8080  LISTEN

# Check logs
tail -f /var/www/delivery-dispatch/storage/logs/reverb.log
```

---

## Queue Workers Setup

### Step 1: Create Supervisor Config for Queue Workers

```bash
sudo nano /etc/supervisor/conf.d/delivery-dispatch-worker.conf
```

Add:

```ini
[program:delivery-dispatch-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/delivery-dispatch/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
user=deployer
numprocs=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/delivery-dispatch/storage/logs/worker.log
stopwaitsecs=60
```

Update Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start delivery-dispatch-worker:*

# Check status
sudo supervisorctl status
# Should show 3 workers RUNNING
```

### Step 2: Test Queue

```bash
cd /var/www/delivery-dispatch

php artisan tinker
```

In Tinker:

```php
// Dispatch a test job
dispatch(function() {
    logger('Test job executed!');
});

exit
```

Check logs:

```bash
tail -f storage/logs/laravel.log
# Should see: Test job executed!
```

### Step 3: Setup Laravel Scheduler

```bash
# Edit crontab as deployer user
crontab -e
```

Add this line:

```cron
* * * * * cd /var/www/delivery-dispatch && php artisan schedule:run >> /dev/null 2>&1
```

Verify scheduler:

```bash
cd /var/www/delivery-dispatch
php artisan schedule:list
```

Should show:
```
0 4 * * * php artisan rider-locations:cleanup .... Next Due: XX hours from now
```

---

## Nginx Configuration

### Step 1: Create Nginx Server Block

```bash
sudo nano /etc/nginx/sites-available/delivery-dispatch
```

Add comprehensive configuration:

```nginx
# Upstream for PHP-FPM
upstream php-fpm {
    server unix:/run/php/php8.3-fpm.sock;
}

# Upstream for Laravel Reverb
upstream reverb {
    server 127.0.0.1:8080;
}

# Rate limiting zone for API
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=60r/m;

# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Allow Let's Encrypt verification
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/delivery-dispatch/public;
        allow all;
    }

    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$host$request_uri;
    }
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/delivery-dispatch/public;
    index index.php index.html;

    # SSL certificates (will be added by Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/yourdomain.com/chain.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Logging
    access_log /var/log/nginx/delivery-dispatch-access.log;
    error_log /var/log/nginx/delivery-dispatch-error.log;

    # Max upload size
    client_max_body_size 20M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json application/javascript;

    # Laravel Reverb WebSocket proxy
    location /app {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }

    # Laravel Reverb health check
    location /reverb/health {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
    }

    # Rate limiting on rider location endpoint
    location ~ ^/api/rider/locations {
        limit_req zone=api_limit burst=10 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Laravel public directory
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass php-fpm;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Deny access to sensitive files
    location ~ /(?:\.env|\.git|composer\.json|composer\.lock|package\.json|package-lock\.json) {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

### Step 2: Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/delivery-dispatch /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t
# Should show: syntax is ok
```

### Step 3: Temporary HTTP Configuration (for SSL setup)

Before getting SSL, modify the config temporarily:

```bash
sudo nano /etc/nginx/sites-available/delivery-dispatch
```

Comment out SSL lines:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/delivery-dispatch/public;
    index index.php index.html;

    # ... rest of config without SSL directives
}
```

Reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## SSL/TLS Setup

### Step 1: Install Certbot

```bash
# Install Certbot and Nginx plugin
sudo apt install -y certbot python3-certbot-nginx
```

### Step 2: Obtain SSL Certificate

```bash
# Get certificate (replace yourdomain.com with your actual domain)
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Follow prompts:
# - Enter email address
# - Agree to terms of service
# - Choose whether to share email with EFF (optional)
# - Redirect HTTP to HTTPS: Yes (2)
```

Certbot will:
1. Verify domain ownership
2. Issue SSL certificate
3. Automatically configure Nginx
4. Setup auto-renewal

### Step 3: Verify SSL

Visit: `https://yourdomain.com`

Check SSL rating:
- Go to: https://www.ssllabs.com/ssltest/
- Enter: yourdomain.com
- Should get: A or A+ rating

### Step 4: Test Auto-Renewal

```bash
# Dry run renewal
sudo certbot renew --dry-run
# Should show: success

# Check renewal timer
sudo systemctl status certbot.timer
# Should be active
```

### Step 5: Update .env with HTTPS

```bash
nano /var/www/delivery-dispatch/.env
```

Update:

```env
APP_URL=https://yourdomain.com
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
```

Clear cache:

```bash
cd /var/www/delivery-dispatch
php artisan config:cache
php artisan optimize
```

---

## Security Hardening

### Step 1: Configure ModSecurity (Web Application Firewall)

```bash
# Install ModSecurity
sudo apt install -y libmodsecurity3 modsecurity-crs

# Configure Nginx ModSecurity module (optional, advanced)
# Skip for now, can add later if needed
```

### Step 2: Install and Configure Logwatch

```bash
# Install logwatch
sudo apt install -y logwatch

# Send daily email reports
sudo logwatch --output mail --mailto your@email.com --detail high
```

### Step 3: Harden PHP Configuration

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Add/modify:

```ini
; Security settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; File upload restrictions
file_uploads = On
upload_max_filesize = 20M
max_file_uploads = 20

; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

Create log directory:

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

### Step 4: Configure Secure MySQL

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add under `[mysqld]`:

```ini
# Bind to localhost only
bind-address = 127.0.0.1

# Disable LOAD DATA LOCAL INFILE
local-infile = 0

# Enable binary logging (for backups and replication)
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### Step 5: SSH Security Enhancements

Already done earlier, but verify:

```bash
# Check SSH config
sudo cat /etc/ssh/sshd_config | grep -E 'PermitRootLogin|PasswordAuthentication|PermitEmptyPasswords'
```

Should show:
```
PermitRootLogin no
PasswordAuthentication no
PermitEmptyPasswords no
```

### Step 6: Enable Automatic Security Updates

```bash
# Install unattended-upgrades
sudo apt install -y unattended-upgrades

# Configure
sudo dpkg-reconfigure -plow unattended-upgrades
# Answer: Yes

# Edit config
sudo nano /etc/apt/apt.conf.d/50unattended-upgrades
```

Ensure these are uncommented:

```
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}-security";
};
Unattended-Upgrade::Automatic-Reboot "false";
Unattended-Upgrade::Mail "your@email.com";
```

### Step 7: File Integrity Monitoring (Optional)

```bash
# Install AIDE
sudo apt install -y aide

# Initialize database
sudo aideinit

# Copy database
sudo cp /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# Run check
sudo aide --check
```

---

## Monitoring & Logging

### Step 1: Laravel Pulse Dashboard

Access at: `https://yourdomain.com/pulse`

Configure authentication:

```bash
nano app/Providers/AppServiceProvider.php
```

Add to `boot()` method:

```php
use Laravel\Pulse\Facades\Pulse;

public function boot(): void
{
    Pulse::auth(function ($request) {
        // Restrict to authenticated admin users
        return $request->user() && $request->user()->is_admin;
    });
}
```

### Step 2: Laravel Telescope (Development Only)

If enabled in production, restrict access:

```bash
nano app/Providers/TelescopeServiceProvider.php
```

Modify `gate()` method:

```php
protected function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return in_array($user->email, [
            'admin@yourdomain.com',
        ]);
    });
}
```

**RECOMMENDED:** Disable Telescope in production:

```bash
nano .env
```

Set:

```env
TELESCOPE_ENABLED=false
```

### Step 3: Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/delivery-dispatch
```

Add:

```
/var/www/delivery-dispatch/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 deployer www-data
    sharedscripts
}

/var/log/nginx/delivery-dispatch-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        if [ -f /var/run/nginx.pid ]; then
            kill -USR1 `cat /var/run/nginx.pid`
        fi
    endscript
}
```

Test:

```bash
sudo logrotate -d /etc/logrotate.d/delivery-dispatch
```

### Step 4: Monitor Server Resources

Install monitoring tools:

```bash
# Install htop, iotop, nethogs
sudo apt install -y htop iotop nethogs

# Real-time monitoring
htop           # CPU/Memory
sudo iotop     # Disk I/O
sudo nethogs   # Network
```

### Step 5: Setup Uptime Monitoring (External)

Use external service:
- **UptimeRobot** (free): https://uptimerobot.com/
- **Pingdom** (paid): https://www.pingdom.com/

Monitor endpoints:
- `https://yourdomain.com/api/health` (create health endpoint)
- `https://yourdomain.com/pulse` (if accessible)

Create health endpoint:

```bash
nano routes/api.php
```

Add:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'error',
            'redis' => Redis::ping() ? 'ok' : 'error',
        ],
    ]);
});
```

### Step 6: Email Alerts for Critical Errors

Configure Laravel to email on critical errors:

```bash
nano config/logging.php
```

Add Slack/email channel:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

Update `.env`:

```env
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

---

## Backup Configuration

### Step 1: Enable Hetzner Automated Backups

Already enabled during server creation (€2.63/month).

Backups run daily and keep 7 snapshots.

**To restore:**
1. Go to Hetzner Cloud Console
2. Click server → Backups
3. Select backup → Restore

### Step 2: Database Backup Script

```bash
sudo nano /usr/local/bin/backup-database.sh
```

Add:

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/var/backups/mysql"
DB_NAME="delivery_dispatch"
DB_USER="delivery_user"
DB_PASS="<YOUR_DB_PASSWORD>"
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

# Timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz"

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_FILE

# Verify backup
if [ -f "$BACKUP_FILE" ]; then
    echo "Backup successful: $BACKUP_FILE"
    echo "Size: $(du -h $BACKUP_FILE | cut -f1)"
else
    echo "Backup failed!"
    exit 1
fi

# Delete old backups
find $BACKUP_DIR -name "${DB_NAME}_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Old backups cleaned up (retention: $RETENTION_DAYS days)"
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/backup-database.sh
```

### Step 3: Setup Automated Daily Backups

```bash
sudo crontab -e
```

Add:

```cron
# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-database.sh >> /var/log/database-backup.log 2>&1
```

### Step 4: Test Backup

```bash
sudo /usr/local/bin/backup-database.sh

# Verify
ls -lh /var/backups/mysql/
```

### Step 5: Backup .env File (Manually)

```bash
# Copy .env to secure location (NOT in web directory)
sudo cp /var/www/delivery-dispatch/.env /root/.env.backup.$(date +%Y%m%d)

# Verify
sudo ls -la /root/.env.*
```

### Step 6: Off-site Backup (Recommended)

Install rclone for remote backups:

```bash
# Install rclone
curl https://rclone.org/install.sh | sudo bash

# Configure for S3, Google Drive, etc.
rclone config

# Example: Sync to S3
rclone sync /var/backups/mysql/ s3:your-bucket/mysql-backups/
```

Or use Hetzner Storage Box (paid):
```bash
# Mount storage box
sudo apt install -y cifs-utils
sudo mkdir -p /mnt/storage-box
# Add to /etc/fstab for persistent mount
```

### Step 7: Disaster Recovery Plan

**To restore from backup:**

1. **Restore Server from Hetzner Backup:**
   - Hetzner Console → Server → Backups → Restore

2. **Restore Database:**
   ```bash
   # Find latest backup
   ls -lht /var/backups/mysql/

   # Restore
   gunzip < /var/backups/mysql/delivery_dispatch_TIMESTAMP.sql.gz | mysql -u delivery_user -p delivery_dispatch
   ```

3. **Restore .env:**
   ```bash
   sudo cp /root/.env.backup.YYYYMMDD /var/www/delivery-dispatch/.env
   ```

4. **Restart services:**
   ```bash
   sudo systemctl restart php8.3-fpm nginx mysql redis-server
   sudo supervisorctl restart all
   ```

---

## Performance Optimization

### Step 1: Laravel Optimization

```bash
cd /var/www/delivery-dispatch

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload -o

# Combined optimization
php artisan optimize
```

**Add to deployment script:**

```bash
nano /usr/local/bin/deploy.sh
```

Add:

```bash
#!/bin/bash
cd /var/www/delivery-dispatch

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache everything
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart services
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "Deployment complete!"
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/deploy.sh
```

### Step 2: PHP OPcache Verification

```bash
# Check if OPcache is enabled
php -i | grep opcache.enable
# Should show: opcache.enable => On => On

# Check OPcache stats
php -r "print_r(opcache_get_status());"
```

### Step 3: Database Query Optimization

Monitor slow queries:

```bash
# Enable slow query log (already done in MySQL config)
sudo tail -f /var/log/mysql/slow-query.log
```

Use Laravel Pulse to identify slow queries:
- Visit: `https://yourdomain.com/pulse`
- Check "Slow Queries" tab

Optimize identified queries:
- Add indexes
- Use eager loading (with())
- Cache results

### Step 4: Redis Optimization

```bash
# Check Redis memory usage
redis-cli INFO memory

# Check hit rate
redis-cli INFO stats | grep keyspace
```

Monitor cache hit ratio in Laravel Pulse.

### Step 5: Asset Optimization

Build production assets:

```bash
cd /var/www/delivery-dispatch

# If using Vite (Laravel 11+)
npm run build

# Clear old assets
rm -rf public/build-old
```

Verify assets are versioned and cached (already configured in Nginx).

### Step 6: Enable HTTP/2

Already enabled in Nginx config (`listen 443 ssl http2`).

Verify:

```bash
curl -I --http2 https://yourdomain.com
# Should show: HTTP/2 200
```

### Step 7: Configure MySQL Connection Pooling

Edit PHP-FPM pool:

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Ensure:

```ini
pm = dynamic
pm.max_children = 25
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
```

This allows connection reuse.

### Step 8: Implement CDN (Optional)

For static assets, use Cloudflare CDN (free):

1. Sign up at Cloudflare
2. Add your domain
3. Update nameservers
4. Enable Auto Minify (CSS, JS, HTML)
5. Enable Brotli compression
6. Set caching rules

---

## Troubleshooting

### Common Issues & Solutions

#### 1. 502 Bad Gateway

**Cause:** PHP-FPM not running or socket issue

**Solution:**
```bash
# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Restart if needed
sudo systemctl restart php8.3-fpm

# Check socket permissions
ls -la /run/php/php8.3-fpm.sock
# Should show: www-data www-data

# Check Nginx error log
sudo tail -f /var/log/nginx/error.log
```

#### 2. 500 Internal Server Error

**Cause:** Laravel error, check logs

**Solution:**
```bash
# Check Laravel logs
tail -f /var/www/delivery-dispatch/storage/logs/laravel.log

# Check PHP errors
sudo tail -f /var/log/php/error.log

# Clear cache
cd /var/www/delivery-dispatch
php artisan optimize:clear
```

#### 3. WebSocket Not Connecting

**Cause:** Reverb not running or firewall blocking

**Solution:**
```bash
# Check Reverb status
sudo supervisorctl status reverb

# Restart Reverb
sudo supervisorctl restart reverb

# Check logs
tail -f /var/www/delivery-dispatch/storage/logs/reverb.log

# Test connection
curl http://127.0.0.1:8080
# Should return Reverb info
```

#### 4. Queue Jobs Not Processing

**Cause:** Queue workers not running

**Solution:**
```bash
# Check worker status
sudo supervisorctl status delivery-dispatch-worker:*

# Restart workers
sudo supervisorctl restart delivery-dispatch-worker:*

# Check logs
tail -f /var/www/delivery-dispatch/storage/logs/worker.log

# Check failed jobs
cd /var/www/delivery-dispatch
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### 5. Database Connection Refused

**Cause:** MySQL not running or wrong credentials

**Solution:**
```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u delivery_user -p delivery_dispatch

# Check .env credentials
cat /var/www/delivery-dispatch/.env | grep DB_

# Clear config cache
cd /var/www/delivery-dispatch
php artisan config:clear
php artisan config:cache
```

#### 6. High Memory Usage

**Cause:** Too many PHP-FPM workers or memory leak

**Solution:**
```bash
# Check memory usage
free -h
htop

# Check PHP-FPM processes
ps aux | grep php-fpm

# Reduce max_children if needed
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
# Set pm.max_children = 15 (reduce from 25)

# Restart
sudo systemctl restart php8.3-fpm
```

#### 7. SSL Certificate Renewal Failing

**Cause:** Nginx config blocking Let's Encrypt

**Solution:**
```bash
# Check certbot logs
sudo tail -f /var/log/letsencrypt/letsencrypt.log

# Ensure .well-known is accessible
sudo nano /etc/nginx/sites-available/delivery-dispatch
# Verify location ^~ /.well-known/acme-challenge/ exists

# Manual renewal
sudo certbot renew --dry-run
```

#### 8. Slow Page Load Times

**Cause:** Missing cache or N+1 queries

**Solution:**
```bash
# Check Laravel Pulse
# Visit: https://yourdomain.com/pulse

# Enable query logging temporarily
php artisan tinker
```

In Tinker:
```php
DB::enableQueryLog();
// Run problematic request
DB::getQueryLog();
```

Look for duplicate queries (N+1 problem).

#### 9. Disk Space Full

**Cause:** Logs not rotating or old backups

**Solution:**
```bash
# Check disk usage
df -h

# Find large files
sudo du -h /var/www/delivery-dispatch | sort -rh | head -20

# Clear old logs
sudo find /var/log -type f -name "*.log" -mtime +30 -delete

# Clear old backups
sudo find /var/backups/mysql -type f -mtime +7 -delete

# Clear Laravel logs (if too large)
cd /var/www/delivery-dispatch
truncate -s 0 storage/logs/laravel.log
```

#### 10. GPS Location Data Growing Too Fast

**Cause:** Cleanup command not running

**Solution:**
```bash
# Verify cron is set
crontab -l | grep schedule:run

# Run cleanup manually
cd /var/www/delivery-dispatch
php artisan rider-locations:cleanup

# Check database size
sudo mysql delivery_dispatch -e "
SELECT table_name AS 'Table',
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'delivery_dispatch'
ORDER BY (data_length + index_length) DESC;"
```

---

## Deployment Workflow

### Production Deployment Checklist

**Every deployment:**

```bash
# 1. SSH to server
ssh deployer@yourdomain.com

# 2. Navigate to app directory
cd /var/www/delivery-dispatch

# 3. Put application in maintenance mode
php artisan down

# 4. Pull latest code
git pull origin main

# 5. Install dependencies
composer install --no-dev --optimize-autoloader

# 6. Run migrations
php artisan migrate --force

# 7. Clear and rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 8. Restart services
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm

# 9. Bring application back online
php artisan up

# 10. Verify deployment
curl -I https://yourdomain.com
# Should show: HTTP/2 200
```

### Automated Deployment Script

Create deployment script:

```bash
nano /home/deployer/deploy.sh
```

Add:

```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

cd /var/www/delivery-dispatch

# Maintenance mode
php artisan down || true

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear cache
echo "🧹 Clearing cache..."
php artisan optimize:clear

# Rebuild cache
echo "⚡ Rebuilding cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart services
echo "🔄 Restarting services..."
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm

# Exit maintenance mode
php artisan up

echo "✅ Deployment complete!"

# Health check
echo "🏥 Running health check..."
curl -f https://yourdomain.com/api/health || echo "⚠️  Health check failed!"

echo "🎉 Deployment successful!"
```

Make executable:

```bash
chmod +x /home/deployer/deploy.sh
```

Usage:

```bash
~/deploy.sh
```

---

## Cost Summary

### Monthly Costs

| Item | Cost | Annual |
|------|------|--------|
| **Hetzner CPX31** | €13.15 | €157.80 |
| **Backups** | €2.63 | €31.56 |
| **Domain** | ~€1.00 | ~€12.00 |
| **Email (Mailgun)** | $0-35 | $0-420 |
| **Total** | **~€17** | **~€201** |

**In USD: ~$19/month or $220/year**

### Cost Scaling

| Scale | Server | Monthly | Annual |
|-------|--------|---------|--------|
| **Current (50 riders)** | CPX31 | $19 | $228 |
| **Growth (100 riders)** | CPX41 | $31 | $372 |
| **Enterprise (200+ riders)** | CCX23 | $45 | $540 |

---

## Support & Maintenance

### Monthly Maintenance Tasks

- [ ] Review Pulse metrics for slow queries
- [ ] Check disk space usage
- [ ] Review error logs
- [ ] Verify backups are running
- [ ] Check SSL certificate expiry (auto-renewed)
- [ ] Review failed jobs in queue
- [ ] Monitor database size growth
- [ ] Check for security updates

### Quarterly Tasks

- [ ] Review and optimize database indexes
- [ ] Audit user access and permissions
- [ ] Test disaster recovery process
- [ ] Review and update dependencies
- [ ] Security audit (check for vulnerabilities)

### Useful Commands Reference

```bash
# Server status
sudo systemctl status php8.3-fpm nginx mysql redis-server
sudo supervisorctl status

# View logs
tail -f /var/www/delivery-dispatch/storage/logs/laravel.log
sudo tail -f /var/log/nginx/delivery-dispatch-error.log

# Database size
sudo mysql delivery_dispatch -e "SELECT table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES WHERE table_schema = 'delivery_dispatch';"

# Clear all caches
cd /var/www/delivery-dispatch && php artisan optimize:clear

# Restart all services
sudo supervisorctl restart all && sudo systemctl reload php8.3-fpm nginx

# Check disk space
df -h

# Monitor resources
htop
```

---

## Conclusion

You now have a **production-ready deployment** of your Delivery Dispatch application on **Hetzner CPX31** server.

### Key Achievements

✅ **Cost-effective**: $19/month (vs $48+ on DigitalOcean)
✅ **Secure**: SSL, firewall, fail2ban, hardened SSH
✅ **High-performance**: NVMe SSD, OPcache, Redis, HTTP/2
✅ **Scalable**: Easy upgrade path to larger servers
✅ **Monitored**: Pulse dashboard, logs, uptime monitoring
✅ **Backed up**: Daily database backups + Hetzner snapshots
✅ **Automated**: Queue workers, scheduler, auto-updates

### What You Can Handle

- **50+ riders** with real-time GPS tracking
- **500+ orders/day** with room to grow
- **10,000-20,000 location updates/day**
- **200+ concurrent WebSocket connections**

### Next Steps

1. **Test thoroughly** before going live
2. **Setup monitoring** (UptimeRobot)
3. **Configure email alerts** (Slack/email)
4. **Train your team** on the admin dashboard
5. **Monitor Pulse** for first few weeks
6. **Scale up** when you hit 100+ riders

### Getting Help

- **Laravel Docs**: https://laravel.com/docs
- **Hetzner Support**: https://docs.hetzner.com/
- **Community**: https://laracasts.com/discuss

---

**🎉 Congratulations! Your delivery dispatch system is now live on Hetzner!**

*Last updated: 2025-11-29*
