# Laravel Forge + Hetzner Migration Guide

**Migrating from Laravel Cloud Free Tier to Laravel Forge + Hetzner**

**Estimated Time:** 2-3 hours
**Cost:** $24/month ($12 Forge + $12 Hetzner)
**Difficulty:** Easy (Forge does most of the work)

---

## Prerequisites

- [ ] GitHub account with your repository
- [ ] Credit card for Forge and Hetzner
- [ ] Access to current database backup
- [ ] Domain name (optional but recommended)

---

## Phase 1: Preparation (15 minutes)

### 1.1 Backup Your Current Application

**On Laravel Cloud:**

```bash
# Export database
php artisan db:backup
# OR manually via phpMyAdmin/database tool
```

**Download backup locally or save to GitHub private repo**

### 1.2 Prepare Your Repository

Ensure your `.env.example` file exists with all required variables:

```bash
# Check if .env.example exists
ls -la .env.example

# If not, create from .env (remove sensitive values)
cp .env .env.example
```

**Edit `.env.example` to have placeholder values:**
- Remove actual passwords, API keys
- Keep structure and variable names
- Commit to repository

### 1.3 Document Current Configuration

Create a temporary file with current production values:

```bash
# Save locally (NOT in git)
cp .env production_env_backup.txt
```

You'll need these values:
- Database credentials
- Redis settings
- Broadcasting/Reverb settings
- Any API keys (Pusher, mail services, etc.)

---

## Phase 2: Hetzner Setup (10 minutes)

### 2.1 Create Hetzner Account

1. Visit: https://www.hetzner.com
2. Click "Sign Up"
3. Complete registration
4. Verify email

### 2.2 Generate API Token

1. Log into Hetzner Cloud Console: https://console.hetzner.cloud
2. Create a new project: "Production Server" (or any name)
3. Go to **Security** → **API Tokens**
4. Click "Generate API Token"
5. Name: "Laravel Forge"
6. Permissions: **Read & Write**
7. **SAVE THIS TOKEN** - you'll only see it once!

```
Example: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 2.3 Choose Server Location

Recommended locations:
- **Nuremberg, Germany** (eu-central) - Default, great connectivity
- **Falkenstein, Germany** (eu-central)
- **Helsinki, Finland** (eu-central)

Choose the location closest to your users.

---

## Phase 3: Laravel Forge Setup (10 minutes)

### 3.1 Sign Up for Laravel Forge

1. Visit: https://forge.laravel.com
2. Click "Sign Up" or "Start Free Trial"
3. Choose plan: **Growth** ($12/month)
4. 5-day free trial available - no credit card needed initially
5. Complete registration

### 3.2 Connect GitHub

1. In Forge dashboard → **Source Control**
2. Click "Connect GitHub"
3. Authorize Forge to access your repositories
4. Select repositories Forge can access

### 3.3 Add Hetzner as Server Provider

1. In Forge → **Server Providers**
2. Click "Connect Provider"
3. Choose **Hetzner Cloud**
4. Enter:
   - **Name:** Hetzner Production
   - **API Token:** (paste token from Step 2.2)
5. Click "Add Provider"

---

## Phase 4: Provision Server via Forge (15 minutes)

### 4.1 Create New Server

1. In Forge dashboard → Click **"Create Server"**
2. Fill in details:

**Server Provider:**
- Provider: **Hetzner Cloud**
- Credentials: Select the one you just added

**Server Details:**
- Name: `production` (or `delivery-dispatch-prod`)
- Size: **CX31** (2 CPU, 8 GB RAM, 80 GB SSD)
- Region: Select closest to users (e.g., Nuremberg)
- PHP Version: **8.2** (or 8.3 if your app supports it)

**Optional Features:**
- ✅ Enable weekly backups (HIGHLY recommended)
- ✅ Install Database (MySQL 8.0)
- ✅ Install Redis
- ✅ Install Node.js (needed for assets)

**Database:**
- Name: `delivery_dispatch` (or your database name)
- User: `forge` (default)
- Password: (Forge auto-generates, or set your own)

3. Click **"Create Server"**

**Wait 5-10 minutes** - Forge will:
- Create Hetzner VPS
- Install Nginx, PHP, MySQL, Redis, Node
- Configure firewall
- Set up SSH keys
- Harden security

### 4.2 Verify Server is Ready

When provisioning completes:
- Server status shows: **Active** (green)
- You'll receive email notification
- Server appears in Forge dashboard

---

## Phase 5: Deploy Your Application (20 minutes)

### 5.1 Create New Site

1. On your server page in Forge → Click **"New Site"**
2. Fill in:

**Site Details:**
- Root Domain: `yourdomain.com` (or use server IP for now)
- Project Type: **Laravel**
- Web Directory: `/public` (auto-filled)

**Advanced Options:**
- PHP Version: **8.2**
- ✅ Create Database: Select `delivery_dispatch`

3. Click **"Add Site"**

Forge creates site structure in: `/home/forge/yourdomain.com`

### 5.2 Install Repository

1. Click on your site → **"Git Repository"** tab
2. Fill in:
   - Provider: **GitHub**
   - Repository: `yourusername/your-repo-name`
   - Branch: `main` (or `master`)
   - ✅ Install Composer Dependencies
   - ✅ Install NPM Dependencies (if you have frontend assets)

3. Click **"Install Repository"**

**Forge will:**
- Clone your repository
- Run `composer install --no-dev --optimize-autoloader`
- Run `npm install && npm run build` (if package.json exists)
- Set correct permissions

### 5.3 Configure Environment Variables

1. In site → **"Environment"** tab
2. You'll see default `.env` file
3. **Update with your production values:**

```env
APP_NAME="Delivery Dispatch"
APP_ENV=production
APP_KEY=base64:... # Will generate this next
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

# Database (Forge auto-configures these)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_dispatch
DB_USERNAME=forge
DB_PASSWORD=your-forge-generated-password

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (Forge auto-configures)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb WebSocket
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_APP_MAX_CONNECTIONS=200
REVERB_SCALING_ENABLED=true

# Mail (configure your mail service)
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Any other API keys or services
# Add your specific configuration here
```

4. Click **"Save"**

### 5.4 Generate Application Key

1. In site → **"Commands"** section (or SSH)
2. Run:

```bash
php artisan key:generate
```

This updates `APP_KEY` in `.env` automatically.

### 5.5 Import Database

**Option A: Using Forge Database Tool**

1. Site → **"Database"** tab
2. Click on database name
3. Upload your SQL backup file

**Option B: Via SSH (Recommended for large databases)**

1. Click **"SSH"** in Forge (or use terminal)

```bash
# SSH into server (Forge provides SSH command)
ssh forge@your-server-ip

# Upload database backup (from your local machine)
# On local machine:
scp backup.sql forge@your-server-ip:/home/forge/

# On server:
mysql -u forge -p delivery_dispatch < /home/forge/backup.sql
# Enter database password when prompted

# Cleanup
rm /home/forge/backup.sql
```

### 5.6 Run Migrations & Seeders (if needed)

```bash
# Via Forge Commands or SSH
php artisan migrate --force
php artisan db:seed --force  # Only if needed
```

### 5.7 Optimize Application

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5.8 Test Application

1. Visit your site URL or server IP
2. Check if homepage loads
3. Test login functionality
4. Verify database connection

---

## Phase 6: Configure Laravel Features (30 minutes)

### 6.1 Setup Queue Workers

1. In Forge site → **"Queue"** tab
2. Click **"New Worker"**
3. Configure:
   - Connection: `redis`
   - Queue: `default`
   - Processes: `3` (start with 3, can increase)
   - Max Tries: `3`
   - Sleep: `3` (seconds)
   - Timeout: `60` (seconds)
   - ✅ Force restart on deploy

4. Click **"Create Worker"**

Forge automatically:
- Creates supervisor config
- Starts queue workers
- Monitors and restarts if crashed

### 6.2 Setup Scheduler (Cron Jobs)

1. In Forge site → **"Scheduler"** tab
2. Click **"Enable Scheduler"**

This adds Laravel scheduler to cron:
```bash
* * * * * php /home/forge/yourdomain.com/artisan schedule:run >> /dev/null 2>&1
```

Your scheduled tasks in `routes/console.php` will now run automatically:
- Rider location cleanup (daily at 4:30 AM)
- Any other scheduled tasks

**Verify scheduler:**
```bash
php artisan schedule:list
```

### 6.3 Setup Laravel Reverb (WebSocket)

1. In Forge site → **"Daemons"** tab
2. Click **"New Daemon"**
3. Configure:
   - Command: `php artisan reverb:start --host=0.0.0.0 --port=8080`
   - Directory: `/home/forge/yourdomain.com`
   - User: `forge`
   - Processes: `1`
   - ✅ Start on boot

4. Click **"Create Daemon"**

### 6.4 Configure Nginx for WebSocket

1. Site → **"Nginx"** tab (Edit Files → Nginx Configuration)
2. Find the `server` block and add before the last `}`

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 86400;
}
```

3. Click **"Save"**
4. Nginx auto-reloads

---

## Phase 7: SSL Certificate (5 minutes)

### 7.1 Setup SSL (If using domain)

1. Ensure domain DNS points to server IP
2. In site → **"SSL"** tab
3. Choose **"LetsEncrypt"**
4. Click **"Obtain Certificate"**

Forge will:
- Request Let's Encrypt certificate
- Install certificate
- Configure Nginx for HTTPS
- Setup auto-renewal

### 7.2 Force HTTPS

After SSL is installed:
1. Check ✅ **"Force HTTPS"** in SSL tab
2. All HTTP traffic redirects to HTTPS

---

## Phase 8: Deployment Configuration (10 minutes)

### 8.1 Setup Deploy Script

1. Site → **"Deployment"** tab
2. Review/edit the deployment script

**Default script (should be good):**
```bash
cd /home/forge/yourdomain.com
git pull origin main

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service php8.2-fpm reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan optimize
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
    $FORGE_PHP artisan queue:restart
fi
```

**Customize if needed** (e.g., add npm build):
```bash
# Add after composer install:
npm ci
npm run build
```

3. Click **"Save"**

### 8.2 Enable Quick Deploy

1. Toggle ✅ **"Quick Deploy"**
2. This auto-deploys when you push to main branch

### 8.3 Setup Deploy Webhook (Optional)

Forge provides webhook URL for CI/CD integration.

---

## Phase 9: Monitoring & Backups (15 minutes)

### 9.1 Enable Server Monitoring

Forge automatically monitors:
- CPU usage
- Memory usage
- Disk space
- Server uptime

**Configure alerts:**
1. Server page → **"Monitoring"** tab
2. Set alert thresholds:
   - CPU: 80%
   - Memory: 85%
   - Disk: 80%

### 9.2 Setup Database Backups

1. Server → **"Backups"** tab
2. Click **"Configure Backups"**
3. Choose provider:
   - **S3** (AWS)
   - **DigitalOcean Spaces**
   - **Dropbox**
   - **Custom S3-compatible**

**Recommendation: S3 (cheapest)**
- Cost: ~$0.50-2/month for daily backups
- Setup: Need AWS account + S3 bucket

4. Configure:
   - Frequency: **Daily**
   - Time: **3:00 AM** (low traffic time)
   - Retention: **14 days**
   - Databases: Select all

5. Click **"Save Configuration"**

**Manual backup:**
```bash
# Via Forge
Click "Backup Now" button

# Via SSH
mysqldump -u forge -p delivery_dispatch > backup_$(date +%Y%m%d).sql
```

### 9.3 Enable Log Monitoring

Forge provides log viewer:
1. Site → **"Logs"** tab
2. View:
   - Laravel logs (`storage/logs/laravel.log`)
   - Nginx access/error logs
   - PHP-FPM logs

**Setup log rotation** (automatic in Forge, but verify):
```bash
# SSH into server
ls -la /etc/logrotate.d/
# Should see nginx, php8.2-fpm configs
```

---

## Phase 10: Testing & Verification (20 minutes)

### 10.1 Functional Testing Checklist

- [ ] Homepage loads correctly
- [ ] User login works
- [ ] Supervisor dashboard accessible
- [ ] Rider app can connect (test on mobile)
- [ ] GPS location updates working
- [ ] Real-time updates via WebSocket working
- [ ] Order creation/assignment works
- [ ] Database queries performing well
- [ ] Redis cache working
- [ ] Queue jobs processing

### 10.2 Performance Testing

```bash
# SSH into server
# Check memory usage
free -h

# Check CPU
htop

# Check Redis
redis-cli INFO memory

# Check MySQL
mysql -u forge -p
> SHOW PROCESSLIST;
> SHOW STATUS LIKE 'Threads_connected';
```

### 10.3 WebSocket Testing

1. Open browser console on your site
2. Check WebSocket connection in Network tab
3. Should see: `ws://yourdomain.com/app/...` (or wss:// for SSL)
4. Connection status: **Connected**

### 10.4 Queue Worker Verification

```bash
# Via Forge Commands or SSH
php artisan queue:work redis --once

# Check supervisor status
sudo supervisorctl status
# Should show queue workers running
```

### 10.5 Scheduler Verification

```bash
php artisan schedule:list
# Verify your scheduled tasks appear

# Test run (doesn't wait for schedule)
php artisan schedule:run
```

---

## Phase 11: DNS & Domain Setup (if applicable)

### 11.1 Point Domain to Server

In your domain registrar (Namecheap, GoDaddy, Cloudflare, etc.):

**A Record:**
```
Type: A
Name: @ (or root)
Value: YOUR_SERVER_IP
TTL: 300 (5 minutes)
```

**WWW Subdomain (optional):**
```
Type: CNAME
Name: www
Value: yourdomain.com
TTL: 300
```

**Wait 5-30 minutes for DNS propagation**

### 11.2 Update Site in Forge

1. Site → **"Meta"** tab
2. Update domain if needed
3. Obtain SSL certificate (Phase 7)

---

## Phase 12: Post-Migration Optimization (10 minutes)

### 12.1 PHP OPcache Verification

```bash
# SSH into server
php -i | grep opcache

# Should see:
# opcache.enable => On
```

### 12.2 MySQL Optimization

Forge configures MySQL well by default, but verify:

```bash
mysql -u forge -p

SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
# Should be ~2-4G on 8GB server

SHOW VARIABLES LIKE 'max_connections';
# Should be 100-150
```

### 12.3 Application Optimizations

Already covered in deployment script:
- ✅ Config cached
- ✅ Routes cached
- ✅ Views cached
- ✅ Composer optimized

---

## Phase 13: Migration Completion (5 minutes)

### 13.1 Update Mobile Apps (If applicable)

Update API endpoint in:
- Rider mobile app
- Supervisor mobile app

From: `laravel-cloud-url.com`
To: `yourdomain.com` (or server IP)

### 13.2 Notify Users

Send notification about:
- Server migration complete
- Any downtime window
- New domain (if changed)

### 13.3 Monitor First 24 Hours

Watch for:
- Error rates in logs
- Performance issues
- WebSocket connectivity
- Queue job failures
- Memory/CPU usage spikes

---

## Troubleshooting Common Issues

### Issue: Site shows "502 Bad Gateway"

**Solution:**
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart if needed
sudo systemctl restart php8.2-fpm
```

### Issue: Queue jobs not processing

**Solution:**
```bash
# Check supervisor
sudo supervisorctl status

# Restart queue workers (via Forge)
# Site → Queue → Restart button
```

### Issue: WebSocket not connecting

**Solution:**
1. Check Reverb daemon is running (Site → Daemons)
2. Verify Nginx config has WebSocket proxy
3. Check firewall allows port 8080
4. Verify `.env` has correct Reverb settings

### Issue: Permission errors

**Solution:**
```bash
# Fix storage permissions
cd /home/forge/yourdomain.com
sudo chown -R forge:forge storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Issue: High memory usage

**Solution:**
```bash
# Check processes
htop

# Restart services to free memory
sudo systemctl restart php8.2-fpm
sudo systemctl restart mysql

# Adjust PHP-FPM workers if needed
# Edit: /etc/php/8.2/fpm/pool.d/www.conf
```

---

## Rollback Plan (Emergency)

If something goes wrong:

### Quick Rollback to Laravel Cloud

1. Keep Laravel Cloud instance running during migration
2. Update mobile apps to point back to old URL
3. Deploy fixes to Laravel Cloud

### Forge Rollback

1. Site → **"Deployments"** tab
2. View deployment history
3. Click **"Redeploy"** on previous working version

---

## Cost Breakdown

| Item | Cost | Billing |
|------|------|---------|
| Laravel Forge | $12/month | Monthly |
| Hetzner CX31 | €10.52 (~$12/month) | Monthly |
| SSL Certificate | Free (Let's Encrypt) | - |
| S3 Backups (optional) | ~$1-2/month | Monthly |
| **Total** | **~$24-26/month** | - |

**Annual:** ~$288-312/year

**Compare to:**
- Laravel Cloud Free: $0 but broken
- Laravel Cloud Paid: ~$360-720+/year
- Self-managed: $144/year (but your time cost)

---

## Maintenance Schedule

### Daily (Automatic)
- Database backups
- Log rotation
- Rider location cleanup
- SSL certificate check

### Weekly (Automatic via Forge)
- Security updates
- System package updates

### Monthly (Manual, 15 minutes)
- Review error logs
- Check disk space
- Review performance metrics
- Optimize database tables (if needed)

### Quarterly (Manual, 30 minutes)
- Review and update dependencies
- PHP version updates (test in staging first)
- Review and optimize caching strategy

---

## Next Steps After Migration

1. **Setup Staging Environment**
   - Create another CX21 Hetzner server ($6/month)
   - Use Forge to provision staging site
   - Test changes before production

2. **Implement Monitoring**
   - Laravel Pulse (built-in Laravel monitoring)
   - Or: Sentry, Bugsnag for error tracking

3. **Improve Deployment**
   - Setup CI/CD with GitHub Actions
   - Add automated tests before deploy

4. **Scale When Needed**
   - Monitor resource usage
   - Upgrade Hetzner plan if needed (one-click)
   - Add load balancer for multiple servers

---

## Support Resources

**Laravel Forge:**
- Docs: https://forge.laravel.com/docs
- Support: support@laravel.com

**Hetzner:**
- Docs: https://docs.hetzner.com
- Support: Via console ticket system

**Laravel:**
- Docs: https://laravel.com/docs
- Forum: https://laracasts.com/discuss
- Discord: https://discord.gg/laravel

---

## Summary

✅ **Estimated Total Time:** 2-3 hours (mostly waiting)
✅ **Difficulty:** Easy (Forge automates 80% of work)
✅ **Cost:** $24/month
✅ **Result:** Production-ready, scalable, monitored infrastructure

**You'll have:**
- Powerful server (8 GB RAM vs 512 MB)
- Automatic deployments
- SSL certificate
- Database backups
- Queue workers
- WebSocket support
- Professional monitoring
- Easy scaling path

**Worth it?** Absolutely. $24/month for peace of mind and working system.
