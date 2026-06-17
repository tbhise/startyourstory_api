# Redis Setup Guide — Start Your Story API

How to install and configure Redis for this Laravel API on **(A) a local Windows
development machine** and **(B) a Hostinger VPS (Ubuntu/Debian)**.

This project uses Redis **for the cache store only** (and, because the
`RateLimiter` uses the default cache store, for rate limiting). **Sessions stay
on the `file` driver** — do not change `SESSION_DRIVER` as part of this.

Relevant `.env` keys (already set in this repo):

```env
CACHE_STORE=redis          # was: file
SESSION_DRIVER=file        # unchanged — leave as-is

REDIS_CLIENT=predis        # was: phpredis (the phpredis C-extension is not installed)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null         # set a real password on the VPS (see §B)
REDIS_PORT=6379
```

> **Client note.** Two PHP clients can talk to Redis:
> - **predis** — a pure-PHP library installed via Composer (`predis/predis`). No
>   PHP extension needed. Already installed here. Works on any machine.
> - **phpredis** — a compiled C extension (faster). Optional. If you install it
>   (see §B.7), switch `REDIS_CLIENT=phpredis`.
>
> The app works with `predis` everywhere. Use `phpredis` on production only if
> you want the extra performance.

---

## A. Local Development Machine (Windows 11)

Redis has no official native Windows build. Pick **one** of the three options
below. **Memurai (Option 1)** is the simplest for a Windows dev box; **WSL2
(Option 2)** is closest to production; **Docker (Option 3)** is best if you
already use Docker.

### Option 1 — Memurai (Redis-compatible Windows service) — recommended for Windows

1. Download the free **Memurai Developer** edition: <https://www.memurai.com/get-memurai>
2. Run the installer (`.msi`). Accept defaults — it installs as a **Windows
   service** that auto-starts on boot and listens on `127.0.0.1:6379`.
3. Verify it is running (PowerShell):
   ```powershell
   Get-Service Memurai
   ```
   Status should be `Running`.
4. Test connectivity with the bundled CLI:
   ```powershell
   memurai-cli ping
   # -> PONG
   ```
   (If `memurai-cli` isn't on PATH, find it under
   `C:\Program Files\Memurai\memurai-cli.exe`.)

### Option 2 — WSL2 + Ubuntu (most production-like)

1. Install WSL2 (PowerShell **as Administrator**), then reboot:
   ```powershell
   wsl --install -d Ubuntu
   ```
2. Open the **Ubuntu** terminal and install Redis:
   ```bash
   sudo apt update
   sudo apt install -y redis-server
   ```
3. Start it and test:
   ```bash
   sudo service redis-server start
   redis-cli ping        # -> PONG
   ```
4. WSL2 forwards `localhost`, so the Laravel app on Windows can reach it at
   `127.0.0.1:6379` with no extra config.
   - To auto-start Redis when WSL launches, add `sudo service redis-server start`
     to your `~/.bashrc`, or enable systemd in `/etc/wsl.conf`
     (`[boot]\nsystemd=true`) and use `sudo systemctl enable --now redis-server`.

### Option 3 — Docker Desktop

1. Install Docker Desktop for Windows.
2. Run a Redis container (persists data in a named volume, auto-restarts):
   ```powershell
   docker run -d --name sys-redis -p 6379:6379 --restart unless-stopped redis:7
   ```
3. Test:
   ```powershell
   docker exec -it sys-redis redis-cli ping   # -> PONG
   ```

### A.4 — Point Laravel at it & verify (all options)

The `.env` is already configured (`CACHE_STORE=redis`, `REDIS_CLIENT=predis`,
`REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, `REDIS_PASSWORD=null`). After Redis is
running:

```bash
# from the startyourstory_api folder
php artisan optimize:clear

# verify cache + rate limiter work on Redis
php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
Cache::put('sys_test','ok',60);
echo 'cache.default=' . config('cache.default') . PHP_EOL;
echo 'get=' . Cache::get('sys_test') . PHP_EOL;
echo 'store=' . get_class(Cache::getStore()) . PHP_EOL;
echo 'ping=' . Redis::connection()->command('ping') . PHP_EOL;
Cache::forget('sys_test');
"
```

Expected:
```
cache.default=redis
get=ok
store=Illuminate\Cache\RedisStore
ping=PONG
```

> **Local dev tip:** keep config **uncached** locally (`php artisan config:clear`)
> so `.env` edits take effect immediately. Only run `config:cache` on production.

---

## B. Hostinger VPS (Ubuntu / Debian)

Assumes a Hostinger **VPS** plan with root SSH access (not shared hosting). SSH in
first: `ssh root@YOUR_SERVER_IP` (or your sudo user).

### B.1 — Install Redis

```bash
sudo apt update
sudo apt install -y redis-server
```

### B.2 — Run Redis as a managed service (systemd)

Tell Redis to be supervised by systemd, then enable + start it:

```bash
sudo sed -i 's/^supervised .*/supervised systemd/' /etc/redis/redis.conf
sudo systemctl enable --now redis-server
sudo systemctl status redis-server      # should show "active (running)"
redis-cli ping                           # -> PONG
```

### B.3 — Bind to localhost only (security)

Unless the DB/app server is a *separate* machine, Redis must **only** listen on
loopback. In `/etc/redis/redis.conf` confirm:

```conf
bind 127.0.0.1 -::1
protected-mode yes
```

(These are the defaults on Ubuntu. **Never** bind Redis to `0.0.0.0` on a
public-facing VPS without a firewall + password.)

### B.4 — Set a strong password (REQUIRED on a server)

1. Generate a password:
   ```bash
   openssl rand -base64 32
   ```
2. In `/etc/redis/redis.conf`, set:
   ```conf
   requirepass YOUR_GENERATED_PASSWORD
   ```
3. Restart and test auth:
   ```bash
   sudo systemctl restart redis-server
   redis-cli -a 'YOUR_GENERATED_PASSWORD' ping   # -> PONG
   ```

### B.5 — Sensible memory & eviction policy (cache workload)

For a cache, cap memory and evict the least-recently-used keys instead of
erroring when full. In `/etc/redis/redis.conf` (adjust to your VPS RAM):

```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

Restart: `sudo systemctl restart redis-server`.

### B.6 — Firewall (defence in depth)

If using UFW, do **not** open 6379 to the world; loopback traffic is unaffected:

```bash
sudo ufw status
# Ensure port 6379 is NOT in the allow list. SSH (22) + web (80/443) only.
```

### B.7 — (Optional) Install the phpredis extension for speed

`predis` already works. To use the faster compiled client instead, install the
extension for your PHP version (example for PHP 8.3 — match yours):

```bash
sudo apt install -y php8.3-redis     # or: sudo pecl install redis
sudo systemctl restart php8.3-fpm    # restart your PHP-FPM service
php -m | grep redis                  # confirm "redis" is listed
```

Then set `REDIS_CLIENT=phpredis` in `.env`. If you keep predis, leave it as
`predis`.

### B.8 — Configure the Laravel app on the VPS

Edit the project `.env` (e.g. `/var/www/startyourstory_api/.env`):

```env
CACHE_STORE=redis
SESSION_DRIVER=file            # leave unchanged

REDIS_CLIENT=predis            # or phpredis if you installed §B.7
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_GENERATED_PASSWORD   # the §B.4 password (not the word "null")
REDIS_PORT=6379
# REDIS_CACHE_DB=1             # optional: isolate the cache on its own Redis DB
```

> **Password gotcha:** `REDIS_PASSWORD=null` literally means "no password".
> On the VPS it MUST be the real password from §B.4, otherwise you'll get
> `NOAUTH Authentication required`.

Apply and cache config for production:

```bash
cd /var/www/startyourstory_api
php artisan optimize:clear
php artisan config:cache        # production: cache config for performance
php artisan route:cache
```

### B.9 — Verify on the VPS

```bash
php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
Cache::put('sys_test','ok',60);
echo 'store=' . get_class(Cache::getStore()) . PHP_EOL;
echo 'ping=' . Redis::connection()->command('ping') . PHP_EOL;
Cache::forget('sys_test');
"
# store=Illuminate\Cache\RedisStore
# ping=PONG
```

Then exercise the real flows once: **student login, firm login, admin login,
registration, email verification** (these use the cache-backed rate limiters), and
confirm responses are normal and no `Failed to open stream` cache errors appear in
`storage/logs/laravel.log`.

---

## C. What this fixes

The recurring `storage/framework/cache/data/... Failed to open stream: No such
file or directory` errors come from the **file** cache driver racing on directory
creation/cleanup. Moving the cache to Redis removes the filesystem entirely from
the cache path, so those errors disappear.

## D. Rollback (revert to file cache)

```env
CACHE_STORE=file
REDIS_CLIENT=phpredis      # back to the original value
```

```bash
php artisan config:clear   # or: php artisan config:cache  (on production)
php artisan optimize:clear
```

Optionally remove the Composer client: `composer remove predis/predis`.
Sessions, auth and login persistence are unaffected either way (sessions were
never moved off `file`).

## E. Quick troubleshooting

| Symptom | Cause / Fix |
|---|---|
| `NOAUTH Authentication required` | `REDIS_PASSWORD` is empty/`null` but Redis has `requirepass`. Set the real password in `.env`, then `php artisan config:clear`/`config:cache`. |
| `Connection refused [tcp://127.0.0.1:6379]` | Redis isn't running. Local: start Memurai/WSL/Docker. VPS: `sudo systemctl start redis-server`. |
| `Class "Redis" not found` while `REDIS_CLIENT=phpredis` | phpredis extension not installed. Install it (§B.7) or set `REDIS_CLIENT=predis`. |
| `.env` change has no effect | Config is cached. Run `php artisan config:clear` (dev) or re-run `config:cache` (prod). |
| Cache works but data "disappears" | Expected when `maxmemory`/eviction is hit, or after a Redis restart without persistence. Fine for a cache. |
