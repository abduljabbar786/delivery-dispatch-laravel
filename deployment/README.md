# Deployment Guide - Laravel Delivery Dispatch Application

**Server:** Hetzner Cloud CPX31
**Cost:** €11.90/month (~$13/month)
**Specs:** 4 vCPU, 8GB RAM, 160GB NVMe SSD
**Scale:** Supports 60-100 riders, 2 branches, 1000-2000 orders/day

---

## Table of Contents

1. [Server Setup](#server-setup)
2. [Initial Configuration](#initial-configuration)
3. [Application Deployment](#application-deployment)
4. [Post-Deployment Setup](#post-deployment-setup)
5. [Monitoring & Maintenance](#monitoring--maintenance)
6. [Troubleshooting](#troubleshooting)
7. [Scaling Guide](#scaling-guide)

---

## Server Setup

### 1. Create Hetzner Server

1. Go to [Hetzner Cloud Console](https://console.hetzner.cloud/)
2. Create a new project: "Delivery Dispatch Production"
3. Add new server:
   - **Location:** Choose nearest to your users (e.g., US East, Germany, Finland)
   - **Image:** Ubuntu 24.04 LTS
   - **Type:** CPX31 (4 vCPU, 8GB RAM, 160GB SSD)
   - **SSH Key:** Add your public SSH key
   - **Name:** delivery-dispatch-prod
4. Note the server IP address

### 2. Initial Server Access

```bash
# Connect to your server
ssh root@YOUR_SERVER_IP

# Update hostname
hostnamectl set-hostname delivery-dispatch-prod

# Update system
apt update && apt upgrade -y
```

---

## Initial Configuration

### 3. Run Server Provisioning Script

```bash
# On your local machine, upload deployment scripts
scp -r deployment/ root@YOUR_SERVER_IP:/root/

# Connect to server
ssh root@YOUR_SERVER_IP

# Navigate to deployment directory
cd /root/deployment

# Make scripts executable
chmod +x *.sh

# Run provisioning script
bash server-provision.sh
```

**This script will:**
- Install PHP 8.2, Composer, Node.js
- Install MySQL 8.0 and create database
- Install and configure Redis
- Install and configure Nginx
- Install Supervisor
- Create deployment user
- Configure firewall

**IMPORTANT:** After provisioning, update these passwords:
```bash
# Update MySQL root password
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'YOUR_SECURE_ROOT_PASSWORD';"

# Update database user password
sudo mysql -e "ALTER USER 'delivery_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_DB_PASSWORD';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 4. Configure MySQL

```bash
# Copy optimized MySQL configuration
sudo cp /root/deployment/mysql-optimized.cnf /etc/mysql/mysql.conf.d/99-delivery-dispatch.cnf

# Restart MySQL
sudo systemctl restart mysql

# Verify MySQL is running
sudo systemctl status mysql
```

### 5. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp /root/deployment/nginx-config.conf /etc/nginx/sites-available/delivery-dispatch

# Update domain name in the config
sudo nano /etc/nginx/sites-available/delivery-dispatch
# Replace "your-domain.com" with your actual domain

# Enable site
sudo ln -s /etc/nginx/sites-available/delivery-dispatch /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 6. Configure SSL Certificate

```bash
# Install SSL certificate with Let's Encrypt
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Certbot will automatically update Nginx config
# Certificate will auto-renew via cron
```

---

## Application Deployment

### 7. Deploy Application (as deploy user)

```bash
# Switch to deploy user
sudo su - deploy

# Clone repository (use SSH or configure GitHub token)
cd /var/www
git clone https://github.com/abduljabbar786/delivery-dispatch-laravel.git delivery-dispatch
# OR use SSH: git clone git@github.com:abduljabbar786/delivery-dispatch-laravel.git delivery-dispatch

cd delivery-dispatch

# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env
```

**Update .env file:**
```env
APP_NAME="Delivery Dispatch"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_dispatch
DB_USERNAME=delivery_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=https

# Frontend URL (for CORS)
FRONTEND_URL=https://delivery-dispatch-react.vercel.app
```

**Install dependencies and setup:**
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install NPM dependencies
npm ci --production=false

# Build frontend assets
npm run build

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Set permissions
sudo chown -R deploy:www-data /var/www/delivery-dispatch
sudo chmod -R 755 /var/www/delivery-dispatch
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
```

---

## Post-Deployment Setup

### 8. Configure Supervisor (Queue Workers & Reverb)

```bash
# Exit from deploy user
exit

# Copy Supervisor configuration
sudo cp /root/deployment/supervisor-config.conf /etc/supervisor/conf.d/delivery-dispatch.conf

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start all services
sudo supervisorctl start delivery-dispatch:*

# Check status
sudo supervisorctl status
```

**You should see:**
```
delivery-dispatch:delivery-dispatch-queue-worker_00  RUNNING
delivery-dispatch:delivery-dispatch-queue-worker_01  RUNNING
delivery-dispatch:delivery-dispatch-queue-worker_02  RUNNING
delivery-dispatch:delivery-dispatch-reverb           RUNNING
delivery-dispatch:delivery-dispatch-scheduler        RUNNING
```

### 9. Setup Cron for Laravel Scheduler

```bash
# Switch to deploy user
sudo su - deploy

# Setup cron
cd /var/www/delivery-dispatch/deployment
bash setup-cron.sh

# Verify cron is installed
crontab -l
```

### 10. Setup Automated Backups

```bash
# Exit from deploy user
exit

# Create backup script
sudo mkdir -p /var/backups/delivery-dispatch

# Copy backup script
sudo cp /root/deployment/backup.sh /usr/local/bin/delivery-dispatch-backup.sh
sudo chmod +x /usr/local/bin/delivery-dispatch-backup.sh

# Update database password in backup script
sudo nano /usr/local/bin/delivery-dispatch-backup.sh
# Update: DB_PASSWORD="YOUR_SECURE_DB_PASSWORD"

# Setup daily backup cron (as root)
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/delivery-dispatch-backup.sh >> /var/log/delivery-dispatch/backup.log 2>&1") | crontab -

# Test backup manually
sudo /usr/local/bin/delivery-dispatch-backup.sh
```

---

## Monitoring & Maintenance

### Access Monitoring Tools

1. **Laravel Pulse** (Application Performance)
   - URL: `https://your-domain.com/pulse`
   - Monitor: Requests, slow queries, cache hits, exceptions

2. **Laravel Telescope** (Debugging - Disable in production)
   - URL: `https://your-domain.com/telescope`
   - Restrict access by IP in `nginx-config.conf`

### Important Commands

```bash
# Check application status
sudo supervisorctl status

# Restart queue workers
sudo supervisorctl restart delivery-dispatch-queue-worker:*

# Restart Reverb
sudo supervisorctl restart delivery-dispatch-reverb

# View application logs
tail -f /var/www/delivery-dispatch/storage/logs/laravel.log

# View Nginx access logs
tail -f /var/log/delivery-dispatch/access.log

# View Nginx error logs
tail -f /var/log/delivery-dispatch/error.log

# Check queue status
php artisan queue:monitor

# Check scheduled tasks
php artisan schedule:list

# Manual cleanup commands
php artisan rider-locations:cleanup --days=1
php artisan orders:cleanup --months=1 --dry-run
```

### Scheduled Cleanup Tasks

**Automated cleanup keeps database size manageable:**

1. **Daily Location Cleanup** (4:30 AM)
   - Deletes rider locations older than 1 day
   - Keeps database ~500MB instead of growing infinitely
   - Command: `php artisan rider-locations:cleanup --days=1`

2. **Monthly Order Cleanup** (1st of month, 5:00 AM)
   - Deletes DELIVERED and FAILED orders older than 1 month
   - Keeps active and recent orders only
   - Command: `php artisan orders:cleanup --months=1`

### Server Resource Monitoring

```bash
# Check disk usage
df -h

# Check memory usage
free -h

# Check CPU usage
htop

# Check MySQL performance
sudo mysqladmin -u root -p status
sudo mysqladmin -u root -p processlist

# Check Redis memory
redis-cli info memory
```

---

## Troubleshooting

### Queue Workers Not Processing Jobs

```bash
# Check Supervisor status
sudo supervisorctl status delivery-dispatch-queue-worker:*

# Restart workers
sudo supervisorctl restart delivery-dispatch-queue-worker:*

# Check queue connection
php artisan queue:monitor
```

### WebSocket (Reverb) Not Working

```bash
# Check Reverb status
sudo supervisorctl status delivery-dispatch-reverb

# View Reverb logs
tail -f /var/log/delivery-dispatch/reverb.log

# Restart Reverb
sudo supervisorctl restart delivery-dispatch-reverb

# Test WebSocket connection
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" https://your-domain.com:8080
```

### High Database Load

```bash
# Check slow queries
sudo tail -f /var/log/mysql/slow-query.log

# Check MySQL process list
sudo mysql -u root -p -e "SHOW FULL PROCESSLIST;"

# Optimize tables
php artisan db:show
sudo mysql -u root -p delivery_dispatch -e "OPTIMIZE TABLE rider_locations, orders;"
```

### Out of Disk Space

```bash
# Check disk usage by directory
sudo du -sh /var/www/delivery-dispatch/*
sudo du -sh /var/lib/mysql/*

# Clear old logs
sudo find /var/www/delivery-dispatch/storage/logs -name "*.log" -mtime +7 -delete

# Run cleanup commands
php artisan rider-locations:cleanup --days=1
php artisan orders:cleanup --months=1

# Clear Laravel cache
php artisan cache:clear
php artisan view:clear
```

---

## Scaling Guide

### When to Upgrade

**Upgrade to 2-server setup when:**
- Riders exceed 100
- Orders exceed 2000/day
- Database load consistently high (>70% CPU)
- Response times increase

### Scaling Path

**Current:** Single CPX31 (~$13/month)
- 60-100 riders
- 1000-2000 orders/day

**Phase 1:** Upgrade to CPX41 (~$25/month)
- 8 vCPU, 16GB RAM
- 100-200 riders
- 2000-4000 orders/day

**Phase 2:** Split into 2 servers (~$52/month)
- App Server: CPX41 (Nginx, PHP, Redis, Reverb)
- Database Server: CPX31 (MySQL only)
- 200+ riders
- 4000+ orders/day

**Phase 3:** Load balanced (~$80/month)
- Load Balancer: €5.83/month
- 2x App Servers: CPX31 each
- 1x Database Server: CPX41
- 500+ riders
- 10,000+ orders/day

### Database Optimization Tips

1. **Add indexes for frequent queries**
2. **Archive old data to separate database**
3. **Enable MySQL query cache tuning**
4. **Consider read replicas for reports**

---

## Quick Reference

### File Locations

```
Application:           /var/www/delivery-dispatch
Logs:                 /var/log/delivery-dispatch
Backups:              /var/backups/delivery-dispatch
Nginx Config:         /etc/nginx/sites-available/delivery-dispatch
Supervisor Config:    /etc/supervisor/conf.d/delivery-dispatch.conf
MySQL Config:         /etc/mysql/mysql.conf.d/99-delivery-dispatch.cnf
```

### Port Usage

```
HTTP:        80  (redirects to HTTPS)
HTTPS:       443 (Nginx - Laravel app)
WebSocket:   8080 (Reverb - SSL enabled)
MySQL:       3306 (localhost only)
Redis:       6379 (localhost only)
```

### Important URLs

```
Application:  https://your-domain.com
API:          https://your-domain.com/api
Pulse:        https://your-domain.com/pulse
WebSocket:    wss://your-domain.com:8080
```

---

## Future Deployment (Automated)

For subsequent deployments, use the deployment script:

```bash
# As deploy user
sudo su - deploy
cd /var/www/delivery-dispatch/deployment
bash deploy.sh main
```

This will:
1. Enable maintenance mode
2. Pull latest code from Git
3. Install dependencies
4. Build assets
5. Run migrations
6. Clear and cache config
7. Restart services
8. Disable maintenance mode

---

## Support & Resources

- **Laravel Documentation:** https://laravel.com/docs
- **Hetzner Docs:** https://docs.hetzner.com
- **Laravel Reverb:** https://reverb.laravel.com
- **Application Repository:** https://github.com/abduljabbar786/delivery-dispatch-laravel

---

**Deployment Checklist:**

- [ ] Server provisioned on Hetzner
- [ ] MySQL passwords updated
- [ ] Domain DNS configured
- [ ] SSL certificate installed
- [ ] Application deployed
- [ ] Environment variables configured
- [ ] Supervisor services running
- [ ] Cron jobs configured
- [ ] Backups scheduled
- [ ] Monitoring enabled
- [ ] Frontend connected to API
- [ ] WebSocket connections working
- [ ] Test orders and rider tracking

**Estimated total setup time:** 2-3 hours

---

*Last updated: 2025-11-29*
