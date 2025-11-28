# Deployment Checklist

**Quick reference checklist for deploying to Laravel Forge + Hetzner**

Use this as your step-by-step guide during deployment. Check off items as you complete them.

---

## Pre-Deployment (1-2 hours before)

### Code Preparation

- [ ] All code committed and pushed to `main` branch
- [ ] `.env.example` file is up to date with all required variables
- [ ] Frontend builds successfully (`npm run build`)
- [ ] Tests passing (`php artisan test`)
- [ ] No `dd()`, `dump()`, or `var_dump()` statements in code
- [ ] All hardcoded values moved to environment variables

### Database Backup

- [ ] Export current database to SQL file
  ```bash
  mysqldump -u root -p delivery_dispatch > backup_$(date +%Y%m%d).sql
  ```
- [ ] Verify backup file size is reasonable
- [ ] Save backup file securely (local + cloud)
- [ ] Test backup can be imported (optional but recommended)

### Credentials Preparation

- [ ] Document current environment variables
- [ ] Generate new production API keys
  ```bash
  # New POS Webhook Key
  php -r "echo bin2hex(random_bytes(32));"
  ```
- [ ] Generate new Reverb credentials
  ```bash
  # APP_ID: Random number (e.g., 335156)
  # APP_KEY: php -r "echo bin2hex(random_bytes(10));"
  # APP_SECRET: php -r "echo bin2hex(random_bytes(10));"
  ```
- [ ] Prepare mail service credentials (if using SMTP)

---

## Phase 1: Hetzner Setup (10 minutes)

### Account & API Token

- [ ] Create Hetzner Cloud account at https://www.hetzner.com
- [ ] Verify email address
- [ ] Create new project: "Production Server"
- [ ] Generate API token:
  - Go to **Security** → **API Tokens**
  - Name: "Laravel Forge"
  - Permissions: **Read & Write**
- [ ] **SAVE TOKEN SECURELY** (you'll only see it once!)

---

## Phase 2: Laravel Forge Setup (10 minutes)

### Forge Account

- [ ] Sign up at https://forge.laravel.com
- [ ] Choose **Growth Plan** ($12/month)
- [ ] Complete registration
- [ ] Connect GitHub account
  - **Source Control** → Connect GitHub
  - Authorize Forge
- [ ] Add Hetzner as server provider:
  - **Server Providers** → Connect Provider
  - Choose **Hetzner Cloud**
  - Name: "Hetzner Production"
  - Paste API token
  - Save

---

## Phase 3: Server Provisioning (15 minutes)

### Create Server in Forge

- [ ] Click **"Create Server"**
- [ ] Configure:
  - **Provider:** Hetzner Cloud
  - **Name:** `production`
  - **Size:** CX31 (2 CPU, 8 GB RAM)
  - **Region:** Choose closest to users (e.g., Nuremberg)
  - **PHP Version:** 8.2
  - **Enable weekly backups:** ✓
  - **Install Database:** MySQL 8.0
  - **Database Name:** `delivery_dispatch`
  - **Install Redis:** ✓
  - **Install Node.js:** ✓
- [ ] Click **"Create Server"**
- [ ] Wait 5-10 minutes for provisioning
- [ ] Verify server status shows **Active** (green)
- [ ] Note server IP address: `_______________`

---

## Phase 4: Site Deployment (20 minutes)

### Create Site

- [ ] On server page → **"New Site"**
- [ ] Configure:
  - **Root Domain:** `yourdomain.com` (or server IP)
  - **Project Type:** Laravel
  - **Web Directory:** `/public`
  - **PHP Version:** 8.2
  - **Create Database:** `delivery_dispatch`
- [ ] Click **"Add Site"**

### Install Repository

- [ ] Go to site → **"Git Repository"** tab
- [ ] Configure:
  - **Provider:** GitHub
  - **Repository:** `yourusername/your-repo-name`
  - **Branch:** `main`
  - **Install Composer Dependencies:** ✓
  - **Install NPM Dependencies:** ✓
- [ ] Click **"Install Repository"**
- [ ] Wait for installation to complete (~2-5 minutes)

### Configure Environment Variables

- [ ] Go to site → **"Environment"** tab
- [ ] Update `.env` file with production values:

```env
# Critical Updates
APP_NAME="Delivery Dispatch System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com  # or http://SERVER_IP for now

# Business Config
PICKUP_LOCATION_LAT=40.7489
PICKUP_LOCATION_LNG=-73.9680

# Database (Forge auto-fills these, verify they're correct)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delivery_dispatch
DB_USERNAME=forge
DB_PASSWORD=<check-forge-generated-value>

# Performance (IMPORTANT: Use Redis!)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (Forge auto-fills)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb WebSocket (UPDATE THESE!)
REVERB_APP_ID=<new-random-id>
REVERB_APP_KEY=<new-random-key>
REVERB_APP_SECRET=<new-random-secret>
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Vite (for frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Logging
LOG_LEVEL=error

# Mail (configure later or use 'log' for now)
MAIL_MAILER=log

# POS Integration
POS_WEBHOOK_API_KEY=<new-secure-key>
```

- [ ] Click **"Save"**

### Generate Application Key

- [ ] In site → **"Commands"** section or SSH
- [ ] Run command:
  ```bash
  php artisan key:generate
  ```
- [ ] Verify `APP_KEY` is updated in `.env`

---

## Phase 5: Database Setup (15 minutes)

### Import Database

**Choose one method:**

**Option A: Via Forge Database Tool (Small databases <10MB)**
- [ ] Site → **"Database"** tab
- [ ] Click database name
- [ ] Upload SQL backup file

**Option B: Via SSH (Recommended for larger databases)**
- [ ] Click **"SSH"** button in Forge to get SSH command
- [ ] Upload backup from local machine:
  ```bash
  scp backup_20241127.sql forge@YOUR_SERVER_IP:/home/forge/
  ```
- [ ] SSH into server:
  ```bash
  ssh forge@YOUR_SERVER_IP
  ```
- [ ] Import database:
  ```bash
  mysql -u forge -p delivery_dispatch < /home/forge/backup_20241127.sql
  ```
- [ ] Enter database password when prompted
- [ ] Wait for import to complete
- [ ] Cleanup:
  ```bash
  rm /home/forge/backup_20241127.sql
  ```

### Run Migrations (if needed)

- [ ] Via SSH or Forge Commands:
  ```bash
  php artisan migrate --force
  ```

### Optimize Application

- [ ] Run optimization commands:
  ```bash
  php artisan optimize
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```

---

## Phase 6: Laravel Features Configuration (30 minutes)

### Queue Workers

- [ ] Site → **"Queue"** tab
- [ ] Click **"New Worker"**
- [ ] Configure:
  - **Connection:** `redis`
  - **Queue:** `default`
  - **Processes:** `3`
  - **Max Tries:** `3`
  - **Sleep:** `3`
  - **Timeout:** `60`
  - **Force restart on deploy:** ✓
- [ ] Click **"Create Worker"**
- [ ] Verify worker is running (status: active)

### Scheduler (Cron Jobs)

- [ ] Site → **"Scheduler"** tab
- [ ] Click **"Enable Scheduler"**
- [ ] Verify enabled
- [ ] Test via SSH:
  ```bash
  php artisan schedule:list
  ```
- [ ] Should show:
  - Daily rider location cleanup at 04:30

### Laravel Reverb (WebSocket)

- [ ] Site → **"Daemons"** tab
- [ ] Click **"New Daemon"**
- [ ] Configure:
  - **Command:** `php artisan reverb:start --host=0.0.0.0 --port=8080`
  - **Directory:** `/home/forge/yourdomain.com`
  - **User:** `forge`
  - **Processes:** `1`
  - **Start on boot:** ✓
- [ ] Click **"Create Daemon"**
- [ ] Verify daemon is running

### Nginx WebSocket Proxy

- [ ] Site → **Files** → **Edit Nginx Configuration**
- [ ] Add before the last `}` in server block:

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

- [ ] Click **"Save"**
- [ ] Nginx auto-reloads

---

## Phase 7: SSL Certificate (5 minutes)

**Note:** Only if using a domain (skip if using IP for now)

### DNS Configuration (Do this FIRST, before SSL)

- [ ] Point domain A record to server IP
- [ ] Wait 5-30 minutes for DNS propagation
- [ ] Verify: `ping yourdomain.com` (should resolve to server IP)

### Install SSL Certificate

- [ ] Site → **"SSL"** tab
- [ ] Choose **"LetsEncrypt"**
- [ ] Click **"Obtain Certificate"**
- [ ] Wait for certificate installation (~1-2 minutes)
- [ ] Verify: Green padlock in browser

### Force HTTPS

- [ ] After SSL installed, check ✓ **"Force HTTPS"**
- [ ] All HTTP traffic redirects to HTTPS

---

## Phase 8: Deployment Configuration (10 minutes)

### Review Deploy Script

- [ ] Site → **"Deployment"** tab
- [ ] Review deployment script (default is good)
- [ ] If you have frontend assets, ensure `npm run build` is included
- [ ] Click **"Save"** if modified

### Enable Auto-Deploy

- [ ] Toggle ✅ **"Quick Deploy"**
- [ ] Now pushes to `main` branch auto-deploy

### Manual Deploy Test

- [ ] Click **"Deploy Now"**
- [ ] Watch deployment log
- [ ] Verify successful completion

---

## Phase 9: Testing & Verification (30 minutes)

### Basic Functionality Tests

- [ ] Visit site URL: `http://YOUR_DOMAIN_OR_IP`
- [ ] Homepage loads without errors
- [ ] Login works (test with user account)
- [ ] Dashboard accessible
- [ ] No browser console errors

### API Tests

Via SSH or locally:
```bash
# Health check
curl https://yourdomain.com/api/health

# Test authenticated endpoint (get token first)
curl -H "Accept: application/json" https://yourdomain.com/api/orders
```

- [ ] API endpoints respond correctly
- [ ] Authentication works

### WebSocket Test

- [ ] Open browser Developer Tools → Network → WS tab
- [ ] Should see WebSocket connection to `/app/...`
- [ ] Status: **Connected** (green)
- [ ] Test real-time updates (GPS location, order updates)

### Queue & Scheduler Tests

Via SSH:
```bash
# Test queue
php artisan queue:work redis --once
# Should process without errors

# Test scheduler
php artisan schedule:list
# Should show scheduled tasks

# Check supervisor (queue workers)
sudo supervisorctl status
# Should show workers running
```

- [ ] Queue jobs process correctly
- [ ] No errors in logs

### Performance Check

Via SSH:
```bash
# Check memory
free -h
# Should have 3-4 GB free

# Check CPU
htop
# Should be low usage (<20%)

# Check Redis
redis-cli ping
# Should respond: PONG

# Check MySQL
mysql -u forge -p delivery_dispatch -e "SELECT COUNT(*) FROM users;"
# Should return count
```

- [ ] Server resources healthy
- [ ] Services responding

### Log Check

Via SSH:
```bash
# Laravel logs
tail -f storage/logs/laravel.log
# Watch for errors

# Nginx errors
tail -f /var/log/nginx/error.log
# Should be minimal or empty
```

- [ ] No critical errors in logs

---

## Phase 10: Post-Deployment Setup (15 minutes)

### Database Backups

- [ ] Server → **"Backups"** tab
- [ ] Click **"Configure Backups"**
- [ ] Choose storage provider (S3, DigitalOcean Spaces, etc.)
- [ ] Configure:
  - **Frequency:** Daily
  - **Time:** 3:00 AM
  - **Retention:** 14 days
  - **Databases:** Select all
- [ ] Click **"Save Configuration"**
- [ ] Optional: Click **"Backup Now"** to test

### Monitoring Setup

- [ ] Server → **"Monitoring"** tab
- [ ] Configure alert thresholds:
  - **CPU:** 80%
  - **Memory:** 85%
  - **Disk:** 80%
- [ ] Add email for alerts
- [ ] Save settings

### Security Review

- [ ] Verify firewall is active (Forge handles this)
- [ ] Check SSH is key-based only (Forge configures this)
- [ ] Verify database only accessible locally
- [ ] Review Forge security settings

---

## Phase 11: Mobile App Update

### Update API Endpoints

- [ ] Update Rider mobile app configuration
  - Old: `https://laravel-cloud-url.com`
  - New: `https://yourdomain.com` (or `http://SERVER_IP`)
- [ ] Update Supervisor mobile app configuration
- [ ] Test mobile app connectivity
- [ ] Verify GPS updates work
- [ ] Verify real-time order updates work

---

## Phase 12: Go Live (Launch!)

### Final Checks

- [ ] All features tested and working
- [ ] Mobile apps updated and tested
- [ ] Backups configured
- [ ] SSL certificate installed (if using domain)
- [ ] Monitoring alerts configured
- [ ] Documentation updated

### Launch

- [ ] Announce maintenance window (if applicable)
- [ ] Switch DNS to new server (if applicable)
- [ ] Update mobile app backend URLs
- [ ] Monitor logs closely for 1-2 hours
- [ ] Test with real users (small group first)
- [ ] Announce launch complete

### Monitor First 24 Hours

**Check every few hours:**

- [ ] Error rates in logs
- [ ] Server resource usage (CPU, RAM, disk)
- [ ] WebSocket connectivity
- [ ] Queue job processing
- [ ] Database performance
- [ ] User feedback

**Via SSH:**
```bash
# Quick health check
free -h && df -h && sudo supervisorctl status
tail -n 50 storage/logs/laravel.log | grep -i error
```

---

## Troubleshooting

### If Something Goes Wrong

**Site showing 502 Bad Gateway:**
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

**Queue jobs not processing:**
```bash
# Via Forge: Site → Queue → Restart button
# Or SSH:
php artisan queue:restart
sudo supervisorctl restart all
```

**WebSocket not connecting:**
- Check Reverb daemon is running (Forge → Daemons → Restart)
- Verify Nginx config has WebSocket proxy location
- Check firewall allows port 8080

**500 Server Error:**
```bash
# Check storage permissions
sudo chown -R forge:forge storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Emergency Rollback

**Option 1: Via Forge**
- Site → **Deployments** tab
- Find last working deployment
- Click **"Redeploy"**

**Option 2: Point back to old server**
- Update DNS back to old server
- Update mobile apps to old URL
- Fix issues on new server at your pace

---

## Success Criteria

✅ **Deployment is successful when:**

- [ ] Website loads without errors
- [ ] Users can log in
- [ ] API endpoints work
- [ ] WebSocket connects and updates in real-time
- [ ] GPS tracking works from mobile apps
- [ ] Orders can be created and assigned
- [ ] Queue jobs process automatically
- [ ] Scheduled tasks run on schedule
- [ ] No critical errors in logs
- [ ] Server resources healthy (memory, CPU, disk)
- [ ] Backups configured and tested
- [ ] SSL certificate installed (if using domain)
- [ ] Mobile apps successfully connect

---

## Post-Launch Tasks (First Week)

### Daily (Days 1-7)

- [ ] Review error logs
- [ ] Check resource usage
- [ ] Verify backups completed
- [ ] Monitor user feedback
- [ ] Check queue processing

### End of Week 1

- [ ] Review performance metrics
- [ ] Optimize based on learnings
- [ ] Document any issues and solutions
- [ ] Plan any necessary improvements
- [ ] Celebrate successful launch! 🎉

---

## Costs Tracking

| Item | Cost | Status |
|------|------|--------|
| Laravel Forge | $12/month | Active from: ______ |
| Hetzner CX31 | $12/month | Active from: ______ |
| SSL Certificate | Free | Auto-renewing |
| S3 Backups | ~$1-2/month | Active from: ______ |
| **Total** | **~$25-26/month** | |

**Annual Estimate:** ~$300-312/year

---

## Documentation

### Save These for Reference

- [ ] Server IP: `_______________`
- [ ] Database password: (stored in `.env` on server)
- [ ] Reverb credentials: (stored in `.env` on server)
- [ ] POS Webhook key: (stored in `.env` on server)
- [ ] Backup storage credentials
- [ ] SSH command: `ssh forge@_______________`

### Update Project Documentation

- [ ] Update README.md with production info
- [ ] Document deployment process for team
- [ ] Add production URL to project docs
- [ ] Update API documentation if needed

---

## Need Help?

**Resources:**
- Laravel Forge Docs: https://forge.laravel.com/docs
- Hetzner Support: Via console ticket system
- Laravel Docs: https://laravel.com/docs
- Main Migration Guide: `FORGE_HETZNER_MIGRATION_GUIDE.md`
- Deployment Prep: `FORGE_DEPLOYMENT_PREP.md`

**Emergency Contact:**
- Laravel Forge Support: support@laravel.com
- Hetzner Support: Create ticket in console

---

## Estimated Timeline

| Phase | Time | Your Status |
|-------|------|------------|
| Pre-Deployment | 1-2 hours | ⏳ |
| Hetzner Setup | 10 minutes | ⏳ |
| Forge Setup | 10 minutes | ⏳ |
| Server Provisioning | 15 minutes | ⏳ |
| Site Deployment | 20 minutes | ⏳ |
| Database Setup | 15 minutes | ⏳ |
| Features Config | 30 minutes | ⏳ |
| SSL Setup | 5 minutes | ⏳ |
| Testing | 30 minutes | ⏳ |
| Post-Deploy | 15 minutes | ⏳ |
| **Total** | **~3 hours** | |

**Mark your start time:** __________
**Target completion:** __________

---

**Good luck with your deployment! 🚀**

*Tip: Keep this checklist open during deployment and check off items as you go. It helps track progress and ensures nothing is missed.*
