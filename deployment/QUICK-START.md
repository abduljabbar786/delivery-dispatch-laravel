# Quick Start Deployment Guide

**Time:** ~2 hours
**Cost:** €11.90/month (~$13/month)
**Server:** Hetzner Cloud CPX31

---

## Prerequisites

- Domain name (e.g., api.yourcompany.com)
- GitHub access token (already in deploy.sh)
- SSH key pair

---

## Step-by-Step Deployment

### 1. Create Hetzner Server (5 min)

1. Go to https://console.hetzner.cloud/
2. Create project: "Delivery Dispatch"
3. Add server:
   - Image: **Ubuntu 24.04 LTS**
   - Type: **CPX31** (€11.90/month)
   - SSH Key: Add your public key
4. Note server IP address

### 2. Configure DNS (5 min)

Point your domain to the server IP:
```
A Record:     api.yourcompany.com -> YOUR_SERVER_IP
A Record:     www.api.yourcompany.com -> YOUR_SERVER_IP
```

### 3. Upload Deployment Scripts (2 min)

```bash
# From your local machine
cd /home/purelogics/code/development/delivery-dispatch-laravel
scp -r deployment/ root@YOUR_SERVER_IP:/root/
```

### 4. Run Server Provisioning (15 min)

```bash
# SSH to server
ssh root@YOUR_SERVER_IP

# Run provisioning
cd /root/deployment
chmod +x *.sh
bash server-provision.sh
```

**IMPORTANT:** Note the database password shown at the end!

### 5. Update MySQL Passwords (2 min)

```bash
# Update root password
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'StrongRootPass123!';"

# Update app user password
sudo mysql -e "ALTER USER 'delivery_user'@'localhost' IDENTIFIED BY 'StrongDbPass123!';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 6. Configure MySQL (2 min)

```bash
sudo cp /root/deployment/mysql-optimized.cnf /etc/mysql/mysql.conf.d/99-delivery-dispatch.cnf
sudo systemctl restart mysql
```

### 7. Configure Nginx (5 min)

```bash
sudo cp /root/deployment/nginx-config.conf /etc/nginx/sites-available/delivery-dispatch

# Update domain name
sudo nano /etc/nginx/sites-available/delivery-dispatch
# Replace "your-domain.com" with your actual domain

# Enable site
sudo ln -s /etc/nginx/sites-available/delivery-dispatch /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

### 8. Install SSL Certificate (5 min)

```bash
sudo certbot --nginx -d api.yourcompany.com -d www.api.yourcompany.com
# Follow prompts, select redirect HTTP to HTTPS
```

### 9. Deploy Application (15 min)

```bash
# Switch to deploy user
sudo su - deploy

# Clone repository (use SSH or configure GitHub token)
cd /var/www
git clone https://github.com/abduljabbar786/delivery-dispatch-laravel.git delivery-dispatch
# OR use SSH: git clone git@github.com:abduljabbar786/delivery-dispatch-laravel.git delivery-dispatch
cd delivery-dispatch

# Setup environment
cp .env.example .env
nano .env
```

**Update .env (minimum required):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourcompany.com

DB_PASSWORD=StrongDbPass123!

REVERB_APP_ID=123456
REVERB_APP_KEY=your-random-key
REVERB_APP_SECRET=your-random-secret

FRONTEND_URL=https://delivery-dispatch-react.vercel.app
```

**Install and build:**
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan key:generate
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R deploy:www-data /var/www/delivery-dispatch
sudo chmod -R 755 /var/www/delivery-dispatch
sudo chmod -R 775 storage bootstrap/cache
```

### 10. Configure Supervisor (5 min)

```bash
# Exit deploy user
exit

# Setup Supervisor
sudo cp /root/deployment/supervisor-config.conf /etc/supervisor/conf.d/delivery-dispatch.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start delivery-dispatch:*

# Verify
sudo supervisorctl status
```

**Expected output:**
```
delivery-dispatch:delivery-dispatch-queue-worker_00  RUNNING
delivery-dispatch:delivery-dispatch-queue-worker_01  RUNNING
delivery-dispatch:delivery-dispatch-queue-worker_02  RUNNING
delivery-dispatch:delivery-dispatch-reverb           RUNNING
delivery-dispatch:delivery-dispatch-scheduler        RUNNING
```

### 11. Setup Cron (2 min)

```bash
sudo su - deploy
cd /var/www/delivery-dispatch/deployment
bash setup-cron.sh
exit
```

### 12. Setup Backups (5 min)

```bash
sudo cp /root/deployment/backup.sh /usr/local/bin/delivery-dispatch-backup.sh
sudo chmod +x /usr/local/bin/delivery-dispatch-backup.sh

# Update password
sudo nano /usr/local/bin/delivery-dispatch-backup.sh
# Change: DB_PASSWORD="StrongDbPass123!"

# Add to cron
sudo crontab -e
# Add: 0 3 * * * /usr/local/bin/delivery-dispatch-backup.sh >> /var/log/delivery-dispatch/backup.log 2>&1
```

### 13. Test Application (10 min)

```bash
# Test API
curl https://api.yourcompany.com/api/health

# Test WebSocket
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" https://api.yourcompany.com:8080

# Check logs
sudo tail -f /var/www/delivery-dispatch/storage/logs/laravel.log
```

### 14. Update Frontend Config (5 min)

Update your React frontend environment variables:
```env
VITE_API_URL=https://api.yourcompany.com
VITE_WS_URL=wss://api.yourcompany.com:8080
```

Redeploy frontend on Vercel.

---

## Verification Checklist

- [ ] Server accessible via SSH
- [ ] MySQL running: `sudo systemctl status mysql`
- [ ] Redis running: `redis-cli ping` (returns PONG)
- [ ] Nginx running: `sudo systemctl status nginx`
- [ ] SSL certificate installed (green padlock in browser)
- [ ] Application responds: `curl https://api.yourcompany.com`
- [ ] Supervisor services running: `sudo supervisorctl status`
- [ ] Cron configured: `sudo -u deploy crontab -l`
- [ ] Backups scheduled: `sudo crontab -l`
- [ ] WebSocket working (test from frontend)

---

## Post-Deployment

### Access Monitoring

1. **Laravel Pulse:** https://api.yourcompany.com/pulse
   - Monitor requests, queries, exceptions

2. **Server Stats:**
   ```bash
   htop                    # CPU/Memory
   df -h                   # Disk space
   redis-cli info memory   # Redis memory
   ```

### Manual Cleanup (Optional)

```bash
# Test cleanup commands
php artisan rider-locations:cleanup --dry-run
php artisan orders:cleanup --dry-run
```

### Future Deployments

```bash
sudo su - deploy
cd /var/www/delivery-dispatch/deployment
bash deploy.sh main
```

---

## Common Issues

### Issue: SSL Certificate Fails

**Solution:**
```bash
# Wait for DNS to propagate (5-60 minutes)
# Verify DNS: nslookup api.yourcompany.com
# Try certbot again
```

### Issue: Permission Denied

**Solution:**
```bash
cd /var/www/delivery-dispatch
sudo chown -R deploy:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### Issue: Queue Workers Not Running

**Solution:**
```bash
sudo supervisorctl restart delivery-dispatch-queue-worker:*
sudo supervisorctl tail -f delivery-dispatch-queue-worker_00
```

### Issue: 502 Bad Gateway

**Solution:**
```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm

# Check Nginx
sudo nginx -t
sudo systemctl restart nginx
```

---

## Cost Breakdown

| Item | Cost/Month | Cost/Year |
|------|------------|-----------|
| Hetzner CPX31 | €11.90 | €142.80 |
| Domain | ~$1 | ~$12 |
| **Total** | **~$14** | **~$168** |

**Additional optional costs:**
- Email service (Amazon SES): ~$10/month
- CDN (Cloudflare): Free
- Monitoring (free with Pulse)
- Backups (included in server)

---

## Emergency Contacts

**Need help?**
- Server issues: Check `/var/log/delivery-dispatch/error.log`
- Application errors: Check `/var/www/delivery-dispatch/storage/logs/laravel.log`
- Database issues: Check `/var/log/mysql/error.log`

**Quick restart everything:**
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo supervisorctl restart all
```

---

**Congratulations!** Your application is now live at `https://api.yourcompany.com`

Next steps:
1. Test all features (orders, riders, tracking)
2. Monitor performance with Pulse
3. Setup monitoring alerts
4. Train your team on the system

---

*Deployment time: ~2 hours*
*Monthly cost: ~$14*
*Supports: 60-100 riders, 1000-2000 orders/day*
