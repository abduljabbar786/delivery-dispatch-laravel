# Optimizations for 512 MB RAM Server (HIGH RISK)

⚠️ **WARNING:** These optimizations are for emergency use only. The application will still be unstable with 512 MB RAM.

---

## 1. MySQL Optimizations (Ultra-Light)

**File:** `/etc/mysql/my.cnf` or `/etc/mysql/mysql.conf.d/mysqld.cnf`

```ini
[mysqld]
# Minimal memory configuration
innodb_buffer_pool_size = 64M  # Absolute minimum (normally 50-70% of RAM)
innodb_log_file_size = 16M
innodb_log_buffer_size = 4M

# Reduce connections
max_connections = 20  # Down from 150
thread_cache_size = 4

# Disable features we don't use
performance_schema = OFF
innodb_stats_on_metadata = OFF

# Reduce table cache
table_open_cache = 64
table_definition_cache = 64

# Query cache (deprecated in MySQL 8, but helps in older versions)
query_cache_type = 0
query_cache_size = 0

# Reduce sort/join buffers
sort_buffer_size = 256K
read_buffer_size = 256K
read_rnd_buffer_size = 256K
join_buffer_size = 256K

# InnoDB optimizations
innodb_flush_log_at_trx_commit = 2  # Less safe, better performance
innodb_flush_method = O_DIRECT
innodb_io_capacity = 200
```

**Apply changes:**
```bash
sudo systemctl restart mysql
```

---

## 2. PHP-FPM Configuration (Minimal Workers)

**File:** `/etc/php/8.2/fpm/pool.d/www.conf`

```ini
[www]
; Process management
pm = dynamic
pm.max_children = 3          ; Down from 25-50 (VERY LOW!)
pm.start_servers = 1         ; Start with 1 worker
pm.min_spare_servers = 1     ; Keep 1 idle worker
pm.max_spare_servers = 2     ; Max 2 idle workers
pm.max_requests = 500        ; Restart workers after 500 requests

; Memory limits
pm.process_idle_timeout = 10s
php_admin_value[memory_limit] = 64M  ; Down from 128M

; Emergency restart
emergency_restart_threshold = 3
emergency_restart_interval = 1m
```

**Apply changes:**
```bash
sudo systemctl restart php8.2-fpm
```

---

## 3. Redis Configuration (Minimal)

**File:** `/etc/redis/redis.conf`

```conf
# Memory limits
maxmemory 64mb              # Very limited
maxmemory-policy allkeys-lru

# Disable persistence to save RAM
save ""
appendonly no

# Connection limits
maxclients 50

# Reduce background saves
rdbcompression no
```

**Apply changes:**
```bash
sudo systemctl restart redis
```

---

## 4. Disable Laravel Reverb WebSocket

**Option A: Use Pusher/Ably (Cloud WebSocket)**

Update `.env`:
```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

# Free tier: 200k messages/day, 100 concurrent connections
# Perfect for your scale!
```

**Cost:** Free tier or $5-10/month
**Benefit:** Saves ~100-200 MB RAM

**Option B: Disable Real-time Updates**

Update `.env`:
```env
BROADCAST_CONNECTION=null
```

Update frontend to poll instead of WebSocket.

---

## 5. Queue Configuration (Use Database, Not Workers)

**File:** `.env`

```env
QUEUE_CONNECTION=sync  # Process immediately, no workers
```

**Impact:**
- No background queue workers (saves ~256 MB RAM)
- Jobs run synchronously (slower but works)

---

## 6. Swap File (Emergency Memory)

**Create 1-2 GB swap file:**

```bash
# Create swap file
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Configure swappiness (how aggressively to use swap)
sudo sysctl vm.swappiness=10
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf
```

**Warning:** Swap is MUCH slower than RAM. Your app will be sluggish.

---

## 7. Laravel Optimizations

**File:** `.env`

```env
# Disable debug mode
APP_DEBUG=false

# Minimal logging
LOG_LEVEL=error

# Cache everything
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=sync

# Disable unused features
TELESCOPE_ENABLED=false
PULSE_ENABLED=false
```

**Run optimizations:**
```bash
# Cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize composer autoloader
composer install --optimize-autoloader --no-dev
```

---

## 8. Nginx Configuration

**File:** `/etc/nginx/nginx.conf`

```nginx
worker_processes 1;           # 1 CPU = 1 worker
worker_rlimit_nofile 1024;

events {
    worker_connections 256;   # Down from 1024
}

http {
    # Reduce buffers
    client_body_buffer_size 8k;
    client_header_buffer_size 1k;
    client_max_body_size 2m;
    large_client_header_buffers 2 1k;

    # Connection timeouts
    keepalive_timeout 15;
    client_body_timeout 12;
    client_header_timeout 12;
    send_timeout 10;

    # Gzip compression
    gzip on;
    gzip_comp_level 2;
    gzip_min_length 1000;
    gzip_types text/plain text/css application/json application/javascript;
}
```

---

## 9. Disable Unnecessary Services

```bash
# Stop and disable services you don't need
sudo systemctl stop snapd
sudo systemctl disable snapd

sudo systemctl stop apache2  # If you're using Nginx
sudo systemctl disable apache2

# Remove unnecessary packages
sudo apt autoremove
```

---

## 10. Monitor Memory Usage

**Install htop:**
```bash
sudo apt install htop
htop
```

**Check memory:**
```bash
free -m
```

**Monitor MySQL:**
```bash
mysql -u root -p -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
```

**Monitor Redis:**
```bash
redis-cli INFO memory
```

---

## 11. Application-Level Optimizations

### Reduce GPS Upload Frequency

**File:** `app/Http/Controllers/Api/RiderController.php`

Current: Accepts up to 50 points per request

Consider reducing to 10-20 points or increase upload interval in rider app.

### Aggressive Caching

Update `app/Http/Controllers/Api/MapController.php`:

```php
// Increase cache time from 30s to 2 minutes
$riders = Cache::remember($cacheKey, now()->addMinutes(2), function () {
    // ...
});
```

### Pagination Limits

Reduce default pagination from 20 to 10:

```php
return $query->orderBy('created_at', 'desc')->paginate(10);
```

---

## 12. Monitoring & Alerts

**Create memory check script:**

**File:** `/home/purelogics/check_memory.sh`

```bash
#!/bin/bash
MEMORY_USAGE=$(free | grep Mem | awk '{print ($3/$2) * 100.0}')
THRESHOLD=90

if (( $(echo "$MEMORY_USAGE > $THRESHOLD" | bc -l) )); then
    echo "High memory usage: $MEMORY_USAGE%"
    # Restart services to free memory
    sudo systemctl restart php8.2-fpm
    sudo systemctl restart mysql
fi
```

**Make executable and add to cron:**
```bash
chmod +x /home/purelogics/check_memory.sh
crontab -e

# Add: Check every 5 minutes
*/5 * * * * /home/purelogics/check_memory.sh
```

---

## Expected Performance with These Optimizations

| Metric | Performance |
|--------|-------------|
| 5-10 riders | ⚠️ Might work |
| 20 riders | ⚠️ Very slow, frequent issues |
| 50 riders | ❌ Will crash regularly |
| Response time | 2-5 seconds (vs <1s normally) |
| Stability | Low - expect crashes |

---

## Bottom Line

**These optimizations can help you survive temporarily, but:**

- Application will be **very slow**
- **Frequent crashes** expected
- **Not suitable for production** with 50 riders
- **Users will have poor experience**

**Strongly recommend upgrading to at least 2 GB RAM.**

---

## Testing Checklist

After applying optimizations:

```bash
# 1. Check services are running
sudo systemctl status mysql
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
sudo systemctl status redis

# 2. Check memory usage
free -m

# 3. Test application
curl http://localhost

# 4. Monitor logs
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log

# 5. Load test (be gentle!)
ab -n 100 -c 5 http://localhost/api/orders
```

---

## Upgrade Path

When you're ready to upgrade (HIGHLY RECOMMENDED):

1. **Export database:**
   ```bash
   mysqldump -u root -p delivery_dispatch > backup.sql
   ```

2. **Backup files:**
   ```bash
   tar -czf app_backup.tar.gz /home/purelogics/code/development/delivery-dispatch-laravel
   ```

3. **Provision new server (2 GB+ RAM)**

4. **Restore and migrate**

---

## Cost Comparison

| Option | Cost/Month | Reliability | Performance |
|--------|------------|-------------|-------------|
| Current (512 MB) + Optimizations | $5-8 | ❌ Poor | ❌ Slow |
| Upgrade to 2 GB RAM | $10-14 | ⚠️ OK | ⚠️ Acceptable |
| Upgrade to 4 GB RAM | $6-24 | ✅ Good | ✅ Fast |

**Difference:** $2-16/month for MUCH better experience

Is saving $5-10/month worth:
- Frequent crashes?
- Poor user experience?
- Lost delivery orders?
- Frustrated riders and supervisors?

**Answer: NO. Upgrade your server.**
