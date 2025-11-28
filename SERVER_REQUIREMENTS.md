# Production Server Requirements

**Application:** Delivery Dispatch System
**Scale:** 2 Supervisors, 50 Riders, 500+ Orders/Day
**Last Updated:** 2025-11-27

---

## Quick Summary

**Recommended Setup:** Single Server
**Cost:** $20-40/month
**Specs:** 2-4 vCPU, 4-8 GB RAM, 60 GB SSD

---

## Detailed Requirements

### 1. Application Server (Laravel/PHP)

**Minimum:**
- CPU: 2 vCPUs @ 2.5+ GHz
- RAM: 4 GB
- Storage: 40 GB SSD
- PHP: 8.2+ with extensions: pdo_mysql, mbstring, redis, gd, curl, xml, zip, bcmath

**Recommended:**
- CPU: 4 vCPUs
- RAM: 8 GB
- Storage: 60 GB SSD
- Web Server: Nginx + PHP-FPM (25-50 workers)

### 2. Database (MySQL 8.0+ / MariaDB 10.6+)

**Storage Growth (with daily cleanup):**
- Year 1: ~100-200 MB
- Year 2: ~200-400 MB
- Year 3: ~300-600 MB

**Configuration:**
- CPU: 2 vCPUs (can share with app server)
- RAM: 2-4 GB
- Storage: 40 GB SSD (sufficient for 5+ years)

**MySQL Settings:**
```ini
innodb_buffer_pool_size = 2G
max_connections = 150
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
```

### 3. Redis Cache

**Configuration:**
- RAM: 256-512 MB
- Persistence: RDB snapshots
- Storage: 2-5 GB (for RDB/AOF files)

**Usage:**
- Application cache (~500 KB)
- Sessions (~500 KB - 1 MB)
- Queue jobs (~250-500 KB)
- Broadcasting state (~100 KB)

### 4. WebSocket Server (Laravel Reverb)

**Configuration:**
- Can run on same server as Laravel
- CPU: 0.5-1 vCPU (shared)
- RAM: 512 MB - 1 GB
- Port: 8080 (proxied via Nginx)

**Capacity:**
- Max connections: 200 (configured)
- Current load: 52 connections
- Message throughput: ~50-100/sec

### 5. Queue Workers

**Configuration:**
- Runs on application server
- Workers: 2-5 concurrent processes
- RAM: ~256 MB per worker

---

## Network & Bandwidth

**Estimated Traffic:**
- GPS uploads: ~200-500 MB/day
- WebSocket: ~100-200 MB/day
- API requests: ~50-100 MB/day
- **Total:** ~500 MB - 1 GB/day (~30 GB/month)

**Recommendation:** 1 TB/month allowance

---

## Recommended Providers & Pricing

### Option 1: Hetzner (Best Value)
```
Server: CX21
- 2 vCPU, 4 GB RAM, 40 GB SSD
- 20 TB traffic
- €5.83/month (~$6.50/month)

OR

Server: CX31
- 2 vCPU, 8 GB RAM, 80 GB SSD
- 20 TB traffic
- €10.52/month (~$12/month)
```
**Recommended for: Budget-conscious, excellent performance**

### Option 2: DigitalOcean
```
Droplet: Basic
- 2 vCPU, 4 GB RAM, 80 GB SSD
- 4 TB traffic
- $24/month

OR

- 4 vCPU, 8 GB RAM, 160 GB SSD
- 5 TB traffic
- $48/month
```
**Recommended for: Easy management, good support**

### Option 3: Vultr
```
Cloud Compute
- 2 vCPU, 4 GB RAM, 80 GB SSD
- 3 TB traffic
- $18/month
```
**Recommended for: Global locations, flexibility**

---

## Infrastructure Setup

### Single Server (Recommended)

```
┌─────────────────────────────────────┐
│   Single VPS/Cloud Instance         │
├─────────────────────────────────────┤
│  ✓ Nginx (Web Server)               │
│  ✓ PHP-FPM (Laravel)                │
│  ✓ MySQL 8.0                        │
│  ✓ Redis 7.x                        │
│  ✓ Laravel Reverb (WebSocket)       │
│  ✓ Queue Workers                    │
└─────────────────────────────────────┘

Specs: 2-4 vCPU, 4-8 GB RAM, 60 GB SSD
Cost: $6-50/month depending on provider
```

---

## Daily Cleanup Configuration

### Automated Cleanup (Already Configured)

**Location:** `routes/console.php`

- **Rider Locations:** Daily at 4:30 AM (deletes previous days)
- **Old Orders:** Optional, weekly cleanup (commented out)

### Enable Scheduler

Add to server crontab:
```bash
* * * * * cd /home/purelogics/code/development/delivery-dispatch-laravel && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Cleanup Commands

```bash
# Cleanup rider locations (older than 1 day)
php artisan rider-locations:cleanup

# Keep last 3 days
php artisan rider-locations:cleanup --days=3

# Check scheduled tasks
php artisan schedule:list

# Test schedule without waiting
php artisan schedule:run
```

---

## Environment Configuration

### Required .env Settings for Production

```env
# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
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
```

---

## Monitoring & Maintenance

### Database Size Monitoring

```bash
# Check table sizes
mysql -u root -p -e "
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'delivery_dispatch'
ORDER BY (data_length + index_length) DESC;"
```

### Check Cleanup Status

```bash
# Laravel Tinker
php artisan tinker

# Count today's locations
>>> \App\Models\RiderLocation::whereDate('created_at', today())->count();

# Count all locations (should be low after cleanup)
>>> \App\Models\RiderLocation::count();
```

### Optimize Tables Monthly

```bash
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE rider_locations');
>>> DB::statement('OPTIMIZE TABLE orders');
>>> DB::statement('OPTIMIZE TABLE order_events');
```

---

## Performance Tips

1. **Enable PHP OPcache**
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   opcache.max_accelerated_files=20000
   ```

2. **Laravel Optimizations**
   ```bash
   php artisan optimize
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Nginx Configuration**
   ```nginx
   # WebSocket Proxy
   location /app {
       proxy_pass http://127.0.0.1:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
       proxy_read_timeout 86400;
   }
   ```

---

## Scaling Plan

### When to Scale

- **100+ riders** → Add dedicated database server (2 vCPU, 4-8 GB RAM)
- **1000+ orders/day** → Upgrade to 4-8 vCPU, 16 GB RAM
- **Multiple locations** → Consider load balancer + multiple app servers
- **High availability needs** → Database replication, Redis cluster

### Horizontal Scaling

When you need to scale beyond single server:

1. **App Server:** Can run multiple instances behind load balancer
2. **Database:** Master-replica setup for read scaling
3. **Redis:** Cluster mode for high availability
4. **Reverb:** Enable Redis scaling (`REVERB_SCALING_ENABLED=true`)

---

## Security Checklist

- [ ] Enable firewall (UFW/iptables)
- [ ] Disable root SSH login
- [ ] Use SSH keys instead of passwords
- [ ] Set up SSL/TLS (Let's Encrypt)
- [ ] Configure fail2ban
- [ ] Regular security updates
- [ ] Database access restricted to localhost
- [ ] Redis password authentication
- [ ] Environment files not in web root

---

## Backup Strategy

### What to Backup

1. **Database:** Daily automated backups
2. **Environment files:** `.env`
3. **User uploads:** If any (storage/app)
4. **Configuration:** Nginx, PHP, MySQL configs

### Backup Commands

```bash
# Database backup
mysqldump -u root -p delivery_dispatch > backup_$(date +%Y%m%d).sql

# Compress
gzip backup_$(date +%Y%m%d).sql

# Laravel backup (if using spatie/laravel-backup)
php artisan backup:run
```

---

## Support & Documentation

- **Laravel Docs:** https://laravel.com/docs
- **Laravel Reverb:** https://reverb.laravel.com/docs
- **MySQL Docs:** https://dev.mysql.com/doc/
- **Redis Docs:** https://redis.io/documentation

---

## Summary

For your scale (2 supervisors, 50 riders, 500 orders/day):

✅ **Single server is sufficient**
✅ **4 GB RAM minimum, 8 GB recommended**
✅ **Daily cleanup keeps database tiny (~100-200 MB/year)**
✅ **Budget: $6-50/month depending on provider**
✅ **Easy to scale when needed**

**Recommended Starting Point:** Hetzner CX21 or DigitalOean Basic Droplet (4GB)
