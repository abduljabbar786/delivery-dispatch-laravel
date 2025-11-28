# Forge Deployment Preparation Guide

**Analysis of your current application for Laravel Forge deployment**

---

## Current Application Analysis

### Tech Stack Detected

✅ **Laravel 12** (Latest)
✅ **PHP 8.2+** required
✅ **MySQL** database (currently using)
✅ **Laravel Reverb** for WebSocket (self-hosted)
✅ **Redis** capable (currently using file cache)
✅ **Vite** for frontend assets
✅ **Tailwind CSS 4.0**
✅ **Laravel Sanctum** for API authentication
✅ **Laravel Octane** (optional performance boost)
✅ **Laravel Nightwatch** for database management

### Current Configuration Issues for Production

⚠️ **CRITICAL - Must Fix:**
1. `APP_ENV=local` → Must be `production`
2. `APP_DEBUG=true` → Must be `false`
3. `CACHE_STORE=file` → Should be `redis` for performance
4. `QUEUE_CONNECTION=database` → Should be `redis` for production
5. `SESSION_DRIVER=database` → Should be `redis` for performance
6. `LOG_LEVEL=debug` → Should be `error` or `warning`

⚠️ **WARNING - Review Required:**
1. Database credentials hardcoded (will change on Forge)
2. `REVERB_HOST=localhost` → Must update to production domain
3. `APP_URL=http://localhost` → Must update to production URL
4. Mail is logging only → Need real SMTP for production
5. `POS_WEBHOOK_API_KEY` exposed → Secure this value

✅ **Good Practices Found:**
1. Scheduled tasks configured in `routes/console.php`
2. Frontend build process defined
3. Proper Laravel packages installed
4. API authentication setup

---

## Pre-Migration Tasks Checklist

### 1. Update .env.example File ✓

Your `.env.example` needs updates for production. Create a production-ready version:

```bash
# Update .env.example
cp .env .env.example
```

**Edit `.env.example` to remove sensitive data:**
- Remove actual database password
- Remove actual API keys
- Remove actual Reverb credentials
- Keep structure and variable names

**Then commit:**
```bash
git add .env.example
git commit -m "Update .env.example for production deployment"
git push origin main
```

### 2. Database Backup ✓

**Export current database:**

```bash
# Option A: Using mysqldump
mysqldump -u root -p delivery_dispatch > backup_$(date +%Y%m%d).sql

# Option B: Using Laravel
php artisan db:backup  # If you have backup package installed
```

**Save backup securely:**
- Store locally
- Upload to cloud storage
- Keep encrypted if contains sensitive data

### 3. Review Dependencies ✓

**Check all packages are production-ready:**

```bash
# Update composer dependencies
composer update --no-dev

# Check for security vulnerabilities
composer audit

# Revert to dev dependencies for development
composer install
```

### 4. Frontend Assets Build Test ✓

**Ensure frontend builds without errors:**

```bash
# Install dependencies
npm install

# Test build
npm run build

# Check output in public/build
ls -la public/build
```

**If build succeeds, you're ready.**

### 5. Test Environment Variables ✓

Create a production environment template to prepare:

```bash
# Create production env template (don't commit this!)
cp .env .env.production.template
```

**Edit `.env.production.template` with production values:**
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Update `APP_URL` to your domain
- Configure Redis settings
- Update Reverb host

### 6. Review Code for Hardcoded Values ✓

Search for any hardcoded configurations:

```bash
# Search for localhost references
grep -r "localhost" app/ resources/ --exclude-dir=vendor

# Search for hardcoded IPs
grep -r "127.0.0.1" app/ resources/ --exclude-dir=vendor

# Search for hardcoded credentials
grep -ri "password\|secret\|key" app/ --exclude-dir=vendor
```

**Fix any hardcoded values** by moving them to `.env` file.

---

## Required Environment Variables for Forge

### Core Application Settings

```env
# Application
APP_NAME="Delivery Dispatch System"
APP_ENV=production
APP_KEY=base64:... # Forge will generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Timezone & Locale
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# Business Configuration
PICKUP_LOCATION_LAT=40.7489
PICKUP_LOCATION_LNG=-73.9680
```

### Database (Forge Auto-Configures)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_dispatch
DB_USERNAME=forge
DB_PASSWORD=<forge-generated>
```

### Cache & Sessions (IMPORTANT: Use Redis in Production)

```env
CACHE_STORE=redis
CACHE_PREFIX=delivery-dispatch-

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
```

### Queue Configuration (Use Redis)

```env
QUEUE_CONNECTION=redis
```

### Redis (Forge Auto-Configures)

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Broadcasting (Laravel Reverb)

```env
BROADCAST_CONNECTION=reverb

# Reverb WebSocket - UPDATE FOR PRODUCTION
REVERB_APP_ID=<generate-new-id>
REVERB_APP_KEY=<generate-new-key>
REVERB_APP_SECRET=<generate-new-secret>
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Reverb Advanced Settings
REVERB_APP_MAX_CONNECTIONS=200
REVERB_SCALING_ENABLED=true

# Vite Variables (for frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Generate new Reverb credentials:**
```bash
# Generate random strings for Reverb
php artisan reverb:install --no-interaction
# Or manually:
# APP_ID: Random number (e.g., 335156)
# APP_KEY: Random string (e.g., php -r "echo bin2hex(random_bytes(10));")
# APP_SECRET: Random string (e.g., php -r "echo bin2hex(random_bytes(10));")
```

### Logging

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error  # or 'warning' for more detail
LOG_DEPRECATIONS_CHANNEL=null
```

### Mail Configuration (IMPORTANT: Setup Real SMTP)

**Options:**

**Option A: Using Gmail SMTP (Free, Limited)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Option B: Using Mailgun (Recommended)**
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=your-mailgun-secret
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Option C: Using AWS SES (Cheapest at Scale)**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**For now, you can keep `log` during initial setup:**
```env
MAIL_MAILER=log
```

### POS Integration

```env
# Regenerate this for production security
POS_WEBHOOK_API_KEY=<generate-new-secure-key>
```

**Generate new secure key:**
```bash
php -r "echo bin2hex(random_bytes(32));"
# Copy output to POS_WEBHOOK_API_KEY
```

### Optional: Laravel Octane (Performance Boost)

If using Laravel Octane:
```env
OCTANE_SERVER=roadrunner  # or 'swoole'
```

---

## Files to Prepare Before Deployment

### 1. Update .gitignore

Ensure these are ignored:
```
.env
.env.backup
.env.production
*.sql
/vendor/
/node_modules/
/public/hot
/public/storage
/storage/*.key
/storage/logs
```

### 2. Create Production Deploy Script

Forge generates this, but you can customize it. Create `.forge/deploy.sh`:

```bash
#!/usr/bin/env bash

cd $FORGE_SITE_PATH

# Pull latest code
git pull origin $FORGE_SITE_BRANCH

# Install PHP dependencies
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install Node dependencies and build assets
npm ci
npm run build

# Run database migrations
$FORGE_PHP artisan migrate --force

# Cache configurations
$FORGE_PHP artisan optimize
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan event:cache

# Restart queue workers
$FORGE_PHP artisan queue:restart

# Restart PHP-FPM
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

**Commit this:**
```bash
mkdir -p .forge
# Create the file above
git add .forge/deploy.sh
git commit -m "Add Forge deployment script"
git push origin main
```

### 3. Ensure Storage Directories Exist

These must be writable on the server:
```
storage/app/
storage/framework/
storage/logs/
bootstrap/cache/
```

Forge handles permissions, but ensure they're in your repo structure.

---

## Database Migration Strategy

### Option A: Fresh Migration (Recommended if Small Dataset)

On Forge after deployment:
```bash
# SSH into Forge server
ssh forge@your-server-ip

# Navigate to site directory
cd /home/forge/yourdomain.com

# Run migrations
php artisan migrate --force

# Seed initial data if needed
php artisan db:seed --force
```

### Option B: Import Existing Database (If Large Dataset)

```bash
# Upload backup to server
scp backup_20241127.sql forge@your-server-ip:/home/forge/

# SSH into server
ssh forge@your-server-ip

# Import database
mysql -u forge -p delivery_dispatch < /home/forge/backup_20241127.sql

# Cleanup
rm /home/forge/backup_20241127.sql
```

---

## Post-Deployment Configuration

### 1. Queue Workers (Forge Dashboard)

**Configure in Forge UI:**
- Connection: `redis`
- Queue: `default`
- Processes: `3`
- Max Tries: `3`
- Timeout: `60`

### 2. Scheduler (Forge Dashboard)

**Enable in Forge UI:**
- Click "Enable Scheduler"
- Automatically runs: `php artisan schedule:run`

**Your scheduled tasks:**
- ✅ Daily rider location cleanup at 4:30 AM
- ✅ Optional weekly order archival (currently commented out)

### 3. Laravel Reverb Daemon (Forge Dashboard)

**Configure in Forge UI (Daemons):**
```bash
# Command
php artisan reverb:start --host=0.0.0.0 --port=8080

# Directory
/home/forge/yourdomain.com

# User
forge

# Processes
1
```

### 4. Nginx WebSocket Configuration

Add to your Nginx config in Forge:

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

---

## Performance Optimizations for Production

### 1. Enable PHP OPcache

Forge enables this by default. Verify:

```bash
php -i | grep opcache.enable
# Should output: opcache.enable => On => On
```

### 2. Laravel Caching Commands

Run after every deployment (already in deploy script):

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3. Composer Optimization

Already in deploy script:
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Database Indexing

Ensure proper indexes exist (should be in migrations):
- `orders` table: index on `status`, `rider_id`, `created_at`
- `rider_locations` table: index on `rider_id`, `created_at`
- `users` table: index on `email`, `type`

---

## Security Checklist

### Before Deployment

- [ ] Remove all `dd()`, `dump()`, `var_dump()` from code
- [ ] Set `APP_DEBUG=false` in production
- [ ] Generate new `APP_KEY` (Forge does this)
- [ ] Generate new `POS_WEBHOOK_API_KEY`
- [ ] Generate new Reverb credentials
- [ ] Update CORS settings if needed
- [ ] Review API rate limiting
- [ ] Set up proper error tracking (Sentry, Bugsnag)

### After Deployment

- [ ] Enable SSL certificate in Forge
- [ ] Force HTTPS
- [ ] Configure firewall (Forge handles this)
- [ ] Set up database backups
- [ ] Configure log rotation
- [ ] Test all API endpoints
- [ ] Test WebSocket connections
- [ ] Verify queue jobs are processing
- [ ] Verify scheduled tasks run correctly

---

## Testing Before Going Live

### 1. Local Production Simulation

Test with production settings locally:

```bash
# Create .env.testing with production-like settings
cp .env .env.testing

# Edit .env.testing to match production (except credentials)
# Set APP_ENV=testing
# Set APP_DEBUG=false
# Set CACHE_STORE=redis
# Set QUEUE_CONNECTION=redis

# Test
php artisan config:clear
php artisan test
```

### 2. Smoke Tests After Forge Deployment

```bash
# SSH into Forge server
ssh forge@your-server-ip

cd /home/forge/yourdomain.com

# Test artisan commands
php artisan about
php artisan config:show database
php artisan queue:work --once
php artisan schedule:list

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> \App\Models\User::count();

# Check logs
tail -f storage/logs/laravel.log
```

### 3. API Testing

```bash
# Test API endpoints
curl https://yourdomain.com/api/health
curl -H "Accept: application/json" https://yourdomain.com/api/orders
```

---

## Rollback Plan

### Quick Rollback via Forge

1. Go to Site → **Deployments**
2. Find last working deployment
3. Click **"Redeploy"**

### Manual Rollback via Git

```bash
# SSH into server
ssh forge@your-server-ip

cd /home/forge/yourdomain.com

# Revert to previous commit
git log --oneline  # Find commit hash
git reset --hard <previous-commit-hash>

# Run deploy commands manually
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
sudo service php8.2-fpm reload
```

---

## Common Issues & Solutions

### Issue: "No application encryption key has been specified"

**Solution:**
```bash
php artisan key:generate
```

### Issue: WebSocket not connecting (ERR_CONNECTION_REFUSED)

**Solutions:**
1. Check Reverb daemon is running (Forge → Daemons)
2. Verify Nginx config has WebSocket proxy
3. Check `.env` has correct `REVERB_HOST` and `REVERB_SCHEME`
4. Test: `curl http://localhost:8080` (should connect)

### Issue: Queue jobs not processing

**Solutions:**
1. Check queue workers are running (Forge → Queue)
2. Verify Redis connection: `redis-cli ping`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Restart workers: `php artisan queue:restart`

### Issue: 500 Server Error

**Solutions:**
1. Check storage permissions: `sudo chmod -R 775 storage bootstrap/cache`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Clear cache: `php artisan cache:clear && php artisan config:clear`
4. Check `.env` file exists and has correct values

### Issue: Assets not loading (404 on CSS/JS)

**Solutions:**
1. Run build: `npm run build`
2. Check public/build directory exists
3. Clear view cache: `php artisan view:clear`
4. Check Vite manifest: `cat public/build/manifest.json`

---

## Monitoring After Launch

### First 24 Hours

Monitor these metrics closely:

```bash
# Memory usage
free -h
# Should have at least 2-3 GB free

# CPU usage
htop
# Should be under 50% normally

# Disk space
df -h
# Should have plenty of free space

# Active connections
netstat -an | grep :80 | wc -l
# Monitor connection count

# MySQL connections
mysql -u forge -p -e "SHOW STATUS LIKE 'Threads_connected';"

# Redis memory
redis-cli INFO memory
```

### Log Monitoring

```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log

# Watch Nginx error logs
tail -f /var/log/nginx/error.log

# Watch PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

### Performance Metrics to Track

- Average response time (should be <500ms)
- Error rate (should be <1%)
- Queue processing time
- WebSocket connection count
- Database query performance

---

## Cost Tracking

### Monthly Costs

| Item | Cost |
|------|------|
| Laravel Forge | $12/month |
| Hetzner CX31 | $12/month |
| SSL Certificate | Free (Let's Encrypt) |
| Backups (S3) | ~$1-2/month |
| Mail Service (optional) | $0-10/month |
| **Total** | **$25-36/month** |

### Cost Optimization

- Start with Hetzner CX21 (4GB) if budget tight: Save $6/month
- Use free mail services initially (Gmail, Mailtrap)
- Enable Forge backups only for production databases
- Use CloudFlare for CDN (free tier)

---

## Summary

### Ready for Deployment? ✓

Check these items:

- [ ] `.env.example` updated and committed
- [ ] Database backed up
- [ ] Frontend builds successfully (`npm run build`)
- [ ] All environment variables documented
- [ ] Production configuration template ready
- [ ] Deployment script prepared (optional)
- [ ] Security keys regenerated for production
- [ ] Mail service configured (or plan to)
- [ ] Domain name ready (optional, can use IP initially)

### Deployment Time Estimate

- Hetzner + Forge setup: **30 minutes**
- Repository deployment: **10 minutes**
- Environment configuration: **15 minutes**
- Database import: **5-30 minutes** (depends on size)
- Queue/Scheduler/Reverb setup: **20 minutes**
- SSL certificate: **5 minutes**
- Testing: **30 minutes**
- **Total:** **2-3 hours** (mostly waiting for processes)

### Post-Deployment

- [ ] Update mobile apps with new API URL
- [ ] Test all features end-to-end
- [ ] Monitor logs for 24-48 hours
- [ ] Setup error tracking service
- [ ] Configure backup strategy
- [ ] Document production credentials securely

---

**You're now ready to deploy to Laravel Forge + Hetzner!**

Follow the main migration guide: `FORGE_HETZNER_MIGRATION_GUIDE.md`
